# CLAUDE.md — frontend/ (Rolle: Frontend-Entwickler:in)

Vue-3-PWA für Schüler:innen und Lehrkräfte. Allgemeine Regeln: `../CLAUDE.md`.

## Stack

Vue 3 (`<script setup>`), Vite, Pinia, Vue Router, Vitest. Geplant: vue-i18n, PWA-Plugin, Playwright.
Lokal läuft Node 22 (nvm in WSL2). Vite 8 verlangt mindestens Node 20.19 (`package-lock.json`). Tests mit Vitest 5 und jsdom 30. Neue Versionen erst nehmen, wenn sie mindestens zwei Wochen alt sind. Bricht `npm install` mit `reading 'edgesOut'` ab (npm-Fehler bei Peer-Abhängigkeiten), die Lock-Datei einmal mit `--legacy-peer-deps` erzeugen und danach mit `npm ci` prüfen.

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
  assets/avatar/ Avatar-Teile als SVG je Slot, Format im README.md dort
  avatar/       Avatar-Engine: RobotAvatar.vue, Katalog, Lackierungen, Posen, Gesichter, Stimmung
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

## Stand (2026-10-17, nach G1 Hub, G4 Avatar-Engine und G2)

- **Ankommen (M4a, E-025, E-027):** ohne Login und ohne Rahmen. `meta.guest` schickt Angemeldete weiter zum Hub, Login und Registrierung enden dort auch. `LoginView` verlinkt `RegisterView` (`/registrieren`: Schul-Mail → Code → Gamer-Tag, Passwort, Häkchen ab 14 und Zustimmung) und `ForgotPasswordView` (`/passwort-vergessen`) und zeigt danach Hinweise über `?hinweis=passwort|geloescht`. `LegalView` zeigt `/datenschutz`, `/impressum`, `/nutzungsregeln` und `/einwilligung-eltern` (beim Drucken ohne „Zurück“).
- **Login:** Der Router-Guard schickt ohne Login zu `/login`, eine 401 leert den Store. Eine 403 mit `reason: account_deactivated` leert ihn auch und führt zu `/login?gesperrt=1` (`main.js`).
- **API:** `api/client.js` ist der einzige Ort mit `fetch` (Cookies, `X-XSRF-TOKEN`, bei 419 ein gemeinsamer CSRF-Refresh und genau eine Wiederholung, deutsche Fehlertexte). `api/learn.js` bündelt die Lern-Routen, `api/account.js` Registrierung, Rechtstexte und Passwort, `api/hub.js` den Hub, `api/shop.js` Shop und Booster-Kauf. Eine 409 zeigt die deutsche Meldung des Servers. Basis-URL aus `VITE_API_URL` (`.env.example`, lokal `.env.local`).
- **Store:** `stores/auth.js` hält `me`, daraus Level, Rang und Prestige für den Rahmen. `register()` und `deleteAccount()` für M4a. `isStaff`: Personal-Rolle. `waitingForClass`: keine Klasse und kein Personal (E-024). `applyProgress` übernimmt Fortschritt, Streak, Brainpower und Booster vom Server.
- **Lernen:** `/learn` → `LearnView` (Fächer mit Fach-Level) → `SubjectView` (Themen) → `TopicView` → `TheoryView` und `LessonView` (beide `meta.focus`, ohne Tab-Leiste). Laden und Fehler über `composables/useLoad.js` und `LoadError`. Ohne Klasse zeigt `LearnView` „Noch keiner Klasse zugeordnet.“
- **Profil:** `ProfileView` zeigt Gamer-Tag, Klasse, Fortschritt (Level, Rang, EP, längster Streak, Fach-Level aus `GET /subjects`), den Roboter mit Slots, „Abmelden“ und die Rechtstexte, dazu für Schüler:innen „Konto löschen“ (`/profile/delete`, `meta.focus`, nur mit Passwort). Pose und Lackierung sind nur Vorschau, gespeichert wird noch nichts.
- **Hub (G1):** `/` → `HubView`: Level, EP und Brainpower aus `auth.me`, „Weiterlernen“ und `last_study_at` aus `GET /hub` (`api/hub.js`). Begrüßung und Stimmung des Roboters nach Zustand (Aufstieg, Pause ab 3 Tagen, heute gelernt, erster Besuch) und Tageszeit, Kalendertage nach Gerätezeit. Den Aufstieg merkt sich `auth` (`applyProgress`, `takeLevelUp`) bis zum nächsten Hub. „Heute gelernt“ kommt vom Server (`streak.today`), daraus auch „Streak in Gefahr“. Akku aus der Brainpower. Unter der EP-Leiste die Chip-Zeile (`lh-chiprow`): Streak, dann Brainpower. Zeile „Shop“ und Brainpower-Chip führen nach `/shop`, ein laufender Booster steht auf „Weiterlernen“ und am Chip.
- **Shop (G2, E-037):** `/shop` → `ShopView`: EP-Booster aus `GET /shop` kaufen, zu teure mit Grund, laufender einmal oben mit Restzeit, Hinweis ohne EP für heute (`xp_left_today`). Im Kopf der Roboter, der nach einem Kauf jubelt. Restzeit und Faktor rechnet `composables/useBooster.js` (Minuten, kein Sekunden-Countdown), auch für den Hub. In der Lerneinheit nur „2×“ neben den EP, im Ergebnis „davon +n durch Booster“.
- **Avatar (G4):** `avatar/RobotAvatar.vue` legt die Teile aus `assets/avatar/` übereinander und zeichnet Display, Akku und Bewegung selbst (nur `transform` und `opacity`, außerhalb des Bildes pausiert). `avatar/catalog.test.js` prüft das Format jeder Teil-Datei. Die Teile sind Platzhalter von Claude.
- **Offen:** Team und Liga sind noch `PlaceholderView`.

14 `Lh`-Bausteine in `src/components/ui/` (seit M4a mit `LhCheckbox` und `LhButton variant="danger"`, Design-Artefakt Version 35), jeder mit Test. `/styleguide` zeigt alle Tokens, Icons und Bausteine (nur in der Entwicklung). Beide Schriften liegen selbst gehostet in `public/assets/fonts/`. In WSL2 sind 186 Tests, `build` und `check:tokens` grün (Stand 2026-10-17).
