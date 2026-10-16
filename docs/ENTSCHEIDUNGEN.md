# Entscheidungslog

Jede Entscheidung: Nummer, Datum, was, warum, Folgen. Offene Fragen stehen unten.

## Entschieden

### E-001 — Hosting auf Hetzner statt World4You (2026-10-14)
- **Entscheidung:** learnharder.app läuft auf einem Hetzner-Cloud-VPS in der EU.
- **Warum:** Laravel braucht Queue-Worker, Scheduler, Redis und Docker. Das geht auf Shared Hosting (World4You) nicht sauber.
- **Folgen:** Die World4You-Pläne (`PHASE_1–5`, `PHP_TRANSFORMATION_PLAN*`) sind gelöscht (erreichbar über Tag `v1.1.0-w4y-plans-and-review`). Server-Betrieb (Updates, Backups, Firewall) liegt bei uns → `infra/`.

### E-002 — Laravel + Filament + Vue 3 statt Plain-PHP/Vanilla-JS (2026-10-11)
- **Entscheidung:** Backend Laravel mit Filament-CMS, Frontend Vue 3 + Vite als PWA.
- **Warum:** Auth, Rollen, Mehrsprachigkeit und ein CMS gibt es fertig. Spiellogik läuft serverseitig (Cheat-Schutz).
- **Folgen:** Kompletter Neubau. Der V1-Prototyp wird nicht weiterentwickelt.

### E-003 — Neues Design-System (2026-10-14)
- **Entscheidung:** `frontend/src/styles/{fonts,tokens,bundle}.css` sind die Design-Wahrheit (warm-neutral, Atkinson Hyperlegible Next + Space Mono, `lh-`-Komponenten, hell als Standard).
- **Warum:** Lesbarkeit, Barrierefreiheit, ein einheitliches System für alle V2-Oberflächen.
- **Folgen:** Das lila Space-Grotesk-System aus V1 ist abgelöst.
- **Präzisierung:** Seit E-006 ist das Design-System-Artefakt die Quelle. Die Dateien hier sind die Kopie.

### E-004 — Branch-Regel (2026-10-14)
- **Entscheidung:** Arbeit auf `dev`. Große Features auf `feat/<name>`, danach Merge nach `dev`.

### E-005 — Lokale Entwicklung in WSL2, `main` als Worktree (2026-10-14)
- **Entscheidung:** Das Repo liegt in Ubuntu-WSL2 (`~/learnharder.app`), `main` als Git-Worktree daneben (`~/learnharder.app-main`).
- **Warum:** Unter `C:\` laufen Requests im Sail-Container ~20 s, `tempnam()` schlägt fehl, Port 8000 ist von Windows gesperrt und das Sail-Skript läuft nicht in Git Bash.
- **Folgen:** Einrichtung siehe `README.md`. Der alte Checkout `C:\v\learnharder.app` bleibt bis zur Nutzer-Migration liegen (Root-`.env`, Backup-Zips), wird aber nicht mehr entwickelt.

### E-006 — Design-System-Artefakt ist die Quelle, das Repo hält eine Kopie (2026-10-14)
- **Entscheidung:** Das Design-System-Artefakt (https://claude.ai/artifact/89Tg8wEdFdFDMUNBWG2YNb) ist die Quelle. Das Repo hält eine Kopie: `frontend/src/styles/tokens.json`, `bundle.css`, `docs/design/` und die Icons. Designänderungen passieren zuerst im Artefakt. Danach wird die Kopie ersetzt und `npm --prefix frontend run check:tokens` läuft.
- **Warum:** Ein Ort für Designänderungen, damit Repo und Design nicht auseinanderlaufen. Das Google-Fonts-`@import` des Artefakts würde beim Laden externe Server kontaktieren. Das verbietet die Datenschutz-Regel in `CLAUDE.md` (keine externen CDNs oder Fonts).
- **Folgen:** Das `@import` wird nie übernommen. Schriften liegen selbst gehostet unter `frontend/public/assets/fonts/`. Stand 2026-10-14 entspricht die Kopie dem Artefakt (Version 25) bis auf dieses `@import`. Geänderte Token-Namen werden in den Komponenten nachgezogen. Regeln: `frontend/src/styles/CLAUDE.md`.

### E-007 — Datenbank: PostgreSQL (2026-10-15)
- **Entscheidung:** learnharder.app nutzt PostgreSQL, lokal und in Produktion. MariaDB fällt weg.
- **Warum:** Sail und `backend/.env.example` laufen schon mit Postgres 18, und die Nutzerdaten in Supabase liegen ebenfalls in Postgres. Der Umzug bleibt so ein Postgres-zu-Postgres-Import ohne Typumwandlung.
- **Folgen:** Der Prod-Stack in `infra/` bekommt Postgres. Die Pläne nennen noch „PostgreSQL / MariaDB“. Es gilt dieser Eintrag. Schließt O-001.

### E-008 — Filament 5 statt Filament 3 (2026-10-15)
- **Entscheidung:** Das CMS läuft auf Filament 5 mit Livewire 4. Upgrade von 3.3 über 4 auf 5.10 vor dem CMS-Ausbau in M2.
- **Warum:** Filament 3 bekommt seit 2026-08-01 keine Bugfixes mehr (Sicherheitsfixes bis 2028-01-01), Filament 4 nur bis 2027-01-15. Filament 5 ist Filament 4 mit Livewire 4 und hat den längsten Support. Betroffen waren nur 510 generierte Zeilen ohne Plugins, später wäre der Wechsel teurer geworden.
- **Folgen:** Filament-Code nutzt `Filament\Schemas\Schema` und `Filament\Actions\*`. Ein eigenes Panel-Theme braucht Tailwind 4. Für de/en kommt in M2 der Community-Fork `lara-zeus/spatie-translatable` (v2 für Filament 5) in Frage, ein offizielles Plugin für v5 war nicht zu finden. Fehlt eine Policy-Methode, erlaubt Filament die Aktion weiterhin (abgesichert in `tests/Feature/Filament`). Schließt O-002.
- **Präzisierung:** Seit E-012 kommt kein Translatable-Plugin, das CMS pflegt zunächst nur Deutsch.

### E-009 — Supabase-Daten gesichert (2026-10-15)
- **Entscheidung:** Die V1-Daten (`public` und `auth`) sind per `pg_dump` aus Supabase gesichert. Der Dump liegt außerhalb des Repos. Eine zweite Kopie gehört verschlüsselt offline (z. B. USB-Stick).
- **Warum:** Die Prod-Daten lagen nur in Supabase, ein Free-Projekt ohne Traffic kann pausiert werden.
- **Folgen:** Dumps kommen nie ins Repo, in keine Cloud und in keinen Chat (Daten von Minderjährigen). Der Import nach V2 bekommt einen eigenen Branch. Verbindung nur über den Session-Pooler (Port 5432), den manche Netze sperren. Schließt O-004.
- **Präzisierung:** Seit E-011 gibt es keinen Import. Der Dump ist nur noch Notfall-Archiv.

### E-010 — Level-Kurve, Prestige und XP-Booster (2026-10-15)
- **Entscheidung:** Level n beginnt bei `round((n−1)^1.5 × 8)` XP im Zyklus (Level 2 bei 8, Level 100 bei 7.880). Ein Prestige-Zyklus hat immer 8.000 XP, der Prestige-Rang ist `floor(Gesamt-XP / 8000)`. XP-Booster für Brainpower: 1,5× 30 Min (20 BP), 2× 30 Min (40 BP), 2× 60 Min (70 BP).
- **Warum:** Der erste Level-Aufstieg kommt nach 4 richtigen Antworten, Level 100 ist ein echtes Band vor dem Prestige. Gleiche XP pro Zyklus bestrafen fleißige Lernende nicht, und der Prestige-Rang ist ohne eigenen Zähler aus den Gesamt-XP berechenbar. V2 startet ohne alte Fortschritte, deshalb zählte V1-Kompatibilität nicht.
- **Folgen:** Tabelle in `GAMIFICATION_OEKONOMIE_MODELL.md` §2 korrigiert, Widerspruch 4 erledigt. Booster: nur serverseitig, höchstens einer gleichzeitig, kein Kauf während einer läuft, Zeit läuft in Echtzeit, Multiplikator nur auf richtige Antworten. Mit 158 Fragen (316 XP pro Durchlauf) braucht ein Zyklus viele Wiederholungen. Mehr Inhalte oder weniger XP für wiederholte Antworten klären vor M3.

### E-011 — V2 startet ohne V1-Nutzer (2026-10-15)
- **Entscheidung:** Aus V1 werden weder Accounts noch Fortschritte übernommen. Alle registrieren sich in V2 neu.
- **Warum:** V2 startet ohne alte Fortschritte (E-010). Eine neue Registrierung holt die Einwilligung unter den V2-Bedingungen ein, wichtig bei Minderjährigen. Ohne Import-Command wird M5 deutlich kleiner, und die V1-Test-Accounts kommen nicht mit.
- **Folgen:** Kein Branch `feat/supabase-import`. §2 im Setup-Plan (Datenmigration) entfällt. Die Wartungsseite kündigt die Neuregistrierung an. Nach dem V2-Start wird das V1-Supabase-Projekt abgeschaltet und der Dump (E-009) 3 Monate später gelöscht. Danach kann auch `C:\v\learnharder.app` weg.

### E-012 — CMS zunächst nur auf Deutsch (2026-10-15)
- **Entscheidung:** Das CMS pflegt bis nach M5 nur Deutsch. Formularfelder schreiben direkt in den deutschen Eintrag der JSON-Spalte (z. B. `title.de`). Kein Translatable-Plugin.
- **Warum:** Alle Inhalte gibt es nur auf Deutsch, Englisch steht erst nach M5 an. Das einzige Plugin für Filament 5 (`lara-zeus/spatie-translatable` 2.0.1) ist der offizielle Nachfolger, wird aber von einer Person gepflegt und hat offene Fehler beim Sprachwechsel (Issues #42, #51, #63). Eigene Sprach-Tabs ohne Umschalter für das ganze Formular waren keine saubere Lösung.
- **Folgen:** Die JSON-Spalten `{"de": …}` bleiben, Englisch braucht später keine Migration. Wenn Englisch kommt, wird der Sprachumschalter neu entschieden (Plugin oder selbst gebaut, beide Sprachen im Formular-State). M2 heißt jetzt „Deutsch“ statt „de/en“.

### E-013 — Datenmodell für Schule, Klassen und Registrierung (2026-10-15)
- **Entscheidung:** Schule → Abteilung → Ausbildungsgang → Klasse pro Schuljahr. Der Lehrplan verbindet Fach und Ausbildungsgang, `teaching_assignments` regelt, wer in welcher Klasse welches Fach unterrichtet. Schüler:innen registrieren sich nur mit Schul-Mail und Bestätigungscode und sehen Inhalte erst nach der Zuordnung zu einer Klasse. Lehrkräfte legt der Admin an. Plan: `plans/DATENMODELL_SCHULE.md`.
- **Warum:** Die HTL Mödling hat 12 Abteilungen und Ausbildungsformen mit unterschiedlicher Dauer (5 Jahre bis 4 Semester) und eigenen Lehrplänen. Ein Fach kann in einer Klasse von mehreren Lehrkräften unterrichtet werden. Die Szenarien Klassenwechsel, DSGVO-Löschung und Sommerferien haben das erste Modell gebrochen und sind eingearbeitet.
- **Folgen:** `classes` wird zu `school_classes`, `schools.domain` zu `school_email_domains`. Die Kaskade über `classes.teacher_id` entfällt. Löschregeln richten sich nach der Richtung (Personendaten `CASCADE`, Inhalte und Struktur `RESTRICT`). Offene Punkte stehen im Plan §7.
- **Präzisierung:** Die bestehenden Migrationen wurden geändert, statt neue anzulegen, weil noch keine auf einem Server lief.

### E-014 — Veröffentlichen nur durch Admins, Autor-Änderungen gehen zurück in Prüfung (2026-10-15)
- **Entscheidung:** Themen und Fragen veröffentlichen nur Admins. Speichert jemand ohne dieses Recht einen veröffentlichten Inhalt oder ändert die Theorie eines veröffentlichten Themas, geht der Inhalt zurück auf „In Prüfung“. Neue Inhalte starten als Entwurf.
- **Warum:** Die Inhalte lernen Minderjährige. Eine falsche Antwort soll nicht ungeprüft live gehen, auch nicht durch eine kleine Korrektur.
- **Folgen:** Der Inhalt verschwindet bis zur erneuten Freigabe aus der App. Admins geben mit der Mehrfachaktion „Veröffentlichen“ gesammelt frei. Die App (M3) zeigt nur `published`.

### E-015 — EP pro Frage höchstens einmal pro Tag (2026-10-15)
- **Entscheidung:** Eine richtige Antwort bringt die EP der Frage nur, wenn die Person für diese Frage am selben Kalendertag noch keine EP bekommen hat. Danach gibt es für diese Frage bis Mitternacht 0 EP, üben geht trotzdem weiter. Der Tag gilt in `Europe/Vienna`.
- **Warum:** Mit 158 Fragen (316 EP pro Durchlauf) und 8.000 EP pro Zyklus würde sich Durchklicken auswendig gelernter Fragen lohnen. Einmal pro Tag belohnt tägliches Wiederholen und macht Farming im selben Thema sinnlos. Wien statt UTC, damit „heute“ für Schüler:innen um Mitternacht endet und nicht um 1 oder 2 Uhr.
- **Folgen:** Der Server merkt sich pro Antwort die vergebenen EP und den Tag. Verbindlich vergibt erst der Abschluss der Lerneinheit die EP. Schließt den offenen Punkt aus E-010.

### E-016 — Rang-Grenzen (2026-10-15)
- **Entscheidung:** Bronze ab Level 1, Silber ab 25, Gold ab 50, Platin ab 75. Nach einem Prestige beginnt der Rang wieder bei Bronze.
- **Warum:** Gleichmäßige Viertel sind leicht zu merken. Silber kommt nach 941 EP, laut Ökonomie-Modell in Woche 3–4.
- **Folgen:** Der Server liefert den Rang, die App färbt nur. Tabelle in `GAMIFICATION_OEKONOMIE_MODELL.md` §2.3.

### E-017 — M3 ohne Klassenfilter (2026-10-15)
- **Entscheidung:** In M3 sieht jede eingeloggte Person alle veröffentlichten Fächer und Themen. Der Filter nach Einschreibung (Plan Schule §8 Schritte 2–3) kommt als eigener Meilenstein vor M5.
- **Warum:** M3 soll nur den Lernablauf liefern. Klassen und Einschreibungen in Filament würden M3 etwa verdoppeln.
- **Folgen:** Die Fächer-API filtert in M3 nur nach Status und `is_active`. Mit dem Klassenfilter kommt die Einschreibung dazu, die Routen bleiben gleich.

### E-018 — Fach-Level aus den EP eines Fachs (2026-10-15)
- **Entscheidung:** Fach-EP sind alle EP, die eine Person in einem Fach bekommen hat. Das Fach-Level nutzt dieselbe Kurve wie das Gesamt-Level (`round((n−1)^1.5 × 8)`), ohne Prestige und mit Schluss bei Level 100.
- **Warum:** So war es in V1 (`subject_xp`), und es wächst mit jedem Üben, auch nachdem alle Themen einmal erledigt sind. Eine zweite Kurve müsste man erklären.
- **Folgen:** Keine neue Tabelle: Die Fach-EP sind die Summe von `question_answers.xp_awarded` über die Fragen des Fachs. Die Tagesregel (E-015) gilt damit auch fürs Fach-Level. Der Server rechnet, die Fach-Karte zeigt „Fach-Level n“.

### E-019 — App und API unter einer Adresse je Umgebung (2026-10-16)
- **Entscheidung:** Die App und ihre API laufen unter einer Adresse: `app.learnharder.app` liefert die App aus, Caddy leitet `/api` und `/sanctum` an Laravel weiter. Staging genauso unter `staging.learnharder.app`. Filament bleibt unter `api.learnharder.app/admin`.
- **Warum:** Mit getrennten Subdomains (`app.` und `api.`) muss das Login-Cookie für die ganze Domain `.learnharder.app` gelten. Dann bekäme auch Staging die Cookies der Produktion, und es gäbe zwei `XSRF-TOKEN`. Unter einer Adresse gelten die Cookies nur für den eigenen Host.
- **Folgen:** Kein `SESSION_DOMAIN` und kein CORS für die App. Der Frontend-Build ruft die API ohne eigene Adresse auf (`VITE_API_URL` leer). Der Setup-Plan §3 nennt noch `api.` für die App und `staging.app…`/`staging.api…` (Widerspruch 7 in `plans/README.md`). Filament hat eine eigene Session auf `api.learnharder.app`.

## Offen

| Nr. | Frage | Empfehlung | Warum offen |
|---|---|---|---|
| O-003 | MVP-Umfang | Kürzen, siehe `plans/README.md` | Der Plan packt Landing Page, KI-Übersetzung, Newsletter und PWA-Offline ins MVP. |
| O-005 | Landing Page: Astro/Nuxt oder statisch? | Zuerst statisch | Eine weitere Technologie lohnt sich für eine Seite noch nicht. |
