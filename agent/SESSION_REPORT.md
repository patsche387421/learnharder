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

## 2026-10-15 — Umzug nach WSL2 abgeschlossen

- **Branch:** `dev`
- **Commits:** dieser Doku-Commit
- **Ergebnis:**
  - Erste Session im WSL2-Checkout `~/learnharder.app`. `C:\v\learnharder.app` ist nur noch Archiv bis zur Nutzer-Migration (E-005).
  - Claude-Memory aus `~/claude-memory-backup/learnharder-2026-10-15/` in den Memory-Ordner des WSL2-Projekts kopiert (13 Dateien).
  - Doku angepasst: Windows-Begründung in `CLAUDE.md` gekürzt, Node 22 in `README.md` und `frontend/CLAUDE.md`, alte `C:\`-Notlösungen in `backend/.env` als Baustelle in `backend/CLAUDE.md`, Ist-Stand 2.1 in `docs/plans/README.md`.
- **Geprüft:** In WSL2 mit Node 22.23.3: `sail artisan test` 13/13 grün, `npm run test` 47/47 grün, `npm run build` und `npm run check:tokens` grün.
- **Offen:**
  - `dev` pushen.
  - M1: Policies (Schritt 6), danach CI.
  - `backend/.env` aufräumen (`APP_PORT`, `VITE_PORT`, `VIEW_COMPILED_PATH`, Caches nach `/tmp`).
  - Vitest 5 und jsdom 30.
  - Supabase-Dump (O-004), Level-Formel (Widerspruch 4), Filament 3 oder 4 (O-002).

## 2026-10-15 — `backend/.env` aufgeräumt

- **Branch:** `dev`
- **Commits:** `3ff43af` (`APP_URL` auf Sail-Port 80, `VITE_PORT` neben dem Frontend), dazu dieser Doku-Commit
- **Ergebnis:**
  - Alte `C:\`-Notlösungen aus der lokalen `backend/.env` entfernt (`APP_PORT=8080`, `WWWUSER`/`WWWGROUP`, `VIEW_COMPILED_PATH` und Caches nach `/tmp`). Die App läuft unter http://localhost.
  - `VITE_PORT=5174` bleibt und steht jetzt auch in `.env.example`: `compose.yaml` veröffentlicht den Port, und 5173 gehört dem Vue-Frontend.
  - Lokalen `APP_KEY` neu erzeugt, weil er in einer Agent-Ausgabe im Klartext stand. Im Code ist nichts damit verschlüsselt, nur lokale Sessions sind abgemeldet.
- **Geprüft:** Nach Sail-Neustart: `/` 200, `/admin/login` 200, `/sanctum/csrf-cookie` 204, Login und `/api/v1/me` mit Demo-Schüler 200, ohne Login 401. `sail artisan test` 13/13 grün.
- **Offen:** wie im Eintrag davor, ohne die `.env`-Aufräumung.

## 2026-10-15 — M1 Policies

- **Branch:** `feat/policies` (von `dev`), nach `dev` gemerged als `b0b0f13` (`--no-ff`), Branch gelöscht.
- **Commits:** `49cae8c` (Policies für Fach, Thema, Frage), `f0ebb01` (Demo-Account `tester@learnharder.app`), `46956aa` (Doku), dazu dieser Doku-Commit
- **Ergebnis:**
  - Basis `app/Policies/ContentPolicy.php`, davon erben `SubjectPolicy`, `TopicPolicy` und `QuestionPolicy`. Rollen werden direkt in der Policy geprüft, ohne Spatie-Permissions.
  - Admin darf alles (`Gate::before` im `AppServiceProvider`). autor: sehen, anlegen, bearbeiten, duplizieren, sortieren, kein Löschen. tester: nur sehen. teacher, student und Nutzer ohne Rolle: nichts.
  - Filament 3 erlaubt eine Aktion, wenn die Policy-Methode fehlt (`vendor/filament/filament/src/helpers.php`). Deshalb definiert die Basis alle 12 Aktionen, die Filament prüft. Als Regel in `backend/CLAUDE.md`.
  - Neuer Demo-Account für die Rolle tester (nur `local`/`testing`).
- **Geprüft:** `sail artisan test` 23/23 grün (neu: Matrix 5 Rollen × 12 Aktionen × 3 Models, Filament-Tests für Lösch-Button, Sammel-Löschen, 403 für tester und student), `pint --test` grün. Gegenprobe: „delete erlaubt“ lässt 5 Tests scheitern, fehlendes `deleteAny` fängt nur der Filament-Test. Nach dem Merge auf `dev` erneut grün. Nicht geprüft: Klicktest im Browser.
- **Offen:**
  - CI (Pint und Tests). Vorher `ImportLegacyContent.php` durch `pint --test` bringen.
  - M2: `publish` in `ContentPolicy`. Policies für `TheorySection` und `QuestionOption`, sobald es Ressourcen oder API dafür gibt.
  - M3: Schüler:innen brauchen `view` auf veröffentlichte Inhalte, sobald die Lern-API kommt.
  - Vitest 5 und jsdom 30, Supabase-Dump (O-004), Level-Formel (Widerspruch 4), Filament 3 oder 4 (O-002).

## 2026-10-15 — M1 CI

- **Branch:** `chore/ci` (von `dev`), auf GitHub geprüft, nach `dev` gemerged als `44f72e2` (`--no-ff`), Branch lokal und auf GitHub gelöscht.
- **Commits:** `a740f13` (Pint auf das ganze Backend), `0bacf52` (CI-Workflow), `78e58a6` (Doku), dazu dieser Doku-Commit
- **Ergebnis:**
  - `.github/workflows/ci.yml` läuft bei jedem Push und per Hand. Backend: PHP 8.5, Postgres 18 als Service (Datenbank `testing`), `pint --test`, `php artisan test`. Frontend: Node 22, `npm ci`, `check:tokens`, `test`, `build`.
  - `shivammathur/setup-php` ist die einzige Drittanbieter-Action und auf den Commit von 2.40.0 festgelegt. `checkout` und `setup-node` laufen auf `@v7`.
  - Pint auf 8 Dateien angewendet (`ImportLegacyContent.php`, 6 generierte Filament-Dateien, `bootstrap/providers.php`). Ohne Leerraum nur gekürzte Imports und einfache Anführungszeichen, kein geändertes Verhalten.
  - M1 ist damit abgeschlossen: Postgres, Sanctum-Login, Policies, Feature-Tests, CI.
- **Geprüft:** `actionlint` ohne Befund. Beide Jobs lokal aus sauberem Stand nachgestellt (frische Kopie ohne `vendor/` und `.env`, frisches Postgres 18, `npm ci`): grün. Erster Lauf auf GitHub (Run `38709057726`): backend 34 s, frontend 23 s, beide grün. Nach dem Merge auf `dev` Tests und `pint --test` grün.
- **Offen:**
  - M2: CMS redaktionstauglich (Antwortoptionen, Wertelisten als Enum, de/en), `publish` in `ContentPolicy`. Vorher Filament 3 oder 4 entscheiden (O-002).
  - Vitest 5 und jsdom 30, jetzt mit CI als Netz.
  - Supabase-Dump (O-004), Level-Formel (Widerspruch 4).

## 2026-10-15 — Filament 5 und Supabase-Sicherung

- **Branch:** `chore/filament-5` (von `dev`), nach `dev` gemerged als `11452eb` (`--no-ff`), Branch gelöscht. Danach `dev`.
- **Commits:** `de34cff` (Filament 3 auf 4), `903120b` (Filament 4 auf 5 mit Livewire 4), `5a6e7a8` (E-008), `f40d91e` (E-009), dazu dieser Doku-Commit
- **Ergebnis:**
  - Filament 3.3.56 → 5.10.2, Livewire 3 → 4.4.7, Laravel 13.35 → 13.36 (E-008, schließt O-002). Upgrade-Skripte `filament-v4` und `filament-v5`, danach Pint. Composer nur für Filament und Abhängigkeiten aktualisiert, `filament/upgrade` wieder entfernt. Der v5-Schritt brauchte keine Code-Änderung.
  - Recherche: Filament 3 bekommt seit 2026-08-01 keine Bugfixes mehr, Filament 4 nur bis 2027-01-15. Translatable-Plugin für v5 ist der Community-Fork `lara-zeus/spatie-translatable` (noch nicht installiert).
  - Supabase-Dump (`public` und `auth`) von Patsche gezogen, liegt außerhalb des Repos (E-009, schließt O-004). Das WLAN sperrt Port 5432, der Dump lief über einen Handy-Hotspot.
- **Geprüft:** Nach jedem Schritt 23/23 Tests und `pint --test` grün. Gegenprobe „`deleteAny` fehlt“ schlägt in v4 und v5 im Filament-Test an. `/admin/login` lädt nichts Externes. Klicktest von Patsche im Browser mit admin, autor und tester wie erwartet. CI auf `dev` (Run `38715630054`) grün. Dump enthält alle 6 V1-Tabellen und `auth.users` (`pg_restore --list`).
- **Offen:**
  - Verschlüsselte Zweitkopie des Dumps (Patsche).
  - `feat/supabase-import`: vorher Gamer-Tags, Umfang (XP/Streak oder auch Fortschritt und Quiz-Verlauf) und Level-Formel (Widerspruch 4) entscheiden.
  - M2: Translatable-Plugin testen, `APP_LOCALE=de`, Enums, Antwortoptionen, Relations-Labels (Spalten Fach/Thema sind leer), Name und Farben im Panel.
  - Vitest 5 und jsdom 30.

## 2026-10-15 — Level-Kurve, Booster, Start ohne V1-Nutzer

- **Branch:** `dev`
- **Commits:** `e1f826b` (E-010), `40af4aa` (E-011), dazu dieser Doku-Commit
- **Ergebnis:**
  - E-010: Level n beginnt bei `round((n−1)^1.5 × 8)` XP (wie V1, Level 2 bei 8, Level 100 bei 7.880). Prestige-Zyklus immer 8.000 XP, Rang = `floor(Gesamt-XP / 8000)`. XP-Booster 1,5× 30 Min (20 BP), 2× 30 Min (40 BP), 2× 60 Min (70 BP), nur einer gleichzeitig, kein Kauf während einer läuft. Tabelle im Ökonomie-Modell korrigiert, Widerspruch 4 erledigt.
  - E-011: V2 startet ohne V1-Accounts und -Fortschritte, alle registrieren sich neu. Kein `feat/supabase-import`. M5 heißt jetzt „Start“ (DNS, Registrierung, V1-Supabase abschalten). Dump wird 3 Monate nach V2-Start gelöscht.
  - Setup-Plan §2 (Datenmigration) als Widerspruch 6 vermerkt, Plan selbst nicht geändert.
- **Geprüft:** Alle Schwellenwerte der Level-Tabelle per Formel nachgerechnet. Nur Doku, kein Code geändert.
- **Offen:**
  - M2: Translatable-Plugin `lara-zeus/spatie-translatable` testen, `APP_LOCALE=de` und Relations-Labels (gegen leere Fach-/Thema-Spalten), Enums, Antwortoptionen, Name und Farben im Panel.
  - Vor M3: mehr Fragen oder weniger XP für wiederholte Antworten (158 Fragen = 316 XP pro Durchlauf, ein Zyklus hat 8.000).
  - Registrierung in V2 mit sauberer Einwilligung, auch für unter 14-Jährige.
  - Verschlüsselte Zweitkopie des Dumps (Patsche). Vitest 5 und jsdom 30.

## 2026-10-15 — Nur Deutsch im CMS, Datenmodell Schule, Schulstruktur in Filament

- **Branch:** `feat/cms-translatable` (nur Prüfung, unverändert gelöscht), `dev` (E-012, E-013), `feat/schulstruktur` (nach `dev` gemerged als `9ac2005`, `--no-ff`, gelöscht), `feat/schulstruktur-models` (offen, noch nicht gemerged)
- **Commits:** `1f7d64e` (E-012), `552efb7` (E-013 und Plan), `769829b` (Schema), `b33e910` (Doku), `8379316` (Models und Enums), `a32570b` (Doku), `bb3af01` (Filament und Policies), `cd23e3d` (Doku), `9325ad3` (Menü), dazu dieser Eintrag
- **Ergebnis:**
  - `lara-zeus/spatie-translatable` 2.0.1 geprüft (MIT, Dry-Run ohne Konflikte, eine Person pflegt es, offene Fehler #42, #51, #63). Entscheidung E-012: CMS bis nach M5 nur Deutsch, kein Plugin.
  - Recherche HTL Mödling: 12 Abteilungen, 5 Ausbildungsformen (5 Jahre bis 4 Semester). Daraus E-013 und `docs/plans/DATENMODELL_SCHULE.md`, Szenarien durchgespielt: kritische eingebaut, mittlere geplant, schwache in §6 gemerkt.
  - Schema in den bestehenden Migrationen (lief noch auf keinem Server): Schule → Abteilung → Ausbildungsgang → Klasse je Schuljahr, Lehrplan, Lehrkraft-Zuordnung. Postgres sichert ab: zusammengesetzter Fremdschlüssel, Teil-Index „eine aktive Klasse pro Schuljahr“, `NULLS NOT DISTINCT`, `CHECK`s, `lower()`-Indizes auf E-Mail, Gamer-Tag und Domain. `quiz_attempts.topic_id` jetzt `RESTRICT`, `users.deactivated_at` neu.
  - Models und Enums (`ProgramForm`, `ProgramUnit`) für die Schulstruktur. Filament: `SchoolResource` (Mail-Domains, Abteilungen, Schuljahre) und `ProgramResource` (Lehrplan), nur Admin über `SchoolStructurePolicy`. Menü deutsch, Gruppen „Inhalte“ und „Schule“, „Übersicht“ statt Dashboard.
  - Gelernt: Selbstverweis-Fremdschlüssel braucht ein eigenes `Schema::table` nach dem `create`. `ON DELETE RESTRICT` meldet SQLSTATE `23001`, nicht `23503`.
  - Haiku-Subagent zur DB-Überwachung bewusst weggelassen. Vorher/Nachher stattdessen per Skript (Schema-Dump und Zeilenzahlen).
- **Geprüft:** Tests 23 → 56 grün, Pint sauber. `migrate:fresh`, kompletter Rollback, `migrate`, Seed und Import: Inhalte wieder vollständig (7 Fächer, 32 Themen, 158 Theorieblöcke, 158 Fragen, 632 Optionen), nur Cache und Sessions leer. CI auf `dev` grün (Runs `38754459893`, `38759218245`). Menü von Patsche im Browser gesehen. Formulare der Schulstruktur nur per Test geprüft, nicht im Browser. `feat/schulstruktur-models` noch nicht gepusht, CI dafür offen.
- **Offen:**
  - `feat/schulstruktur-models` mit `--no-ff` nach `dev`, pushen, CI.
  - `APP_LOCALE=de` und Fallback in `.env.example` (lokal steht `en`/`en`), dazu deutsche Validierungsmeldungen (Laravel-Sprachdateien fehlen).
  - Leere Spalten Fach/Thema in den Inhaltstabellen (Labels auf JSON-Spalten) samt Feature-Tests.
  - M2-Rest: Enums für `status`/`type`/`difficulty`, Antwortoptionen im Fragenformular, Panel-Theme aus `tokens.css` (Tailwind 4), Veröffentlichen in `ContentPolicy`.
  - Plan Schule §8 Schritte 2–4 (Klassen, Sichtbarkeit, Registrierung) und §7 (Mail-Domains und Microsoft 365, Kürzel, `users.name`, Einwilligung unter 14).
  - Hetzner (M4a Staging und Backups) erst, wenn andere Inhalte schreiben. Patsche arbeitet vorerst lokal. Bausteine für Backups (Dump, offsite, verschlüsselt, append-only, Aufbewahrung, Alarm, Restore-Test, Runbook) noch nirgends dokumentiert.
  - Lehrkraft-Testlauf verworfen.
  - `docs/plans/README.md` „Ist-Stand“ veraltet (u. a. „13 Tests“).
  - Weiter offen: verschlüsselte Zweitkopie des Dumps, mehr Fragen oder weniger XP vor M3, Vitest 5 und jsdom 30.

## 2026-10-15 — Schulstruktur gemerged, CMS auf Deutsch

- **Branch:** `feat/schulstruktur-models` (nach `dev` gemerged als `6b30da6`, `--no-ff`, gelöscht), `feat/filament-deutsch` (nach `dev` gemerged als `099865c`, `--no-ff`, gelöscht)
- **Commits:** `dfaf8d4` (Locale `de`), `968e971` (Validierungsmeldungen), `2dbd816` (Login-, Passwort-, Blättermeldungen), `c16c7f8` (Fix Fach-/Themenauswahl), `929d356` (deutsche Feldnamen), `2656aae` (Fix Sprachfelder `.de`), `637f17a` (Doku), dazu dieser Eintrag
- **Ergebnis:**
  - `APP_LOCALE` und `APP_FALLBACK_LOCALE` in `.env.example` und lokal auf `de`. Die CI baut ihre `.env` aus der Vorlage.
  - Laravel-Texte in eigenen Dateien `backend/lang/de/` (validation, auth, passwords, pagination), du-Form nach `docs/design/Sprache.md`. `laravel-lang` verworfen: `laravel-lang/publisher` 16.8 unterstützt nur bis Laravel 12, für 13 gibt es nur `17.x-dev`. `GermanTranslationTest` prüft, dass gegenüber Laravel kein Schlüssel fehlt.
  - Fehler gefunden: Anlegen/Bearbeiten von Themen und Fragen gab 500, weil die Auswahlfelder nach einer `json`-Spalte sortierten (Postgres hat dafür keinen Sortier-Operator). Jetzt `relationship(…, 'slug')` mit `getOptionLabelFromRecordUsing` und `getTranslation(…, 'de')`.
  - Fehler gefunden: Sprachfelder banden die ganze JSON-Spalte ans Formular (Wert war ein Array). Jetzt `name.de`, `title.de`, `description.de`, `question_text.de`, `explanation.de` (E-012). Ein vorhandener `en`-Eintrag bleibt beim Speichern erhalten.
  - Fach- und Thema-Spalten lesen fest aus `de`, alle Felder und Spalten in Fächer, Themen und Fragen haben deutsche Labels.
- **Geprüft:** Tests 56 → 66 grün, Pint sauber, beides auf jedem Zwischen-Commit. Gegenprobe: ohne den Auswahl-Fix schlagen 2 Tests fehl. CI auf `dev` grün (Runs `38765669854`, `38770637866`). CMS nicht im Browser angesehen, nur per Test und gerendertem HTML.
- **Offen:**
  - Leere Sprachfelder speichert das CMS als `{"de":null}` statt `NULL` (`backend/CLAUDE.md`, Baustelle 1).
  - Browser-Titel zeigt „Laravel“ (`APP_NAME`). Filaments eigene Texte siezen („Wählen Sie eine Option“).
  - M2-Rest: Enums für `status`/`type`/`difficulty` (Werte erscheinen noch als `published`, `single_choice`), Antwortoptionen im Fragenformular, Panel-Theme aus `tokens.css` (Tailwind 4), Veröffentlichen in `ContentPolicy`.
  - Plan Schule §8 Schritte 2–4 und §7.
  - `docs/plans/README.md` „Ist-Stand“ veraltet (u. a. „13 Tests“).
  - Weiter offen: Hetzner (M4a) und Backup-Bausteine dokumentieren, verschlüsselte Zweitkopie des Dumps, mehr Fragen oder weniger XP vor M3, Vitest 5 und jsdom 30.

## 2026-10-15 — M2: CMS redaktionstauglich

- **Branch:** je Schritt ein Branch, mit `--no-ff` nach `dev` gemerged und gelöscht: `feat/cms-wertelisten` (`d159746`), `feat/cms-antwortoptionen` (`03d3789`), `feat/cms-veroeffentlichen` (`34c30f5`), `feat/cms-theorie` (`28cc886`), `feat/cms-panel-design` (`571c4a8`), `chore/cms-aufraeumen` (`45f98ca`), `docs/m2-abschluss` (`6e1c930`)
- **Commits:** 17 Einzel-Commits und 7 Merges, siehe `git log 2b370f9..6e1c930`, dazu dieser Eintrag
- **Ergebnis:**
  - Wertelisten als Enums (`ContentStatus`, `QuestionType`, `Difficulty`) mit `CHECK`-Regeln in Postgres. Neue Inhalte starten als Entwurf. Fragetypen in M2: eine richtige und mehrere richtige Antworten, Lückentext später (Entscheidung Patsche).
  - Fragenformular: Antworten direkt pflegen (2–6, sortierbar), bei einer richtigen schaltet der neue Schalter die anderen aus, erst Fach dann Thema, Hilfetexte. Kürzel prüft das Formular auf Format und Eindeutigkeit (vorher Serverfehler).
  - E-014: Veröffentlichen nur Admins. Ändern Autor:innen Veröffentlichtes oder dessen Theorie, geht es zurück in Prüfung. Mehrfachaktion „Veröffentlichen“ für Admins.
  - Theorie als Relation Manager am Thema (Markdown, ohne Uploads, kein CDN).
  - Panel: Name „learnharder“, Bronze-Palette aus `--accent` von Hand in OKLCH (Filaments Automatik wurde Orange mit Kontrast 4,15), `Color::Stone`, Atkinson Hyperlegible Next als Kopie in `backend/public/fonts/`. `APP_NAME=learnharder` in `.env.example`.
  - Aufräumen: leere Sprachfelder werden `NULL` (`HasTranslationsWithoutEmpty`), `color_token` nutzt `--subject-*`, der Import überschreibt keine Themen mit Inhalt mehr (vorher löschte er Fragen und Theorie).
  - Gelernt: Filament schreibt Labels in Fehlermeldungen klein („titel“), jetzt zentral über `Field::configureUsing` gelöst. Gesperrte Felder prüft Filament trotzdem, gesperrte Optionen lehnt es auf dem Server ab. GitHub hat für einen Push zwei CI-Läufe gestartet, `concurrency` brach einen ab.
- **Geprüft:** Tests 66 → 108 grün, Pint sauber, beides auf jedem Zwischen-Commit. CI auf `dev` nach jedem Merge grün. Lokale DB zweimal neu aufgesetzt (`migrate:fresh --seed` und Import), Zahlen danach vollständig. Fragenformular, Theorie und Panel-Design von Patsche im Browser gesehen, das Veröffentlichen als Autor nur per Test.
- **Offen:**
  - Lokale `.env` hat noch `APP_NAME=Laravel` (Umstellen loggt einmal aus).
  - Ändert ein Autor Theorie, zeigt das Status-Feld des Themas bis zum Neuladen noch „Veröffentlicht“.
  - Für M3: Die App zeigt nur `published`, auch das Thema muss veröffentlicht sein. Theorie-Markdown sicher rendern.
  - Schriftdatei liegt doppelt (Frontend und Backend), bei Änderungen beide ersetzen. `tokens.css` §5 (`--fach-*`) kann jetzt raus.
  - Plan Schule §8 Schritte 2–4 und §7. Vor M3: mehr Fragen oder weniger XP für wiederholte Antworten.
  - Weiter offen: Hetzner (M4a) und Backup-Bausteine, verschlüsselte Zweitkopie des Dumps, Vitest 5 und jsdom 30.

## 2026-10-15 — M3: Lernen funktioniert

- **Branch:** je Schritt ein Branch, mit `--no-ff` nach `dev` gemerged und gelöscht: `docs/m3-entscheidungen` (`3e77848`), `feat/api-fortschritt` (`c612a25`), `feat/api-inhalte` (`70457bd`), `feat/api-lerneinheit` (`10852b1`), `feat/app-login` (`9e12e5c`), `feat/app-faecher` (`3939b40`), `feat/app-theorie` (`f9ee94e`), `feat/app-lerneinheit` (`2a5a9a9`), `fix/app-fach-anzeige` (`1827e20`), `feat/fach-level` (`b7e667e`), dazu `docs/m3-abschluss`
- **Commits:** 11 Einzel-Commits und 10 Merges, siehe `git log 10d58ba..b7e667e`, dazu der Doku-Abschluss mit diesem Eintrag
- **Ergebnis:**
  - Entscheidungen: E-015 (EP je Frage einmal pro Tag, Tag in `Europe/Vienna`), E-016 (Ränge 1/25/50/75), E-017 (M3 ohne Klassenfilter, Klassen kommen in M3b), E-018 (Fach-Level aus den EP eines Fachs, wie in V1).
  - Server: `Progression` rechnet Level, Rang und Prestige aus `total_xp`. API für Fächer, Themen und Theorie (Markdown sicher als HTML) und für Lerneinheiten. `finish` sperrt erst `user_stats` und prüft dann die Tagesregel, die Attempt-ID ist der Idempotency-Key. Neue Tabelle `question_answers` (bestehende Migration geändert, lief noch auf keinem Server).
  - App: API-Client mit CSRF und einer Wiederholung bei 419, Login und Router-Guard, Store für Spielerdaten, Fächer → Thema → Theorie → Lerneinheit mit Feedback, Wiederholung falscher Fragen am Ende und Ergebnis mit Level-Aufstieg. Fach-Icon auf der Fach-Seite, Fach-Level in der Fach-Karte.
  - **Vorfall:** `migrate:fresh --env=testing` hat die lokale Entwicklungs-DB gelöscht, weil es keine `.env.testing` gibt und Laravel dann `.env` nimmt. Mit `migrate:fresh --seed` und Import wiederhergestellt (7 Fächer, 32 Themen, 158 Theorieblöcke, 158 Fragen, 632 Optionen, 5 Accounts). Änderungen im CMS seit dem letzten Import sind weg. Regel jetzt in `backend/CLAUDE.md`: nur `sail exec -T -e DB_DATABASE=testing`, Ziel vorher prüfen.
  - Gelernt: Eine Nicht-UUID in der Route gab in Postgres 500, jetzt vorher 404 bzw. 422. Ein zweiter Login im selben Test braucht `flushSession()` (Ursache nicht untersucht). `LhCard` ohne Link zeigt nur seinen Slot. `pint --dirty` geht im Container nicht (kein Git), deshalb Pint mit Pfaden.
- **Geprüft:** Backend-Tests 108 → 164 grün, Pint sauber. Frontend-Tests 47 → 92 grün, `build` und `check:tokens` grün. Gegenprobe: Ohne die Wiederholungs-Logik schlägt der Ablauf-Test der Lerneinheit fehl. CI auf `dev` nach jedem Merge grün (zuletzt Run `38808007645`). Patsche hat Login, Fächer und Lerneinheit im Browser durchgeklickt (Handy-Ansicht). Die 0 EP bei Wiederholung am selben Tag und das Verschwinden eines Themas „In Prüfung“ nur per Test geprüft, nicht im Browser.
- **Offen:**
  - Der Abstand in `.lh-xp__meta` steht als Scoped-CSS in `LearnView`, gehört aber ins Design-Artefakt (E-006).
  - Admins dürfen über `Gate::before` fremde Lerneinheiten beantworten und abschließen.
  - Eine Frage mit Antworten lässt sich nicht löschen (`RESTRICT`), Filament zeigt dann vermutlich 500.
  - Fragen sind reiner Text, kein Markdown und kein Code. Bei 403 zeigt die Lerneinheit nur den allgemeinen Fehlertext.
  - `user_stats.prestige_level` wird nicht mehr gelesen und kann weg.
  - Für M4: `SESSION_DOMAIN=.learnharder.app`, damit das XSRF-Cookie über Subdomains geht.
  - Weiter offen: M3b (Klassen und Sichtbarkeit), Hetzner (M4) und Backup-Bausteine, verschlüsselte Zweitkopie des Dumps, Vitest 5 und jsdom 30.

## 2026-10-16 — Offene Punkte aus M3 bereinigt

- **Branch:** je Punkt ein Branch, mit `--no-ff` nach `dev` gemerged und gelöscht: `chore/prestige-level-entfernen` (`9f20c48`), `fix/admin-fremde-lerneinheit` (`d4a4410`), `fix/cms-loeschen-mit-lernverlauf` (`30d6084`), `fix/app-gesperrte-lerneinheit` (`752a9a6`), `fix/app-zeilenumbrueche-in-fragen` (`d01cf32`), `style/xp-meta-abstand` (`26f011c`), `docs/m4-session-domain` (`454a254`). Dazu `93f5942` (Tab-Leiste im Report geklärt) direkt auf `dev`.
- **Ergebnis:**
  - Tab-Leiste: verdeckt nichts, lag am Ganzseiten-Screenshot (Patsche).
  - `user_stats.prestige_level` per neuer Migration entfernt (erste Migration nach den Ursprungs-Migrationen, lokal ohne `migrate:fresh`).
  - Admin darf fremde Lerneinheiten nicht mehr beantworten oder abschließen (`Gate::before` lässt `QuizAttempt` aus). Gesperrte Themen starten darf Admin weiter.
  - Löschen im CMS (Entscheidung Patsche: sperren mit Hinweis): Fach, Thema und Frage mit Lernverlauf verweigern `delete()` im Model (`BlocksDeletionWhileInUse`), Filament meldet „Nicht gelöscht“ mit dem Weg über Entwurf bzw. „Aktiv“. Ein Fach im Lehrplan oder in einer Lehrkraft-Zuordnung zählt auch. Mehrfach löschen überspringt diese Einträge.
  - Lerneinheit: Ein gesperrtes Thema zeigt „Noch gesperrt.“ mit Level und Weg zur Theorie statt eines allgemeinen Fehlers, auch bei 403 vom Server.
  - Fragen (Entscheidung Patsche: kein Markdown): Fragetext, Antworten und Erklärung behalten ihre Zeilenumbrüche. 6 Fragen mit mehrzeiligem Code standen vorher in einer Zeile.
  - Abstand in `.lh-xp__meta` jetzt im Design-Artefakt (Version 28, nur `bundle.css`) und in der Kopie im Repo, der Umweg in `LearnView` ist weg.
  - `infra/CLAUDE.md`: Cookie-Einstellungen für Produktion (`SESSION_DOMAIN=.learnharder.app` und Co.).
- **Geprüft:** Backend-Tests 164 → 169 grün, Pint sauber. Frontend-Tests 92 → 94 grün, `build` und `check:tokens` grün. Gegenproben: Ohne den Fix scheitern der Admin-Test, 4 Lösch-Tests und beide Gesperrt-Tests. Migration lokal hin, zurück und wieder hin. CI auf `dev` nach jedem Merge grün (zuletzt Run `38818453986`). Nichts davon im Browser angesehen: Lösch-Hinweis im CMS, „Noch gesperrt.“, Zeilenumbrüche und Abstand nur per Test.
- **Offen:**
  - Vor M4 entscheiden: Staging unter `learnharder.app` teilt sich die Cookies mit Produktion, am sichersten eine eigene Domain.
  - Zweimal mit Änderungen auf `dev` angefangen statt auf dem Branch. Vor dem Commit verschoben, `dev` blieb sauber.
  - Weiter offen: M3b (Klassen und Sichtbarkeit), Hetzner (M4) und Backup-Bausteine, verschlüsselte Zweitkopie des Dumps, Vitest 5 und jsdom 30.

## 2026-10-16 — Offene Punkte, Planung bis zum Start, M3b Klassen und Sichtbarkeit

- **Branch:** je Schritt ein Branch, mit `--no-ff` nach `dev` gemerged und gelöscht, von `chore/tokens-uebergang-entfernen` (`fbe0cde`) bis `feat/app-klasse` (`d866e68`), siehe `git log --first-parent 582ace5..d866e68`, dazu `docs/m3b-abschluss` mit diesem Eintrag
- **Ergebnis:**
  - Offene Punkte: alte Token-Namen aus `tokens.css` entfernt, Status-Feld des Themas nach Theorie-Änderung sofort aktuell, Filament-Texte in du-Form (`lang/vendor/filament-*`), Vitest 5.0.3 und jsdom 30.1.1 (Versionen mindestens zwei Wochen alt, npm-Fehler `edgesOut` umgangen), Ursache für `flushSession()` geklärt (Sanctums `AuthenticateSession` merkt sich den Passwort-Hash).
  - Entscheidungen: E-019 App und API unter einer Adresse je Umgebung, E-020 Tags nur `v2.0.0-alpha.N`, E-021 schlankes MVP, E-022 Hub schlank und Team/Liga ausgeblendet zum Start, E-023 Rechtstexte von Claude entworfen und von der Schule geprüft, E-024 Sichtbarkeit nach Klasse. Abstimmung mit Lehrkräften wird vor M5 nachgeholt. Meilensteine bis zum Start: M3b → M4a (Ankommen), parallel M4 → M5.
  - Browser-Tests der M3-Aufräumrunde: alle 8 passen (Patsche).
  - M3b: Models `SchoolClass` (mit `currentStage()`, Semester nach Wiener Zeit), `ClassEnrollment`, `TeachingAssignment`, Policies nur Admin. Filament `SchoolClassResource` mit „Schüler:innen“ und „Lehrkräfte“. `DemoSchoolSeeder` (Klasse 2AHIT, MEDT erst ab Jahrgang 3). API filtert über `Subject::visibleTo()`, `/me` liefert die eigene Klasse. App zeigt „Noch keiner Klasse zugeordnet.“ und die Klasse im Profil.
  - Fehler gefunden: Austragen direkt nach dem Eintragen schlug an `class_enrollments_left_check` fehl, weil `enrolled_at` von der Datenbank-Uhr kam. Jetzt setzt das Model die Zeit.
  - Gelernt: Filament prüft Auswahlfelder mit Relation schon gegen die gefilterte Liste (eigene `scopedExists` dafür überflüssig). Livewire liest `:` in erwarteten Fehlertexten als Regel-Trenner. `actingAs` behält geladene Beziehungen, echte Anfragen nicht.
- **Geprüft:** Backend-Tests 169 → 204 grün, Pint sauber. Frontend-Tests 94 → 97 grün, `build` und `check:tokens` grün. Gegenproben für Admin-Ausnahme, Löschschutz, Statusanzeige, aktive Einschreibung, Abschnitt-Filter und Start-Filter. CI auf `dev` nach jedem Merge grün. Demo-Seeder lokal zweimal gelaufen. **M3b noch nicht im Browser geprüft.**
- **Offen:**
  - Browser-Test M3b (Plan „Prüfen am Ende“ Punkt 3).
  - Für M4a: Antworten zu Plan Schule §7 (Mail-Domains, Microsoft 365, `users.name`, Einwilligung unter 14), Rechtstexte prüfen lassen.
  - Für M4: Staging-Adresse nach E-019, Mailversand aus der EU, Backups mit Restore-Test.
  - Patsche: `APP_NAME=learnharder` in der lokalen `.env`, verschlüsselte Zweitkopie des Dumps.

## 2026-10-16 — Browser-Test M3b, Fragen zu Plan Schule §7, M4a „Ankommen“

- **Branch:** je Schritt ein Branch, mit `--no-ff` nach `dev` gemerged, von `docs/m4a-entscheidungen` (`addfde5`) bis `feat/app-passwort-und-loeschen` (`a1f93f0`), danach `fix/schulmail-beispiel` (`84aa582`), `fix/elternformular-tabellenkopf` (`0d940ac`) und `fix/cms-konten-aktionen` (`f25145d`), siehe `git log --first-parent 310e883..f25145d`. Dazu `docs/m4a-abschluss` mit diesem Eintrag.
- **Ergebnis:**
  - Browser-Test M3b: alle 20 Fälle bestanden (Patsche).
  - Geklärt mit Patsche, festgehalten in E-025 bis E-028:
    - Schul-Mail `Nummer@htl-md.schule` gilt für alle, auch für Microsoft 365. Die Nummer hat nach Patsches Wissen 8 Ziffern (4 für das Jahr, 4 als Kennung). Das ist noch nicht von der Schule bestätigt, deshalb prüft die App nur die Domain.
    - Schüler:innen bekommen keinen Namen.
    - Ab 14 reicht ein Häkchen. Unter 14 braucht es das Elternformular, und der Admin legt das Konto an.
    - Mail-Versand über Scaleway TEM (Einrichtung in M4), lokal über Mailpit.
    - Konten löschen nur Schüler:innen selbst, sofort. Der Admin sperrt mit Grund. Keine Sperrliste.
    - Admins können sich nicht selbst sperren und sich nicht gegenseitig gleichzeitig herabstufen (Row-Lock auf alle Admin-Zeilen). Für den Notfall gibt es `learnharder:make-admin`.
    - Konten werden 12 Monate nach der letzten Anmeldung gelöscht, mit Hinweis 30 Tage vorher.
  - Backend:
    - Neue Spalten in `users`. `first_name` und `last_name` sind entfernt.
    - `App\Support\Accounts` mit `logoutEverywhere` (Sessions löschen, neues Remember-Token) und Middleware `EnsureAccountActive`.
    - Registrierung mit Mail-Code (`EmailCode`, Einmal-Token, Limits je E-Mail, IP und Tag), Passwort vergessen, `DELETE me`.
    - Rechtstexte als Markdown mit Fassung in `config/legal.php`.
    - Konten im CMS (`UserResource`). „Gamer-Tag zurücksetzen“ und „Löschen“ liegen im ⋮-Menü.
  - Design-Artefakt Version 29: Häkchen und Gefahr-Button, ins Repo kopiert.
  - App: Registrierung, Passwort vergessen, Rechtstexte, Konto löschen, Hinweis „gesperrt“ beim Login.
  - Fehler, die die Gegenproben gefunden haben:
    - `logout()` nach dem Löschen speichert das Remember-Token und legt das Konto neu an. Jetzt wird `forgetUser()` verwendet.
    - Die Regel „letzter Admin“ war unerreichbar und ist entfernt: Der Schutz folgt schon aus „aktiver Admin handelt“ plus „nicht an sich selbst“.
  - Browser-Test M4a: R1–R7, T1, P1–P2, K1–K7 und L1–L3 bestanden (Patsche). Danach behoben: Beispiel-Mail mit 8 Ziffern, leere Kopfzeile in den Tabellen des Elternformulars, abgeschnittene Aktionen in der CMS-Kontenliste.
- **Geprüft:**
  - Backend-Tests 204 → 262 grün, Pint sauber. Frontend-Tests 97 → 124 grün, `build` und `check:tokens` grün.
  - Gegenproben für jede neue Regel, darunter der Test mit zwei Datenbankverbindungen für gleichzeitige Admin-Änderungen.
  - CI auf `dev` nach jedem Merge grün, zuletzt Lauf `38923263076`.
  - P1/P2 (andere Sitzung nach neuem Passwort abgemeldet) im Browser nur grob angesehen, der Test deckt es ab.
- **Offen:**
  - Rechtstexte: Platzhalter `[[…]]` mit der Schule füllen, Prüfung durch die Schule (E-023).
  - Vor M5: Löschlauf nach 12 Monaten und Hinweis-Mail per Scheduler (E-028), erneute Zustimmung bei neuer `terms_version`.
  - Später: Personal anonymisieren, 2FA fürs CMS, EP zurücksetzen, Änderungsverlauf für Inhalte.
  - M4: Scaleway TEM einrichten, Mails über die Queue, Logs täglich rotieren (`LOG_STACK=daily`, 14 Tage).
  - `main.js`: Die Weiterleitung nach einer Sperre hat keinen eigenen Unit-Test (der Browser-Test K2 deckt sie ab).
  - Design-Artefakt:
    - In der README fehlen Häkchen und Gefahr-Button in der Komponententabelle, ebenso in der Kopie `docs/design/README.md`.
    - `lastChange` im Index ist nicht aktualisiert.
  - Lokal:
    - `DemoSchoolSeeder` gibt MEDT keine Themen.
    - Das Icon im Team-Tab wird eventuell verzögert aktiv.
    - Der Demo-Schüler heißt bis `migrate:fresh --seed` noch „Lukas Lerner“.
    - `backend/.env.bak` löschen.
  - Patsche: verschlüsselte Zweitkopie des Dumps.

## 2026-10-16 — Testwerkzeuge, getrennte Logs, Vorbereitung M4

- **Branch:** `feat/dev-dashboard` (`b5d3f00`), `feat/dev-web` (`3faf1fc`), `chore/logs-trennen` (`a754df3`), `feat/testzentrale-logs` (`d9aea5e`), je mit `--no-ff` nach `dev`. Dazu `docs/session-testwerkzeuge` mit diesem Eintrag.
- **Ergebnis:**
  - Mailpit lokal eingerichtet (`.env` von Patsche angepasst). Mails landen nur in Mailpit, echte Zustellung erst mit Scaleway TEM in M4.
  - `scripts/dev.py`: Dashboard im Terminal (curses) mit Diensten, CPU/RAM, `/up`, letztem Mail-Code, Log-Warnungen und Tasten für Start, Stop, Tests, `config:clear`, Neu aufsetzen und Mailpit leeren. Dazu `scripts/dev.py start|stop`.
  - `scripts/dev.py web`: Testzentrale im Browser (http://localhost:8090, `dev_web.py` und `dev_web.html`). Sie bietet Grafiken, Mails im Volltext mit Code zum Kopieren und einen Testverlauf (die letzten 50 Läufe in `~/.cache/learnharder-dev/`). Logs sind getrennt nach App, Anfragen, Datenbank, Tests und Vite. Nur Standardbibliothek, Design-Tokens und Schriften der App, kein CDN. Sie hört nur auf `127.0.0.1` und prüft Host und Origin, Aktionen brauchen den Header `X-LH-Dev`.
  - Backend: Tests loggen nach `storage/logs/testing.log`. Postgres schreibt Benutzer@Datenbank vor jede Zeile und meldet Abfragen ab 250 ms (`backend/compose.yaml`). So sind die gewollten Fehler aus Tests von echten Fehlern zu trennen.
  - Gelernt:
    - Die Dauer im Log von `php artisan serve` ist falsch: gemessen 50–120 ms, im Log 0,1 ms oder 500 ms. Die Testzentrale zeigt bei Anfragen deshalb keine Dauer.
    - Läuft Patsches Testzentrale schon auf 8090, braucht ein Probe-Server einen anderen Port.
  - M4 vorbereitet: Patsche hat noch kein Hetzner-Konto. Wer Vertragspartner und datenschutzrechtlich verantwortlich ist, klärt Patsche mit einer Lehrkraft (Mail- und Teams-Entwurf erstellt). Bis dahin ist ein Testserver nur mit Testkonten und Passwortschutz vorgesehen.
- **Geprüft:**
  - Backend-Tests 262 → 263 grün, Pint sauber. Frontend unverändert bei 124. Neue Python-Tests: 21 grün (`python3 -m unittest discover -s scripts`).
  - Gegenproben: Code-Erkennung ohne Ziffern-Grenzen, Host-Prüfung, Sperre gegen doppelte Aktionen, Datenbankfilter, Test-Log ohne `LOG_STACK`.
  - Probeläufe gegen die echten Dienste mit Screenshots über Chrome ohne Fenster. Patsche hat Terminal-Dashboard und Testzentrale im Browser benutzt.
  - CI auf `dev` nach jedem Merge grün, zuletzt Lauf `38950666557`.
- **Offen:**
  - M4: Antwort der Lehrkraft abwarten, dann Hetzner-Konto (Zwei-Faktor-Anmeldung, Projekt „learnharder“, noch kein Server) und Planung von M4: Server, DNS, Backup-Ziel, Deploy-Weg, Staging, Scaleway.
  - Widerspruch: Der Projektplan nennt für Lehrkräfte „Klarnamensicht“, E-025 speichert für Schüler:innen keine Namen. Vor dem Lehrkraft-Dashboard klären.
  - Die Python-Tests laufen nicht in der CI (nur lokales Werkzeug).
  - In der Testzentrale nur teilweise im Browser geklickt: Details aufklappen, Suche und „Ältere ausblenden“ im Log.
  - Weiter offen aus dem Eintrag davor: Rechtstexte mit der Schule, Löschlauf per Scheduler, erneute Zustimmung, Design-Artefakt-README.

## 2026-10-17 — Aufgabenliste, Projekt-Tab, Handbücher

- **Branch:** je Schritt ein Branch, mit `--no-ff` nach `dev` gemerged und gepusht: `docs/aufgaben` (`b6a3657`), `feat/aufgaben-parser` (`2267660`), `feat/projekt-tab` (`3a732eb`), `docs/aufgaben-regel` (`8b04546`), `docs/doku-werkzeuge` (`42f229e`), `docs/cms-handbuch` (`47f1c65`), `docs/betriebshandbuch` (`5b040be`). Dazu `docs/session-projekt` mit diesem Eintrag.
- **Ergebnis:**
  - `docs/AUFGABEN.md`: alle offenen Punkte an einer Stelle, mit `@claude`, `@patsche`, `@schule`, `seit:` und `!fokus`. Gesammelt aus allen Session-Reports, `plans/README.md` und Plan Schule §7, gegen den Code geprüft.
  - Testzentrale, Tab „Projekt“: Fokus, Stand (Branch, ungepushte Commits auf `dev`, CI von `dev` höchstens einmal pro Minute, nicht gemergte Branches, letzte Session), Meilensteine aus `plans/README.md`, drei Spalten nach Zuständigkeit, Formfehler der Aufgabenliste.
  - Session-Ablauf (`CLAUDE.md`, `agent/CLAUDE.md`, Skill `doku-pflegen`): Aufgabenliste am Anfang lesen und am Ende abgleichen. Offene Punkte stehen nicht mehr im Report.
  - E-029: API-Referenz mit Scramble, VitePress erst mit Publikum, kein DocFX. Neue Gruppe „Übergabe“ in der Aufgabenliste.
  - `docs/handbuch/redaktion.md` (Inhalte pflegen, Prüfen und Veröffentlichen, Löschen) und `docs/handbuch/betrieb.md` (Konten, Registrierung, Schulstruktur, Sichtbarkeit, Notfall-Befehl).
  - Gelernt: Das Feld „Icon“ im Fach-Formular nutzt die App nicht, Bild und Farbe hängen am URL-Kürzel. Admins können Schüler:innen-Konten löschen (⋮), nicht nur diese selbst. „Bis Abschnitt“ im Lehrplan blendet nichts aus.
- **Geprüft:**
  - Python-Tests 20 → 29 grün (`python3 -m unittest discover -s scripts`), Gegenproben für die Tag-Erkennung und den Fokus auf erledigten Aufgaben. Backend und Frontend unverändert, nicht neu gelaufen.
  - Projekt-Tab mit Probe-Server auf Port 8091 und Chrome ohne Fenster bei 1400 und 390 px Breite angesehen.
  - Handbücher Satz für Satz gegen Policies, Formulare, Models und API geprüft. Nicht im laufenden CMS angesehen (Docker war aus).
  - CI auf `dev` grün für `3a732eb` (Lauf `39008683410`). Die späteren Doku-Merges nicht abgewartet.
- **Offen:** neu in `docs/AUFGABEN.md`: Gruppe „Übergabe“ (Scramble, Architektur-Überblick, Docblocks, Larastan, ESLint, README-Einstieg), Icon-Feld im Fach-Formular, Testzentrale bei Handy-Breite, Handbücher im laufenden CMS gegenlesen.

## 2026-10-17 — Projekt-Tab aufgeräumt, Spielwelt geplant

- **Branch:** je Schritt ein Branch, mit `--no-ff` nach `dev` gemerged und gepusht: `chore/projekt-tab` (`25c24fd`), `docs/spielwelt` (`4249a4d`). Dazu `docs/session-spielwelt` mit diesem Eintrag.
- **Commits:** `32ae50a` (Projekt-Tab mit Jetzt, Wartet auf und zugeklappten Abschnitten), `6a2179b` (Konzept Spielwelt, E-030, Tracks G1–G6 und L1).
- **Ergebnis:**
  - Testzentrale, Tab „Projekt“: Stand in einer Zeile, fertige Meilensteine in einem Kästchen, „Wartet auf die Schule“ als Streifen, „Jetzt“ zeigt nur die Gruppe mit `!fokus` (sonst den ersten offenen Meilenstein), der Rest zugeklappt mit Anzahl. Zeilennummern nur noch beim Darüberfahren. `parse_milestones` kennt Tracks (`G1`, `L1`) und pausierte Meilensteine (`⏸`) und liefert `status` statt `done`. Der Umschalter „Bis zum Start / Alles“ ist weg.
  - `docs/plans/KONZEPT_SPIELWELT.md`: Hub und Profil, Brainpower und Streaks, Erfolge (getrennt von Trophäen), Avatar aus SVG-Ebenen ohne fremde Laufzeit, Shop (freischalten, dann kaufen), Rangliste, Wow-Momente, Landing Page. Geparkt: Forschungsbaum, Minispiel, native App.
  - E-030: Die Spielwelt entsteht lokal, solange M4 auf die Schule wartet. M5 wartet nicht auf sie. O-006 (Grundlage der Rangliste) offen, Empfehlung EP pro Saison in der Klasse, nicht Brainpower.
  - `plans/README.md`: M4 pausiert, Tracks G1–G6 und L1 in der Meilenstein-Tabelle, Umfang als größtes Risiko. `AUFGABEN.md` nach Tracks gegliedert, „Hub schlank“ von M5 nach G1, `!fokus` auf dem Hub.
  - Gelernt: Chrome ohne Fenster unter Windows hat eine Mindestbreite von etwa 500 px. Für die Handy-Ansicht die Seite in einem 375 px breiten `iframe` aufnehmen.
- **Geprüft:**
  - Python-Tests 29 grün (`python3 -m unittest discover -s scripts`), der Meilenstein-Test deckt `⏸` und Tracks ab. Backend und Frontend unverändert, nicht neu gelaufen.
  - Projekt-Tab mit Probe-Server auf Port 8091 und Chrome ohne Fenster bei 1400 px und 375 px (im `iframe`), nur dunkel. Vor und nach dem neuen `AUFGABEN.md`. Auf- und Zuklappen nicht im Browser geklickt.
  - CI auf `dev` grün für `25c24fd` (Lauf `39011829130`). Den Lauf für `4249a4d` nicht abgewartet.
- **Offen:** neu in `docs/AUFGABEN.md`: Gruppen G1–G6 und L1 mit ersten Aufgaben (Hub, Tab-Leiste, Hub-Zustände, Profil, Brainpower, Glossar „Erfolge“, Erfolge, Avatar-Stil und Avatar, Shop-Preise und Shop, O-006, Landing Page).

## 2026-10-17 — Hub (G1)

- **Branch:** `feat/hub`, mit `--no-ff` nach `dev` gemerged (`3494abd`). Dazu `docs/session-hub` mit diesem Eintrag. Nicht gepusht.
- **Commits:** `b4483ff` (Hub-Endpunkt liefert das nächste offene Thema), `a942f21` (Hub mit Begrüßung, Level, Brainpower und Weiterlernen).
- **Ergebnis:**
  - `GET /api/v1/subjects` reicht fürs nächste Thema nicht: Es liefert je Fach nur Zahlen, kein Thema. Neu ist deshalb `GET /api/v1/hub` mit `next_topic`. Zuerst kommt das Fach der zuletzt abgeschlossenen Lerneinheit, danach die übrigen sichtbaren Fächer nach Reihenfolge. Es zählen nur veröffentlichte Themen mit Fragen, die nicht gesperrt und noch nicht abgeschlossen sind. Ist alles erledigt, kommt `null`. Die Antwort läuft über `TopicResource`, die dafür `subject` per `whenLoaded` bekommt.
  - `HubView` auf `/`: Begrüßung nach Tageszeit (Morgen, Hey, Abend, „Noch wach?“), Level, Rang, EP-Leiste und Brainpower aus `auth.me`, „Weiterlernen“ als hervorgehobene Karte. Ist alles erledigt, schlägt sie Wiederholen vor. Ohne Klasse kommt ein Hinweis statt einer Abfrage. Platzhalter: Avatar neben der Begrüßung und das Raster „Bald“ (Streak, Herausforderung, Spielhalle, Liga-Platz, Shop, Erfolge).
  - Begriffe nach dem Glossar: „Herausforderung“ statt „Challenge“, „Liga-Platz“ statt „Ranglistenplatz“.
- **Geprüft:**
  - Backend: 271 Tests grün (8 neu in `HubApiTest`), `pint --test` sauber.
  - Frontend: 135 Tests grün (11 neu in `HubView.test.js`), `build` und `check:tokens` grün.
  - Patsche hat den Hub im Browser angesehen. CI nicht abgewartet, weil nicht gepusht.
- **Offen:** neu in `docs/AUFGABEN.md`: nach dem Login zum Hub statt zu `/learn`, Icons für Spielhalle und Shop. Kein `!fokus` gesetzt.

## 2026-10-17 — Avatar-Konzept (G4)

- **Branch:** `dev`. Nicht gepusht.
- **Commits:** `7722065` (Avatar-Konzept mit Roboter, Slots und Stimmungen), dazu ein Commit mit Klassen-Teil, Aufgaben und diesem Eintrag.
- **Ergebnis:**
  - Reine Planung, kein Code. Der Avatar ist ein Roboter aus einem gemeinsamen Skelett mit acht Slots (Kopf, Aufsatz, Rumpf, Arme, Antrieb, Werkzeug, Abzeichen, Lackierung), Starter-Kit je Abteilung und einem Werkzeug je Fach in Stufen (Fach-Level 3, 10, 25, 100). Display-Gesicht, Akku (Brainpower) und Animationen zeichnet die Engine. Claude zeichnet zuerst Platzhalter (E-031).
  - Kein Zufall bei Avatar-Teilen, keine Lootboxen (E-032). Stimmungen nie negativ, andere sehen nur eine Pose (E-033).
  - Klasse: Werkhalle, Klassenprojekt mit Klassenfoto, Meilensteine nur nach Opt-in, Reaktionen nur gezählt (E-034). Alles in `plans/KONZEPT_SPIELWELT.md` §7.
- **Geprüft:** Nur Doku. Abteilung über Klasse → Ausbildungsgang → Abteilung im Schema nachgesehen. Keine Tests gelaufen.
- **Offen:** neu in `docs/AUFGABEN.md` unter G4: wer die endgültigen Teile zeichnet, Werkzeug je Fach und Starter-Kit je Abteilung, Avatar-Engine mit Platzhaltern, Werkhalle.

## 2026-10-17 — Avatar-Stil und Avatar-Engine (G4)

- **Branch:** `dev` für die Doku, `feat/avatar` für die Engine, danach mit `--no-ff` nach `dev`. Nicht gepusht.
- **Commits:** `8ef68e2` (Avatar-Stil, Leistung und Schutz vor Mobbing), dazu die Engine und dieser Eintrag.
- **Ergebnis:**
  - Stil: Werkstatt-Roboter mit LED-Punktmatrix, warme Farben, Akzent = Abteilung. Referenz `docs/plans/avatar-referenz-v1.webp` (KI-Entwurf, austauschbar). Akku für andere unsichtbar, Grundausstattung ohne Anfänger-Stigma (Präzisierungen zu E-031 und E-034).
  - Engine `frontend/src/avatar/`: `RobotAvatar.vue` legt SVG-Teile auf einer Fläche (200 × 240) übereinander, Arme gespiegelt, Werkzeug an der rechten Hand. Display mit 6 Gesichtern, Akku mit Blitz, Posen, `wave`, `celebrate`, `think`, Atmen, Blinzeln, Antenne. Nur `transform` und `opacity`, pausiert außerhalb des Bildes, still bei `prefers-reduced-motion`. `pickMood` wählt die Stimmung, Müde geht vor Zufrieden (Tabelle im Konzept angepasst).
  - Platzhalter-Teile, Vorlage und Format-Regeln in `frontend/src/assets/avatar/`. Ein Test prüft jede Teil-Datei (Fläche, Klassen, keine Farben, unter 3 KB).
  - Hub: Roboter neben der Begrüßung (96 px statt 72, sonst ist das Display nicht lesbar). Profil: Roboter mit Slot-Liste, Pose und Lackierung als Vorschau ohne Speichern.
- **Geprüft:** Frontend 153 Tests grün (18 neu), `build` und `check:tokens` grün. Varianten als statisches SVG gerendert und mit Chrome ohne Fenster angesehen. In der laufenden App nicht angesehen.
- **Offen:** neu in `docs/AUFGABEN.md` unter G4: Starter-Kit je Abteilung, Avatar speichern, Stimmungs-Fakten vom Server, Antippen, Porträt und `celebrate` auf dem Ergebnis.

## 2026-10-17 — G1 fertig: Hub-Zustände, Login zum Hub, Profil

- **Branch:** `feat/hub-g1`. Nicht gepusht, noch nicht nach `dev` gemergt.
- **Commits:** `1b53982` (E-035 Tab-Leiste), `77ce685` (Login zum Hub), `5277d25` (`last_study_at` in `/hub`), `c1c873e` (Hub-Zustände), `e503174` (Profil), `8f80e3a` (Raster „Bald“ entfernt), dazu dieser Doku-Commit.
- **Ergebnis:**
  - E-035: Die Tab-Leiste behält fünf Tabs wie im Design-Artefakt. Team und Liga werden Startbedingung statt ausgeblendet (ersetzt diesen Teil von E-022).
  - Nach Login und Registrierung geht es zum Hub, `meta.guest` leitet auch dorthin.
  - `GET /hub` liefert `last_study_at`. Der Hub zeigt Aufstieg, Pause ab 3 Tagen, heute gelernt und ersten Besuch, die Stimmung des Roboters passt dazu. Den Aufstieg merkt sich die App aus der Lerneinheit, der Server speichert nichts (weniger Daten über Minderjährige, kein neuer Endpunkt).
  - Profil: Level, Rang, Prestige, EP gesamt, Fach-Level. Der längste Streak kommt mit G2.
  - Raster „Bald“ aus dem Hub entfernt, Funktionen erscheinen, sobald es sie gibt.
  - `.git` war durch einen abgebrochenen Schreibvorgang beschädigt (8 leere Objekte, Branch auf leerem Commit). Repariert, `fsck` sauber, Sicherung im Scratchpad der Session.
- **Geprüft:** Backend 273 Tests grün, `pint --test` sauber. Frontend 163 Tests grün, `build` und `check:tokens` grün. In der laufenden App nicht angesehen.
- **Offen:** neu in `docs/AUFGABEN.md`: Hub und Profil in der App ansehen (Patsche), längster Streak im Profil (G2). Die Zuordnung Rang → Name steht dreimal im Code (`LhLevelBadge`, Hub, Profil), nicht als Aufgabe eingetragen.

## 2026-10-17 — G2: Streak, Brainpower, EP-Booster und Shop

- **Branch:** `feat/g2-brainpower`, danach mit `--no-ff` nach `dev`. Nicht gepusht.
- **Commits:** `a1dfe92` (Streak), `015754f` (längster Streak im Profil), `108c5c6` (Brainpower mit Maximum und Aufladen, E-036), `4df8ec7` (Booster im Backend), `cf0ea9d` (Shop-Design aus dem Artefakt, E-037), `8448a61` (Shop-Seite), `e82ba83` (Hub führt in den Shop), `50e947c` (Booster-Anteil in der Lerneinheit), dazu dieser Doku-Commit.
- **Ergebnis:**
  - Streak: eine abgeschlossene Lerneinheit am Kalendertag in Wien zählt, ein ausgelassener Tag setzt auf 0. `users.current_streak` entfällt, der Streak steht nur in `user_stats`.
  - Brainpower (E-036): Maximum aus dem Level, ab Prestige 1 fest 200. Aufladen einmal je Tag in Wien beim ersten Lesen, ohne Scheduler. Werte in `config/gamification.php`.
  - EP-Booster: Kauf über `POST /boosters` unter Sperre, nur der laufende Booster steht am Konto, jede Antwort merkt sich ihren Faktor. Es zählt der Booster beim Antworten, die Tagesregel (E-015) bleibt.
  - Shop (E-037): erst im Design-Artefakt (Version 32: Komponente `Shop`, Marken, `icon-booster`, Glossar), dann Kopie ins Repo und Vue-Seite `/shop`. Hub mit Shop-Zeile, Chip als Link, Marke auf „Weiterlernen“. In der Lerneinheit nur „2×“, im Ergebnis der Anteil.
  - Datenschutzerklärung (Entwurf) um Streak, Brainpower und Booster ergänzt.
- **Geprüft:** Backend 312 Tests grün, `pint --test` sauber. Frontend 179 Tests grün, `build` und `check:tokens` grün. Shop-Design als Vorschau in hell und dunkel mit Chrome gerendert, Kontraste nachgerechnet. In der laufenden App nicht angesehen. Die App wurde einmal mitten in der Arbeit beendet, danach war `.git` heil (`fsck`), Sail musste neu gestartet werden.
- **Offen:** neu in `docs/AUFGABEN.md` unter G2: ansehen (Patsche), Hinweis „Heute bringen deine Themen keine EP mehr“, Streak im Hub, Herausforderung, Jubel beim Kauf.
