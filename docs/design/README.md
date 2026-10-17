# learnharder.app – Design System

Die Quelle der Wahrheit für jede Oberfläche von learnharder.app. Die App ist eine Vue-3-PWA: Ihre `Lh*`-Komponenten in `frontend/src/components/ui/` bauen auf den `lh-`-Klassen aus `bundle.css` auf (`LhButton` passt zu `lh-btn`). Wer hier baut, Mensch oder KI-Agent, soll ohne Designer im Raum dieselben Entscheidungen treffen können.

## So verwendest du das System

1. **Lies zuerst diese Seite**, dann den Abschnitt **Sprache & Begriffe**.
2. **Tokens** (`tokens.json`) sind die einzigen erlaubten Werte für Farbe, Schrift, Abstand, Radius und Layout. Daraus entsteht `tokens.css` mit CSS-Variablen (`--surface`, `--space-4`, `--font-text` …). Im Code werden nie Hex-Werte oder px-Zahlen direkt eingetragen.
3. **Komponenten** sind reines CSS: `components/bundle.css`, alle Klassen mit dem Präfix `lh-`. Zustände laufen über Standard-Attribute (`aria-current`, `aria-pressed`, `disabled`, `data-rank`), damit Barrierefreiheit und Styling zusammenfallen. Jede Komponente hat eine Anleitung mit Aufbau, Regeln und dem, was die App liefern muss.
4. **Icons** liegen als SVG in drei Gruppen (Fächer, Spiel, UI), jeweils mit eigener Anleitung.
5. **Flows** zeigen, wie die Teile zusammenspielen. Bei neuen Screens zuerst nachsehen, ob ein Flow das schon regelt.

Einbinden, in dieser Reihenfolge: Schriften (selbst gehostet) → `tokens.css` → `bundle.css` → eigenes CSS.

## Point of View

- **Für wen:** Schüler:innen und Studierende der HTL Mödling, im Alltag (5 Minuten zwischendurch) und vor Prüfungen. Langfristig für mehrere Abteilungen, jede Person sieht nur, was für sie relevant ist.
- **Was wir sind:** Gamifiziertes Lernen wie bei Duolingo, aber für technische Inhalte. Von Duolingo übernehmen wir die *Mechanik* (Streaks, kurze Einheiten, sofortiges Feedback), nicht die *Ästhetik*. Was uns unterscheidet, ist die HTL-Welt: Code, Technik, Messwerte.
- **Kernwert: Fortschritt.** Fortschritt ist in jeder Farbe, jedem Text und jeder Animation spürbar.

## Grundsätze

- **Mobile first.** Jede Oberfläche wird zuerst für das Handy entworfen. Der Laptop bekommt dieselbe App mit mehr Rand.
- **Gamification steckt in den Funktionen, nicht im Styling.** Streaks, Level, Prestige und Ligen erzeugen das Spielgefühl. Die Oberfläche bleibt ruhig, damit Lernen im Vordergrund steht.
- **Jeder Screen beantwortet „Was mache ich jetzt?“** Es gibt genau einen Primär-Button, unten in Daumenreichweite.
- **Fehler sind Lernmomente.** Es gibt keine Leben und keine Strafen, sondern eine Erklärung und eine Wiederholung.
- **Nur eigene Icons**, keine Emojis.
- **Level-System:** Level 1–100, danach steigt Prestige um 1, und das Level beginnt wieder bei 1.

## Farbe

**Hell zuerst, Dunkel als Option.** Die Basis ist neutral und warm: Kreideweiß (`surface`) und Graphit (`ink`). Kein Navy, kein Violett, keine Tailwind-Standardfarben.

**Fortschritt hat eine Farbe, die sich verändert.** Die einzige Akzentfarbe ist `accent`, also der aktuelle Rang der Person. Die App setzt ihn zur Laufzeit:

| Rang | Token |
|---|---|
| Bronze | `rank-bronze` |
| Silber | `rank-silver` |
| Gold | `rank-gold` |
| Platin | `rank-platin` |

**Prestige ist kein Akzent, sondern ein Ring** in `rank-prestige` (Patina) um das Level-Badge. Nach einem Prestige beginnt das Badge wieder bei Bronze, der Ring bleibt. Die Level-Grenzen der Ränge legt die Spiellogik fest.

Regeln:
- `accent` erscheint **nur**, wo Fortschritt passiert: EP-Leiste, Level-Badge, Streak, Vorbereitungs-Prozent.
- Primär-Buttons sind `ink` mit `ink-inverse`-Text, also neutral, damit Handeln nicht mit Fortschritt konkurriert.
- Fachfarben (`subject-*`) sind nur Flächen für Fach-Icons, nie Textfarbe.
- `correct`/`wrong` stehen immer zusammen mit Icon und Wort, nie nur über die Farbe.
- Text erreicht mindestens 4,5:1 Kontrast in beiden Modi. Das ist für alle Tokens geprüft.

## Typografie

- **Atkinson Hyperlegible Next** (`text`) für alles, was gelesen wird: gebaut für maximale Lesbarkeit.
- **Space Mono** (`mono`) für alles, was gemessen wird: EP, Level, Streak, Plätze, Code. Zahlen wirken wie Messwerte, das ist die HTL-Signatur.

Regeln:
- Fließtext mindestens **16px** (`body`), Quizfragen 18px (`question`). Für Lesetext ist `small` (14px) das Minimum.
- Keine Großbuchstaben-Labels mit Sperrung.
- Buttons nutzen die Text-Schrift, nie die System-Schrift.
- Beide Schriften sind selbst gehostet (WOFF2), keine externen CDNs.

## Abstand, Form & Layout

- 4px-Raster, Grundtakt `space-4` (16px). Die Namen zählen in 4er-Schritten: `space-6` = 24px.
- Jedes antippbare Element ist mindestens `tap-target` (48px) groß.
- Vier Radien: `radius-sm` (Kleines), `radius-md` (Bedienbares), `radius-lg` (Flächen), `radius-full` (Pills, Leisten).
- Keine Schatten. Tiefe entsteht über Fläche (`surface` → `surface-raised`) und Rand (`line`).
- Media Queries immer `min-width`. Breakpoints: `bp-tablet` 768px, `bp-desktop` 1200px. Der Inhalt ist höchstens `content-max` (640px) breit, in Lerneinheiten `lesson-max` (560px). Mehr Platz wird zu Rand, nicht zu mehr Spalten.

## Navigation

| Ziel | Label (DE / EN) | Inhalt |
|---|---|---|
| Fächer | Lernen / Learn | Nur freigeschaltete Fächer |
| Social | Team / Team | Team, Team-Herausforderungen |
| **Hub** (Startseite) | *das eigene Level-Badge* | Streak, Weiterlernen, Prüfung, Herausforderung, Kampagne, Shop |
| Ranglisten | Liga / League | Monatliche Liga |
| Profil | Profil / Profile | Erfolge, Einstellungen |

- Handy: Tab-Leiste unten, **Hub in der Mitte**. Ab 768px: Leiste links, **Hub oben**. Details in `AppLayout`.
- Labels sind ein Wort mit höchstens 7 Zeichen. Der aktive Tab ist an Icon *und* Label erkennbar.
- In Lerneinheit, Quiz und Theorie (Fokus-Modus) gibt es keine Navigation.

## Icons

1. **Illustrierte Icons** für Fächer, Spiel-Elemente und leere Zustände: kleine App-Icon-Kacheln mit Verlauf in Fach- bzw. Rangfarbe und hellem Motiv. Sie müssen bei 32px erkennbar sein. Die Variante mit Kürzel-Label erst ab 64px.
2. **UI-Icons** für Navigation und Aktionen: einfarbig (`currentColor`), 24px-Raster, 2px Strich. Tabs haben eine Linien- und eine gefüllte (`-active`) Variante.

Dateinamen sind englisch (`subject-pos`, `icon-xp`, `ui-learn`). Patina kommt in keinem Icon vor, und kein Spiel-Icon ist grau, denn Grau bedeutet „gesperrt“.

## Komponenten & Flows

| Name | Wofür |
|---|---|
| `AppLayout` | Seitenrahmen: Handy, Tablet/Laptop, großer Bildschirm |
| `TabBar` | Hauptnavigation mit Level-Badge als Hub |
| `Button` | Primär, Sekundär, Ghost |
| `TextField` | Beschriftetes Eingabefeld mit Fehlerzustand |
| `Card` | Inhalt, antippbar, hervorgehoben |
| `EmptyState` | Statt „–“: was fehlt und was als Nächstes kommt |
| `LevelBadge` | Level, Rang und Prestige-Ring |
| `XpBar` | EP bis zum nächsten Level |
| `CurrencyChip` | Brainpower, Trophäen, Streak, EP |
| `SubjectCard` | Fach mit Fortschritt oder gesperrt |
| `AnswerOption` | Antwort im Quiz: ausgewählt, richtig, falsch |
| `Shop` | Brainpower einsetzen: Abschnitte mit Angeboten, zuerst EP-Booster |
| `Achievement` | Erfolge: Raster im Profil, neue Erfolge im Ergebnis |
| `Challenge` | Herausforderung: Zeile im Hub, Start, Frage mit Fach, Ergebnis mit Bonus |
| `LearnFlow` | Hub (Alltag und Prüfung) → Thema → Fragen mit Feedback → Ergebnis; Theorie-Modus |

## Qualitätscheck vor dem Release

Fertig ist nicht gleich gut. Bevor ein Screen live geht:

- [ ] Am **echten Handy** einmal so durchgespielt, wie eine Schülerin ihn erlebt.
- [ ] Ist auf einen Blick klar, was als Nächstes zu tun ist? Gibt es genau einen Primär-Button?
- [ ] Wird nach jeder Aktion Fortschritt sichtbar?
- [ ] Sind alle Texte nach *Sprache & Begriffe* formuliert, mit den Glossar-Begriffen?
- [ ] Funktioniert der Screen in Hell und Dunkel?
- [ ] Ist er ohne Farbe verständlich (Icons, Wörter)?
- [ ] Werden nur Tokens und `lh-`-Klassen verwendet, keine fest eingetragenen Werte?
- [ ] Gibt es leere Zustände und Fehlerzustände, nicht nur den Idealfall?

## Umstieg vom alten CSS

| Alt | Neu |
|---|---|
| `--bg`, `--bg-base` | `--surface` |
| `--surface` | `--surface-raised` |
| `--surface-2` | `--surface-sunken` |
| `--border` | `--line` (Deko) / `--line-strong` (Bedienelemente) |
| `--text` | `--ink` |
| `--text-muted`, `--text-subtle` | `--ink-muted` |
| `--primary` | `--ink` (Buttons) / `--accent` (Fortschritt) |
| `--success` / `--error` | `--correct` / `--wrong` |
| `--gold`, `--xp`, `--gradient-brand` | `--accent` |
| `--prestige` | `--rank-prestige` |
| `--energy` | entfällt, Brainpower nutzt `icon-brainpower` |
| `--fach-*` | `--subject-*` |
| `--font-display` | `--font-text` |
| `--space-xs/sm/md/base/lg/xl/2xl` | `--space-1/2/3/4/6/8/12` |
| `--radius-sm/md/lg`, `--radius-cta` | `--radius-sm/md/lg` (6/12/20px) |
| `--fs-body` 14px | Text-Stil `body` 16px |

## Offene Entscheidungen

- Wie „Prozent vorbereitet“ für Prüfungen berechnet wird.
- Flow für die Lehrkraft, die eine Prüfung anlegt (braucht Rollen und Klassen).
- Gestaltung, sobald das Feature drankommt: Herausforderung, Prestige-Moment, Team, Liga, Profil & Einstellungen.
- Fachfarben, sobald eine zweite Abteilung dazukommt (Farbe pro Abteilung, Icon pro Fach).
