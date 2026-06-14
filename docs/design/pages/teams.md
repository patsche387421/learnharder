# Rangliste & Lernteams

**Typ:** Seite (Mockup)
**Datei:** `teams.html`

## Kurzbeschreibung
Soziale Ebene: Rangliste (Leaderboard) über User/Klassen und Lernteams-Ansicht
mit Mitgliedern, Team-Score, Wochen-Highlight.

## Verwendete Tokens (aus `src/css/tokens.css`)
- `--bg`, `--surface`, `--surface-2`
- `--primary` (Top-Platz, Team-Akzent)
- `--text`, `--text-muted`
- `--success` (Aufstieg / Streaks)
- `--radius`

## Betroffene `src/`-Seiten
- **Noch keine** — Feature ist neu, es gibt aktuell keine `teams.html`
  oder Rangliste in `src/`. Mockup dient als Spezifikation für später.

## TODOs / Abweichungen vom aktuellen Stand
- DB-Modell offen: Aggregat-Tabellen für Leaderboard / Teams müssen
  geplant werden (gehört evtl. in `DATA_MIGRATION_V2.md`).
- RLS-Frage: User sehen Team-Scores aller Mitglieder — Policy entsprechend
  weiter fassen als bei `fach_stats`.
