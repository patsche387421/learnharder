# CurrencyChip

Zeigt einen Spielwert kompakt an: illustriertes Icon plus Zahl in Space Mono, z. B. Brainpower, Trophäen, Streak oder EP.

**Aufbau:** `span.lh-chip` > `img` (Spiel-Icon, 24px) · Zahl. Groß: `.lh-chip--lg` (32px-Icon, 40px Höhe).

- Im Hub-Kopf stehen höchstens **zwei** Chips (Brainpower und Trophäen). Streak und EP haben eigene Plätze.
- Die Zahl wird nie abgekürzt, solange sie unter 10.000 liegt. Darüber gilt `12,4k`.
- `aria-label` mit Wert und Begriff, z. B. „5 Brainpower“. Das Icon selbst hat `alt=""`.
- Antippbar nur, wenn es dazu eine Seite gibt (z. B. Trophäen tauschen). Dann als `a.lh-chip.lh-chip--link`: Die Tap-Fläche ist `tap-target` (48px) hoch, der Chip sieht gleich aus. Der Brainpower-Chip im Hub führt zum `Shop`.
- Läuft ein EP-Booster, trägt der Brainpower-Chip die Marke `span.lh-chip__boost` mit dem Faktor („2×“). Sie ist statisch, ohne Leuchten und ohne Bewegung, und steht auch im `aria-label` („60 Brainpower, 2× EP läuft“).
