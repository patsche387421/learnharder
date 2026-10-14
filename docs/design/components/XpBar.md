# XpBar

Zeigt die Erfahrungspunkte bis zum nächsten Level. Sie ist der sichtbarste Fortschritt in der App.

**Aufbau:** `div.lh-xp[role=progressbar]` > `.lh-xp__meta` (Level, EP-Stand) · `.lh-xp__track` > `.lh-xp__fill` (Breite über `--value`) · `.lh-xp__meta` („46 EP bis Level 22“).

- Die Füllung ist immer `accent`, also die aktuelle Rangfarbe. Die Leiste ist der Ort, an dem Fortschritt Farbe bekommt.
- Zahlen in Space Mono (`meta`). Text steht neben der Leiste, nie darauf, damit er in jedem Modus lesbar bleibt.
- Neue EP füllen die Leiste animiert (0,5 s). Bei `prefers-reduced-motion` springt sie ohne Animation.
- `aria-valuenow`, `aria-valuemin`, `aria-valuemax` immer setzen.
- Dieselbe Leiste dient für Fach-Fortschritt in der `SubjectCard`.

**Die App liefert:** aktuelle EP, EP-Ziel des Levels, Level und Rang.
