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
- Aktuell verwendet `manifest.json` Emoji-Icons (z.B. `"💻"`). Wechsel auf
  SVG-Icons ist nicht trivial — Migration separat planen.
- Icons sollen `currentColor` als Fill/Stroke nutzen, damit Token-Wechsel
  (Theme) automatisch greift. Im Mockup teils noch hartkodiert.
- Icons als einzelne SVG-Dateien unter `src/assets/icons/` ablegen (Ordner
  existiert noch nicht).
