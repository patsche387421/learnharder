# Strategie- & Architektur-Plan v1: Initiale Überlegungen zur PHP-Transformation

> **Archiv-Notiz:** Dies ist die ursprüngliche Fassung (v1) der Umstellungsüberlegungen vor der Fokussierung auf den konkreten World4You-Migrationspfad.

---

## 1. Ausgangslage & Bestandsaufnahme

* **Aktueller Stack:** Vanilla HTML, CSS, JavaScript (kein Bundler/Build-Schritt, client-side Fetch auf JSON-Dateien).
* **Backend / Services:** Supabase (PostgreSQL, Auth via GoTrue, Row Level Security RLS).
* **Hosting bisher:** Netlify (automatischer Build & CDN-Hosting über `scripts/gen-config.js`).
* **Hosting Zieloption:** World4You (klassisches Shared Webhosting mit Apache, PHP 8.x, MariaDB/MySQL).
* **Sicherungsstand:**
  * Git-Branch: `backup/pre-php`
  * Git-Tag: `v1.0.0-vanilla-backup`
  * Vollständiges Archiv: `backup_full_pre_php.zip` (im Projekt-Root)

---

## 2. Architektur-Optionen für PHP

### Option A: Hybrid-Modell (PHP als Templating & Security Layer + Supabase weiter nutzen)
* **Konzept:**
  * HTML-Dateien werden zu `.php` migriert.
  * Auslagern redundanter UI-Komponenten (Header, Navbar, Footer, Head-Tags) in `src/partials/`.
  * Einbindung von Supabase-Schlüsseln serverseitig via PHP (`getenv` / `.env`), wodurch das Build-Skript `gen-config.js` entfällt.
  * Auth und Datenbank verbleiben unverändert bei Supabase über den bestehenden JavaScript-Client (`@supabase/supabase-js`).
* **Bewertung:** Sehr geringes Risiko, minimale Umbauzeit, sofortiger Nutzen für Code-Wartbarkeit.

### Option B: Vollständige Transformation (PHP + MySQL/MariaDB auf World4You)
* **Konzept:**
  * Komplette Ablösung von Supabase.
  * Eigene Benutzerverwaltung in PHP mit Sessions (`$_SESSION`) und gehashten Passwörtern (`password_hash`).
  * Migration des Schemas (User Stats, Quiz-Logs, Thema-Progress) von PostgreSQL zu MariaDB/MySQL.
  * Eigene REST-Endpunkte (`/api/...`) in PHP für Quiz-Ergebnisse und Level-Fortschritt.
* **Bewertung:** Hoher Aufwand, da RLS, Auth-Trigger, Token-Refresh und JSON-APIs komplett in PHP neu geschrieben werden müssen.

---

## 3. Stärken & Schwächen einer PHP-Transformation

### Vorteile (Stärken)
1. **Wartbare Templates (DRY):**
   * Kein Copy-Paste von Navigation, Headern und Scripts über 15+ HTML-Dateien hinweg.
   * Zentrale Komponentenänderungen wirken sofort überall.
2. **Sicherere Konfiguration:**
   * Supabase-Keys und Umgebungsvariablen müssen nicht mehr über Build-Hooks in statische JS-Dateien geschrieben werden.
3. **Volle World4You-Kompatibilität:**
   * World4You ist für PHP/Apache optimiert. `.htaccess`-Routen, Caching und serverseitige Header greifen nativ.
4. **DSGVO & Datenhoheit (bei Option B):**
   * Datenbank und Applikation laufen komplett auf österreichischen Servern von World4You ohne externe Drittanbieter-Cloud.

### Nachteile & Risiken (Schwächen)
1. **Verlust des reinen Client-Statik-Vorteils:**
   * Vorher reichte `npx serve src` oder jeder beliebige Webspace; mit PHP wird lokal ein PHP-Server (`php -S localhost:8000`) benötigt.
2. **Migrationsaufwand der Datenbank (falls Supabase abgelöst wird):**
   * PostgreSQL-spezifische Features (UUID-Defaults, RLS-Policies, PL/pgSQL-Trigger wie `on_auth_user_created`) müssen in MariaDB/MySQL mit Triggern oder PHP-Applikationslogik nachgebaut werden.
3. **Deployment-Workflow auf World4You:**
   * World4You bietet kein direktes Git-Push-to-Deploy wie Netlify. Ein automatisierter Rollout erfordert eine GitHub Action (SFTP/FTPS-Deploy).
4. **Cheat-Anfälligkeit bleibt ohne vollständige API-Validierung:**
   * Solange Quizze clientseitig ausgewertet werden, ändert auch ein PHP-Template nichts an der Manipulierbarkeit der Scores. Eine echte Absicherung erfordert PHP-API-Validierung.

---

## 4. Datenbank-Strategie (Supabase vs. World4You MySQL)

### Szenario 1: Supabase beibehalten (Empfohlen für Phase 1)
* **Mechanismus:** PHP liefert die HTML/JS-Seiten aus, der Browser spricht direkt mit Supabase REST/Auth.
* **Vorteile:**
  * Keine SQL-Datenmigration nötig.
  * Bestehende Migrationsskripte (`001_schema.sql`, `003_level_system.sql`) und RLS-Regeln bleiben 1:1 gültig.
  * Null Ausfallrisiko für bestehende User-Accounts.

### Szenario 2: Migration auf World4You MariaDB/MySQL (Langfristig / Phase 2)
* **Schema-Anpassungen nötig:**
  * `UUID` $\rightarrow$ `CHAR(36)` oder `BIGINT AUTO_INCREMENT`.
  * `TIMESTAMPTZ` $\rightarrow$ `DATETIME` oder `TIMESTAMP`.
  * Supabase `auth.users` $\rightarrow$ Eigene Tabelle `users (id, email, password_hash, created_at)`.
  * RLS-Ersatz $\rightarrow$ SQL-Queries in PHP müssen stets mit `WHERE user_id = :session_user_id` gekapselt werden.
* **Datenübertragung:** Export via CSV/SQL-Dump aus Supabase und Import über World4You phpMyAdmin.

---

## 5. Deployment- & Hosting-Konzept für World4You

1. **Repo-Anbindung:**
   * GitHub Repository bleibt SSOT (Single Source of Truth).
   * Einrichtung von `.github/workflows/deploy.yml`:
     * Trigger: Push auf Branch `main`.
     * Aktion: Automatischer Upload via `SamKirkland/FTP-Deploy-Action` (SFTP/FTPS) in das World4You Verzeichnis `/html` oder `/subdomain`.
2. **Verzeichnisstruktur auf World4You:**
   * Ideal: Web-Root zeigt auf `src/` oder das Projekt wird so strukturiert, dass sensible Dateien (Docs, SQL, Backups) außerhalb des öffentlichen Web-Roots liegen.
3. **Environment-Konfiguration:**
   * `.env` auf dem World4You-Server ablegen (geschützt per `.htaccess` gegen HTTP-Zugriff).
