# CLAUDE.md — _archiv/

**Nur lesen. Hier wird nichts geändert, nichts neu angelegt und nichts als aktuelle Wahrheit verwendet.**

Enthält den V1-Prototyp (Vanilla JS + Supabase + Netlify) und die V1-Teile, die für den Umzug
noch gebraucht werden. Die Ordnerstruktur spiegelt die alten Pfade:
`_archiv/legacy/` war `legacy/` (davor `src/`), `_archiv/docs/` war `docs/` usw.
Alles andere aus V1 wurde am 2026-10-14 gelöscht (siehe `INVENTAR.md`, Git-Historie und Tags).

## Wofür das Archiv noch gebraucht wird

- **Content-Import:** `legacy/assets/data/` ist die Quelle für `php artisan learnharder:import-legacy-content --path=../_archiv/legacy/assets/data`.
- **Nutzer-Migration:** `sql/migrations/001–003` beschreiben das Supabase-Schema, aus dem die Prod-Nutzerdaten exportiert werden.
- **Konzepte nachschlagen:** Teams/Saisons, Mandanten, Reiter, Ideen, Namenskonvention. Was davon weiterlebt, wird nach `docs/` übernommen und dort neu geschrieben, nicht hier gepflegt.

Welche Teile behalten oder gelöscht werden, steht in `INVENTAR.md` (Entscheidung durch Patsche).
