# CLAUDE.md — frontend/ (Rolle: Frontend-Entwickler:in)

Vue-3-PWA für Schüler:innen und Lehrkräfte. Allgemeine Regeln: `../CLAUDE.md`.

## Stack

Vue 3 (`<script setup>`), Vite, Pinia. Geplant: Vue Router, vue-i18n, PWA-Plugin, Vitest, Playwright.

## Start

```
npm --prefix frontend install
npm --prefix frontend run dev      # http://localhost:5173
npm --prefix frontend run build
```

## Struktur (Ziel)

```
src/
  styles/       Design-System (Wahrheit) → styles/CLAUDE.md
  components/   wiederverwendbare UI-Bausteine (nutzen lh-Klassen)
  views/        Seiten (eine pro Route)
  stores/       Pinia-Stores (auth, user, quiz)
  api/          einziger Ort, der mit dem Laravel-Backend spricht
  router/
public/assets/fonts/   selbst gehostete Schriften (keine CDNs)
```

## Regeln

- Styling nur über Tokens und `lh-`-Klassen aus `src/styles/`. Keine Hex-Werte in Komponenten. Komponentenspezifisches CSS als `<style scoped>`.
- Mobile first: Basis ist das Handy, Erweiterungen per `min-width`. Tap-Ziele mindestens `var(--tap-target)` (48px).
- Der Client vergibt nie selbst XP, Brainpower oder Streaks. Er zeigt an, was die API liefert.
- Quiz starten und abschließen nur online. Theorie darf offline aus dem Cache kommen.
- Keine Secrets im Frontend. Konfiguration über `import.meta.env.VITE_*`.
- `public/` wird 1:1 ausgeliefert, deshalb dort keine CLAUDE.md und keine internen Notizen.

## Stand (2026-10-14)

Nur das Gerüst: `App.vue` ist ein Platzhalter. Das Design-System ist eingebunden. Die Schrift Atkinson Hyperlegible Next fehlt noch (`public/assets/fonts/AtkinsonHyperlegibleNext-Variable.woff2`), bis dahin greift die Systemschrift.
