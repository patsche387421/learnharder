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

### E-017 — M3 ohne Klassenfilter (2026-10-15, abgelöst durch E-024)
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

### E-020 — Versionen nur als v2.0.0-alpha.N (2026-10-16)
- **Entscheidung:** Tags folgen dem Setup-Plan §1: der erste ist `v2.0.0-alpha.1` beim Start (M5), danach `alpha.2` …, `beta.x` und `v2.0.0`. Meilensteine auf `dev` bekommen keinen Tag. Das `v0.x`-Schema aus Setup-Plan §4 gilt nicht.
- **Warum:** Ein Schema reicht. Zu jedem Meilenstein führt schon der Merge-Commit auf `dev` zurück.
- **Folgen:** Getaggt wird auf `main`, wenn Patsche „deployen“ sagt. Es gibt bisher nur `v2.0.0-plan` (und die V1-Tags).

### E-021 — Schlankes MVP bis zum Start (2026-10-16)
- **Entscheidung:** Bis zum Start (M5) kommen nur dazu: Klassen und Sichtbarkeit (M3b), Registrierung und Rechtliches (M4a), Hetzner (M4). Brainpower, Streaks, Trophäen, Liga, Englisch mit KI-Übersetzung, PWA-Offline und Landing Page folgen nach dem Start. Bis dahin bleibt die statische Seite.
- **Warum:** Ein Ein-Personen-Projekt. Mit dem MVP aus dem Projektplan (Phase 1) rückt der Start weit nach hinten (Risiko R4).
- **Folgen:** Schließt O-003 und O-005 (zuerst statisch). Das Ökonomie-Modell gilt weiter, nur Brainpower und Booster werden erst später gebaut. Bis dahin gibt es EP, Level, Rang und Fach-Level.

### E-022 — Zum Start: Hub schlank, Team und Liga ausgeblendet (2026-10-16)
- **Entscheidung:** Der Hub zeigt Begrüßung, Level-Leiste und „Weiterlernen“ (nächstes offenes Thema). Team und Liga verschwinden aus der Tab-Leiste, bis es sie gibt. Die Tab-Leiste hat dann Hub, Lernen und Profil.
- **Warum:** Leere Tabs wirken unfertig. „Kommt bald“ weckt Erwartungen, die nach E-021 länger offen bleiben.
- **Folgen:** Kommt in M5. Die Routen `/team` und `/league` bleiben im Code, nur ohne Tab.
- **Teilweise ersetzt (2026-10-17):** Die Tab-Leiste behält ihre fünf Tabs (E-035). Der schlanke Hub gilt weiter.

### E-023 — Rechtstexte: Entwurf von Claude, Prüfung durch die Schule (2026-10-16)
- **Entscheidung:** Claude entwirft Datenschutzerklärung, Impressum und Einwilligung (auch für unter 14-Jährige) aus dem, was die App wirklich speichert. Patsche klärt mit der Schule oder ihrer Datenschutzstelle, wer verantwortlich ist, und lässt die Texte prüfen.
- **Warum:** Die App speichert Daten Minderjähriger. Die Entwürfe müssen zum Code passen, die rechtliche Prüfung kann nur jemand außerhalb machen.
- **Folgen:** Kommt in M4a. Ohne geprüfte Texte kein Start (M5).

### E-024 — Sichtbarkeit nach Klasse, nur für Schüler:innen (2026-10-16)
- **Entscheidung:** Schüler:innen sehen die Fächer aus dem Lehrplan ihrer Klasse: letzte aktive Einschreibung, Fächer mit `stage_from` bis zum aktuellen Abschnitt (Plan Schule §4). Ohne aktive Klasse sehen sie nichts. Admin, Autor:in, Tester:in und Lehrkraft sehen alle veröffentlichten Fächer. Wer keine dieser vier Rollen hat, wird gefiltert wie Schüler:innen.
- **Warum:** Wer Inhalte pflegt, prüft oder unterrichtet, muss sie in der App sehen. Der Filter als Standard schützt, falls eine Person ohne passende Rolle angelegt wird.
- **Folgen:** Löst E-017 ab. Fach, Thema und Lerneinheit außerhalb des Lehrplans geben 404 wie Unveröffentlichtes. Lehrkräfte nur mit ihren Fächern kommt mit dem Lehrkraft-Dashboard nach dem Start.

### E-025 — Registrierung und Einwilligung (2026-10-16)
- **Entscheidung:** Registrieren kann sich, wer eine Adresse `…@htl-md.schule` hat. Die Domain gilt an der HTL Mödling für alle, auch für Microsoft 365. Wer sich registriert, wird immer `student`, Personal legt der Admin an. Die Registrierung fragt keinen Namen ab, `users.name` wird nullable und ist nur im CMS-Formular fürs Personal Pflicht. Mit einem Häkchen bestätigt man „mindestens 14“ und stimmt Datenschutzerklärung und Nutzungsregeln zu. Gespeichert werden `consented_at` und `terms_version`, kein Geburtsdatum. Unter 14 gibt es keine Selbstregistrierung: Die Eltern füllen ein Formular aus, danach legt der Admin das Konto an und bestätigt, dass das Formular vorliegt.
- **Warum:** Adressen haben die Form `Nummer@htl-md.schule` und enthalten keinen Klarnamen. Ohne Namensfeld liegt für Schüler:innen gar kein Klarname im System. Ab 14 dürfen Jugendliche in Österreich selbst einwilligen (§ 4 Abs. 4 DSG). Unter 14 sind im 1. Jahrgang nur Einzelfälle, eine Eltern-Mail mit Link lohnt sich dafür nicht.
- **Folgen:** An der Adresse lässt sich nicht erkennen, ob jemand Lehrkraft ist, die Rolle vergibt nur der Admin. Weil die Nummern leicht zu erraten sind, braucht der Code-Versand Rate-Limits pro E-Mail, pro IP und pro Tag. Eine erneute Zustimmung nach geänderten Texten kommt nach M4a. Die rechtliche Prüfung bleibt bei der Schule (E-023).

### E-026 — Mailversand über Scaleway TEM (2026-10-16)
- **Entscheidung:** Codes und Hinweise verschickt die App über Scaleway Transactional Email. Lokal fängt Mailpit alle Mails ab.
- **Warum:** EU-Anbieter ohne US-Mutterkonzern, Daten in der EU. 300 Mails im Monat sind frei, darüber kostet es 0,25 € je 1000. Ein eigener Mailserver auf Hetzner scheidet aus: Port 25 ist für neue Konten gesperrt, und Microsoft 365 behandelt Mails von einer frischen IP schlecht.
- **Folgen:** Konto und DNS-Einträge (SPF, DKIM, DMARC für `learnharder.app`) kommen in M4, Patsche führt aus. Vor dem Start die Schul-IT fragen, ob sie den Absender freigibt. Die App nutzt nur Laravels Mail-Schnittstelle, der Anbieter steht in der `.env`.

### E-027 — Konten löschen, sperren und Admins schützen (2026-10-16)
- **Entscheidung:** Schüler:innen löschen ihr Konto selbst, sofort und vollständig, nach Eingabe des Passworts. Personal kann sich nicht selbst löschen. Der Admin sperrt Konten (`deactivated_at`, `deactivated_by`, sachlicher interner Grund). Das wirkt sofort in App und CMS, und nur der Admin entsperrt. Beim Sperren, beim neuen Passwort und beim Löschen enden alle Sessions, Tokens und Remember-Cookies. Niemand kann sich selbst sperren, löschen oder die Admin-Rolle nehmen, und der letzte aktive Admin ist geschützt. Eine Sperrliste für gelöschte Konten gibt es nicht.
- **Warum:** Sofort löschen ist eindeutig und passt zu Art. 17 DSGVO. Wenn man anonymisiert, bleiben die Antworten in einer Klasse mit 25 Personen oft zuordenbar. Lehrkräfte hängen an Zuordnungen (`RESTRICT`) und werden deshalb gesperrt, nicht gelöscht. Ein Hash einer Nummern-Adresse lässt sich zurückrechnen, eine Sperrliste wäre also personenbezogen. Wiederholungsfälle regelt die Schule mit ihren Mitteln.
- **Folgen:** Die Schutzregeln stehen nicht in Policies, weil `Gate::before` Admins alles erlaubt. Die Prüfung sperrt alle Admin-Zeilen, damit zwei gleichzeitige Änderungen nicht beide durchgehen. Ein Artisan-Befehl auf dem Server legt im Notfall einen Admin an. Zum Start (M5) gibt es mindestens zwei Admins, einer davon von der Schule. Die Datenschutzerklärung nennt die Backup-Frist, bis zu der Gelöschtes noch in Sicherungen liegt.

### E-028 — Konten nach 12 Monaten ohne Anmeldung löschen (2026-10-16)
- **Entscheidung:** Wer sich 12 Monate nicht anmeldet, wird gelöscht, 30 Tage vorher kommt eine Hinweis-Mail. Ein Login stoppt das Löschen. Personal ist ausgenommen.
- **Warum:** Die Regel deckt Matura, Schulwechsel, nie zugeordnete und vergessene Konten ab. Die Sommerferien liegen weit darunter. Gesperrte können sich nicht anmelden, ihr Konto verschwindet damit nach 12 Monaten von selbst.
- **Folgen:** `users.last_login_at` kommt in M4a, damit die Frist ab dem Start richtig läuft. Hinweis-Mail und Löschlauf brauchen den Scheduler (M4) und kommen vor M5. Die Frist steht in der Datenschutzerklärung.

### E-029 — Doku-Werkzeuge: Scramble ja, VitePress später, DocFX nein (2026-10-17)
- **Entscheidung:** Die API-Referenz erzeugt Scramble (`dedoc/scramble`) aus Routen, Validierung und Resources, nur lokal sichtbar. Die übrige Doku bleibt Markdown im Repo. Eine Doku-Seite mit VitePress kommt erst, wenn es Leser:innen außerhalb des Repos gibt. DocFX kommt nicht.
- **Warum:** Das Projekt soll übergeben werden können (CMS, Betrieb, Entwicklung). Eine API-Doku, die aus dem Code entsteht, kann nicht veralten. DocFX erzeugt API-Doku nur für .NET und bräuchte die .NET-Werkzeuge. VitePress passt zum Frontend (Vue, Vite), lohnt sich aber erst mit Publikum. Bis dahin zeigt GitHub das Markdown gut an.
- **Folgen:** Scramble ist eine 0.x-Version (Stand 2026-10-17: 0.13.48, unterstützt Laravel 13). Die Version wird deshalb fest gepinnt und in Produktion nicht ausgeliefert. Die Schritte stehen in `AUFGABEN.md` unter „Übergabe“.

### E-030 — Spielwelt lokal bauen, solange M4 wartet (2026-10-17)
- **Entscheidung:** Solange M4 auf die Antwort der Schule wartet, entstehen die Tracks der Spielwelt lokal auf eigenen Branches: G1 Hub und Profil, G2 Brainpower und Streaks, G3 Erfolge, G4 Avatar, G5 Shop, G6 Rangliste, L1 Landing Page (`plans/KONZEPT_SPIELWELT.md`). Der Start (M5) wartet nicht auf sie. Was beim Start fertig und getestet ist, geht mit. Der Rest bleibt in der App unsichtbar.
- **Warum:** M4 hängt an einer Antwort von außen. Die Wartezeit lässt sich für das Frontend nutzen, das dem Backend weit hinterherhinkt, und für die Alleinstellungsmerkmale. Die Startbedingungen aus E-021 bleiben, damit der Start nicht vom Umfang abhängt (Risiko R4).
- **Folgen:** Präzisiert E-021, hebt es nicht auf. M4 steht in `plans/README.md` als pausiert (⏸). Das Brainpower-Konzept wird trotzdem vor dem Start mit den Lehrkräften abgestimmt (M5), deshalb stehen die Werte gesammelt in `config/`. Avatar und Effekte halten sich an die ruhige Oberfläche aus `design/README.md`.

### E-031 — Avatar: Roboter aus Slots, Starter-Kit je Abteilung (2026-10-17)
- **Entscheidung:** Der Avatar ist ein Roboter. Alle Roboter teilen ein Skelett mit festen Steckplätzen und acht Slots: Kopf, Aufsatz, Rumpf, Arme, Antrieb, Werkzeug, Abzeichen, Lackierung. Jede Abteilung bekommt ein kostenloses Starter-Kit. Jedes Fach hat ein Werkzeug, das mit dem Fach-Level in Stufen aufgerüstet wird. Display-Gesicht, Akku und Animationen zeichnet die Engine. Claude zeichnet zuerst Platzhalter.
- **Warum:** Ein Roboter passt zur HTL und braucht keine Hautfarben und kein Geschlecht, wichtig bei Minderjährigen. Ein gemeinsames Skelett hält Zeichenaufwand und Engine klein, eigene Grundmodelle je Abteilung bräuchten beides mehrfach. Varianten je Slot statt fertiger Skins ergeben viele Kombinationen aus wenigen Zeichnungen. Das Werkzeug macht sichtbar, was jemand gelernt hat.
- **Folgen:** Details in `plans/KONZEPT_SPIELWELT.md` §7. Das endgültige Aussehen entscheidet das Design-Artefakt (E-006), offen ist noch, wer die Teile zeichnet. Die Werkzeug-Stufen stehen in `config/`.
- **Präzisierung (2026-10-17):** Stil wie ein Schulprojekt aus der HTL-Werkstatt, Display als LED-Punktmatrix, Akzent in der Farbe der Abteilung. Patsche entwirft die Teile mit KI-Hilfe, Claude überträgt sie als SVG. Der erste Entwurf ist vorläufig. Animiert werden nur `transform` und `opacity`.

### E-032 — Avatar-Teile ohne Zufall (2026-10-17)
- **Entscheidung:** Jedes Avatar-Teil hat eine feste, sichtbare Quelle (Fach-Level, Erfolg, Trophäen, Shop). Es gibt keine Lootboxen und keine Zufalls-Drops.
- **Warum:** Die Nutzer:innen sind minderjährig, und Lootboxen gelten in Österreich und der EU rechtlich als heikel. Ein sichtbares Ziel ist fairer als Glück.
- **Folgen:** Eine Bauplan-Ansicht zeigt alle Teile und ihre Bedingung. Auch Belohnungen aus Minispielen und Saisons sind fest.

### E-033 — Avatar-Stimmungen nie negativ, für andere nur Pose (2026-10-17)
- **Entscheidung:** Der Avatar zeigt nur positive oder ruhige Stimmungen (stolz, Wiedersehen, zufrieden, neugierig, müde), nie traurige, besorgte oder kaputte. Andere sehen nicht die Stimmung, sondern eine selbst gewählte Pose.
- **Warum:** Lernen soll ohne schlechtes Gewissen funktionieren (Ton in `design/Sprache.md`). Die Stimmung verrät, wann jemand lernt, schläft oder weg war. Bei Minderjährigen bleibt das privat.
- **Folgen:** Hinweise wie „Streak in Gefahr“ kommen nur als Text. Die App wählt die Stimmung aus Fakten des Servers und der Gerätezeit, der Server speichert sie nicht.

### E-034 — Avatar in der Klasse: Werkhalle, Klassenprojekt, Reaktionen nur gezählt (2026-10-17)
- **Entscheidung:** Die Klassenseite „Werkhalle“ zeigt die Roboter der Klasse in ihrer Pose. Die Klasse baut gemeinsam an einem Klassenprojekt, sichtbar ist nur die Summe, am Saisonende gibt es ein Klassenfoto. Meilensteine erscheinen dort nur nach Opt-in. Reaktionen gibt es nur auf Meilensteine, aus einer festen positiven Auswahl, und wer sie bekommt, sieht nur die Anzahl.
- **Warum:** Datenschutz durch Voreinstellung (DSGVO Art. 25) bei Minderjährigen: Meilensteine verraten Aktivität. Gemeinsam bauen statt vergleichen, ohne Einzelbeiträge, damit niemand bloßgestellt wird. Ohne freien Text und ohne Namen bei Reaktionen gibt es nichts zu moderieren, keinen Druck und keine sichtbaren Grüppchen.
- **Folgen:** Details in `plans/KONZEPT_SPIELWELT.md` §7. Die Werkhalle kann später den Tab „Team“ füllen (E-022). Podest und Porträts in der Rangliste hängen an O-006.
- **Präzisierung (2026-10-17):** Der Akku ist für andere unsichtbar, er verrät Aktivität wie die Stimmung. Die Grundausstattung sieht so gut aus wie verdiente Teile, damit niemand als Anfänger:in auffällt.

### E-035 — Tab-Leiste mit fünf Tabs, Team und Liga zum Start fertig (2026-10-17)
- **Entscheidung:** Die Tab-Leiste bleibt, wie das Design-Artefakt sie vorgibt: Lernen · Team · Hub · Liga · Profil. Team und Liga werden nicht ausgeblendet, sondern sind zum Start (M5) fertig.
- **Warum:** Das Design-Artefakt ist die Quelle (E-006) und baut die Leiste für genau fünf Ziele. Ausblenden bräuchte dort eine Änderung, die zum Start wieder zurückkäme.
- **Folgen:** Ersetzt den Teil von E-022 zur Tab-Leiste. Team und Liga (G6, nach O-006) werden Startbedingung. Für diese beiden Seiten gilt damit nicht mehr, dass die Liga erst nach dem Start kommt (E-021) und der Start nicht auf die Spielwelt wartet (E-030).

### E-036 — Brainpower: Aufladen um Mitternacht in Wien, Maximum ab Prestige fest (2026-10-17)
- **Entscheidung:** Brainpower lädt sich einmal je Kalendertag in `Europe/Vienna` auf, nicht um 00:00 UTC. Das Maximum folgt dem Level (Ökonomie-Modell §3.1), ab Prestige 1 bleibt es beim Deckel von 200. Aufgeladen wird beim ersten Lesen an einem neuen Tag, verpasste Tage zählen mit, bis zum Maximum.
- **Warum:** Derselbe Tag wie bei EP (E-015) und Streak, sonst lädt sich Brainpower um 1 oder 2 Uhr nachts auf. Ohne festes Maximum fiele es beim Prestige von 200 auf 100, das wäre eine Strafe für die Fleißigsten (E-010). Mehr Kapazität je Prestige brächte einen Vorteil in der Rangliste, weil Booster EP bringen. Prestige belohnen Kosmetik und Trophäen. Aufladen beim Lesen braucht keinen Scheduler und keinen nächtlichen Schreibvorgang für alle Konten.
- **Folgen:** Werte in `config/gamification.php`, Rechnung in `App\Support\Brainpower`. `users.brainpower_max` entfällt, neu ist `users.brainpower_refilled_on`. Ökonomie-Modell §3.1 und §3.2 entsprechend ergänzt.

## Offen

| Nr. | Frage | Empfehlung | Warum offen |
|---|---|---|---|
| O-006 | Wonach richtet sich die Rangliste (G6)? | EP pro Saison (Monat) in der eigenen Klasse, dazu Ranglisten je Fach. Brainpower nicht, weil sie sich täglich auflädt und beim Ausgeben sinkt (`plans/KONZEPT_SPIELWELT.md` §8) | Erst vor G6 nötig, Patsche entscheidet |

Offene Fakten zur Schule: `plans/DATENMODELL_SCHULE.md` §7.
