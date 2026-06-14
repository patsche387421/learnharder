# Navigation

**Typ:** Komponente
**Datei:** `nav.html`

## Kurzbeschreibung
Top-/Sidebar-Navigation mit Logo, Hauptmenüpunkten, aktivem Zustand,
User-Bereich und Theme-Toggle.

## Verwendete Tokens (aus `src/css/tokens.css`)
- `--bg`, `--surface` (Nav-Hintergrund)
- `--text`, `--text-muted`
- `--primary` (aktiver Menüpunkt, Highlight)
- `--radius`

## Verwendet auf `src/`-Seiten
- Alle geschützten Seiten: `dashboard.html`, `faecher.html`, `pos.html`,
  `dbi.html`, `fach.html` und alle weiteren Fach-Übersichten.
- Login (`index.html`): meist ohne Nav — prüfen.

## TODOs / Abweichungen vom aktuellen Stand
- Aktuell duplizieren mehrere HTML-Seiten den Header — beim Umsetzen
  überlegen, ob die Nav per JS-Modul (IIFE-Pattern) zentral injiziert wird.
- Active-State soll automatisch anhand der aktuellen URL gesetzt werden.
- Theme-Toggle (Dark/Light) anbinden an `data-theme`-Attribut auf `<html>`
  (siehe `DESIGN_GUIDELINES.md` §2).
