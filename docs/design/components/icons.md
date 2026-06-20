# Custom Icons

**Typ:** Komponente (Asset-Gallery)
**Datei:** `icons.html`

## Kurzbeschreibung
Übersicht aller projekt-eigenen Icons (SVG): Fach-Icons, Status-Icons
(richtig/falsch/info), Nav-Icons.

## Verwendete Tokens (aus `src/css/tokens.css`)
- `--text` (Default-Stroke/Fill)
- `--primary` (aktive Icons)
- `--success`, `--error` (Status-Icons)
- `--text-muted` (deaktivierte Icons)

## Verwendet auf `src/`-Seiten
- `src/faecher.html` (Fach-Karten)
- alle Fach-Themenübersichten (Themen-Icons)
- `src/dashboard.html`, `src/fach.html` (Status-Icons im Quiz)
- Navigation (siehe `nav.md`)

## TODOs / Abweichungen vom aktuellen Stand
- **Architektur erledigt:** Fach-/Themen-Icons-System ist von Emoji auf bunte
  SVGs umgestellt — Dateien unter `src/assets/icons/{faecher,themen}/<id>.svg`,
  `manifest.json` führt im Feld `icon` den Pfad. Eingebunden via
  `ContentIcons.render()`. Fehlt eine SVG, greift ein Buchstaben-Badge-Fallback.
  Vollständiger Stil-Guide: `icons-content.md`.
- Status-/Nav-Icons (`icons.js`) sollen `currentColor` nutzen, damit der
  Theme-Wechsel automatisch greift; im Mockup teils noch hartkodiert.
  (Content-Icons sind bewusst fix-farbig — siehe `icons-content.md`.)
- Offen: 21 DBI-Themen-Icons fehlen noch; bis dahin greift der
  Buchstaben-Badge-Fallback.
