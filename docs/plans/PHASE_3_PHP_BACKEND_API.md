# Phase 3: PHP Backend API (Modulare Unterschritte)

## 1. Übersicht

Das PHP-Backend ersetzt sämtliche Funktionen von Supabase GoTrue Auth und PostgreSQL RLS. Es wird in 4 klar getrennte Unterschritte zerlegt, damit jedes Skript isoliert implementiert und getestet werden kann.

---

## 2. Die 4 Unterschritte von Phase 3

```
┌─────────────────────────────────────────────────────────────┐
│ 3.1: DB-Kern & Session-Sicherheit (db.php)                  │
└──────────────────────────────┬──────────────────────────────┘
                               │
       ┌───────────────────────┴───────────────────────┐
       ▼                                               ▼
┌─────────────────────────────┐ ┌─────────────────────────────┐
│ 3.2: Auth-Endpunkt          │ │ 3.3: Stats- & Gamification  │
│ (api/auth.php)              │ │ (api/stats.php & level.php) │
└─────────────────────────────┘ └─────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────┐
│ 3.4: Serverseitige Quiz-Validierung (api/quiz-submit.php)    │
└─────────────────────────────────────────────────────────────┘
```

---

### Unterschritt 3.1: Datenbank-Kern & Session-Sicherheit (`src/api/db.php`)
* **Aufgabe:** Zentraler Einstiegspunkt für alle API-Routen.
* **Implementierungsdetails:**
  * Aufbau einer stabilen `PDO`-Verbindung zu MariaDB mit UTF-8 (`utf8mb4`).
  * Sichere Session-Initialisierung:
    * `session_set_cookie_params(['httponly' => true, 'secure' => true, 'samesite' => 'Strict'])`.
  * Helper-Funktionen:
    * `json_response($data, $statusCode = 200)`
    * `require_auth()` $\to$ Bricht mit HTTP 401 ab, falls keine Session vorliegt.
* **Sicherheitsvorteil:** Kein direkter Zugriff von außen auf DB-Passwörter; standardisierte Fehlerbehandlung.

---

### Unterschritt 3.2: Authentifizierungs-API (`src/api/auth.php`)
* **Aufgabe:** Ersatz für Supabase GoTrue Auth.
* **Endpunkte:**
  1. `POST /api/auth.php?action=login`
     * Input: `{ email, password }`
     * Prüfung via `password_verify($password, $user['password_hash'])`
     * Bei Erfolg: Regenerieren der Session-ID (`session_regenerate_id(true)`) und Speichern von `$_SESSION['user_id']`.
     * Response: `{ success: true, user: { id, email, displayName } }`.
  2. `GET /api/auth.php?action=session`
     * Prüft aktive Session.
     * Response: `{ loggedIn: true, user: ... }` oder `{ loggedIn: false }`.
  3. `POST /api/auth.php?action=logout`
     * Zerstört Cookie und Session via `session_destroy()`.

---

### Unterschritt 3.3: Gamification- & Stats-API (`src/api/stats.php` & `src/api/level.php`)
* **Aufgabe:** Bereitstellung von Nutzerwerten, Energie und Trophäen.
* **Endpunkte:**
  1. `GET /api/stats.php?action=user_stats`
     * Liefert `total_xp`, `level`, `energy`, `trophies`, `prestige` aus Tabelle `user_stats`.
     * Führt bei Bedarf den nächtlichen Energie-Reset auf 5 Dosen durch (`energy_last_reset < heute`).
  2. `GET /api/stats.php?action=fach_stats&fach=pos`
     * Liefert bearbeitete Themen und Fach-XP aus `subject_xp` und `thema_progress`.
  3. `POST /api/level.php?action=trade_trophy`
     * Zieht 1 Trophäe ab und lädt Energie um +1 auf (Ersatz für den Supabase-Client in `tauschen.html`).
  4. `GET /api/stats.php?action=streak`
     * Berechnet die Streak serverseitig anhand der `daily_quiz_log`-Historie.

---

### Unterschritt 3.4: Server-Quiz-Validierung (`src/api/quiz-submit.php`)
* **Aufgabe:** Vollständiger Manipulationsschutz (Cheat-Schutz).
* **Workflow:**
  1. Der Client schickt **nur** die Antworten:
     ```json
     { "themaId": "pos-oop-1", "userAnswers": [1, 3, 0, 2] }
     ```
  2. PHP lädt die zugehörige Datei `src/assets/data/pos/themen/oop-1.json`.
  3. PHP prüft Serverseitig:
     * Wie viele Fragen sind korrekt?
     * Berechnung von `score` (0–100%) und `xp_earned`.
  4. PHP führt eine atomare Datenbank-Transaktion aus:
     * Insert in `quiz_results`
     * Upsert in `thema_progress`
     * Update `user_stats.total_xp` und `subject_xp.xp`
  5. Response an den Client: `{ score, richtig, gesamt, xpVerdient, levelUp: bool }`.
* **Ergebnis:** Schüler können in den Browser-DevTools keine Punkte oder Erfolge mehr erfinden.

---

## 3. Checkliste für Phase 3

- [ ] 3.1: `src/api/db.php` erstellt & DB-Verbindung getestet
- [ ] 3.2: `src/api/auth.php` erstellt & Login/Logout via Postman/Curl getestet
- [ ] 3.3: `src/api/stats.php` & `level.php` für Nutzerdaten und Trophäen-Tausch erstellt
- [ ] 3.4: `src/api/quiz-submit.php` mit serverseitigem Fragenabgleich fertiggestellt
