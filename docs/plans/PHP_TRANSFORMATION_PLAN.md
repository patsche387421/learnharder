# Masterplan: Vollständige Migration auf World4You (PHP & MariaDB, ohne Supabase)

## 1. Strategische Richtungsentscheidung

* **Entscheidung:** Wir lösen **Supabase vollständig ab** und betreiben LearnHarder als autarkes System auf **World4You**.
* **Begründung:**
  1. **100% DSGVO-Sicherheit:** Alle Daten (Schülerdaten, E-Mails, Passwörter, Scores) lagern in Linz/Wien auf World4You-Servern. Keine US-Cloud-Abhängigkeit (Supabase/Netlify) mehr.
  2. **Keine Mehrkosten:** Im gebuchten World4You Webhosting sind **10 MariaDB-Datenbanken gratis enthalten**.
  3. **Echter Manipulationsschutz (Cheat-Schutz):** Quizze werden serverseitig in PHP validiert – Schüler können Punkte nicht mehr über die Browser-Konsole fälschen.
  4. **Maximale Session-Sicherheit:** Beseitigung von JWT-Tokens im `localStorage`; stattdessen Standard-PHP-Sessions mit `HttpOnly`-, `Secure`- und `SameSite=Strict`-Cookies.
  5. **Kein Build-Overhead:** `gen-config.js`, `config.js` und externe CDN-Bibliotheken (`@supabase/supabase-js`) entfallen ersatzlos.

---

## 2. Die neue Systemarchitektur auf World4You

```
┌──────────────────────────────────────────────────────────────────────────┐
│                   BROWSER / CLIENT (HTL-Schüler)                         │
│   HTML5 + CSS (Dark Mode) + Vanilla JS (UI, Events, Animationen)        │
└─────────────────────────────────┬────────────────────────────────────────┘
                                  │ HTTPS (Session-Cookie HttpOnly)
                                  ▼
┌──────────────────────────────────────────────────────────────────────────┐
│                   WORLD4YOU WEBSERVER (Apache + PHP 8.x)                 │
│                                                                          │
│  ├── /src/maintenance.html   (Wartungsseite bei Updates / Schularbeiten) │
│  ├── /src/api/auth.php       (Login, Logout, Session-Status)             │
│  ├── /src/api/stats.php      (Punkte abrufen, Quizze servervalidieren)   │
│  ├── /src/api/level.php      (Energie-Reset, Trophäen-Tausch)            │
│  └── /src/api/db.php         (Zentrale PDO-Datenbankverbindung)          │
└─────────────────────────────────┬────────────────────────────────────────┘
                                  │ Lokaler Socket / PDO
                                  ▼
┌──────────────────────────────────────────────────────────────────────────┐
│                   WORLD4YOU MARIADB / MYSQL (In Linz/Wien)               │
│                                                                          │
│  ├── users                   (id, email, password_hash, created_at)      │
│  ├── user_stats              (user_id, total_xp, level, energy, trophies)│
│  ├── subject_xp              (user_id, subject_id, xp, level, correct)   │
│  ├── thema_progress          (user_id, thema_id, abgeschlossen, score)   │
│  ├── daily_quiz_log          (id, user_id, played_at, score, xp, success)│
│  └── quiz_results            (id, user_id, thema_id, richtig, gesamt)    │
└──────────────────────────────────────────────────────────────────────────┘
```

---

## 3. Die 5 Migrationsphasen im Überblick

| Phase | Fokus | Hauptaufgabe |
| :--- | :--- | :--- |
| **Phase 1** | **Wartung & Vorbereitung** | `maintenance.html` im Brand-Design erstellen & `.htaccess`-Schalter testen. |
| **Phase 2** | **World4You & MariaDB** | Hosting buchen, SSL aktivieren, MariaDB-Schema via phpMyAdmin importieren. |
| **Phase 3** | **PHP-Backend (API)** | Erstellung von `db.php`, `auth.php`, `stats.php` und serverseitiger Quiz-Prüfung. |
| **Phase 4** | **Frontend-Entkopplung** | `auth.js` & `stats.js` auf `fetch('/api/...')` umstellen, Supabase-Skripte komplett entfernen. |
| **Phase 5** | **Automatisierung & Go-Live** | GitHub Action für SFTP einrichten, End-to-End-Test, Netlify & Supabase abschalten. |

---

## 4. Detaillierter Ablaufplan der Phasen

### Phase 1: Wartungsseite & Umschalt-Mechanismus
* Erstellung von `src/maintenance.html` (Dark-Mode, autark, keine CDNs).
* Vorbereitung der Wartungs-Regel in `src/.htaccess` (HTTP 503 Weiterleitung).
* **Ziel:** Die Domain kann jederzeit während des Umbaus oder bei Wartungsfenstern in den Wartungsmodus versetzt werden.

### Phase 2: World4You Setup & MariaDB-Schema
* Im World4You Portal:
  * Paket *Webhosting Go* wählen, Let's Encrypt SSL aktivieren.
  * Neue MariaDB-Datenbank anlegen (z. B. `learnharder_db`).
  * FTP/SFTP-Benutzer anlegen.
* Einspielen des bereinigten SQL-Schemas (Ersatz für PostgreSQL/Supabase):
  * Automatische Kennwort-Verschlüsselung mit `BCRYPT` (`password_hash`).
  * Anlage von Testnutzern (`test@lernhub.htl`, `schueler1@lernhub.htl` etc.).

### Phase 3: Schlankes PHP-Backend (`/src/api/`)
* **`api/db.php`:** PDO-Verbindung mit Prepared Statements (100% SQL-Injection-sicher).
* **`api/auth.php`:**
  * `action=login`: Prüft E-Mail und `password_verify()`, setzt Session-Cookie.
  * `action=session`: Gibt aktuellen Benutzerstatus zurück (`{ loggedIn: true, email: "...", name: "Max" }`).
  * `action=logout`: Zerstört die Session.
* **`api/stats.php`:**
  * `action=get`: Liefert Fach- und Gesamtstatistiken des angemeldeten Benutzers.
  * `action=submit_quiz`: Nimmt gegebene Antworten entgegen, vergleicht serverseitig mit der JSON-Datei und speichert Punkte fälschungssicher.

### Phase 4: Frontend-Umstellung (Supabase entfernen)
* In allen HTML-Dateien:
  * Entfernen von `<script src="...supabase-js..."></script>`.
  * Entfernen von `<script src="/js/config.js"></script>` und `<script src="/js/supabase.js"></script>`.
* In `src/js/auth.js`:
  * Umbau auf `fetch('/api/auth.php?action=...')`.
  * Das bestehende Interface (`Auth.login`, `Auth.logout`, `Auth.isLoggedIn`, `Auth.currentUser`) bleibt für alle anderen Skripte **100% identisch**.
* In `src/js/stats.js`:
  * Umbau auf `fetch('/api/stats.php')`.

### Phase 5: CI/CD Deployment & Go-Live
* GitHub Action `.github/workflows/deploy.yml` aktivieren:
  * Bei `git push main` werden die Dateien automatisch per SFTP nach World4You übertragen.
* End-to-End-Testing auf der World4You-Domain.
* Abschalten des Netlify-Accounts und Archivierung des Supabase-Projekts.

---

## 5. Vorteile nach Abschluss

1. **Unabhängigkeit:** Keine US-Dienste, keine externen Ausfälle, kein Cloud-Lock-in.
2. **Datenschutz:** Schulleitung und Elternvertreter können vollste DSGVO-Konformität attestiert werden.
3. **Kostenkontrolle:** Fixe 48 € im 1. Jahr (~4 €/Monat) direkt an World4You in Österreich.
4. **Schülersicherheit:** Kein Manipulieren von XP oder Tages-Quiz-Ergebnissen mehr über die Konsole möglich.
