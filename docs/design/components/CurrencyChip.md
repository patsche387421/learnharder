# CurrencyChip

Zeigt einen Spielwert kompakt an: illustriertes Icon plus Zahl in Space Mono, z. B. Brainpower, Trophäen, Streak oder EP.

**Aufbau:** `span.lh-chip` > `img` (Spiel-Icon, 24px) · Zahl. Groß: `.lh-chip--lg` (32px-Icon, 40px Höhe).

- Im Hub-Kopf stehen höchstens **zwei** Chips (Brainpower und Trophäen). Streak und EP haben eigene Plätze.
- Die Zahl wird nie abgekürzt, solange sie unter 10.000 liegt. Darüber gilt `12,4k`.
- `aria-label` mit Wert und Begriff, z. B. „5 Brainpower“. Das Icon selbst hat `alt=""`.
- Antippbar nur, wenn es dazu eine Seite gibt (z. B. Trophäen tauschen). Dann als `a.lh-chip.lh-chip--link`: Die Tap-Fläche ist `tap-target` (48px) hoch, der Chip sieht gleich aus.
