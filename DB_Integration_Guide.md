# DB_Integration_Guide.md — Datenbankanbindung für LernHub

Dieses Dokument beschreibt, wie die Lernstatistiken an eine echte Datenbank
angebunden werden. Die Platzhalter-Funktionen in `js/stats.js` sind so
strukturiert, dass die Migration **Schritt für Schritt** erfolgen kann —
ohne die restliche Applikation anfassen zu müssen.

---

## 1. Aktuelle Architektur (Ist-Zustand)

```
Browser
  ├── js/auth.js      → sessionStorage-Login (Demo)
  ├── js/stats.js     → gibt Platzhalterwerte zurück (keine DB)
  └── js/app.js       → ruft Stats-Funktionen auf, rendert Ergebnisse
```

Die Stats-Leiste auf jeder Fach-Seite (`pos.html`, `dbi.html` …) sowie der
Quiz-Auswertungs-Hook laufen bereits durch `stats.js`. Die HTML-Elemente tragen
`data-stat`-Attribute, damit sie per Selektor gezielt befüllbar sind.

---

## 2. Zielarchitektur (Soll-Zustand)

```
Browser                    API-Server                  Datenbank
  ├── js/auth.js    ──►  POST /api/v1/auth/login  ──►  users
  ├── js/stats.js   ──►  GET  /api/v1/stats/fach  ──►  stats
  └── js/app.js          GET  /api/v1/stats/thema ──►  thema_progress
                         POST /api/v1/stats/quiz  ──►  quiz_results
```

### Empfohlener Stack (Vorschlag, nicht bindend)

| Schicht     | Technologie                          |
|-------------|--------------------------------------|
| API         | Node.js + Express **oder** .NET 8 Web API |
| Datenbank   | PostgreSQL (empfohlen) oder SQLite   |
| Auth        | JWT (JSON Web Token) oder Sessions   |
| ORM         | Prisma (Node) / EF Core (.NET)       |

---

## 3. Datenbankschema

### Tabelle `users`
```sql
CREATE TABLE users (
  id       SERIAL PRIMARY KEY,
  username VARCHAR(64) UNIQUE NOT NULL,
  password_hash TEXT NOT NULL,      -- bcrypt, niemals Klartext
  created_at TIMESTAMPTZ DEFAULT NOW()
);
```

### Tabelle `fach_stats`
```sql
CREATE TABLE fach_stats (
  id               SERIAL PRIMARY KEY,
  user_id          INT REFERENCES users(id) ON DELETE CASCADE,
  fach_id          VARCHAR(32) NOT NULL,  -- z.B. "pos"
  themen_bearbeitet INT DEFAULT 0,
  quiz_punkte_gesamt INT DEFAULT 0,
  letzte_aktivitaet TIMESTAMPTZ,
  UNIQUE(user_id, fach_id)
);
```

### Tabelle `thema_progress`
```sql
CREATE TABLE thema_progress (
  id            SERIAL PRIMARY KEY,
  user_id       INT REFERENCES users(id) ON DELETE CASCADE,
  thema_id      VARCHAR(64) NOT NULL,  -- z.B. "pos-datenstrukturen"
  abgeschlossen BOOLEAN DEFAULT FALSE,
  letzter_score INT,                   -- Prozentwert 0–100
  quiz_punkte   INT DEFAULT 0,
  updated_at    TIMESTAMPTZ DEFAULT NOW(),
  UNIQUE(user_id, thema_id)
);
```

### Tabelle `quiz_results` (Verlauf)
```sql
CREATE TABLE quiz_results (
  id          SERIAL PRIMARY KEY,
  user_id     INT REFERENCES users(id) ON DELETE CASCADE,
  thema_id    VARCHAR(64) NOT NULL,
  richtig     INT NOT NULL,
  gesamt      INT NOT NULL,
  score       INT NOT NULL,  -- Prozentwert
  erstellt_at TIMESTAMPTZ DEFAULT NOW()
);
```

---

## 4. Benötigte API-Endpunkte

### Auth
| Methode | Pfad                     | Beschreibung                        |
|---------|--------------------------|-------------------------------------|
| POST    | `/api/v1/auth/login`     | Login, gibt JWT oder Session zurück |
| POST    | `/api/v1/auth/logout`    | Session/Token invalidieren          |

### Stats
| Methode | Pfad                            | Beschreibung                           |
|---------|---------------------------------|----------------------------------------|
| GET     | `/api/v1/stats/fach/:fachId`    | Fach-Gesamtstatistik für den eingeloggten User |
| GET     | `/api/v1/stats/thema/:themaId`  | Thema-Fortschritt für den eingeloggten User    |
| POST    | `/api/v1/stats/quiz`            | Quiz-Ergebnis speichern                |

#### Beispiel-Response: `GET /api/v1/stats/fach/pos`
```json
{
  "fachId": "pos",
  "fortschritt": 33,
  "themenBearbeitet": 1,
  "quizPunkte": 5,
  "letzteAktivitaet": "2026-06-10T09:00:00Z"
}
```

#### Beispiel-Body: `POST /api/v1/stats/quiz`
```json
{
  "themaId": "pos-datenstrukturen",
  "richtig": 5,
  "gesamt": 6,
  "zeitstempel": "2026-06-10T09:05:00Z"
}
```

---

## 5. Migration: stats.js anpassen

Die gesamte DB-Anbindung läuft über **drei Funktionen in `js/stats.js`**.
Kein anderer Code muss verändert werden.

### Schritt 1 — Auth-Header-Hilfsfunktion in `auth.js` ergänzen

```js
// In js/auth.js, in der IIFE ergänzen:
function headers() {
  const token = sessionStorage.getItem("lernhub_token");
  return {
    "Content-Type": "application/json",
    ...(token ? { "Authorization": "Bearer " + token } : {})
  };
}
return { login, logout, isLoggedIn, currentUser, requireLogin, headers };
```

### Schritt 2 — Login auf echten API-Call umstellen

```js
// js/auth.js — login() ersetzen:
async function login(user, pass) {
  const res = await fetch("/api/v1/auth/login", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ username: user, password: pass })
  });
  if (!res.ok) return false;
  const data = await res.json();
  sessionStorage.setItem("lernhub_user", user);
  sessionStorage.setItem("lernhub_token", data.token); // JWT speichern
  return true;
}
```

### Schritt 3 — `ladeFachStats()` in stats.js auf DB umstellen

```js
// js/stats.js — ladeFachStats() ersetzen:
async function ladeFachStats(fachId) {
  const res = await fetch(
    "/api/v1/stats/fach/" + encodeURIComponent(fachId),
    { headers: Auth.headers() }
  );
  if (!res.ok) {
    console.warn("[Stats] Fach-Stats konnten nicht geladen werden:", res.status);
    return { fortschritt: 0, themenBearbeitet: 0, quizPunkte: 0, letzteAktivitaet: null };
  }
  return res.json();
}
```

### Schritt 4 — `speichereQuizErgebnis()` auf POST umstellen

```js
// js/stats.js — speichereQuizErgebnis() ersetzen:
async function speichereQuizErgebnis(themaId, richtig, gesamt) {
  await fetch("/api/v1/stats/quiz", {
    method: "POST",
    headers: Auth.headers(),
    body: JSON.stringify({
      themaId,
      richtig,
      gesamt,
      zeitstempel: new Date().toISOString()
    })
  });
}
```

---

## 6. Wo Stats im DOM angezeigt werden

Die Stats-Leiste auf Fach-Seiten (`pos.html` etc.) wird von `App.renderFachSeite()`
befüllt. Die Pill-Elemente tragen `data-stat`-Attribute, die als Hook dienen:

```html
<div class="stat-pill" data-stat="fortschritt">
  <span class="stat-pill-label">Fortschritt</span>
  <span class="stat-pill-value">–</span>   <!-- wird durch JS ersetzt -->
</div>
```

Nach der DB-Anbindung erscheinen reale Werte ohne Änderung an der HTML-Struktur.

---

## 7. Reihenfolge der Implementierung (empfohlen)

```
Phase 1 — Backend-Grundstruktur
  ✦ API-Server aufsetzen (Express / .NET)
  ✦ Datenbankschema anlegen (Migrations-Skript)
  ✦ /api/v1/auth/login implementieren + testen

Phase 2 — Auth-Integration im Frontend
  ✦ auth.js: login() auf API umstellen
  ✦ auth.js: headers()-Hilfsfunktion ergänzen
  ✦ Logout: Token aus sessionStorage entfernen

Phase 3 — Stats lesen
  ✦ GET /api/v1/stats/fach/:id implementieren
  ✦ stats.js: ladeFachStats() umstellen
  ✦ Fach-Seiten zeigen echte Werte

Phase 4 — Stats schreiben
  ✦ POST /api/v1/stats/quiz implementieren
  ✦ stats.js: speichereQuizErgebnis() umstellen
  ✦ Quiz-Auswertung speichert Ergebnisse

Phase 5 — Dashboard
  ✦ GET /api/v1/stats/dashboard (aggregiert über alle Fächer)
  ✦ dashboard.html: stat-value-Elemente befüllen
```

---

## 8. Sicherheitshinweise

- **Passwörter:** Immer mit `bcrypt` oder `argon2` hashen — niemals Klartext.
- **JWT:** Kurze Laufzeit (z.B. 1h) + Refresh-Token-Mechanismus.
- **CORS:** API nur für die eigene Origin freigeben.
- **Input-Validierung:** Alle API-Eingaben serverseitig validieren.
- **HTTPS:** Auf dem Produktions-Host immer TLS/HTTPS verwenden.
- **Secrets:** API-Keys, DB-Passwörter niemals im Frontend-Code — nur im Backend.
  (Gilt auch lokal: keine `.env`-Dateien ins Git committen.)
