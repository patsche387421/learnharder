# Themenübersicht & Theorie

**Typ:** Seite (Mockup)
**Datei:** `theory.html`

## Kurzbeschreibung
Themenübersicht eines Faches und Theorie-Lesemodus (Fließtext, Code-Blöcke,
Hervorhebungen). Beispielthema im Mockup: „Klassen in Python".

## Verwendete Tokens (aus `src/css/tokens.css`)
- `--bg`, `--surface`, `--surface-2`
- `--text`, `--text-muted`
- `--primary` (Links, Hervorhebungen)
- `--radius`
- evtl. künftiger Token für Code-Block-Hintergrund (offen)

## Betroffene `src/`-Seiten
- `src/pos.html`, `src/dbi.html` und weitere Fach-Themenübersichten
- `src/fach.html` (Theorie-Modus)

## TODOs / Abweichungen vom aktuellen Stand
- Code-Block-Styles brauchen evtl. einen neuen Token (z.B. `--code-bg`) — vor
  Einführung in `tokens.css` ergänzen + in `DESIGN_GUIDELINES.md` dokumentieren.
- Aktuelle Theorie-JSONs (`<thema>_theorie.json`) liefern Markdown — Render-Pfad
  beim Umsetzen prüfen.
