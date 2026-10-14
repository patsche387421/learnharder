# Session-Report — V2

Fortlaufendes Protokoll. Neue Einträge unten anhängen, nie überschreiben.
V1-Protokoll: `_archiv/agent/SESSION_REPORT.md` und `SESSION_REPORT_ARCHIVE.md`.

---

## 2026-10-14 — M0 Projektstruktur

- **Branch:** `chore/v2-projektstruktur` (von `dev`)
- **Commits:** `61f972e` (Archiv), `f8fc001` (Design-System), dazu dieser Doku-Commit
- **Ergebnis:**
  - V1-Prototyp, Supabase-SQL, V1-Agent-Dateien und V1-Konzepte nach `_archiv/` verschoben (alte Pfade gespiegelt). Alle Inventar-Empfehlungen von Patsche angenommen: World4You-Pläne, V1-Design-Doku, V1-Bugs/Roadmap/Tests und `gen-config.js` gelöscht (`_archiv/INVENTAR.md`).
  - Neues Design-System (`tokens.css`, `fonts.css`, `bundle.css`) nach `frontend/src/styles/` übernommen und in `main.js` eingebunden. Vite-Beispieldateien und altes lila `tokens.css` gelöscht. Space Mono nach `frontend/public/assets/fonts/` kopiert.
  - CLAUDE.md für Root, backend, backend/database, backend/app/Filament, frontend, frontend/src/styles, infra, docs, agent, _archiv.
  - `docs/ENTSCHEIDUNGEN.md` und `docs/plans/README.md` (Ist-Stand, Widersprüche, Meilensteine M0–M5) neu.
  - Neue `README.md` im Root.
- **Geprüft:** `npm run build` grün. Dev-Server: Tokens greifen, Konsole fehlerfrei, Atkinson-Font fehlt erwartungsgemäß.
- **Offen:** V1-Konzepte als V2-Doku neu schreiben (Inventar A8–A10), Backup-Zips aus dem Projektordner verschieben (B1, macht Patsche), Atkinson-woff2 und `tokens.json` beschaffen, `backend/laravel` aus Git entfernen, Seeder absichern.

## 2026-10-14 — M0 Aufräumen, WSL2-Setup, Doku-Subagent

- **Branch:** `chore/repo-aufraeumen` (von `dev`), nach `dev` gemerged als `d6a7487` (`--no-ff`).
- **Commits:** `527a504` (eingecheckte SQLite `backend/laravel` entfernt), `a1950af` (Boost-Bootstrap `backend/AGENTS.md` entfernt), `fa0672d` (Laravel-/Vite-Boilerplate entfernt), `2d3c920` (`.gitignore` bereinigt, `.claude/agents`, `.claude/skills`, `.claude/launch.json` versioniert), `22c4109` (`backend/.env.example` auf Sail-Postgres/Redis), `74c46b5` (WSL2-Setup und main-Worktree dokumentiert), `926a2c3` (Subagent `doku` auf Haiku, Skill `doku-pflegen`), `ba15d65` (`_archiv/agent/CLAUDE.md` nach `V1_CLAUDE.md`, damit sie nicht automatisch geladen wird).
- **Ergebnis:**
  - Repo aufgeräumt. `_archiv/legacy/assets/data` ist read-only nach `/var/www/legacy-data` gemountet (`backend/compose.yaml`).
  - Entscheidung E-005: lokale Entwicklung in WSL2, `main` als Worktree `~/learnharder.app-main`. Auf dem Windows-Dateisystem war ein Request etwa 20 s langsam, `tempnam()` schlug auf dem Bind-Mount fehl, Port 8000 war von Windows aus gesperrt und das Sail-Skript lief in Git Bash nicht.
  - WSL2-Setup steht in `README.md`, `CLAUDE.md`, `backend/CLAUDE.md` und `docs/ENTSCHEIDUNGEN.md` (E-005).
  - Subagent `doku` (`.claude/agents/doku.md`, Modell haiku) mit Skill `doku-pflegen` (`.claude/skills/doku-pflegen/SKILL.md`).
  - Gemergte Branches `chore/v2-projektstruktur`, `fix/btn-disabled-styling` und `chore/repo-aufraeumen` gelöscht. `backup/*` bleibt als Archiv.
- **Geprüft:** Unter Windows (`C:\`): Frontend `npm run build` grün, Dev-Server ohne Konsolenfehler. Backend per `docker compose`: Migrationen und Seeder ok, Content-Import 7 Fächer / 32 Themen / 158 Theorieblöcke / 158 Fragen, `php artisan test` 2/2 grün. Nicht geprüft: Sail-Stack in WSL2.
- **Offen:**
  - Repo in WSL2 klonen und Worktree `~/learnharder.app-main` anlegen (Patsche, siehe `README.md`).
  - Sail-Stack in WSL2 komplett testen.
  - `dev` nach `main` mergen und pushen erst nach „deployen“. Das stellt Netlify von V1 auf die Wartungsseite um.
  - Seeder absichern.
  - `ImportLegacyContent`-Standardpfad korrigieren (zeigt noch auf `../src/assets/data`, siehe `backend/CLAUDE.md`).
  - Atkinson-Font beschaffen.
