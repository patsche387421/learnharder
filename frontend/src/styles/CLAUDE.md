# CLAUDE.md — frontend/src/styles/ (Rolle: Design-System)

**Diese Dateien sind die Design-Wahrheit von V2** (festgelegt am 2026-10-14). Quelle ist das Design-System-Artefakt
https://claude.ai/artifact/89Tg8wEdFdFDMUNBWG2YNb. Dort wird das Design geändert, hier liegt die Kopie für den Code.
Regeln und Komponenten-Anleitungen: `docs/design/`. Icons: `src/assets/icons/`.

| Datei | Inhalt |
|---|---|
| `tokens.json` | Quelle aller Tokens (Kopie aus dem Artefakt). `tokens.css` muss dieselben Werte haben |
| `fonts.css` | `@font-face` für Atkinson Hyperlegible Next (Text) und Space Mono (Zahlen/Code) |
| `tokens.css` | Nur CSS-Variablen: Farben hell/dunkel, Schrift, Abstände, Radien, Layout, Text-Klassen |
| `bundle.css` | Komponenten mit `lh-`-Präfix (Button, Badge, Karte, Antwort, Feedback, Tabbar, Themen, Level-up …) |
| `style.css` | Eigene Basis-Stile der App (Seitenrand, Seitenhintergrund, `color-scheme`). Nicht aus dem Artefakt, nur hier gepflegt |

**Reihenfolge beim Einbinden ist Pflicht:** fonts → tokens → bundle → style (siehe `src/main.js`).

## Regeln

- Neue Farben, Abstände und Radien zuerst als Token im Artefakt anlegen, dann `tokens.json` und `tokens.css` hier nachziehen, dann verwenden.
- Theme: Hell ist Standard. Dunkel über `<html data-theme="dark">` oder die Systemeinstellung. Jede neue Farbe braucht einen Wert für beide Themes.
- Neue Komponenten-Klassen bekommen das `lh-`-Präfix und eine BEM-artige Struktur (`lh-block__element--modifier`). Ausnahme: die Text-Stil-Klassen aus `tokens.css` (`.display`, `.h1` … `.question`, `.code`) heißen bewusst wie die Text-Stile in `tokens.json`.
- Kein `!important`, keine externen Fonts oder CDNs.
- Kontrast mindestens WCAG AA. Fokus immer sichtbar (`:focus-visible`).
- Wenn sich das Artefakt ändert: `tokens.json`, `bundle.css`, `docs/design/` und Icons hier ersetzen, den Diff prüfen und Änderungen an Token-Namen in Komponenten nachziehen. Das `@import` von Google Fonts in der `bundle.css` des Artefakts wird dabei nicht übernommen. Danach `npm run check:tokens`: prüft, ob `tokens.css` zu `tokens.json` passt.
- Schriften: `AtkinsonHyperlegibleNext-Variable.woff2` (Version 2.001, Achse `wght` 200–800) ist aus der Variable-TTF von Google Fonts mit `fontTools` nach WOFF2 umgewandelt. Copyright und OFL-Lizenz stecken in der Datei (`name`-Tabelle). Neue Version: genauso umwandeln, Dateiname bleibt.
