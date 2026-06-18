// =============================================================================
// content-icons.js — illustrative Fach-/Themen-Icons (bunt, 96×96, via <img>)
// -----------------------------------------------------------------------------
// Bewusst getrennt von icons.js (UI-Linien-Icons), weil das Render-Modell anders
// ist:
//   • <img src> statt inline-SVG
//   • fix-farbig (Gradienten), kein currentColor, keine Hydration
// Die Pfade kommen aus manifest.json (Feld "icon"). Fehlt die SVG-Datei, zeigt
// die Karte einen Buchstaben-Badge (erster Buchstabe des Namens im farbigen Kreis).
//
// Nutzung:
//   element.innerHTML = ContentIcons.render(fach.icon, fach.name);
//   // opts.size: CSS-Maß als String (Default "3rem"); opts.className: Zusatzklasse
// =============================================================================

const ContentIcons = (() => {

  // ---------------------------------------------------------------------------
  // ersterBuchstabe(label) – ermittelt den Anzeige-Buchstaben für den Fallback.
  //   1. label trimmen, ersten Char nehmen
  //   2. ist er ein Buchstabe → diesen (großgeschrieben)
  //   3. sonst (Ziffer/Sonderzeichen): ersten Buchstaben NACH einem Trenner
  //      (Leerzeichen, "-", ":", "_") nehmen
  //   4. nichts gefunden → trotzdem ersten Char (auch wenn Ziffer)
  // Beispiele:
  //   "POS" → "P"   "Variablen & Datentypen" → "V"   "1:1-Beziehungen" → "B"
  //   "1n-Beziehungen" → "B"   "ANY, SOME und ALL" → "A"   "" → "?"
  // ---------------------------------------------------------------------------
  function ersterBuchstabe(label) {
    const s = (label || "").trim();
    if (!s) return "?";
    const erster = s.charAt(0);
    if (/[a-zA-ZÀ-ÿ]/.test(erster)) return erster.toUpperCase();
    const treffer = s.match(/[\s\-:_]+([a-zA-ZÀ-ÿ])/);
    return (treffer ? treffer[1] : erster).toUpperCase();
  }

  // ---------------------------------------------------------------------------
  // fallback(label, size) – Buchstaben-Badge (farbiger Kreis) als <span>.
  // Setzt NUR --icon-size; width/height/font-size leiten sich im CSS daraus ab
  // (.content-icon-fallback). Wird auch vom onerror-Handler des <img> aufgerufen.
  // ---------------------------------------------------------------------------
  function fallback(label, size) {
    // Sicherheit: nur erlaubte CSS-Längen ins style-Attribut lassen
    // (Defense-in-depth — robust für künftige Quelle manifest.json → Supabase).
    const sichereGroesse = /^[0-9.]+(?:rem|em|px|%)$/.test(size) ? size : "3rem";
    return (
      '<span class="content-icon-fallback" aria-hidden="true" ' +
      'style="--icon-size:' + sichereGroesse + '">' + ersterBuchstabe(label) + '</span>'
    );
  }

  // ---------------------------------------------------------------------------
  // render(value, label, opts) → HTML-String
  //   value – manifest-Feld "icon" (Pfad ODER Emoji/leer/undefined)
  //   label – Klartext-Name (fach.name / thema.name) für alt + Fallback
  //   opts.size      – CSS-Maß als String (Default "3rem")
  //   opts.className – zusätzliche Klasse(n) am <img>
  // Beginnt value mit "/assets/" → <img>; sonst → Buchstaben-Badge.
  // ---------------------------------------------------------------------------
  function render(value, label, opts = {}) {
    // Sicherheit (Defense-in-depth, robust für künftige Quelle manifest.json →
    // Supabase): Größe nur als erlaubtes CSS-Maß; Pfad nur als Whitelist unter
    // /assets/icons/ mit harmlosen Zeichen (?=& für Cache-Bust wie ?v=2). So ist
    // kein Attribut-Breakout via " oder > möglich; sonst sauberer Fallback.
    const groesseInput = opts.size || "3rem";
    const groesse = /^[0-9.]+(?:rem|em|px|%)$/.test(groesseInput) ? groesseInput : "3rem";
    const istPfad = typeof value === "string" &&
                    /^\/assets\/icons\/[a-zA-Z0-9._\/?=&-]+$/.test(value);
    if (!istPfad) return fallback(label, groesse);

    const klasse = "content-icon" + (opts.className ? " " + opts.className : "");
    const safeLabel = (label || "").replace(/"/g, "&quot;");
    // onerror = bewusste Ausnahme zur "kein Inline-JS"-Regel (siehe docs/CLAUDE.md
    // → Verbote): fehlt die SVG, ersetzt this.outerHTML das <img> EINMALIG durch
    // ein <span> (kein erneutes onerror → kein Loop). Das Label kommt aus this.alt
    // → kein String-Escaping-Risiko im Attribut.
    return (
      '<img class="' + klasse + '" src="' + value + '" alt="' + safeLabel + '" ' +
      'loading="lazy" style="--icon-size:' + groesse + '" ' +
      'onerror="this.outerHTML = ContentIcons.fallback(this.alt, \'' + groesse + '\')">'
    );
  }

  return { render, fallback, ersterBuchstabe };
})();
