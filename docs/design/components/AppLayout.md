# AppLayout

Der Rahmen jeder Seite: mobile first. Auf dem Handy liegt die Tab-Leiste unten, auf Tablet und Laptop wird sie zur Leiste links. Der Inhalt bleibt immer eine schmale, mittige Spalte.

**Warum so:** learnharder wird meistens am Handy genutzt, am Laptop in Pausen im Unterricht und zuhause am PC. Der Laptop bekommt deshalb **dieselbe App mit mehr Rand**, kein eigenes Layout. Wer am Handy lernt, findet sich am PC sofort zurecht.

**Aufbau:** `div.lh-app` > `main.lh-app__main` · `nav.lh-tabbar`. Im Fokus-Modus (Lerneinheit, Quiz, Theorie): `div.lh-app.lh-app--focus` > `main.lh-app__main.lh-app__main--lesson`, ohne `nav` und ohne Navigationsspalte.

| Breite | Navigation | Inhalt |
|---|---|---|
| unter `bp-tablet` (768px) | Tab-Leiste unten, Hub in der Mitte | volle Breite, 16px Rand |
| ab `bp-tablet` | Rail links (`rail-w`, 88px), **Hub oben** | höchstens `content-max` (640px), mittig |
| ab `bp-desktop` (1200px) | Seitenleiste (`sidebar-w`, 240px), Icon und Text nebeneinander | höchstens 640px, mittig |

Regeln:
- **Keine zweite Spalte**, auch nicht auf großen Screens. Mehr Platz wird zu mehr Rand.
- In Lerneinheit, Quiz und Theorie (Fokus-Modus) gibt es auf **allen** Größen keine Navigation. Der Inhalt ist höchstens `lesson-max` (560px) breit.
- **Hover** nur bei Maus oder Trackpad (`@media (hover: hover)`). Am Handy gibt es keine Hover-Effekte.
- **Tastatur am Laptop:** Im Quiz wählen `A`/`B`/`C` (oder `1`/`2`/`3`) eine Antwort, `Enter` prüft und geht weiter, `Esc` schließt. Die Tasten werden als `.lh-kbd` neben den Antworten angezeigt, aber nur am Laptop/PC.
- Media Queries immer `min-width` (mobile first). CSS-Variablen funktionieren nicht in Media Queries, dort stehen die Werte direkt.
