# Archiv-Inventar

Entschieden von Patsche am 2026-10-14: alle Empfehlungen angenommen.
Gelöschtes bleibt in der Git-Historie und über die Tags `v1.0.0-vanilla-backup`, `v1.1.0-w4y-plans-and-review` und `v2.0.0-plan` erreichbar.

## Noch im Archiv

| # | Pfad | Was | Entscheidung |
|---|---|---|---|
| A1 | `legacy/` | V1-Prototyp; `legacy/assets/data/` ist die Quelle für den Content-Import | behalten bis M5 (Umzug) |
| A2 | `sql/migrations/001–003` | Supabase-Schema + Test-User-Seed | behalten bis zur Nutzer-Migration |
| A6 | `agent/` | V1-Regelwerk, Session-Reports, Prompts | behalten als Verlauf, nicht pflegen |
| A8 | `docs/KONZEPT_TEAMS_SAISONS.md`, `KONZEPT_MANDANTEN.md`, `KONZEPT_REITER.md` | Feature-Konzepte | als V2-Konzepte in `docs/` neu schreiben, danach hier löschen |
| A9 | `docs/IDEEN.md` | Ideen-Backlog | gegen V2 aussortieren, nach `docs/` übernehmen, danach hier löschen |
| A10 | `docs/NAMENSKONVENTION.md` | Begriffe | auf V2-Begriffe anpassen, nach `docs/` übernehmen, danach hier löschen |

## Gelöscht (2026-10-14)

| # | Was |
|---|---|
| A3 | `data/import/`: älterer Entwurf von DBI „1:1-Beziehungen“, neuere Fassung steckt in `legacy/assets/data/dbi/` |
| A4 | `scripts/gen-config.js` |
| A5 | `tests/` (V1-Handtests T-001–T-005, als Idee für Playwright-E2E) |
| A7 | alte `README.md` |
| A11 | `docs/LEVEL_SYSTEM.md` |
| A12 | `docs/DESIGN_GUIDELINES.md`, `docs/design/` |
| A13 | `docs/BUGS.md`, `ROADMAP.md`, `CODE_REVIEW_UND_DOKUMENTATION.md`, `DATA_MIGRATION_V2.md`, `README.md`, `archive/` |
| A14 | `docs/plans/PHASE_1–5_*`, `PHP_TRANSFORMATION_PLAN*.md` |
| A15 | Vite-Beispieldateien + altes lila `tokens.css` aus `frontend/` |

## Außerhalb des Archivs (nicht getrackt)

| # | Pfad | Entscheidung |
|---|---|---|
| B1 | `backup_*.zip` (Root) | aus dem Projektordner an einen sicheren Ort verschieben (macht Patsche) |
| B2 | `.env` (Root, V1-Supabase) | nach der Nutzer-Migration löschen |
| B3 | `.netlify/` | behalten, solange Netlify die Wartungsseite ausliefert |
