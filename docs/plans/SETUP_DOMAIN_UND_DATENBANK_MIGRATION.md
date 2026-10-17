# Setup-, Domain- & Datenbank-Migrationsplan (V2 Transformation)

> **Status:** Verbindlicher Migrationsleitfaden  
> **Basis-Tag:** `v2.0.0-plan` (Aktueller Code- und Planungsstand, schließt an v1.0.0/v1.1.0 an)  
> **Ziel-Tag:** `v2.0.0-alpha.1` (Vollständig migriertes Laravel-Backend, migrierte Daten & Vue 3 Setup)  
> **Verantwortlich:** Lead DevOps & Backend Architecture
>
> **Was nicht mehr gilt (Stand 2026-10-17):** Laravel 13 statt 11, nur PostgreSQL (E-007). §2 Schritt 3 entfällt, von Schritt 4 bleibt nur das Löschen von Supabase: V2 startet ohne V1-Nutzer (E-011). §3: App und API unter einer Adresse, Staging unter `staging.learnharder.app` (E-019). §4: keine `v0.x`-Tags, nur `v2.0.0-alpha.N` (E-020). Die Stellen sind unten mit *Stand:* markiert.

---

## 1. Versionierungs- & Release-Roadmap

Wir folgen striktem Semantic Versioning (SemVer) für den Umschwung:

* **`v2.0.0-plan` (IST-Zustand):**
  * Vanilla JS (IIFE) Webapp, Prototyp mit 17 HTML-Dateien.
  * Statische Lerninhalte in `src/assets/data/*.json`.
  * Supabase Auth & relationale Schemas (`sql/migrations/001-003`).
  * Vollständige V2-Architekturpläne, PSP, Risikomatrix und Ökonomiemodell.
* **`v2.0.0-alpha.1` (Meilenstein nach Abschluss der Migration):**
  * Frisches Headless-Laravel-11-Setup mit Filament Admin/CMS auf Hetzner VPS. *Stand: Laravel 13, Filament 5 (E-008).*
  * Vollständig importierte Fächer, Themen und Fragen aus JSON in die relationale DB.
  * Migrierte Nutzer- und Statistikdaten aus Supabase. *Stand: entfällt (E-011).*
  * Neues Vue-3-PWA-Frontend mit implementierten Design-Tokens aus dem Claude-Designsystem.
  * Live-Domain auf dem neuen Server mit SSL.
* **`v2.0.0-beta.x`:**
  * Kontinuierliche Minor- und Patch-Releases für Staging-Tester und Schul-Feedback.
* **`v2.0.0` (Final):**
  * Go-Live des regulären Schulbetriebs (Lehrkraft-Dashboard, Klassen-Verbünde, monatliche Ranglisten).

---

## 2. Datenbank-Migration: Was geschieht mit der alten Datenbank?

### 2.1 Derzeitiger Stand (Supabase)
Die bisherige Datenbank in Supabase umfasst:
* `auth.users`: Authentifizierungs-Identitäten (E-Mails, Hashes).
* `user_stats`: XP gesamt, Energie (alt), Trophäen, Streaks.
* `subject_xp`: XP aufgeschlüsselt pro Fach.
* `fach_stats`, `thema_progress`, `quiz_results`: Quizverlauf und Abschlussquoten.
* `daily_quiz_log`: Tägliche Quiz-Historie.
* Lokale Dateisystem-Daten: `src/assets/data/manifest.json`, `dbi/*.json`, `pos/*.json`.

---

### 2.2 Der 4-Schritte-Migrationsprozess

```
┌────────────────────────┐      Dump & Export       ┌────────────────────────┐
│    Supabase (Alt)      │ ───────────────────────> │  Archiv-Export (.json) │
└────────────────────────┘                          └───────────┬────────────┘
                                                                │
                                                                │ ETL / Seeder
                                                                ▼
┌────────────────────────┐     Seeder / Parser      ┌────────────────────────┐
│  src/assets/data/*.json│ ───────────────────────> │ Laravel 11 (Neu DB)    │
│  (Lerninhalte/Fragen)  │                          │ MariaDB / PostgreSQL   │
└────────────────────────┘                          └────────────────────────┘
```

*Stand: Laravel 13, nur PostgreSQL (E-007). Der obere Weg (Supabase → Export → Seeder) entfällt bis auf die Sicherung (E-009, E-011).*

#### Schritt 1: Freeze & Vollständiger Datenexport (Vor dem Stichtag)
1. Schreibzugriff in Supabase per RLS oder Wartungsmodus sperren.
2. Vollständiger SQL-Dump und CSV/JSON-Export aller Tabellen über das Supabase-Dashboard / CLI:
   * `storage/backups/supabase_users_export.json`
   * `storage/backups/supabase_stats_export.json`
   * `storage/backups/supabase_results_export.json`
3. Ablage des Dumps im gesicherten Projekt-Archiv.

#### Schritt 2: Content-Transformation (JSON ➔ Relationale DB)
Die bestehenden Lerninhalte liegen nicht in Supabase, sondern als JSON-Dateien vor. Diese werden über ein Laravel-Artisan-Command (`php artisan learnharder:import-legacy-content`) migriert:
* `manifest.json` ➔ Tabelle `subjects` (Fächer: DBI, POS, NSVS, SYP, MEDT, TINF).
* `pos/*.json` & `dbi/*.json` ➔ Tabellen `topics`, `theory_sections` und `questions`.
* Die deutschen Texte werden im JSON-Feld `{"de": "..."}` der neuen `spatie/laravel-translatable`-Spalten gespeichert.

#### Schritt 3: User- & Statistik-Mapping

*Stand: entfällt. Aus V1 werden weder Konten noch Fortschritte übernommen, alle registrieren sich neu (E-011).*

* **Passwörter & Accounts:**
  * Da Supabase GoTrue Bcrypt verwendet, können Hashes direkt in die Laravel `users`-Tabelle übernommen werden.
  * Schüler-Accounts ohne Klarnamen erhalten automatisch einen Gamer-Tag (z. B. aus E-Mail-Präfix generiert).
* **Gamification-Umrechnung (Energie ➔ Brainpower):**
  * Alte "Energydrinks" (max. 5) werden nicht 1:1 übernommen, sondern jeder migrierte Account startet mit vollem **Brainpower-Startguthaben ($100\text{ BP}$)**.
  * `total_xp`, Streaks und Trophäen werden 1:1 in die neue `user_stats`-Tabelle übernommen.

#### Schritt 4: Validierung & Stilllegung von Supabase (DSGVO-Konformität)

*Stand: Punkt 1 und 2 entfallen (keine migrierten Konten). Punkt 3 gilt: Supabase nach dem Start abschalten (M5).*

1. Parallele Testabnahme auf Staging: Stichprobenprüfung von 10 migrierten Accounts.
2. Sobald `v0.2.0` produktiv läuft, bleibt das Supabase-Projekt für 14 Tage als Fallback schreibgeschützt (Read-Only).
3. Nach Ablauf der Frist wird das Supabase-Projekt **vollständig und unwiderruflich gelöscht**, um keine parallelen Datensätze (DSGVO-Datensparsamkeit) außerhalb der EU zu belassen.

---

## 3. Domain- & Hosting-Migration

### 3.1 Ziel-Infrastruktur & Subdomain-Konzept

Alle Dienste laufen auf einem dedizierten Hetzner Cloud VPS (Standort Falkenstein/Nürnberg oder Wien) unter deutscher/österreichischer Gerichtsbarkeit:

| Domain / Host | Ziel-Komponente | Technologie / Port | Zweck |
| :--- | :--- | :--- | :--- |
| **`learnharder.app`** | Marketing & Landing Page | Nuxt / Astro (Port 3000 via Reverse Proxy) | Öffentliche SEO-Landingpage, Registrierung & Newsletter |
| **`app.learnharder.app`** | Lern-Plattform PWA | Vue 3 + Vite SPA/PWA (Static Build / Caddy) | Die eigentliche Web-App für Schüler & Lehrkräfte |
| **`api.learnharder.app`** | REST-API & Filament CMS | Laravel 11 / PHP 8.3 FPM (Nginx/Caddy) | API für App + Filament Admin (`/admin`) für Autoren |
| **`ws.learnharder.app`** | WebSocket Server | Laravel Reverb (Port 8080 via WSS) | Synchrone 1v1-Duelle & Live-Klassen-Quizze (Phase 2) |

*Stand: `app.learnharder.app` liefert die App, Caddy leitet `/api` und `/sanctum` an Laravel 13 weiter, Filament bleibt unter `api.learnharder.app/admin` (E-019). Die Landing Page kommt mit L1 (E-021, E-030), Reverb erst nach dem Start (Zeile „danach“ in `README.md`).*

---

### 3.2 DNS-Migrationsplan (Zero-Downtime)

1. **Vorbereitung (T - 48h):**
   * TTL (Time-To-Live) aller DNS-Records beim Domain-Registrar auf **300 Sekunden (5 Minuten)** reduzieren.
   * Hetzner VPS provisionieren (Docker, Caddy/Nginx, PHP 8.3, MariaDB/PostgreSQL, Redis). *Stand: nur PostgreSQL (E-007).*
   * SSL-Zertifikate per Caddy / Let's Encrypt für alle Subdomains automatisch bereitstellen.
2. **Staging-Validierung (T - 24h):**
   * Bereitstellung von `staging.app.learnharder.app` und `staging.api.learnharder.app`. *Stand: eine Adresse `staging.learnharder.app` (E-019).*
   * Durchführung der automatisierten Pest- & Playwright-Tests. *Stand: PHPUnit statt Pest, Playwright in `frontend/e2e/` (E-039).*
3. **Umschaltung (Stichtag):**
   * DNS-A-Records für `@`, `app`, `api` auf die feste IP des Hetzner-Servers umbiegen.
   * Caddy übernimmt das Routing und terminiert SSL.
   * Während der weltweiten DNS-Aktualisierung zeigt der alte Webspace eine saubere Weiterleitung bzw. Statusmeldung (`maintenance.html`).
4. **Abschluss:**
   * TTL der DNS-Records wieder auf Standard (z. B. 86400s) anheben.

---

## 4. Git-Tagging & Commit-Protokoll

*Stand: gilt nicht. Es gibt keine `v0.x`-Tags, der erste Tag ist `v2.0.0-alpha.1` beim Start (E-020). Struktur `backend/` und `frontend/` steht seit M0.*

1. **Tag `v0.1.0`:**
   * Markiert den vollständigen Abschluss der Architektur- und Planungsphase.
   * Fixiert den Codebestand des alten Vanilla-Prototyps vor Beginn des Umbaus.
2. **Nächster Entwicklungs-Zweig (`feat/v0.2.0-laravel-setup`):**
   * Trennung in `/backend` (Laravel 11) und `/frontend` (Vue 3).
   * Nach Fertigstellung des Migrations-Imports und Integration der Claude-Design-Tokens erfolgt der Merge auf `main`/`dev` und das Setzen des Tags **`v0.2.0`**.
