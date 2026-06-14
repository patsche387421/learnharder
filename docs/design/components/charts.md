# Diagramme & Charts

**Typ:** Komponente
**Datei:** `charts.html`

## Kurzbeschreibung
Wiederverwendbare Chart-Typen für Lern-Stats: Balken-, Linien- und
Donut-/Ring-Diagramme für Quiz-Score-Verlauf, Fach-Verteilung, Wochen-Aktivität.

## Verwendete Tokens (aus `src/css/tokens.css`)
- `--surface`, `--surface-2` (Chart-Karten-Hintergrund)
- `--primary`, `--primary-hover` (Hauptserie)
- `--success`, `--error` (positive/negative Akzente)
- `--text`, `--text-muted` (Achsen, Labels)
- `--radius`

## Verwendet auf `src/`-Seiten
- `src/dashboard.html` (Quiz-Verlauf, Fortschritt pro Fach)
- evtl. `src/faecher.html` (Mini-Charts pro Fach-Karte)

## TODOs / Abweichungen vom aktuellen Stand
- Keine externe Chart-Library erlaubt (siehe `CLAUDE.md` → keine externen
  Libraries). Charts müssen als Vanilla-SVG oder Canvas umgesetzt werden.
- Datenquelle: `quiz_results` (Verlauf) und `fach_stats` aus `src/js/stats.js`.
- Farbpalette für Multi-Serien-Charts ist im Mockup hartkodiert → zusätzliche
  Akzent-Tokens (`--chart-1`, `--chart-2` …) ggf. später in `tokens.css` ergänzen.
