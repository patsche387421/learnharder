// Lädt JSON-Daten und rendert alle drei Seitenebenen:
//   1. Fächerübersicht (faecher.html)  →  renderFaecher()
//   2. Fach-Seite      (pos.html …)    →  renderFachSeite()
//   3. Themen-Inhalt   (fach.html)     →  renderFach()
const App = (() => {

  // Lädt eine JSON-Datei; wirft bei HTTP-Fehlern eine sprechende Meldung.
  async function loadJSON(path) {
    const res = await fetch(path);
    if (!res.ok) throw new Error("Konnte " + path + " nicht laden (" + res.status + ")");
    return res.json();
  }

  // ── Ebene 1: Fächerübersicht ──────────────────────────────────────────────
  // Rendert Fach-Karten aus dem Manifest. Karten verlinken auf fach.seite.
  async function renderFaecher(container) {
    container.innerHTML = "";
    try {
      const manifest = await loadJSON("/data/manifest.json");
      (manifest.faecher || []).forEach((fach) => {
        const karte = document.createElement("a");
        karte.className = "card";
        karte.href = fach.seite || ("/fach.html?fach=" + encodeURIComponent(fach.id));
        karte.innerHTML =
          '<span class="card-icon">' + (fach.icon || "📘") + "</span>" +
          "<h2>" + fach.name + "</h2>" +
          (fach.vollname ? '<span class="card-vollname">' + fach.vollname + "</span>" : "") +
          "<p>" + (fach.beschreibung || "") + "</p>";
        container.appendChild(karte);
      });
    } catch (fehler) {
      container.innerHTML = '<p class="error">' + fehler.message + "</p>";
    }
  }

  // ── Ebene 2: Fach-Seite (Themen-Übersicht) ───────────────────────────────
  // Rendert Fach-Header, Stats-Leiste und Themen-Karten für eine Fach-Seite.
  // Stats kommen aus stats.js (Platzhalter, später DB – siehe DB_Integration_Guide.md).
  async function renderFachSeite(fachId, { header, stats, grid }) {
    try {
      const manifest = await loadJSON("/data/manifest.json");
      const fach = (manifest.faecher || []).find((f) => f.id === fachId);
      if (!fach) throw new Error("Fach nicht gefunden: " + fachId);

      // Fach-Header
      header.innerHTML =
        '<p class="fach-eyebrow">' + fach.name + "</p>" +
        '<h1 class="fach-vollname">' + (fach.vollname || fach.name) + "</h1>" +
        '<p class="fach-beschreibung">' + (fach.beschreibung || "") + "</p>";

      // Stats-Leiste (Platzhalter-Werte aus stats.js, data-stat-Attribute für spätere DB)
      const statDaten = await Stats.ladeFachStats(fachId);
      stats.innerHTML =
        renderStatPill("Fortschritt",       "fortschritt",      statDaten.fortschritt      > 0 ? statDaten.fortschritt      + "%" : "–") +
        renderStatPill("Themen bearbeitet", "themenBearbeitet", statDaten.themenBearbeitet > 0 ? statDaten.themenBearbeitet       : "–") +
        renderStatPill("Quiz-Punkte",       "quizPunkte",       statDaten.quizPunkte       > 0 ? statDaten.quizPunkte            : "–");

      // Themen-Karten oder Leer-Zustand
      const themen = fach.themen || [];
      grid.innerHTML = "";
      if (!themen.length) {
        grid.innerHTML =
          '<div class="empty-state">' +
            '<span class="empty-icon">📭</span>' +
            "<p>Noch keine Themen verfügbar.</p>" +
          "</div>";
        return;
      }
      themen.forEach((thema) => {
        const karte = document.createElement("a");
        karte.className = "card";
        karte.href = "/fach.html?fach=" + encodeURIComponent(thema.id);
        karte.innerHTML =
          '<span class="card-icon">' + (thema.icon || "📘") + "</span>" +
          "<h2>" + thema.name + "</h2>" +
          "<p>" + (thema.beschreibung || "") + "</p>";
        grid.appendChild(karte);
      });
    } catch (fehler) {
      header.innerHTML = '<p class="error">' + fehler.message + "</p>";
    }
  }

  function renderStatPill(label, statKey, wert) {
    return (
      '<div class="stat-pill" data-stat="' + statKey + '">' +
        '<span class="stat-pill-label">' + label + "</span>" +
        '<span class="stat-pill-value">' + wert  + "</span>" +
      "</div>"
    );
  }

  // ── Ebene 3: Themen-Inhalt (fach.html) ───────────────────────────────────
  // Sucht das Thema in den verschachtelten Themen-Listen des Manifests.
  // Setzt den Zurück-Link dynamisch auf die übergeordnete Fach-Seite.
  async function renderFach(themaId) {
    try {
      const manifest = await loadJSON("/data/manifest.json");

      // Thema + übergeordnetes Fach finden
      let themaEintrag = null;
      let elternFach   = null;
      for (const fach of (manifest.faecher || [])) {
        const gefunden = (fach.themen || []).find((t) => t.id === themaId);
        if (gefunden) { themaEintrag = gefunden; elternFach = fach; break; }
      }

      // Zurück-Link auf übergeordnete Fach-Seite setzen
      const zurueckLink = document.querySelector(".brand");
      if (zurueckLink && elternFach && elternFach.seite) {
        zurueckLink.href        = elternFach.seite;
        zurueckLink.textContent = "← " + elternFach.name;
      }

      if (themaEintrag && themaEintrag.typ === "tool") {
        // Tool-Modus: Standard-UI ausblenden, Tool-Container einblenden
        document.getElementById("main-fach").hidden = true;
        document.getElementById("main-tool").hidden = false;

        const link = document.createElement("link");
        link.rel  = "stylesheet";
        link.href = themaEintrag.toolStyle;
        document.head.appendChild(link);

        const script    = document.createElement("script");
        script.src      = themaEintrag.toolScript;
        script.onload   = () => {
          DSTool.mount({
            root:    "#tool-root",
            dataUrl: themaEintrag.toolData,
            quizUrl: themaEintrag.toolQuiz,
          });
        };
        script.onerror  = () => {
          document.getElementById("tool-root").innerHTML =
            '<p class="error">Tool-Script konnte nicht geladen werden: ' + themaEintrag.toolScript + "</p>";
        };
        document.head.appendChild(script);
        return;
      }

      // Standard-Modus: Theorie + Quiz aus JSON-Dateien laden
      const [theorie, quiz] = await Promise.all([
        loadJSON("/data/" + themaId + "_theorie.json"),
        loadJSON("/data/" + themaId + "_quiz.json"),
      ]);
      document.getElementById("fach-title").textContent = theorie.fach || themaId;
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
    const form   = document.getElementById("quiz");
    form.innerHTML = "";
    const fragen = quiz.fragen || [];

    fragen.forEach((f, i) => {
      const fieldset = document.createElement("fieldset");
      fieldset.className = "frage";
      const legend = document.createElement("legend");
      legend.textContent = (i + 1) + ". " + f.frage;
      fieldset.appendChild(legend);

      f.optionen.forEach((opt, j) => {
        const id    = "f" + i + "_o" + j;
        const label = document.createElement("label");
        label.className = "option";
        label.innerHTML =
          '<input type="radio" name="frage' + i + '" value="' + j + '" id="' + id + '" /> ' + opt;
        fieldset.appendChild(label);
      });

      form.appendChild(fieldset);
    });

    const submitBtn = document.getElementById("quiz-submit");
    const resultEl  = document.getElementById("quiz-result");

    submitBtn.onclick = () => {
      let richtig = 0;
      fragen.forEach((f, i) => {
        const sel      = form.querySelector('input[name="frage' + i + '"]:checked');
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
      resultEl.textContent = "Du hast " + richtig + " von " + fragen.length + " Fragen richtig.";

      // Stats-Hook: Ergebnis für spätere DB-Anbindung melden
      const themaId = new URLSearchParams(location.search).get("fach") || "";
      Stats.speichereQuizErgebnis(themaId, richtig, fragen.length);
    };
  }

  return { renderFaecher, renderFachSeite, renderFach };
})();
