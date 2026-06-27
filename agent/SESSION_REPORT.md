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

## Session 2026-06-14 — Projektstruktur für Daten-Migration vorbereiten

Reine Struktur-/Dokumentations-Vorbereitung. **Kein Feature-Code, keine
Frontend-Änderungen.** Branch: `dev`.

### Neu erstellte Dateien
- `docs/design/README.md` — Design-System-Übersicht + Workflow + Regel für Claude Code
- `docs/design/INBOX/README.md` — Inbox-Beschreibung + Sortier-Regeln
- `docs/design/components/.gitkeep` — hält leeren Ordner in Git
- `docs/design/pages/.gitkeep` — hält leeren Ordner in Git
- `docs/DATA_MIGRATION_V2.md` — neuer Migrations-Plan V2 (subjects/topics/content_items
  mit jsonb) inkl. Abschnitte Sicherheit (RLS), Datenbank-Hygiene, Architektur-Entscheidung
- `src/assets/data/tagesquiz_test.json` — Test-JSON mit 5 Tagesquiz-Fragen (tq-001…tq-005)

### Geänderte Dateien
- `docs/CLAUDE.md` — drei Abschnitte angehängt (Design-System, Design-Inbox-Workflow,
  Bug-Tracking); bestehender Inhalt unverändert
- `docs/DATENMIGRATION.md` — Hinweis-Block am Anfang („Überholt seit Juni 2026 –
  siehe DATA_MIGRATION_V2.md"); restlicher Inhalt unverändert
- `agent/SESSION_REPORT.md` — dieser Eintrag

### Bereits vorhanden, NICHT verändert
- `docs/BUGS.md` — von Patsche manuell angelegt/committed (2 KB), bewusst unangetastet

### Offener Folge-Schritt
- TODO (separater, isolierter Prompt): `tagesquiz.html` anpassen, um
  `src/assets/data/tagesquiz_test.json` zu laden.

---

## Session 2026-06-14 (zweite Runde) — Design-INBOX sortiert

10 HTML-Exporte aus `docs/design/INBOX/` einsortiert. Reine Doku-Arbeit,
**kein `src/`-Code, kein `docs/BUGS.md` angefasst.**

### Verschoben & umbenannt
**Pages (4):**
- `learnharder-dashboard.dc.html` → `pages/dashboard.html` (+ `dashboard.md`)
- `learnharder-quiz.dc.html`      → `pages/quiz.html`      (+ `quiz.md`)
- `learnharder-theory.dc.html`    → `pages/theory.html`    (+ `theory.md`)
- `learnharder-teams.dc.html`     → `pages/teams.html`     (+ `teams.md`)

**Components (6):**
- `learnharder-nav.dc.html`      → `components/nav.html`      (+ `nav.md`)
- `learnharder-progress.dc.html` → `components/progress.html` (+ `progress.md`)
- `learnharder-charts.dc.html`   → `components/charts.html`   (+ `charts.md`)
- `learnharder-brand.dc.html`    → `components/brand.html`    (+ `brand.md`)
- `learnharder-icons.dc.html`    → `components/icons.html`    (+ `icons.md`)
- `learnharder-logo.dc.html`     → `components/logo.html`     (+ `logo.md`)

### Sonstige Änderungen
- `docs/design/components/.gitkeep` und `docs/design/pages/.gitkeep` entfernt
  (Ordner enthalten jetzt echte Inhalte).
- `docs/design/INBOX/` enthält nur noch `README.md` (wie gefordert).

### Inhalte der `.md`-Dateien
Jede `.md` enthält: Kurzbeschreibung, Typ, verwendete Tokens
(echte Namen aus `tokens.css`: `--bg`, `--surface`, `--primary` etc. —
nicht die historischen `--color-*`-Namen aus `DESIGN_GUIDELINES.md`),
betroffene `src/`-Seiten, TODOs/Abweichungen vom aktuellen Stand.

### Mapping-Hinweis
Erwartete Zuordnung war korrekt — keine Korrekturen nötig.
- `teams` wurde als **Seite** eingeordnet (volle Feature-Ansicht
  Rangliste+Teams), nicht als Komponente.
- `progress` als **Komponente** (wiederverwendet auf Dashboard +
  Fach-Übersichten).

---

## Session 2026-06-14 (dritte Runde) — BUG-001 Fix: Tagesquiz lädt tagesquiz_test.json

**Geänderte Datei:** `src/tagesquiz.html`

### Was wurde geändert
- `themaZuPfad()`-Funktion entfernt (nicht mehr benötigt)
- `ladeFragen()` vollständig ersetzt: statt manifest.json + N×2 Themen-JSON-Fetches
  jetzt ein einziger Fetch auf `/assets/data/tagesquiz_test.json`
- Start-Screen-Text: „8 Fragen, 1 Versuch pro Tag" → „1 Versuch pro Tag"
  (Anzahl variiert jetzt je nach JSON-Inhalt)

### Verifikation
- JSON-Fetch via Preview-Server getestet: HTTP 200, 5 Fragen geladen
- Mapping geprüft: alle Felder (`frage`, `optionen`, `richtigeAntwort` als number,
  `fachId` als lowercase-string) korrekt gesetzt
- Kein anderer `src/`-Code angetastet

### Zusammenhang
Behebt BUG-001 (schwarzer Screen) für den Entwicklungsstand mit Test-JSON.
Endgültige Lösung nach Daten-Migration V2 (Supabase `content_items`-Tabelle).

---

## Session 2026-06-14 (vierte Runde) — Drei Quiz-Bugfixes

**Geänderte Dateien:** `src/js/level.js`, `src/js/app.js`, `src/css/style.css`

### A1 — Completion-Bonus nur für Tagesquiz, EP quiztyp-abhängig
`buecheQuizErgebnis()` (`level.js:89–98`): Basis-EP wird konditional gesetzt,
Bonus-Block in `if (istTagesQuiz)` gekapselt.
- Themen-Quiz: `ep = richtig * 1`, kein Bonus
- Tagesquiz:   `ep = richtig * 5` + Completion-Bonus (10/20/35/50)
- Trophäen (`richtig` Stück) und `subject_xp`-Buchung bleiben gleich.

Verifikation: Logik isoliert nachgestellt im Preview, 7 Testfälle:
Themen 4/4 → ep=4, Tages 5/5 → ep=75, Tages 4/5 → ep=40, Tages 3/5 → ep=25,
Tages 2/5 → ep=10, Tages ohne Leben → ep=0. Alle erwartet.

### A2 — Themen-Quiz „Auswerten" zeigt EP/Trophäen
`renderQuiz()` (`app.js:261–275`): `Level.buecheQuizErgebnis(...)` wird jetzt
mit `await` aufgerufen, Reihenfolge umgestellt (erst speichern + buchen, dann
anzeigen). Result-Text erweitert auf
`"Du hast X von N Fragen richtig — +<ep> EP, +<trophien> 🏆"`.
Keine HTML-Änderung — `#quiz-result` ist in `fach.html:49` vorhanden.

### A3 — Tagesquiz-Breite einheitlich, kein Sprung
`style.css`: `.quiz-screen-inner` max-width von 480 px auf **600 px** angehoben.
Neue Regel direkt davor: `#screen-quiz { max-width: 100%; margin-inline: auto }`,
ab `@media (min-width: 600px)` ebenfalls `max-width: 600px`. Damit haben
Start-, Gesperrt-/Gespielt- und Quiz-Screen visuell dieselbe Breite —
Lebensanzeige springt beim Wechsel nicht mehr.

Verifikation: CSS-Regeln im geladenen Stylesheet (`document.styleSheets`)
ausgelesen — alle drei Regeln (`.quiz-screen-inner`, `#screen-quiz`,
`@media (min-width: 600px) #screen-quiz`) liegen korrekt vor.

### Bekannte Doku-Diskrepanz (NICHT in diesem Commit)
`LEVEL_SYSTEM.md §4` sagt aktuell „5 EP überall". Mit A1 gelten ab jetzt
1 EP für Themen-Quiz und 5 EP + Bonus für Tagesquiz. Doku-Anpassung kommt
in einem separaten Konzept-Commit.

---

## Session 2026-06-14 (fünfte Runde) — BUGS.md Code-Analyse & Aktualisierung

**Geänderte Dateien:** `docs/BUGS.md`, `agent/SESSION_REPORT.md`

### Analyse-Grundlage
- Commits `9ed1548`, `7fbb73f`, `e347c6f`, `b2749c2` seit letzter BUGS.md-Aktualisierung
- SESSION_REPORT-Einträge (dritte + vierte Runde)
- Code-Lektüre: `src/js/app.js`, `src/js/stats.js`, `src/tagesquiz.html`

### BUG-001 → ✅ Erledigt (Workaround)
Workaround via Test-JSON in `e347c6f` + SyntaxError-Fix in `7fbb73f`.
Endgültige Lösung folgt nach Daten-Migration V2.

### BUG-003 → Status aktualisiert
Teilweise behoben: `await` + EP-Anzeige in `9ed1548`. Manuelle Supabase-
Verifikation (`user_stats`, `subject_xp`) noch ausstehend.

### Neu hinzugefügt
- **BUG-009** (🟢): LEVEL_SYSTEM.md §4 veraltet — 5 EP dokumentiert,
  Code gibt 1 EP für Themen-Quiz seit `9ed1548`.
- **BUG-010** (🟡): `Stats.speichereQuizErgebnis()` ohne `await` in
  `app.js:262` — Supabase-Schreibfehler werden lautlos verschluckt.
- **⚠️ Bekannte Diskrepanzen** — neuer Abschnitt mit drei Einträgen:
  LEVEL_SYSTEM.md vs. Code, Test-JSON Fragenanzahl, BUG-001-Workaround.

### Neue ✅-Einträge
- Themen-Quiz Auswerten-Übersicht (A2, `9ed1548`)
- Lebensanzeige-Breite kein Sprung (A3, `9ed1548`)
- EP quiztyp-abhängig (A1, `9ed1548`) — bestehenden Eintrag präzisiert

---

## Session 2026-06-14 (sechste Runde) — LEVEL_SYSTEM aktualisiert, Reiter-Konzept angelegt

**Geänderte Dateien:** `docs/LEVEL_SYSTEM.md`, `docs/BUGS.md`, `agent/SESSION_REPORT.md`
**Neue Dateien:** `docs/KONZEPT_REITER.md`

### LEVEL_SYSTEM.md — Abschnitte überarbeitet
Alle Änderungen mit `> Aktualisiert Juni 2026 — war vorher: ...` markiert.

- **§1 Energie:** Klargestellt dass Energie NUR beim Tagesquiz verbraucht wird;
  Energie-Auto-Regen als noch-nicht-implementiert gekennzeichnet; Offene
  Entscheidung §11.1 (Energie=0 → gesperrt) als gelöst markiert.
- **§2 Lebensbalken:** Explizit auf Tagesquiz eingeschränkt; Themen-Quiz
  (Aufgaben) hat keinen Lebensbalken und übergibt immer lebenProzent=100.
- **§4 EP:** Zweistufiges System dokumentiert: Themen-Quiz = 1 EP/Antwort
  ohne Bonus; Tagesquiz = 5 EP/Antwort + Completion-Bonus.
- **§6 DB:** Von „geplant" auf „aktiv" geändert (Migration 003 existiert).
- **§7 Fragen-Pool:** Tatsächlichen Stand dokumentiert (Themen-Quiz: alle
  Fragen, kein Limit; Tagesquiz: tagesquiz_test.json mit 5 Fragen).
- **§10 UI-Übersicht:** Aufgaben-Tab (fach.html) ergänzt mit korrektem Verhalten.
- **§11:** Offene Entscheidung 1 (Energie=0) als ✅ gelöst markiert.

### KONZEPT_REITER.md — neu angelegt
Planungsdokument für die Reiter-Struktur (Theorie / Aufgaben / Modi) in
`fach.html`. 4 Phasen, Abhängigkeit auf DATA_MIGRATION_V2.md, Mobile-Verhalten.

### BUGS.md
- **BUG-009** → ✅ erledigt (LEVEL_SYSTEM.md aktualisiert)
- **Neue Diskrepanz** in ⚠️: Energie-Auto-Regen geplant aber nicht implementiert

### Neue Code-Diskrepanz gefunden beim Lesen
LEVEL_SYSTEM.md §1 plant „+1 Energydrink pro Tag, Deckel bei 5" — nicht im
JS implementiert. Nur manuell über Trophäen-Tausch oder Direkt-DB-Eingriff.

---

## Session 2026-06-22 — Level-System SSOT, BUG-010-Fix, Teams/Saisons-Konzept

**Branch:** `fix/level-design` (von `dev`), 11 Commits, via `--no-ff` nach `dev`
gemergt (Merge `9766912`). Kein Push, `main` unberührt. `fix/level-design` lokal erhalten.

**Geänderte/neue Dateien:** `src/js/level.js`, `src/js/stats.js`, `src/js/app.js`,
`src/profil.html`, `src/{dbi,medt,nsvs,pos,syp,tinf,wir}.html`, `src/tagesquiz.html`;
neu: `docs/KONZEPT_TEAMS_SAISONS.md`, `docs/NAMENSKONVENTION.md`, `docs/IDEEN.md`;
aktualisiert: `docs/LEVEL_SYSTEM.md`, `docs/BUGS.md`.

### SSOT-Umbau (A–C)
- Neue reine Funktion `Level.berechneFortschritt(gesamtEp)` (+ `berechneLevel`
  exportiert) als SSOT für Level/Fortschritt; quelle-agnostisch (später Team-/Saison-EP).
- Fortschrittsbalken auf Level-Band-Semantik (0→100 % pro Level) — behebt den
  Rückwärts-Sprung beim Level-Up. Level wird IMMER aus EP abgeleitet, DB-Spalten = Cache.
- Umgestellt: Topbar (`renderTopbar`), 7 Fach-Seiten, Profil (User-Leiste + Fach-Zeilen).

### BUG-010-Fix (D)
- `app.js`: Speichern + Belohnungs-Buchung in EINEM `await`-`try/catch`; Fehler im UI
  (`#quiz-result`) statt lautlos. Division-durch-0-Guard in `stats.js`. BUG-010 → ✅ (`2030aed`).

### Cleanup + Renames (F, E)
- Toter Code `Stats.ladeThemaStats` entfernt.
- Renames: `buecheQuizErgebnis` → `vergibBelohnungen`,
  `speichereQuizErgebnis` → `speichereLernfortschritt` (inkl. `console.log`-Label);
  alle Aufrufer + Doku mitgezogen.

### Doku (H, G, I)
- `KONZEPT_TEAMS_SAISONS.md` (neu): XP-Ledger als künftige Daten-SSOT (`xp_events`),
  ersetzt geplantes `level_log` (BUG-006); Saison `YYYY-MM`; Sicherheits-Hinweis
  (EP serverseitig via RPC, nie vom Client) + Atomaritäts-Hinweis.
- `NAMENSKONVENTION.md` (neu): Konventionen, Domänen-Glossar, Funktions-Registry.
- `IDEEN.md` (neu): Backlog „Doku-Index in CLAUDE.md ergänzen".
- `LEVEL_SYSTEM.md` §8 (Level-Band + SSOT); `BUGS.md` (BUG-010 → ✅, BUG-003 Name aktualisiert).

### Offen / nächste Schritte
- Energie-Auto-Regen (+1/Tag) offen; XP-Ledger/Teams nur Konzept (DB-Migration eigene
  Session); `fix/level-design`-Cleanup erst nach Push; nächste Session: Branch
  `fix/topbar-behavior`.
- Nächste Sessions vorgemerkt (Reihenfolge offen, alle als IDEEN.md-Einträge zu Beginn
  der jeweiligen Session): `fix/topbar-behavior` (Hamburger-Menü), `fix/trophy-shop`
  (Energydrinks kaufbar machen), Topbar für nicht-eingeloggte Nutzer auf öffentlichen
  Seiten, `fix/daily-challenge` (Tagesquiz einmal-pro-Tag-Sperre + Energie-Aufladung).

---

## Session 2026-06-25 — BUG-012: Tagessperre der Herausforderung entfernen

**Branch:** `feat/tagessperre-entfernen` (von `dev`), 2 Commits, via `--no-ff` nach
`dev` gemergt (Merge `1d3de7d`), gepusht nach `origin/dev` (`dev` == `origin/dev`).

**Geänderte Datei:** `src/tagesquiz.html` (einzige; `level.js` unangetastet).

**Commits:**
- `cce4d4a` feat(tagesquiz): Tagessperre entfernen, Mehrfachspiel via Nochmal-Button (BUG-012)
- `76f2bbc` docs(tagesquiz): Hinweistext an Mehrfachspiel anpassen

### Was geändert wurde
- Lade-Gate (`hatHeuteTagesQuizGespielt()`-Weiche → `screen-gespielt`) aus der IIFE
  entfernt; Limit ist jetzt **ausschließlich Energie**.
- Start-Run in `starteVersuch()` extrahiert, von `btn-start` und neuem `btn-nochmal`
  geteilt → jeder Versuch eigene `logId` + eigener Energie-Abzug.
- „Nochmal"-Button im Ergebnis-Screen (Label „−1 ⚡, X übrig"); bei Energie 0
  `disabled` + Hinweis statt Sprung auf `screen-gesperrt`.
- Frische Stats nach jedem Versuch (Topbar + Nochmal-Label aktualisiert).
- Hinweistext auf `screen-start` an Mehrfachspiel angepasst.
- `#screen-gespielt`-Markup bewusst stehengelassen (ungenutzt).

### Verifikation
Live gegen Supabase (User `schueler1`), Checkliste 1–7 grün:
- EP/Trophäen addieren (totalXp 1010 → 1310), Energie −1 pro Versuch (4 → 0).
- Doppelklick-Guard greift (`startLaeuft`-Flag vor dem ersten `await`).
- Reload bei Energie > 0 → `screen-start` (Kern des Tickets); Reload bei Energie 0
  → `screen-gesperrt`.
- Jeder Versuch erzeugt eine eigene `daily_quiz_log`-Zeile (frische `logId`).

### Vorgemerkt (§12 Streak, Folge-Session)
`daily_quiz_log` hat seit BUG-012 mehrere Zeilen pro Tag → Streak muss **Tage statt
Zeilen** zählen und entscheiden, ob abgebrochene `success=false`-Versuche mitzählen.

---

## Session 2026-06-27 — Session A „Fundament": Doku-Struktur + globaler Header/Footer

**Branch:** `feature/fundament` (von `dev`). Zwei Commits; Merge nach `dev` (`--no-ff`)
im Anschluss an diesen Report-Commit. Kein push, kein `main`, kein Deploy.

**Commits:**
- `6eeeb1b` chore(docs): Doku-Struktur & Archiv einführen
- `223c03e` refactor(layout): Header und Footer global per layout.js injizieren

### Bereich 1 — Doku-Struktur & Archiv (Commit `6eeeb1b`)
- `git mv` (Historie erhalten): `docs/KI_VORLAGE.md` + `data/import/MIGRATION_PROMPT.md`
  → `agent/prompts/`; `docs/CLAUDE.md` → `agent/CLAUDE.md`;
  `docs/DATENMIGRATION.md` → `docs/archive/` (abgelöst durch `DATA_MIGRATION_V2.md`).
- Neu: `docs/README.md` (Wegweiser docs/ ↔ docs/archive/ ↔ agent/).
- `README.md`: Link + Struktur-Prosa auf `agent/CLAUDE.md` angepasst (einziger harter
  Verweis). Prosa-/Kommentar-Erwähnungen anderswo bewusst out of scope.
- `agent/` bleibt getrackt (Entscheidung Patsche); `.gitignore` unverändert.

### Bereich 2 — Globaler Header + Footer (Commit `223c03e`)
- Neu `src/js/layout.js`: `renderTopbar` + Helfer (`topbarGrundgeruest`, `aktiverNav`,
  `schliesseNav`, `verdrahteHamburger`, `topbarInitialen`) **1:1 aus `level.js`
  verschoben** — nur die zwei Cross-Modul-Aufrufe auf `Level.getUserStats` /
  `Level.berechneFortschritt` umgestellt; neu `Layout.renderFooter()`.
- `level.js`: −158 (Topbar-Block + Modul-State + `renderTopbar`-Export entfernt;
  Daten-/Logik-Funktionen byte-identisch unberührt).
- 16 HTML-Seiten: inline `<footer>` → Platzhalter `<footer class="site-footer"
  id="site-footer">`; `Level.→Layout.renderTopbar`; `<script src="/js/layout.js">`
  nach `level.js` (auf `index.html` nach `auth.js`).

### Entscheidungen
- **Footer-Auto-Run statt Pro-Seite-Aufruf:** `renderFooter()` läuft per
  `DOMContentLoaded` (Muster wie `icons.js`). Grund: auf allen Seiten steht `<footer>`
  *nach* den Scripts → ein synchroner Aufruf liefe vor dem Parsen des Footer-Elements
  ins Leere (auf `index.html` garantiert leer). Auto-Run ist robust + DRY; `renderFooter`
  bleibt zusätzlich exportiert.
- **`index.html`-Sonderfall:** Footer ja (Auto-Run), Topbar nein; `layout.js` nach
  `auth.js` (also nach `supabase.js` → `SupabaseClient` definiert), **kein** `level.js`,
  **kein** `renderTopbar`-Aufruf → kein `undefined`.
- **Session-Split:** A = Fundament (dieser Branch), B = UI-Schliff (Bereich 3–6) —
  damit `renderTopbar` nicht in derselben Session verschoben **und** umgebaut wird.

### Verifikation
- `node --check` für `layout.js` + `level.js` grün; grep: 0 zurückgelassene Topbar-
  Referenzen in `level.js`, 0 verbliebene `Level.renderTopbar` in HTML, 16× Footer-
  Platzhalter, 15× korrekte Script-Reihenfolge (index ausgenommen).
- Live-Smoke-Test (Server `lernhub`, User `schueler1`), Console überall clean:
  - **dashboard** — Topbar via `Layout.renderTopbar` (Cross-Modul `Level.*` +
    `Icons.render`, 3 SVGs, Leiste 10 %) + Footer ✓.
  - **pos.html** — Topbar + Footer + Fach-Inhalt (3 Stat-Pills, 11 Themen-Karten) ✓.
  - **index.html** (logged-out) — Footer via Auto-Run **ohne** `level.js`,
    `Level` undefined, **kein** Fehler ✓.

### Offen — Session B (UI-Schliff, Bereich 3–6; eigene Session, neuer Branch auf `dev`)
- **Bereich 3 Header-Umbau:** „Profil" als echter Nav-Link (Avatar-Initialen raus);
  Mobile-Hamburger zeigt alle sechs Punkte (inkl. Team/Rangliste/Profil); Energie-Pill
  „X/5"; Total-XP-Pill raus; Fortschritt (EP-Text) in die Leiste.
- **Bereich 4 Dashboard:** „Zuletzt gelernt" aus `quiz_results` — Sortier-Spalte heißt
  **`erstellt_at`** (NICHT `created_at`!), und `quiz_results` enthält **nur Themen-Quiz**
  (Herausforderung liegt in `daily_quiz_log`, ohne `thema_id`). „+5 morgen" raus →
  sichtbarer Trophäen-Tausch-Link (`tauschen.html` ist sonst verwaist, nur aus
  `tagesquiz.html` verlinkt).
- **Bereich 5 Mobile-First-Politur** + **Bereich 6 Buttons/Icons vereinheitlichen**
  (Fach-Stat-Pills in `renderStatPill` brauchen Icons via `Icons.render`).
- Baut komplett auf dem jetzt stabilen `layout.js` auf.

---

*Bericht auto-generiert am Ende der Session. Alle Pfade relativ zum Projekt-Root.*
