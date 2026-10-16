# CLAUDE.md — frontend/ (Rolle: Frontend-Entwickler:in)

Vue-3-PWA für Schüler:innen und Lehrkräfte. Allgemeine Regeln: `../CLAUDE.md`.

## Stack

Vue 3 (`<script setup>`), Vite, Pinia, Vue Router, Vitest. Geplant: vue-i18n, PWA-Plugin, Playwright.
Lokal läuft Node 22 (nvm in WSL2). Vite 8 verlangt mindestens Node 20.19 (`package-lock.json`). Noch auf Vitest 4 und jsdom 29, das Upgrade auf Vitest 5 und jsdom 30 ist offen.

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

## Stand (2026-10-15, nach M3)

- **Login:** `LoginView` (`/login`, ohne Rahmen). Der Router-Guard schickt ohne Login dorthin, eine 401 leert den Store.
- **API:** `api/client.js` ist der einzige Ort mit `fetch` (Cookies, `X-XSRF-TOKEN`, bei 419 ein gemeinsamer CSRF-Refresh und genau eine Wiederholung, deutsche Fehlertexte). `api/learn.js` bündelt die Lern-Routen. Basis-URL aus `VITE_API_URL` (`.env.example`, lokal `.env.local`).
- **Store:** `stores/auth.js` hält `me`, daraus Level, Rang und Prestige für den Rahmen. `applyProgress` übernimmt den Fortschritt nach einer Lerneinheit.
- **Lernen:** `/learn` → `LearnView` (Fächer mit Fach-Level) → `SubjectView` (Themen) → `TopicView` → `TheoryView` und `LessonView` (beide `meta.focus`, ohne Tab-Leiste). Laden und Fehler über `composables/useLoad.js` und `LoadError`.
- **Offen:** Hub, Team und Liga sind noch `PlaceholderView`, `ProfileView` zeigt nur Gamer-Tag und „Abmelden“.

13 `Lh`-Bausteine in `src/components/ui/`, jeder mit Test. `/styleguide` zeigt alle Tokens, Icons und Bausteine (nur in der Entwicklung). Beide Schriften liegen selbst gehostet in `public/assets/fonts/`. In WSL2 sind 94 Tests, `build` und `check:tokens` grün.
