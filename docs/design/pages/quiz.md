# Quiz-Templates

**Typ:** Seite (Mockup)
**Datei:** `quiz.html`

## Kurzbeschreibung
Templates für den Quiz-Modus: Multiple-Choice-Frage, Auswertung
(richtig / falsch / Erklärung) und Ergebnis-/Score-Bildschirm.

## Verwendete Tokens (aus `src/css/tokens.css`)
- `--bg`, `--surface`, `--surface-2`
- `--primary` (aktive Antwort, Weiter-Button)
- `--success` (richtige Antwort)
- `--error` (falsche Antwort)
- `--text`, `--text-muted`
- `--radius`

## Betroffene `src/`-Seiten
- `src/fach.html` (Quiz-Modus innerhalb eines Themas)
- geplant: `tagesquiz.html` (separates Tagesquiz, siehe
  `docs/DATA_MIGRATION_V2.md` → Nächster Schritt)

## TODOs / Abweichungen vom aktuellen Stand
- Aktuelle `fach.html`-Quiz-Logik in `src/js/app.js` rendert noch ohne dieses
  Layout → bei der Umstellung Tokens nutzen, keine Inline-Farben übernehmen.
- Antwort-States (default / hover / selected / correct / incorrect / disabled)
  müssen alle in `style.css` abgebildet sein.
