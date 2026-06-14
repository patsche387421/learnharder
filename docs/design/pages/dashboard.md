# Dashboard

**Typ:** Seite (Mockup)
**Datei:** `dashboard.html`

## Kurzbeschreibung
Persönliches Dashboard nach Login: Begrüßung, Lern-Stats, Fortschritt
über alle Fächer, Quick-Access zu offenen Themen / Tagesquiz.

## Verwendete Tokens (aus `src/css/tokens.css`)
- `--bg` (Seitenhintergrund)
- `--surface`, `--surface-2` (Karten/Panels)
- `--primary`, `--primary-hover` (Akzent, CTAs)
- `--text`, `--text-muted`
- `--success` (Abschluss-Badges, Streak)
- `--radius`

## Betroffene `src/`-Seiten
- `src/dashboard.html`

## TODOs / Abweichungen vom aktuellen Stand
- Mockup verwendet hartkodierte Hex-Farben (`#F1F5F9` etc.) — bei der
  Umsetzung in `style.css` ausschließlich `var(--…)`-Tokens nutzen.
- Nutzt Sub-Komponenten aus `components/`: `nav`, `charts`, `progress`.
- Tagesquiz-Karte soll später `src/assets/data/tagesquiz_test.json` laden
  (siehe `docs/DATA_MIGRATION_V2.md` → Nächster Schritt).
