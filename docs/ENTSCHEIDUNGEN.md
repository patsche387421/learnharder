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

### E-004 — Branch-Regel (2026-10-14)
- **Entscheidung:** Arbeit auf `dev`. Große Features auf `feat/<name>`, danach Merge nach `dev`.

### E-005 — Lokale Entwicklung in WSL2, `main` als Worktree (2026-10-14)
- **Entscheidung:** Das Repo liegt in Ubuntu-WSL2 (`~/learnharder.app`), `main` als Git-Worktree daneben (`~/learnharder.app-main`).
- **Warum:** Unter `C:\` laufen Requests im Sail-Container ~20 s, `tempnam()` schlägt fehl, Port 8000 ist von Windows gesperrt und das Sail-Skript läuft nicht in Git Bash.
- **Folgen:** Einrichtung siehe `README.md`. Der alte Checkout `C:\v\learnharder.app` bleibt bis zur Nutzer-Migration liegen (Root-`.env`, Backup-Zips), wird aber nicht mehr entwickelt.

## Offen

| Nr. | Frage | Empfehlung | Warum offen |
|---|---|---|---|
| O-001 | Datenbank: Postgres oder MariaDB? | **Postgres** | Sail nutzt schon Postgres, und der Supabase-Export ist Postgres. Die Pläne nennen noch beides. |
| O-002 | Laravel 13 + Filament 3 oder auf Filament 4 wechseln? | Vor dem Ausbau des CMS prüfen | Lock-Datei hat Laravel 13.35 mit Filament 3.3. Je später der Wechsel, desto teurer. |
| O-003 | MVP-Umfang | Kürzen, siehe `plans/README.md` | Der Plan packt Landing Page, KI-Übersetzung, Newsletter und PWA-Offline ins MVP. |
| O-004 | Wann werden die Supabase-Nutzerdaten exportiert? | Möglichst bald einen Sicherungs-Dump ziehen | Die Prod-Daten liegen nur dort. Die App ist im Wartungsmodus und erzeugt keinen Traffic mehr. |
| O-005 | Landing Page: Astro/Nuxt oder statisch? | Zuerst statisch | Eine weitere Technologie lohnt sich für eine Seite noch nicht. |
