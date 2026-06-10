// Lädt JSON-Daten und rendert Dashboard + Fachseite.
const App = (() => {
  // Lädt eine JSON-Datei und wirft bei HTTP-Fehlern eine sprechende Meldung.
  async function loadJSON(path) {
    const res = await fetch(path);
    if (!res.ok) throw new Error("Konnte " + path + " nicht laden (" + res.status + ")");
    return res.json();
  }

  // Rendert die Fächerübersicht datengetrieben aus dem Manifest (data/manifest.json).
  // Neue Fächer werden allein über das Manifest + passende JSON-Dateien ergänzt –
  // hier ist keine Code-Änderung mehr nötig.
  async function renderFaecher(container) {
    container.innerHTML = "";
    try {
      const manifest = await loadJSON("/data/manifest.json");
      const faecher = manifest.faecher || [];

      faecher.forEach((fach) => {
        const karte = document.createElement("a");
        karte.className = "card";
        karte.href = "/fach.html?fach=" + encodeURIComponent(fach.id);
        karte.innerHTML =
          '<span class="card-icon">' + (fach.icon || "📘") + "</span>" +
          "<h2>" + fach.name + "</h2>" +
          "<p>" + (fach.beschreibung || "") + "</p>";
        container.appendChild(karte);
      });
    } catch (fehler) {
      // Kaputtes/fehlendes Manifest soll eine sichtbare Meldung zeigen,
      // statt die Übersicht leer zu lassen.
      container.innerHTML = '<p class="error">' + fehler.message + "</p>";
    }
  }

  // Entscheidet anhand des Manifests, ob ein Fach (Theorie/Quiz) oder ein
  // eigenständiges Tool-Modul (z. B. DSTool) geladen wird.
  async function renderFach(fachId) {
    try {
      const manifest = await loadJSON("/data/manifest.json");
      const eintrag = (manifest.faecher || []).find((f) => f.id === fachId);

      if (eintrag && eintrag.typ === "tool") {
        // Tool-Modus: Standard-UI ausblenden, Tool-Container einblenden
        document.getElementById("main-fach").hidden = true;
        document.getElementById("main-tool").hidden = false;

        // Tool-Stylesheet dynamisch laden
        const link = document.createElement("link");
        link.rel = "stylesheet";
        link.href = eintrag.toolStyle;
        document.head.appendChild(link);

        // Tool-Script laden und dann mounten
        const script = document.createElement("script");
        script.src = eintrag.toolScript;
        script.onload = () => {
          DSTool.mount({
            root:    "#tool-root",
            dataUrl: eintrag.toolData,
            quizUrl: eintrag.toolQuiz,
          });
        };
        script.onerror = () => {
          document.getElementById("tool-root").innerHTML =
            '<p class="error">Tool-Script konnte nicht geladen werden: ' + eintrag.toolScript + "</p>";
        };
        document.head.appendChild(script);
        return;
      }

      // Standard-Modus: Theorie + Quiz aus JSON laden
      const [theorie, quiz] = await Promise.all([
        loadJSON("/data/" + fachId + "_theorie.json"),
        loadJSON("/data/" + fachId + "_quiz.json"),
      ]);
      document.getElementById("fach-title").textContent = theorie.fach || fachId;
      renderTheorie(theorie);
      renderQuiz(quiz);
    } catch (err) {
      document.getElementById("theorie").innerHTML =
        '<p class="error">' + err.message + "</p>";
    }
  }

  function renderTheorie(theorie) {
    const el = document.getElementById("theorie");
    el.innerHTML = "";
    (theorie.themen || []).forEach((t) => {
      const article = document.createElement("article");
      article.className = "theorie-block";
      article.innerHTML = "<h3>" + t.titel + "</h3>" + "<p>" + t.text + "</p>";
      el.appendChild(article);
    });
  }

  function renderQuiz(quiz) {
    const form = document.getElementById("quiz");
    form.innerHTML = "";
    const fragen = quiz.fragen || [];

    fragen.forEach((f, i) => {
      const fieldset = document.createElement("fieldset");
      fieldset.className = "frage";
      const legend = document.createElement("legend");
      legend.textContent = i + 1 + ". " + f.frage;
      fieldset.appendChild(legend);

      f.optionen.forEach((opt, j) => {
        const id = "f" + i + "_o" + j;
        const label = document.createElement("label");
        label.className = "option";
        label.innerHTML =
          '<input type="radio" name="frage' + i + '" value="' + j + '" id="' + id + '" /> ' +
          opt;
        fieldset.appendChild(label);
      });

      form.appendChild(fieldset);
    });

    const submitBtn = document.getElementById("quiz-submit");
    const resultEl = document.getElementById("quiz-result");

    submitBtn.onclick = () => {
      let richtig = 0;
      fragen.forEach((f, i) => {
        const sel = form.querySelector('input[name="frage' + i + '"]:checked');
        const fieldset = form.querySelectorAll("fieldset")[i];
        fieldset.classList.remove("correct", "wrong");
        if (sel && Number(sel.value) === f.loesung) {
          richtig++;
          fieldset.classList.add("correct");
        } else {
          fieldset.classList.add("wrong");
        }
      });
      resultEl.hidden = false;
      resultEl.textContent =
        "Du hast " + richtig + " von " + fragen.length + " Fragen richtig.";
    };
  }

  return { renderFaecher, renderFach };
})();
