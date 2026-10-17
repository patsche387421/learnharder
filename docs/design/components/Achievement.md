# Achievement

Erfolge: ein Raster im Profil und neue Erfolge im Ergebnis einer Lerneinheit oder Herausforderung.

**Aufbau Raster:** `div.lh-achievements__head` (Überschrift „Erfolge“ mit „4 / 12“ in Space Mono, rechts der Trophäen-Chip `lh-chip` mit `icon-trophy`) · `ul.lh-achievements` > `li.lh-achievement` > `span.lh-achievement__art` (Icon 48px, bei Stufen `span.lh-achievement__level`) · `span.lh-achievement__title` · `span.lh-achievement__date` (erreicht) oder `span.lh-achievement__text` (Bedingung).

Zustände:
- **Erreicht:** Icon farbig, Titel, Datum („12. Okt“).
- **Noch nicht erreicht** `lh-achievement--locked`: Icon grau und blass wie ein gesperrtes Fach. Titel und Bedingung bleiben lesbar („30 Tage in Folge lernen“), das Ziel ist sichtbar.
- **Geheim und noch nicht erreicht** `lh-achievement--secret`: `span.lh-achievement__secret` mit „?“ statt Icon, Titel „Geheimer Erfolg“, kein Text. Die App bekommt weder Titel noch Bedingung.

Regeln:
- **Reihenfolge** wie im Katalog. Erreichte bleiben an ihrem Platz, so sieht man, was als Nächstes kommt.
- **Icons:** die vorhandenen Spiel-Icons je Art: `icon-streak` (Streak), `icon-xp` (Level, Lerneinheiten), das Fach-Icon (Fach-Level), `icon-challenge` (Herausforderung), sonst `icon-achievement`. Stufen zeigt die Zahl an der Ecke (Schwellwert, nur bei Erfolgen mit Stufen, nicht bei 1), nicht die Farbe.
- **Nur Anzeige.** Kacheln sind nicht antippbar, alles Wichtige steht auf der Kachel. Jedes `li` hat ein `aria-label` mit Zustand: „Eine Woche, erreicht am 12. Oktober“, „Ein Monat, noch nicht erreicht: 30 Tage in Folge lernen“, „Geheimer Erfolg“.
- **Neuer Erfolg im Ergebnis:** `div.lh-card.lh-card--row` unter der Statistik: Icon 40px · `lh-card__body` mit `lh-card__eyebrow` „Neuer Erfolg“ und `lh-card__title` · `lh-chip` mit `icon-trophy` und den Trophäen („+2“, `aria-label` „+2 Trophäen“). Mehrere stehen untereinander. Keine eigene Animation, kein Konfetti.
- **Ruhig.** Kein Fortschrittsbalken je Erfolg, kein „Nur noch 2!“. Kein Erfolg für Uhrzeit oder Pausen: Andere sollen später Erfolge sehen, und das würde verraten, wann jemand lernt oder weg war.

**Die App liefert:** alle aktiven Erfolge (dazu eigene deaktivierte) mit Titel, Bedingung, Icon, Stufe und Datum, geheime unerreichte ohne Titel und Text, die Trophäen, im Ergebnis die neuen Erfolge mit ihren Trophäen.
