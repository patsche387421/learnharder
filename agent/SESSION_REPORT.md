# SESSION_REPORT.md — LernHub Demo

Erstellt: 2026-06-12 | Branch: `dev` | Commit: `76684f2`

---

## 1. Projektstruktur (aktuell)

```
lernhub-demo/
├── netlify.toml                  Build-Config für Netlify (gen-config.js + publish=src)
├── .gitignore                    ignoriert: .env, src/js/config.js, node_modules/
├── README.md                     Kurzanleitung (leicht veraltet, s. Abschn. 3)
│
├── docs/
│   ├── CLAUDE.md                 Projektregeln für KI-Agenten (teilw. veraltet)
│   ├── DESIGN_GUIDELINES.md      Design-System-Referenz (Dark/Light, Tokens, Reset)
│   ├── DB_Integration_Guide.md   Ursprünglicher DB-Migrationsplan (veraltet)
│   └── KI_VORLAGE.md             Prompt-Vorlagen für Inhalts-Erstellung
│
├── agent/
│   ├── .gitkeep
│   └── SESSION_REPORT.md         ← diese Datei
│
├── scripts/
│   └── gen-config.js             Netlify-Build: generiert config.js aus Env-Vars
│
├── sql/
│   └── migrations/
│       ├── 001_schema.sql         CREATE fach_stats / thema_progress / quiz_results + RLS
│       └── 002_seed_test_users.sql  5 Testuser (schueler1–3, demo, lehrer @lernhub.htl)
│
├── data/
│   └── import/                   Rohdaten (unveröffentlicht, noch nicht importiert)
│       ├── MIGRATION_PROMPT.md
│       ├── dbi-11-beziehungen_quiz.json
│       ├── dbi-11-beziehungen_theorie.json
│       └── manifest_dbi_entry.json
│
└── src/                          ← Netlify Publish-Root
    ├── .htaccess                 Cache-Control für JSON (no-cache, must-revalidate)
    ├── index.html                Login-Seite
    ├── dashboard.html            Dashboard (geschützt)
    ├── faecher.html              Fächerübersicht (geschützt)
    ├── fach.html                 Themen-Inhalt: Theorie+Quiz / Tool-Modus (geschützt)
    ├── pos.html                  POS-Themenübersicht (11 Themen, geschützt)
    ├── dbi.html                  DBI-Themenübersicht (21 Themen, geschützt)
    ├── nsvs.html                 NSVS – Hülle vorhanden, keine Themen
    ├── tinf.html                 TINF – Hülle vorhanden, keine Themen
    ├── wir.html                  WIR – Hülle vorhanden, keine Themen
    ├── medt.html                 MEDT – Hülle vorhanden, keine Themen
    ├── syp.html                  SYP – Hülle vorhanden, keine Themen
    │
    ├── css/
    │   ├── tokens.css            CSS Custom Properties (--color-*, --spacing-*, …)
    │   ├── style.css             Layout + Komponenten (eigene :root mit --bg, --primary …)
    │   └── pos/
    │       └── datenstrukturen.css  Scoped styles für POS-Datenstrukturen-Tool
    │
    ├── js/
    │   ├── config.js             ← GITIGNORED. Enthält Supabase URL + Anon Key (lokal)
    │   ├── config.example.js     Vorlage für config.js (ohne echte Keys)
    │   ├── supabase.js           Initialisiert SupabaseClient aus config.js
    │   ├── auth.js               Supabase Auth: Login, Logout, requireLogin, displayName
    │   ├── stats.js              DB-Queries: fach_stats, thema_progress, quiz_results
    │   ├── app.js                Manifest laden, alle 3 Navigationsebenen rendern
    │   └── pos/
    │       └── datenstrukturen.js   Interaktives Datenstrukturen-Tool (IIFE, window.DSTool)
    │
    └── assets/
        └── data/
            ├── manifest.json       Einzige Quelle für Fächer + Themen (7 Fächer)
            ├── schema/
            │   ├── manifest.schema.json
            │   ├── quiz.schema.json
            │   └── theorie.schema.json
            ├── pos/                10 Standard-Themen + 1 Tool-Thema (datenstrukturen)
            │   └── <thema>_{theorie,fragen,antworten}.json  (je 3 Dateien pro Thema)
            ├── dbi/                21 Standard-Themen
            │   └── <thema>_{theorie,fragen,antworten}.json
            └── import/             Gleiche Rohdaten wie /data/import (Duplikat)
```

---

## 2. Was funktioniert

### Seiten & Navigation
| Seite | Status | Inhalt |
|---|---|---|
| `index.html` | ✅ fertig | Login via Supabase Auth (E-Mail + Passwort) |
| `dashboard.html` | ✅ fertig | Begrüßung, Stats aus DB (Themen, Quiz-Punkte) |
| `faecher.html` | ✅ fertig | 7 Fach-Karten aus manifest.json |
| `pos.html` | ✅ fertig | 11 Themen, Fortschritts-Badges, Stats-Leiste |
| `dbi.html` | ✅ fertig | 21 Themen, Fortschritts-Badges, Stats-Leiste |
| `fach.html` | ✅ fertig | Theorie + Quiz (Standard) oder Tool-Mount |
| `nsvs/tinf/wir/medt/syp.html` | ⚠️ Hülle | Seite + Topbar fertig, aber Themen-Liste leer |

### Features
- **Supabase Auth** vollständig: `signInWithPassword`, `signOut({ scope:'local' })`, `onAuthStateChange`, `getSession`, `requireLogin()`
- **Seitenschutz**: alle 10 geschützten Seiten mit async `requireLogin()` Guard
- **Quiz + Fortschritt**: Ergebnis wird in DB gespeichert (`speichereQuizErgebnis`), Abzeichnung (✓) und Score-Badge auf Themenkarten
- **Fach-Stats**: Fortschritt %, Themen bearbeitet, Quiz-Punkte per Fach aus `fach_stats`
- **Dashboard-Stats**: aggregierte Werte über alle Fächer aus `fach_stats`
- **Interaktives Tool**: POS-Datenstrukturen (IIFE-Modul, Tool-Mount in fach.html)
- **Dark Mode** Standard + Light Mode via `data-theme="light"` auf `<html>`
- **View Transitions** (Chromium, graceful degradation in anderen Browsern)
- **Netlify-Build**: `scripts/gen-config.js` generiert `config.js` aus Env-Vars

### Dateninhalt
- **POS**: 10 C#-Themen (Variablen, Kontrollstrukturen, Schleifen, Strings, Methoden, Arrays, Collections, OOP, Vererbung, Exceptions) + 1 Tool (Datenstrukturen)
- **DBI**: 21 SQL/ERD-Themen (vollständige Prüfungsvorbereitung)
- Je Thema: `_theorie.json` + `_fragen.json` + `_antworten.json`

---

## 3. Was fehlt / offen

### Kritisch – ohne diese Schritte funktioniert die Live-Site nicht

| # | Problem | Lösung |
|---|---|---|
| 🔴 1 | **SQL-Migrations nicht ausgeführt** – Tabellen existieren noch nicht in Supabase | `sql/migrations/001_schema.sql` im Supabase Dashboard → SQL Editor ausführen |
| 🔴 2 | **Testuser noch nicht angelegt** – kein einziger User kann sich einloggen | `sql/migrations/002_seed_test_users.sql` ausführen (Passwort: `lernhub123`) |
| 🔴 3 | **Netlify Env-Vars fehlen** – `config.js` wird bei Deployment nicht erzeugt | Netlify → Site configuration → Environment variables: `SUPABASE_URL` + `SUPABASE_ANON_KEY` setzen |

### Mittelfristig

| # | Problem | Lösung |
|---|---|---|
| 🟡 4 | **Domain-Tippfehler auf Netlify** – Site heißt `lernharder.netlify.app` statt `learnharder` | Netlify → Site configuration → Site name ändern |
| 🟡 5 | **5 leere Fächer** – NSVS, TINF, WIR, MEDT, SYP haben keine Themen | Mit KI_VORLAGE.md neue Themen generieren und Manifest ergänzen |
| 🟡 6 | **Token-Inkonsistenz** – `tokens.css` (--color-*, grau/orange) und `style.css` (eigenes :root, --bg/--primary, blau/indigo) sind inkohärent; `style.css` nutzt `tokens.css` nicht | Entweder style.css auf --color-* migrieren ODER tokens.css durch den style.css-:root ersetzen |
| 🟡 7 | **Import-Ordner-Duplikat** – `data/import/` und `src/assets/data/import/` enthalten dieselben Rohdaten | Einen der Ordner löschen; Dateien aus `/data/import` sind noch nicht in manifest.json eingetragen |

### Dokumentation (veraltet)
| Datei | Was veraltet ist |
|---|---|
| `docs/CLAUDE.md` | Beschreibt auth.js als "sessionStorage-Login", stats.js als "Platzhalter" — beides überholt |
| `docs/DB_Integration_Guide.md` | Ist-Zustand war "keine DB" — DB ist jetzt integriert |
| `README.md` | Login-Tipp zeigt `test / test123` statt `test@lernhub.htl / lernhub123` |
| `src/index.html` | Hint zeigt `test@lernhub.htl / test123` — Passwort ist falsch (korrekt: `lernhub123`) |

### Bekannte Code-Hinweise
- `fach.html` inline-Script: `document.getElementById("logout").addEventListener("click", Auth.logout)` — würde sicherer als `() => Auth.logout()` gebunden werden können (kein funktionaler Bug, aber defensiver Stil)
- Alle Fach-Seiten (pos, dbi, …): `App.renderFachSeite(...)` wird ohne `await` aufgerufen — Fehler werden intern in `header.innerHTML` geschrieben (funktioniert, aber unauffällig)

---

## 4. Technischer Stand

### Supabase
| Thema | Status |
|---|---|
| Client-Konfiguration | ✅ `supabase.js` initialisiert `SupabaseClient` aus `config.js` |
| Auth (Login/Logout/Session) | ✅ vollständig via `auth.js` |
| DB-Schema Migration | ⏳ SQL vorhanden, **noch nicht ausgeführt** |
| Tabelle `fach_stats` | ⏳ geplant; RLS-Policy bereit |
| Tabelle `thema_progress` | ⏳ geplant; RLS-Policy bereit |
| Tabelle `quiz_results` | ⏳ geplant; RLS-Policy bereit |
| Testuser (5 Stück) | ⏳ SQL vorhanden, **noch nicht ausgeführt** |
| Test-Login aus index.html | ⚠️ Hinweis zeigt falsches Passwort (`test123` statt `lernhub123`) |

### Auth
- Implementierung: Supabase `signInWithPassword` / `signOut({ scope:'local' })` / `getSession`
- Session-Caching: `_session`-Variable + `onAuthStateChange`-Listener
- Guard: `requireLogin()` auf allen 10 geschützten Seiten (async IIFE-Pattern)
- Logout-Fix: `scope:'local'` löscht localStorage auch ohne Serverantwort; `window.location.replace` verhindert Redirect-Loop

### Deployment (Netlify)
| Thema | Status |
|---|---|
| Repository | ✅ `github.com/patsche387421/learnharder` (Branch `dev` aktuell) |
| Build-Command | ✅ `node scripts/gen-config.js` in `netlify.toml` |
| Publish-Dir | ✅ `src/` |
| Env-Vars | ❌ `SUPABASE_URL` und `SUPABASE_ANON_KEY` **noch nicht gesetzt** |
| PR dev → main | ✅ erstellt (muss nach Tests gemergt werden) |
| Site-Name | ⚠️ Tippfehler: `lernharder` statt `learnharder` |

---

## 5. Nächste Schritte (priorisiert)

### Schritt 1 — Supabase SQL-Migrations ausführen (10 Minuten)
```
Supabase Dashboard → Projekt öffnen → SQL Editor → New query
1. Inhalt von sql/migrations/001_schema.sql einfügen → Run
2. Inhalt von sql/migrations/002_seed_test_users.sql einfügen → Run
Danach prüfen: Authentication → Users → 5 User sichtbar?
```

### Schritt 2 — Netlify Env-Vars setzen (5 Minuten)
```
Netlify Dashboard → learnharder-Site → Site configuration → Environment variables
→ Add variable: SUPABASE_URL = https://xxxx.supabase.co
→ Add variable: SUPABASE_ANON_KEY = eyJ...
→ Deploys → Trigger deploy (damit gen-config.js die Vars aufgreift)
```

### Schritt 3 — Netlify Site-Name korrigieren (2 Minuten)
```
Netlify → Site configuration → Site name: lernharder → learnharder
```

### Schritt 4 — Token-Inkonsistenz bereinigen
`style.css` hat ein eigenes `:root` mit Tokens (`--bg`, `--primary`, `--success`, …) das `tokens.css` komplett ignoriert. Entscheidung treffen:
- **Option A (empfohlen):** `tokens.css` auf die tatsächlich verwendeten Token-Namen (`--bg`, `--primary`, …) aktualisieren → dann ist `tokens.css` wieder Single Source of Truth
- **Option B:** `tokens.css` entfernen und nur noch `:root` in `style.css` verwenden

### Schritt 5 — Leere Fächer befüllen
NSVS, TINF, WIR, MEDT, SYP zeigen „Noch keine Themen verfügbar". Mit `docs/KI_VORLAGE.md` neue Theorie- und Quiz-JSONs generieren, in `assets/data/<fach>/` ablegen und in `manifest.json` eintragen.

---

*Bericht auto-generiert am Ende der Session. Alle Pfade relativ zum Projekt-Root.*
