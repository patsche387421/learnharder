# Logo

**Typ:** Komponente (Asset)
**Datei:** `logo.html`

## Kurzbeschreibung
LearnHarder-Logo-Varianten: Wortmarke, Kurz-/Icon-Variante, Verwendung auf
hellem vs. dunklem Hintergrund, Mindestabstände.

## Verwendete Tokens (aus `src/css/tokens.css`)
- `--primary` (Hauptlogo-Farbe)
- `--text` (Wortmarke auf neutralem Hintergrund)
- `--bg`, `--surface` (Hintergrund-Beispiele)

## Verwendet auf `src/`-Seiten
- `src/index.html` (Login-Seite, prominent)
- Navigation (Header) — siehe `nav.md`
- evtl. Favicon / OG-Image (Asset-Pipeline noch offen)

## TODOs / Abweichungen vom aktuellen Stand
- Logo aktuell nicht als wiederverwendbares Asset in `src/assets/` abgelegt —
  noch entscheiden: SVG-Datei vs. inline-SVG-Snippet in HTML.
- Dark-/Light-Mode-Varianten via `currentColor` lösen statt zwei Dateien.
- Logo-Mindestbreite/Padding als Design-Token oder im Komponenten-CSS festlegen.
