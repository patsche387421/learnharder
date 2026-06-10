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
  npx serve
  ```

  Danach `http://localhost:3000` öffnen. Login mit Testuser `test` / `test123`.

> Wichtig: Wegen `fetch()` auf die JSON-Dateien muss über einen Server geöffnet
> werden – **nicht** per `file://` (sonst CORS-Fehler).

## Stack

- **Vanilla HTML, CSS und JavaScript** – sonst nichts.
- **Keine externen Libraries** (kein jQuery, kein CDN-Skript, keine Web-Fonts von
  Drittanbietern) – außer solchen, die **hier ausdrücklich erlaubt** werden.
- Aktuell erlaubte externe Abhängigkeiten: **keine.**

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

```
lernhub-demo/
├── CLAUDE.md               Diese Projektregeln
├── DESIGN_GUIDELINES.md    Design-System: Tokens, Theme, Typografie, Reset
├── DB_Integration_Guide.md Anleitung zur späteren Datenbankanbindung
├── index.html              Login-Seite
├── dashboard.html          Persönliches Dashboard (geschützt)
├── faecher.html            Fächerübersicht (geschützt)
├── pos.html                POS-Themenübersicht (geschützt)
├── dbi.html                DBI-Themenübersicht (geschützt)
├── nsvs.html               NSVS-Themenübersicht (geschützt)
├── tinf.html               TINF-Themenübersicht (geschützt)
├── wir.html                WIR-Themenübersicht (geschützt)
├── medt.html               MEDT-Themenübersicht (geschützt)
├── syp.html                SYP-Themenübersicht (geschützt)
├── fach.html               Themen-Inhalt: Theorie+Quiz oder Tool-Mount (geschützt, ?fach=…)
├── .htaccess               Cache-Header für JSON-Dateien (Hosting)
├── data/                   JSON-Lerninhalte und Manifest
│   ├── manifest.json         Fächer-Index mit verschachtelten Themen (Single Source of Truth)
│   ├── KI_VORLAGE.md         Prompts zum Erzeugen und Migrieren von Lerninhalten
│   ├── <thema-id>_theorie.json   Theorieblöcke für Standard-Themen
│   ├── <thema-id>_quiz.json      Quiz-Fragen für Standard-Themen
│   ├── <thema-id>_data.json      Datensatz für Tool-Themen
│   └── schema/               JSON-Schemas für VS-Code-Validierung
│       ├── manifest.schema.json
│       ├── theorie.schema.json
│       └── quiz.schema.json
├── js/                     Logik-Module (IIFE-Pattern)
│   ├── auth.js               Auth-Modul: Login/Logout via sessionStorage, Seitenschutz
│   ├── stats.js              Stats-Modul: Platzhalter für DB-Anbindung
│   ├── app.js                App-Modul: JSON laden, alle Navigationsebenen rendern
│   └── <thema-id>.js         Tool-Module (z. B. pos-datenstrukturen.js)
├── css/                    Styles
│   ├── tokens.css            Design-Tokens (:root Custom Properties)
│   ├── style.css             Komponenten-/Layout-Styles
│   └── <thema-id>.css        Tool-spezifische Styles (z. B. pos-datenstrukturen.css)
└── assets/                 Bilder/Medien (bei Bedarf anlegen)
```

**Reihenfolge der Stylesheets:** `tokens.css` wird **IMMER als erstes `<link>`
vor `style.css`** eingebunden, damit die Custom Properties verfügbar sind:

```html
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
