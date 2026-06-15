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

  // Leitet aus einer Thema-ID Fach-Präfix und Slug ab:
  // "pos-variablen"  → { fach: "pos", slug: "variablen" }
  // "dbi-erd-lesen"  → { fach: "dbi", slug: "erd-lesen" }
  function themaZuPfad(themaId) {
    const trenn = themaId.indexOf("-");
    return {
      fach: themaId.slice(0, trenn),
      slug: themaId.slice(trenn + 1),
    };
  }

  // ── Ebene 1: Fächerübersicht ──────────────────────────────────────────────
  // Rendert Fach-Karten aus dem Manifest. Karten verlinken auf fach.seite.
  async function renderFaecher(container) {
    container.innerHTML = "";
    try {
      const manifest = await loadJSON("/assets/data/manifest.json");
      (manifest.faecher || []).forEach((fach) => {
        const karte = document.createElement("a");
        karte.className = "card";
        karte.href = fach.seite || ("/fach.html?fach=" + encodeURIComponent(fach.id));
        karte.innerHTML =
          '<span class="card-icon">' + (fach.icon || Icons.render('book', { size: 32 })) + "</span>" +
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
  // Rendert Fach-Header, Stats-Leiste (aus DB) und Themen-Karten mit Fortschritt-Badges.
  async function renderFachSeite(fachId, { header, stats, grid }) {
    try {
      const manifest = await loadJSON("/assets/data/manifest.json");
      const fach = (manifest.faecher || []).find((f) => f.id === fachId);
      if (!fach) throw new Error("Fach nicht gefunden: " + fachId);

      // Fach-Header
      header.innerHTML =
        '<p class="fach-eyebrow">' + fach.name + "</p>" +
        '<h1 class="fach-vollname">' + (fach.vollname || fach.name) + "</h1>" +
        '<p class="fach-beschreibung">' + (fach.beschreibung || "") + "</p>";

      const themen = fach.themen || [];

      // Stats und Themen-Fortschritt parallel aus DB laden
      const [statDaten, themaProgress] = await Promise.all([
        Stats.ladeFachStats(fachId),
        Stats.ladeFachThemenProgress(fachId)
      ]);

      // Fortschritt aus abgeschlossenen Themen berechnen
      const fortschritt = themen.length > 0
        ? Math.round((statDaten.themenBearbeitet / themen.length) * 100)
        : 0;

      stats.innerHTML =
        renderStatPill("Fortschritt",       "fortschritt",      fortschritt                > 0 ? fortschritt + "%"           : "–") +
        renderStatPill("Themen bearbeitet", "themenBearbeitet", statDaten.themenBearbeitet > 0 ? statDaten.themenBearbeitet   : "–") +
        renderStatPill("Quiz-Punkte",       "quizPunkte",       statDaten.quizPunkte       > 0 ? statDaten.quizPunkte         : "–");

      // Themen-Karten oder Leer-Zustand
      grid.innerHTML = "";
      if (!themen.length) {
        grid.innerHTML =
          '<div class="empty-state">' +
            '<span class="empty-icon">' + Icons.render('empty', { size: 40 }) + '</span>' +
            "<p>Noch keine Themen verfügbar.</p>" +
          "</div>";
        return;
      }
      themen.forEach((thema) => {
        const progress = themaProgress[thema.id];
        const done     = progress?.abgeschlossen === true;
        const score    = progress?.letzter_score ?? null;

        const karte = document.createElement("a");
        karte.className = "card" + (done ? " card--done" : "");
        karte.href = "/fach.html?fach=" + encodeURIComponent(thema.id);
        karte.innerHTML =
          '<span class="card-icon">' + (thema.icon || Icons.render('book', { size: 32 })) + "</span>" +
          (done ? '<span class="card-check">✓</span>' : "") +
          "<h2>" + thema.name + "</h2>" +
          "<p>" + (thema.beschreibung || "") + "</p>" +
          (score !== null ? '<span class="card-score">' + score + " %</span>" : "");
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
      const manifest = await loadJSON("/assets/data/manifest.json");

      // Thema + übergeordnetes Fach finden
      let themaEintrag = null;
      let elternFach   = null;
      for (const fach of (manifest.faecher || [])) {
        const gefunden = (fach.themen || []).find((t) => t.id === themaId);
        if (gefunden) { themaEintrag = gefunden; elternFach = fach; break; }
      }

      // Guard: kein Parameter oder Thema nicht im Manifest gefunden
      if (!themaId) {
        document.getElementById("fach-title").textContent = "Kein Thema angegeben";
        return;
      }
      if (!themaEintrag) {
        document.getElementById("fach-title").textContent = "Thema nicht gefunden: " + themaId;
        return;
      }

      // Zurück-Link auf übergeordnete Fach-Seite setzen
      const zurueckLink = document.getElementById("fach-zurueck");
      if (zurueckLink && elternFach && elternFach.seite) {
        zurueckLink.href        = elternFach.seite;
        zurueckLink.textContent = "← " + elternFach.name;
        zurueckLink.hidden      = false;
      }

      if (themaEintrag.typ === "tool") {
        // Tool-Modus: Standard-UI ausblenden, Tool-Container einblenden
        document.getElementById("main-fach").hidden = true;
        document.getElementById("main-tool").hidden = false;

        const link = document.createElement("link");
        link.rel  = "stylesheet";
        link.href = themaEintrag.toolStyle;
        document.head.appendChild(link);

        const script   = document.createElement("script");
        script.src     = themaEintrag.toolScript;
        script.onload  = () => {
          DSTool.mount({
            root:    "#tool-root",
            dataUrl: themaEintrag.toolData,
            quizUrl: themaEintrag.toolQuiz,
          });
        };
        script.onerror = () => {
          document.getElementById("tool-root").innerHTML =
            '<p class="error">Tool-Script konnte nicht geladen werden: ' + themaEintrag.toolScript + "</p>";
        };
        document.head.appendChild(script);
        return;
      }

      // Pfad ableiten: "pos-variablen" → /assets/data/pos/variablen
      const { fach: fachPfad, slug } = themaZuPfad(themaId);
      const dataBasis = "/assets/data/" + fachPfad + "/" + slug;

      // Theorie und Fragen sofort laden; Antworten erst beim Auswerten fetchen
      const [theorie, fragen] = await Promise.all([
        loadJSON(dataBasis + "_theorie.json"),
        loadJSON(dataBasis + "_fragen.json"),
      ]);

      document.getElementById("fach-title").textContent = theorie.fach || themaId;
      renderTheorie(theorie);
      renderQuiz(fragen, dataBasis + "_antworten.json");
      initReiterNav();
    } catch (err) {
      document.getElementById("theorie").innerHTML =
        '<p class="error">' + err.message + "</p>";
    }
  }

  // Verdrahtet die Tab-Navigation auf fach.html (Theorie / Aufgaben / Modi).
  // URL-Hash spiegelt den aktiven Tab wider, damit Tabs verlinkbar sind
  // und Browser-Zurück/Vor den Tab synchron mitführt.
  function initReiterNav() {
    // Mapping zwischen URL-Hash und internem data-tab-Wert.
    // data-tab bleibt aus Kompatibilitätsgründen "quiz", der Hash spiegelt
    // das sichtbare Label "Aufgaben" wider.
    const hashZuTab = { theorie: "theorie", aufgaben: "quiz", modi: "modi" };
    const tabZuHash = { theorie: "theorie", quiz: "aufgaben", modi: "modi" };

    function aktiviereTab(tabName) {
      document.querySelectorAll(".tab").forEach((b) => {
        b.classList.toggle("active", b.dataset.tab === tabName);
      });
      document.getElementById("tab-theorie").hidden = tabName !== "theorie";
      document.getElementById("tab-quiz").hidden    = tabName !== "quiz";
      document.getElementById("tab-modi").hidden    = tabName !== "modi";
    }

    // Aktiven Tab aus URL-Hash beim Seitenload bestimmen (Default: theorie)
    const startTab = hashZuTab[location.hash.slice(1)] || "theorie";
    aktiviereTab(startTab);

    document.querySelectorAll(".tab").forEach((btn) => {
      btn.addEventListener("click", () => {
        const tab = btn.dataset.tab;
        aktiviereTab(tab);
        history.replaceState(null, "", "#" + tabZuHash[tab]);
      });
    });

    // Browser-Zurück/Vor: Tab anhand Hash synchronisieren
    window.addEventListener("hashchange", () => {
      const tab = hashZuTab[location.hash.slice(1)] || "theorie";
      aktiviereTab(tab);
    });
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

  // Rendert Quizfragen. antwortenUrl wird erst beim Klick auf „Auswerten" gefetcht,
  // damit die Lösungen nicht beim Seitenaufruf im Network-Tab sichtbar sind.
  function renderQuiz(fragenDaten, antwortenUrl) {
    const form   = document.getElementById("quiz");
    form.innerHTML = "";
    const fragen = fragenDaten.fragen || [];

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

    submitBtn.onclick = async () => {
      // Antworten erst jetzt laden – nicht beim Seitenaufruf
      let loesungen;
      try {
        const antworten = await loadJSON(antwortenUrl);
        loesungen = antworten.loesungen;
      } catch (err) {
        resultEl.hidden = false;
        resultEl.textContent = "Auswertung konnte nicht geladen werden.";
        return;
      }

      let richtig = 0;
      fragen.forEach((f, i) => {
        const sel      = form.querySelector('input[name="frage' + i + '"]:checked');
        const fieldset = form.querySelectorAll("fieldset")[i];
        fieldset.classList.remove("correct", "wrong");
        if (sel && Number(sel.value) === loesungen[i]) {
          richtig++;
          fieldset.classList.add("correct");
        } else {
          fieldset.classList.add("wrong");
        }
      });
      const themaId = new URLSearchParams(location.search).get("fach") || "";
      Stats.speichereQuizErgebnis(themaId, richtig, fragen.length);
      const ergebnis = await Level.buecheQuizErgebnis({
        richtig,
        gesamt:       fragen.length,
        fachId:       themaZuPfad(themaId).fach,
        istTagesQuiz: false,
        lebenProzent: 100
      });

      resultEl.hidden = false;
      resultEl.textContent =
        "Du hast " + richtig + " von " + fragen.length + " Fragen richtig — " +
        "+" + ergebnis.ep + " EP, +" + ergebnis.trophien + " Trophäen";
    };
  }

  return { renderFaecher, renderFachSeite, renderFach };
})();
