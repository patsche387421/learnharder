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
