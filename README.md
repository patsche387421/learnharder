# LernHub Demo

Lokale Lernwebsite für HTL-Schüler:innen. Fächerübersicht, Themen-Detailseiten mit Theorie und Quiz, interaktive Tool-Module.

## Starten

```bash
npx serve src
```

Dann `http://localhost:3000` öffnen. Login: `test` / `test123`

> `fetch()` auf JSON-Dateien erfordert einen HTTP-Server – nicht per `file://` öffnen.

## Stack

Vanilla HTML, CSS, JavaScript – kein Framework, kein Build-Step.

## Struktur

```
src/          Alle Web-Dateien (Publish-Root)
├── css/      Styles (tokens.css → style.css, Fach-Unterordner für Tool-CSS)
├── js/       Logik-Module (auth, stats, app; Fach-Unterordner für Tool-JS)
└── assets/
    └── data/ Lerninhalte (manifest.json, Fach-Unterordner mit Theorie/Fragen/Antworten)

docs/         Projektdokumentation (CLAUDE.md, DESIGN_GUIDELINES.md, …)
agent/        Claude Code Prompts und Automatisierungen
```

## Hosting

Kompatibel mit jedem statischen Host (Apache, Nginx, Netlify). Publish-Verzeichnis: `src/`.

Weitere Details: [docs/CLAUDE.md](docs/CLAUDE.md)
