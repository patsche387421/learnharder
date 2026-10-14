# CLAUDE.md — frontend/src/styles/ (Rolle: Design-System)

**Diese drei Dateien sind die Design-Wahrheit von V2** (festgelegt am 2026-10-14). Quelle: das Claude-Design-Artefakt, laut Header „generiert aus tokens.json“.

| Datei | Inhalt |
|---|---|
| `fonts.css` | `@font-face` für Atkinson Hyperlegible Next (Text) und Space Mono (Zahlen/Code) |
| `tokens.css` | Nur CSS-Variablen: Farben hell/dunkel, Schrift, Abstände, Radien, Layout, Text-Klassen, Übergangs-Aliase |
| `bundle.css` | Komponenten mit `lh-`-Präfix (Button, Badge, Karte, Antwort, Feedback, Tabbar, Themen, Level-up …) |

**Reihenfolge beim Einbinden ist Pflicht:** fonts → tokens → bundle (siehe `src/main.js`).

## Regeln

- Neue Farben, Abstände und Radien zuerst als Token in `tokens.css` anlegen, dann verwenden.
- Theme: Hell ist Standard. Dunkel über `<html data-theme="dark">` oder die Systemeinstellung. Jede neue Farbe braucht einen Wert für beide Themes.
- Neue Komponenten-Klassen bekommen das `lh-`-Präfix und eine BEM-artige Struktur (`lh-block__element--modifier`).
- Kein `!important`, keine externen Fonts oder CDNs.
- Kontrast mindestens WCAG AA. Fokus immer sichtbar (`:focus-visible`).
- Wenn das Design-Artefakt neu exportiert wird: Dateien hier ersetzen, den Diff prüfen und Änderungen an Token-Namen in Komponenten nachziehen.

## Offen

- Die Quelle `tokens.json` liegt nicht im Repo.
- `AtkinsonHyperlegibleNext-Variable.woff2` fehlt in `frontend/public/assets/fonts/`.
- `tokens.css` §5 (Übergangs-Aliase `--fach-*`, `--primary` …) löschen, sobald nichts mehr darauf verweist. Das Backend speichert in `subjects.color_token` noch `--fach-*`-Namen.
