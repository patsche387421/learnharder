# Content-Icons — Fächer & Themen

Illustrative, **bunte** SVG-Icons für Fach- und Themen-Karten. Getrennt von den
monochromen UI-Linien-Icons in `src/js/icons.js` (anderes Render-Modell).

## Ablage & Namen

| Ordner | Inhalt | Dateiname |
|--------|--------|-----------|
| `faecher/` | ein Icon je Fach   | `<fach-id>.svg`  (z. B. `pos.svg`, `dbi.svg`) |
| `themen/`  | ein Icon je Thema  | `<thema-id>.svg` (z. B. `pos-variablen.svg`, `dbi-erd-lesen.svg`) |

Die `id` stammt aus `src/assets/data/manifest.json` (Feld `id`) und ist global
eindeutig — keine Kollision zwischen Fächern.

Die `.gitkeep`-Dateien sind nur Platzhalter, damit Git die leeren Ordner trackt.
Sie können entfernt werden, sobald echte SVGs abgelegt sind.

## Wie ein Icon eingebunden wird

`manifest.json` enthält im Feld `icon` den **Pfad** zur SVG, z. B.
`/assets/icons/faecher/pos.svg`. Gerendert wird über `ContentIcons.render(...)`
(`src/js/content-icons.js`) als `<img src>`. **Kein Code muss geändert werden** —
eine neue Datei unter dem passenden `<id>.svg` ersetzt automatisch den Platzhalter.

**Fehlt die Datei**, zeigt die Karte einen Fallback-Badge (erster Buchstabe des
Namens in farbigem Kreis). Das ist der Normalzustand, solange noch keine SVG
abgelegt wurde.

## Stil-Vorgaben

- **viewBox `0 0 96 96`** (illustratives Format, nicht 24×24 wie UI-Icons).
- **Bunte Gradienten** im Stil von `trophy`/`energy` aus `icons.js`.
- **Fix-farbig** — kein `currentColor`. Theme-Fähigkeit entsteht über das
  Icon-Design selbst: Farben müssen auf **Dark- und Light-Hintergrund** lesbar
  bleiben (genug Kontrast, keine reinweißen Flächen ohne Kontur).
- Selbst-enthalten: keine `<script>`-Tags, keine externen URLs, keine
  eingebetteten Web-Fonts oder Bilder. Gradienten, Filter und Patterns mit lokalen
  `<defs>` sind okay.

Ausführlicher Stil-Guide: `docs/design/components/icons-content.md`.

## Cache bei Updates

Wenn eine SVG unter gleichem Namen ersetzt wird, kann ein Versions-Suffix am Pfad
in `manifest.json` Browser dazu bringen, die neue Datei zu laden (z. B.
`/assets/icons/faecher/pos.svg?v=2`). Die `manifest.json` selbst wird via
`.htaccess` (`Cache-Control: no-cache` für `*.json`) ohnehin frisch geladen.
