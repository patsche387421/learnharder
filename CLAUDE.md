# CLAUDE.md — Projektregeln für den KI-Agenten

Diese Datei definiert die verbindlichen Regeln für jede Änderung an diesem Projekt.
Bei Konflikten zwischen Nutzerwunsch und diesen Regeln: kurz nachfragen.

## Projektbeschreibung

`lernhub-demo` ist eine **lokale Lernwebsite**. Login, Fächerübersicht und
Fach-Detailseiten (Theorie + Quiz) für Schüler:innen.

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

## Ordnerstruktur

```
lernhub-demo/
├── CLAUDE.md              Diese Projektregeln
├── DESIGN_GUIDELINES.md   Design-System: Tokens, Theme, Typografie, Reset
├── index.html             Login-Seite
├── dashboard.html         Persönliches Dashboard: Begrüßung, Stats, CTA (geschützt)
├── faecher.html           Fächerübersicht: Karten aus manifest.json (geschützt)
├── pos.html               POS-Themenübersicht (geschützt)
├── dbi.html               DBI-Themenübersicht (geschützt)
├── nsvs.html              NSVS-Themenübersicht (geschützt)
├── tinf.html              TINF-Themenübersicht (geschützt)
├── wir.html               WIR-Themenübersicht (geschützt)
├── medt.html              MEDT-Themenübersicht (geschützt)
├── syp.html               SYP-Themenübersicht (geschützt)
├── fach.html              Themen-Inhalt: Theorie+Quiz oder Tool-Mount (geschützt, ?fach=…)
├── DB_Integration_Guide.md  Anleitung zur späteren Datenbankanbindung
├── data/                  JSON-Lerninhalte und Manifest
│   ├── manifest.json        Fächer-Index mit verschachtelten Themen
│   └── <id>_*.json          Themen-Inhalte nach Konvention (theorie/quiz/data)
├── js/                    Logik-Module (IIFE-Pattern)
│   ├── auth.js              Auth-Modul: Login/Logout via sessionStorage, Seitenschutz
│   ├── stats.js             Stats-Modul: Platzhalter für DB-Anbindung (siehe DB_Integration_Guide.md)
│   └── app.js               App-Modul: JSON laden, alle drei Navigationsebenen rendern
├── css/                   Styles
│   ├── tokens.css           Design-Tokens (:root Custom Properties)
│   └── style.css            Komponenten-/Layout-Styles
└── assets/                Bilder/Medien (bei Bedarf anlegen, existiert noch nicht)
```

**Reihenfolge der Stylesheets:** `tokens.css` wird **IMMER als erstes `<link>`
vor `style.css`** eingebunden, damit die Custom Properties verfügbar sind:

```html
<link rel="stylesheet" href="/css/tokens.css" />
<link rel="stylesheet" href="/css/style.css" />
```

## Coding-Konventionen

- **Deutsche Kommentare** im gesamten Code.
- **Sprechende Variablennamen** (z. B. `aktuelleFrage`, `richtigeAntworten` statt `x`, `n`).
- **Kein minifizierter Code** – Lesbarkeit hat Vorrang, sauberes Einrücken.
- **IIFE-Modul-Pattern** für JS-Module, wie bei `Auth` und `App`:

  ```js
  const ModulName = (() => {
    // private Hilfsfunktionen …
    return { /* öffentliche API */ };
  })();
  ```

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

- `feat: Login-Seite hinzugefügt`
- `fix: JSON-Ladefehler behoben`
- `docs: CLAUDE.md aktualisiert`

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

## Hinweis: `@view-transition`

Das Projekt nutzt `@view-transition { navigation: auto; }` für sanfte
Seitenübergänge. Das ist aktuell **Chromium-only**.

- In Firefox und älteren Browsern: **graceful degradation** – die Navigation
  funktioniert normal, nur ohne Übergangsanimation.
- Niemals Funktionalität von View Transitions abhängig machen.
