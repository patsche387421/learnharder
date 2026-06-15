# CLAUDE.md — Projektregeln für den KI-Agenten

Diese Datei definiert die verbindlichen Regeln für jede Änderung an diesem Projekt.
Bei Konflikten zwischen Nutzerwunsch und diesen Regeln: kurz nachfragen.

## Projektbeschreibung

`lernhub-demo` ist eine **lokale Lernwebsite** für HTL-Schüler:innen. Login,
persönliches Dashboard, Fächerübersicht, Fach-Detailseiten (Themenübersicht) und
Themen-Inhaltsseiten (Theorie + Quiz oder interaktives Tool).

- **Kein Framework** (kein React/Vue/Svelte etc.)
- **Kein Build-Step** (kein Bundler, kein Transpiler, kein npm-Build)
- Start direkt über einen statischen Server:

  ```bash
  npx serve src
  ```

  Danach `http://localhost:3000` öffnen. Login mit Testuser
  `test@lernhub.htl` / `lernhub123`.

> Wichtig: Wegen `fetch()` auf die JSON-Dateien muss über einen Server geöffnet
> werden – **nicht** per `file://` (sonst CORS-Fehler).

## Stack

- **Vanilla HTML, CSS und JavaScript** – sonst nichts.
- **Keine externen Libraries** (kein jQuery, kein CDN-Skript, keine Web-Fonts von
  Drittanbietern) – außer solchen, die **hier ausdrücklich erlaubt** werden.
- Aktuell erlaubte externe Abhängigkeiten:
  - **Supabase JS Client v2** (`@supabase/supabase-js@2`, CDN: `cdn.jsdelivr.net`) – Backend-Client für Auth und Datenbank (siehe Abschnitt „Backend (Supabase)").

Wird eine neue Abhängigkeit benötigt, zuerst hier eintragen lassen, dann nutzen.

## Navigationsstruktur (5 Ebenen)

```
index.html
  └── dashboard.html          Persönliches Dashboard (Begrüßung + Stats)
        └── faecher.html      Fächerübersicht (Karten aus manifest.json)
              └── <fach>.html Fach-Themenübersicht (pos.html, dbi.html …)
                    └── fach.html?fach=<thema-id>   Themen-Inhalt
```

## Ordnerstruktur

Das gesamte Frontend liegt unter `src/` (Netlify-Publish-Root). Alle absoluten
Pfade (`/css/…`, `/js/…`, `/assets/data/…`) sind relativ zu `src/` zu lesen.

```
lernhub-demo/
├── docs/                   Projektdokumentation
│   ├── CLAUDE.md             Diese Projektregeln
│   ├── DESIGN_GUIDELINES.md  Design-System: Tokens, Theme, Typografie, Reset
│   └── KI_VORLAGE.md         Prompts zum Erzeugen und Migrieren von Lerninhalten
├── scripts/
│   └── gen-config.js       Netlify-Build: generiert js/config.js aus Env-Vars
├── sql/migrations/         Supabase-Schema + Seed-Testuser
├── data/import/            Rohdaten-Archiv (noch nicht in manifest.json eingetragen)
└── src/                    ← Publish-Root (alles Web-Auslieferbare)
    ├── .htaccess             Cache-Header für JSON-Dateien (Hosting)
    ├── index.html            Login-Seite
    ├── dashboard.html        Persönliches Dashboard (geschützt)
    ├── faecher.html          Fächerübersicht (geschützt)
    ├── pos.html … syp.html   Fach-Themenübersichten (geschützt)
    ├── fach.html             Themen-Inhalt: Theorie+Quiz oder Tool-Mount (?fach=…)
    ├── js/                   Logik-Module (IIFE-Pattern)
    │   ├── config.js           Supabase URL + Anon Key (GITIGNORED, Build-generiert)
    │   ├── config.example.js   Vorlage für config.js (ohne echte Keys)
    │   ├── supabase.js         Initialisiert SupabaseClient aus config.js
    │   ├── auth.js             Auth-Modul: Login/Logout via Supabase Auth
    │   │                       (signInWithPassword, signOut, getSession, requireLogin)
    │   ├── stats.js            Stats-Modul: echte DB-Queries
    │   │                       (fach_stats, thema_progress, quiz_results)
    │   ├── app.js              App-Modul: JSON laden, alle Navigationsebenen rendern
    │   └── <fach>/<thema>.js   Tool-Module (z. B. pos/datenstrukturen.js)
    ├── css/                  Styles
    │   ├── fonts.css           @font-face für lokale Schriften (Space Grotesk/Mono)
    │   ├── tokens.css          Design-Tokens (:root Custom Properties)
    │   ├── style.css           Komponenten-/Layout-Styles
    │   └── <fach>/<thema>.css  Tool-spezifische Styles (z. B. pos/datenstrukturen.css)
    ├── assets/data/          JSON-Lerninhalte und Manifest
    │   ├── manifest.json       Fächer-Index mit verschachtelten Themen (Single Source of Truth)
    │   ├── <fach>/<thema>_{theorie,fragen,antworten}.json
    │   └── schema/             JSON-Schemas für VS-Code-Validierung
    ├── assets/fonts/         Lokale WOFF2-Schriften (Space Grotesk, Space Mono)
    └── assets/logo.svg       Logomark „Bolt Brain"
```

**Reihenfolge der Stylesheets:** Immer `fonts.css` → `tokens.css` → `style.css`,
damit Schriften und Custom Properties vor den Komponenten-Styles verfügbar sind:

```html
<link rel="stylesheet" href="/css/fonts.css" />
<link rel="stylesheet" href="/css/tokens.css" />
<link rel="stylesheet" href="/css/style.css" />
```

## Manifest-Struktur

`data/manifest.json` ist die **einzige** Quelle für Fächer und Themen.
Kein Fach und kein Thema existiert ohne Manifest-Eintrag.

```jsonc
{
  "faecher": [
    {
      "id": "pos",
      "name": "POS",
      "vollname": "Programmieren und Software-Engineering",
      "icon": "💻",
      "beschreibung": "…",
      "seite": "/pos.html",           // Pflicht: Link zur Fach-Seite
      "themen": [
        // Standard-Thema (Theorie + Quiz):
        {
          "id": "pos-variablen",
          "name": "Variablen",
          "icon": "📦",
          "beschreibung": "…",
          "typ": "fach"
        },
        // Tool-Thema (interaktives Modul):
        {
          "id": "pos-datenstrukturen",
          "name": "Datenstrukturen",
          "icon": "⚙",
          "beschreibung": "…",
          "typ": "tool",
          "toolScript": "/js/pos-datenstrukturen.js",
          "toolStyle":  "/css/pos-datenstrukturen.css",
          "toolData":   "/data/pos-datenstrukturen_data.json",
          "toolQuiz":   "/data/pos-datenstrukturen_quiz.json"
        }
      ]
    }
  ]
}
```

## Themen-Typen

| `typ`    | Benötigte Dateien                                        | Geladen durch             |
|----------|----------------------------------------------------------|---------------------------|
| `"fach"` | `<id>_theorie.json`, `<id>_quiz.json`                    | `App.renderFach()`        |
| `"tool"` | `toolScript`, `toolStyle`, `toolData`, `toolQuiz`        | `App.renderFach()` (dynamisch) |

Tool-Module exportieren `window.<ModulName>` und implementieren `.mount({root, dataUrl, quizUrl})`.
CSS von Tool-Modulen wird unter einem eigenen Scope-Selektor (z. B. `.dst`) geschrieben.

## Backend (Supabase)

Auth und Lernstatistiken laufen über **Supabase** (Postgres + Auth). Es gibt
keinen eigenen API-Server – das Frontend spricht direkt mit Supabase über den
JS-Client.

- **Client-Init:** `src/js/supabase.js` erzeugt `SupabaseClient.client` aus den
  Werten in `src/js/config.js` (URL + Anon Key). `config.js` ist gitignored und
  wird im Netlify-Build von `scripts/gen-config.js` aus den Env-Vars
  `SUPABASE_URL` / `SUPABASE_ANON_KEY` generiert.
- **Auth** (`src/js/auth.js`): Login via `signInWithPassword`, Logout via
  `signOut({ scope: 'local' })`, Session-Caching über `getSession` +
  `onAuthStateChange`, Seitenschutz über `requireLogin()` (async Guard).
- **Datenzugriff** (`src/js/stats.js`): direkte Tabellen-Queries gegen drei Tabellen.
  **Row Level Security** stellt sicher, dass jeder User nur seine eigenen Zeilen
  liest/schreibt (Filter `user_id = auth.uid()`).

| Tabelle          | Zweck                                                         |
|------------------|---------------------------------------------------------------|
| `fach_stats`     | Aggregierte Fach-Statistik (Themen bearbeitet, Quiz-Punkte)   |
| `thema_progress` | Pro-Thema-Fortschritt (abgeschlossen, letzter Score)          |
| `quiz_results`   | Verlauf einzelner Quiz-Versuche (richtig/gesamt/score)        |

Schema und Testuser liegen als Migrations in `sql/migrations/`. Testuser-Passwort:
`lernhub123` (z. B. `test@lernhub.htl`).

## Coding-Konventionen

- **Deutsche Kommentare** im gesamten Code.
- **Sprechende Variablennamen** (z. B. `aktuelleFrage`, `richtigeAntworten` statt `x`, `n`).
- **Kein minifizierter Code** – Lesbarkeit hat Vorrang, sauberes Einrücken.
- **IIFE-Modul-Pattern** für alle JS-Module:

  ```js
  const ModulName = (() => {
    // private Hilfsfunktionen …
    return { /* öffentliche API */ };
  })();
  ```

- **Absolute Pfade** (`/css/…`, `/data/…`, `/js/…`) – immer, nie relative Pfade.
  (Funktioniert auf Root-Domain-Hosting. GitHub-Pages-Subpfade werden nicht unterstützt.)

## Git-Konventionen

**Conventional Commits auf Deutsch.** Format: `typ: Beschreibung im Imperativ/Perfekt`.

Erlaubte Typen:

| Typ        | Wofür                                            |
|------------|--------------------------------------------------|
| `feat`     | Neue Funktion                                    |
| `fix`      | Fehlerbehebung                                   |
| `docs`     | Nur Dokumentation                                |
| `style`    | Formatierung/CSS ohne Logikänderung              |
| `refactor` | Umbau ohne Verhaltensänderung                    |
| `chore`    | Wartung, Konfiguration, Aufräumarbeiten          |

Beispiele:

- `feat: POS-Datenstrukturen-Tool hinzugefügt`
- `fix: Fallback-Bug in renderFach behoben`
- `docs: KI-Vorlage aktualisiert`

## Branch-Strategie

```
main  (stabil)   ←  dev  (aktiv)  ←  feature/xxx  (neue Features)
```

- **`main`** – immer lauffähig/stabil. Keine direkten Experimente.
- **`dev`** – aktiver Entwicklungsstand.
- **`feature/xxx`** – pro neuem Feature ein eigener Branch, danach zurück nach `dev`.

## Verbote

- **Keine API-Keys oder Secrets im Code** (auch nicht „nur zum Testen").
- **Kein Inline-JS in HTML** im Sinne von `onclick="…"`-Attributen oder Logik in
  Attributen. JS gehört in `js/`-Module bzw. saubere `<script>`-Verdrahtung.
- **Kein `!important` in CSS.** Spezifität sauber über Selektoren lösen.
- **Keine externen Fonts oder CDN-Skripte** ohne explizite Freigabe in diesem Dokument.

## Hosting

Das Projekt besteht aus reinen statischen Dateien und läuft auf jedem Apache/Nginx-Host.
**Deployment immer auf die Root eines (Sub-)Domains** — nicht in einen Unterordner,
da alle Pfade absolut von `/` starten (z. B. `/css/tokens.css`, `/data/manifest.json`).

Empfohlene Hosts (beide technisch identisch kompatibel):

| Kriterium       | World4You              | Hostinger              |
|-----------------|------------------------|------------------------|
| Standort/DSGVO  | Österreich ✅          | Global CDN             |
| Apache/htaccess | ✅                     | ✅                     |
| Preis           | Etwas höher            | Günstiger              |
| Empfehlung      | HTL/AT-Projekt         | Günstigste Option      |

**Pflicht-Datei `.htaccess` im Projektstamm** (verhindert veraltete JSON-Caches):

```apache
# Cache-Control für JSON-Lerninhalte – Änderungen werden sofort sichtbar
<FilesMatch "\.json$">
  Header set Cache-Control "no-cache, must-revalidate"
</FilesMatch>
```

## Hinweis: `@view-transition`

Das Projekt nutzt `@view-transition { navigation: auto; }` für sanfte
Seitenübergänge. Das ist aktuell **Chromium-only**.

- In Firefox und älteren Browsern: **graceful degradation** – die Navigation
  funktioniert normal, nur ohne Übergangsanimation.
- Niemals Funktionalität von View Transitions abhängig machen.

## Design-System (seit Juni 2026)

Vor JEDER UI-Änderung:
1. Prüfe docs/design/components/ und docs/design/pages/
   auf bestehende Mockups oder Spezifikationen
2. Falls vorhanden: halte dich daran
3. Verwende ausschließlich Tokens aus src/css/tokens.css
4. Falls neue Tokens nötig: in tokens.css ergänzen, 
   in docs/DESIGN_GUIDELINES.md dokumentieren

## Design-Inbox-Workflow

Wenn Dateien in docs/design/INBOX/ liegen:
1. Jede Datei analysieren (Seite oder Komponente?)
2. Mit aussagekräftigem Namen umbenennen
3. In docs/design/components/ oder docs/design/pages/ verschieben
4. Gleichnamiges .md daneben anlegen (siehe INBOX/README.md)

## Bug-Tracking

Alle bekannten Bugs werden in docs/BUGS.md gepflegt.
Bei neuen Bugs: dort ergänzen, nicht im Code-Kommentar.
