# Content-Icons (Fächer & Themen)

**Typ:** Komponente / Stil-Guide
**Dateien:** `src/js/content-icons.js`, `src/assets/icons/{faecher,themen}/`

## 1. Zweck

Stil-Guide für die **bunten Content-Icons** der Fach- und Themen-Karten. Abzugrenzen
von den monochromen UI-Linien-Icons aus `icons.js` (siehe `icons.md`): jene sind
inline-SVG mit `currentColor` und folgen dem Theme; Content-Icons sind eigenständige
`<img>`-Dateien, **fix-farbig**, ohne Hydration.

`manifest.json` ist die **Single Source of Truth** — das Feld `icon` jedes Fachs/Themas
enthält den Pfad zur SVG. Kein Fach- oder Themen-Icon existiert ohne Manifest-Eintrag.

## 2. Architektur

Die SVGs liegen unter `src/assets/icons/faecher/<id>.svg` bzw.
`src/assets/icons/themen/<id>.svg`, wobei `<id>` 1:1 aus dem `id`-Feld der
`manifest.json` stammt (z. B. `pos.svg`, `pos-variablen.svg`, `dbi-erd-lesen.svg`).
Gerendert werden sie über `ContentIcons.render()` (aus `src/js/content-icons.js`) als
`<img src>`. Fehlt die Datei (HTTP 404) oder ist der Pfad ungültig, ersetzt ein
`onerror`-Handler das `<img>` durch einen **Buchstaben-Badge** (erster Buchstabe des
Namens im farbigen Kreis). Neue Icons brauchen daher **keine Code-Änderung** — Datei
ablegen genügt.

## 3. Stil-Vorgaben (Pflicht)

- **viewBox `0 0 96 96`** — Pflicht. (Hinweis: einige Bestandsdateien nutzen noch
  `0 0 80 80`; beim nächsten Touch auf 96×96 angleichen — siehe §9.)
- **Bunte Gradienten** im Stil von `trophy`/`energy` aus `icons.js`.
- **2–3 Farben** pro Icon; die jeweilige **Fach-Farbe** als roter Faden
  (vgl. `--fach-*` in `tokens.css`).
- **Lesbar auf Dark *und* Light** — genug Kontrast, keine reinweißen Flächen ohne
  Kontur.
- **Selbst-enthalten** — keine `<script>`-Tags, keine externen URLs, keine
  eingebetteten Web-Fonts oder Bilder. Gradienten/Filter/Patterns mit lokalen
  `<defs>` sind okay.
- **Gradient-IDs eindeutig benennen** (z. B. `posVarBoxFront`), damit mehrere Icons
  auf einer Seite nicht kollidieren.

## 4. Helper-API (`content-icons.js`)

```js
ContentIcons.render(value, label, opts)   // value=manifest-Pfad ODER Emoji/leer
ContentIcons.fallback(label, size)        // Buchstaben-Badge; vom onerror aufgerufen
ContentIcons.ersterBuchstabe(label)       // exponiert für Testbarkeit
```

- `value` beginnt mit einem gültigen `/assets/icons/`-Pfad → `<img>`; sonst → Badge.
- `label` → `alt`-Text **und** Quelle für den Fallback-Buchstaben.
- `opts.size` → CSS-Maß als **String**, Default `"3rem"`. Wird als
  `style="--icon-size: …"` gesetzt; Breite/Höhe und `font-size` leiten sich im CSS ab
  (`.content-icon` / `.content-icon-fallback`).
- `opts.className` → optionale Zusatzklasse am `<img>`.

Die **drei realen Render-Sites:**

```js
// Fach-Karte — app.js renderFaecher
ContentIcons.render(fach.icon, fach.name, { size: "3rem" })

// Themen-Karte — app.js renderFachSeite
ContentIcons.render(thema.icon, thema.name, { size: "3rem" })

// Profil-Zeile — profil.html renderFachZeile
ContentIcons.render(fach.icon, fach.name, { size: "1.25rem" })
```

Verwendete Tokens: `.content-icon-fallback` nutzt `--primary` (Hintergrund),
`--text-on-primary` (Schrift), `--font-display`, `--fw-bold`, `--radius-full`.

## 5. Security-Härtung

Defense-in-depth — robust für eine künftige Quelle der `icon`-Werte
(`manifest.json` → Supabase). Alles im Realcode von `render()`/`fallback()`:

- **Pfad-Whitelist** (RegEx): nur `^/assets/icons/[a-z0-9._/?=&-]+$` wird als `<img>`
  gerendert; alles andere fällt auf den Badge. Kein Attribut-Breakout via `"` oder `>`.
- **CSS-Maß-Whitelist**: `opts.size` muss `^[0-9.]+(?:rem|em|px|%)$` erfüllen, sonst
  Default `"3rem"`. Gilt für `render()` **und** `fallback()` (Letzteres wird auch vom
  `onerror` aufgerufen).
- **HTML-Escape**: `label` wird für das `alt`-Attribut escaped (`"` → `&quot;`).
- **`onerror`-Ausnahme**: Inline-`onerror` am `<img>` ist die einzige erlaubte
  Ausnahme zur „kein Inline-JS"-Regel (dokumentiert in `docs/CLAUDE.md` → Verbote).
  Das Label kommt zur Laufzeit aus `this.alt` (kein String-Injection im Attribut),
  und `this.outerHTML` ersetzt das `<img>` **einmalig** durch ein `<span>` → kein Loop.

## 6. Fallback-Badge-Logik

- **Trigger:** Datei fehlt (404) **oder** `value` ist kein gültiger Pfad
  (z. B. noch ein Emoji, leer, `undefined`).
- **Anzeige:** erster Buchstabe des `label` in farbigem Kreis (`.content-icon-fallback`).
- **Intelligenter Buchstabe** (`ersterBuchstabe`): ist das erste Zeichen ein Buchstabe,
  wird es genommen; ist es eine Ziffer/Sonderzeichen, der erste Buchstabe **nach einem
  Trenner** (` `, `-`, `:`, `_`); sonst trotzdem das erste Zeichen; leer → `"?"`.

```text
"POS"                    → "P"
"Variablen & Datentypen" → "V"
"1:1-Beziehungen"        → "B"   (Buchstabe nach ":")
"ANY, SOME und ALL"      → "A"
""                       → "?"
```

## 7. Cache-Bust

Beim Ersetzen einer SVG unter gleichem Namen kann ein Versions-Suffix am Pfad in
`manifest.json` den Browser-Cache umgehen:

```json
"icon": "/assets/icons/faecher/pos.svg?v=2"
```

Das Schema-Pattern erlaubt das optionale `?v=<zahl>`-Suffix. Die `manifest.json` selbst
wird via `.htaccess` (`Cache-Control: no-cache` für `*.json`) ohnehin frisch geladen.

## 8. Neues Icon hinzufügen (How-To)

1. SVG nach den Stil-Vorgaben (§3) erstellen.
2. Unter `src/assets/icons/faecher/<id>.svg` bzw. `…/themen/<id>.svg` ablegen —
   `<id>` exakt aus `manifest.json`.
3. In `manifest.json` den `icon`-Pfad eintragen (bei bestehenden Einträgen meist
   schon vorhanden).

**Kein Code-Update nötig** — bis die Datei existiert, zeigt die Karte den Badge.

## 9. Follow-ups

- **viewBox-Inkonsistenz:** einige Bestands-SVGs sind `0 0 80 80` statt `0 0 96 96` —
  beim nächsten Update angleichen.
- **DBI-Themen-Icons:** 21 Stück fehlen noch (bis dahin Badge-Fallback).
- **Leere Fächer:** NSVS, TINF, WIR, MEDT, SYP haben noch keine Themen-Inhalte in
  `manifest.json` — Themen-Icons folgen gemeinsam mit den Themen.
