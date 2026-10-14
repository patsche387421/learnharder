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

## 2026-10-14 — Design-System übernehmen, Vue-Bausteine, Tests

- **Branch:** `feat/design-system` (von `dev`), noch nicht gemerged, nicht gepusht.
- **Commits:** `1f4f21f` (`tokens.json`), `92fde6c` (Brand Book, Sprache, Komponenten-Anleitungen nach `docs/design/`), `3269803` (43 Icons nach `frontend/src/assets/icons/`), `f6dc3af` (`npm run check:tokens`), `f2d52ed` (Batch 1: `LhIcon`, `LhArt`, `LhButton`, `LhCard`, `LhEmptyState`, `LhTextField`, Router, `/styleguide`), `c32b89c` (`style.css`: Seitenrand, Hintergrund, `color-scheme`), `529abfb` (Batch 2: `LhLevelBadge`, `LhXpBar`, `LhCurrencyChip`, `LhSubjectCard`, `LhAnswerOption`), `55fc944` (Vitest 4 mit Tests für alle Bausteine), `6aea9b2` (Batch 3: `LhTabBar`, `LhAppLayout`, Platzhalter-Routen, Fokus-Modus), dazu dieser Doku-Commit.
- **Ergebnis:**
  - Das Design-System-Artefakt (https://claude.ai/artifact/89Tg8wEdFdFDMUNBWG2YNb) ist die Quelle. Das Repo hält eine Kopie: `tokens.json`, `bundle.css`, `docs/design/`, Icons. Tokens und `bundle.css` stimmten schon überein (nur das Google-Fonts-`@import` bleibt bewusst weg).
  - 13 dünne `Lh`-Bausteine ohne eigenes Styling, nur `lh-`-Klassen. `App.vue` legt `LhAppLayout` um alle Seiten (`meta.layout`, `meta.focus`).
  - `style.css` ergänzt `lh-app--focus`, weil `bundle.css` im Fokus-Modus ab 768px die leere Navigationsspalte stehen lässt.
  - Vitest 4 und jsdom 29, weil Vitest 5 und jsdom 30 Node 22 brauchen (hier Node 20).
- **Geprüft:** 44 Tests, `npm run build` und `npm run check:tokens` grün, `/styleguide` nicht im Build. Vorschau bei 375, 768 und 1280px in Hell und Dunkel, Fokus-Modus per DOM simuliert. Die Tests fangen den Kompilierfehler aus Batch 2 ab, den `npm run build` nicht sah. Nicht geprüft: echter Fokus-Screen (gibt es noch nicht), echtes Handy, Screenreader.
- **Offen:**
  - `feat/design-system` nach `dev` mergen.
  - Lücken in `bundle.css` im Artefakt schließen (Karte als Button, Ergebnis-Wort in der Antwort, kleines Icon, Chip als Link, `<code>` in Fragen, Fokus-Modus, README-Einleitung). Läuft als eigene Session.
  - Atkinson-Font beschaffen.
  - Repo nach WSL2 umziehen (vorher Branch pushen). Mit Node 22 dort auf Vitest 5 gehen.
  - User-Store für Level, Rang und Prestige statt fester Werte in `App.vue`.
  - Eintrag in `docs/ENTSCHEIDUNGEN.md` prüfen: „Artefakt ist Quelle, Repo ist Kopie“ als E-006.

## 2026-10-14 — Design-System-Lücken, Artefakt v25, Merge nach dev

- **Branch:** `dev` (Merges aus `feat/design-system` und `feat/design-system-luecken`). Beide Branches, der App-Branch `claude/quizzical-keller-ac54c3` und der Worktree der Sub-Session sind gelöscht.
- **Commits:** `30d1940` (Lücken in `bundle.css`), `98043a4` (`Lh`-Bausteine nutzen die neuen Klassen), `93a02e5` (Merge `feat/design-system`), `d24de4f` (Merge `feat/design-system-luecken`), dazu dieser Doku-Commit
- **Ergebnis:**
  - 7 Lücken in `bundle.css` geschlossen, u. a. `.lh-card--link` als Button und `.lh-app--focus` (vorher Patch in `style.css`, jetzt in `bundle.css`). `LhCard` hat `button`, `LhCurrencyChip` `to`/`href`.
  - Artefakt Version 25 veröffentlicht und mit dem Repo verglichen: identisch bis auf das Google-Fonts-`@import`.
  - Entscheidung E-006 in `docs/ENTSCHEIDUNGEN.md`: Artefakt ist Quelle, Repo hält die Kopie.
  - `dev` bis `93a02e5` gepusht. `d24de4f` ist noch nicht gepusht.
  - Sub-Session archiviert, verwaister Vite-Server gestoppt.
- **Geprüft:** Nach dem Merge auf `dev`: `npm run test` (47 Tests in 13 Dateien), `npm run build` und `npm run check:tokens` grün. `bundle.css` und `docs/design/README.md` mit Artefakt v25 verglichen: identisch bis auf das Google-Fonts-`@import`. `lh-app--focus` steht nur noch in `bundle.css`. Nicht geprüft: echtes Handy, Screenreader.
- **Offen:** Atkinson-Font beschaffen (`frontend/public/assets/fonts/`). Repo nach WSL2 umziehen, dort mit Node 22 auf Vitest 5 und jsdom 30. User-Store für Level, Rang und Prestige statt fester Werte in `App.vue`.
