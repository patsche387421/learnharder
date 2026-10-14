# CLAUDE.md — frontend/ (Rolle: Frontend-Entwickler:in)

Vue-3-PWA für Schüler:innen und Lehrkräfte. Allgemeine Regeln: `../CLAUDE.md`.

## Stack

Vue 3 (`<script setup>`), Vite, Pinia, Vue Router, Vitest. Geplant: vue-i18n, PWA-Plugin, Playwright.
Node 20 reicht. Vitest 5 und jsdom 30 brauchen Node 22, deshalb vorerst Vitest 4 und jsdom 29.

## Start

```
npm --prefix frontend install
npm --prefix frontend run dev      # http://localhost:5173
npm --prefix frontend run build
npm --prefix frontend run test           # Komponenten-Tests (Vitest), einmal durchlaufen
npm --prefix frontend run check:tokens   # tokens.css gegen tokens.json prüfen
```

## Struktur (Ziel)

```
src/
  styles/       Design-System (Wahrheit) → styles/CLAUDE.md
  assets/icons/ subjects/, game/ (illustriert, als <img>), ui/ (einfarbig, currentColor). Regeln im README.md je Ordner
  components/   wiederverwendbare UI-Bausteine (nutzen lh-Klassen)
  views/        Seiten (eine pro Route)
  stores/       Pinia-Stores (auth, user, quiz)
  api/          einziger Ort, der mit dem Laravel-Backend spricht
  router/
public/assets/fonts/   selbst gehostete Schriften (keine CDNs)
```

## Regeln

- Styling nur über Tokens und `lh-`-Klassen aus `src/styles/`. Keine Hex-Werte in Komponenten. Komponentenspezifisches CSS als `<style scoped>`.
- `Lh`/`lh-` steht für learnharder: Namensraum für das Design-System, damit nichts mit Filament, Browser-Standards oder Bibliotheken kollidiert. `LhButton` passt 1:1 zu `lh-btn`.
- Jede `Lh`-Komponente in `src/components/ui/` hat eine `Lh*.test.js` daneben. `npm run build` prüft nur, was eine echte Route nutzt, die Tests prüfen alle Bausteine. Icons in Tests über `icons['name']` vergleichen, nicht über den Dateinamen (kleine SVGs werden als Data-URL eingebettet).
- Mobile first: Basis ist das Handy, Erweiterungen per `min-width`. Tap-Ziele mindestens `var(--tap-target)` (48px).
- Der Client vergibt nie selbst XP, Brainpower oder Streaks. Er zeigt an, was die API liefert.
- Quiz starten und abschließen nur online. Theorie darf offline aus dem Cache kommen.
- Keine Secrets im Frontend. Konfiguration über `import.meta.env.VITE_*`.
- `public/` wird 1:1 ausgeliefert, deshalb dort keine CLAUDE.md und keine internen Notizen.

## Stand (2026-10-14)

13 `Lh`-Bausteine in `src/components/ui/`, jeder mit Test. `App.vue` legt den Rahmen (`LhAppLayout`) um alle Seiten, die fünf Hauptziele sind noch `PlaceholderView`. Level, Rang und Prestige im Rahmen sind fest eingetragen, bis ein User-Store sie von der API holt. `/styleguide` zeigt alle Tokens, Icons und Bausteine (nur in der Entwicklung). Die Schrift Atkinson Hyperlegible Next fehlt noch (`public/assets/fonts/AtkinsonHyperlegibleNext-Variable.woff2`), bis dahin greift die Systemschrift.
