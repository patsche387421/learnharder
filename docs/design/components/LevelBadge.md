# LevelBadge

Das Sechseck mit der Levelzahl, gefärbt in der aktuellen Rangfarbe, mit einem Patina-Ring ab dem ersten Prestige.

**Aufbau:** `span.lh-badge[data-rank]` > `span.lh-badge__hex` (Levelzahl). Mit Prestige: zusätzlich `.lh-badge--prestige` und `span.lh-badge__prestige` (Prestige-Zahl).

- `data-rank`: `bronze` | `silver` | `gold` | `platin`. Die Level-Grenzen legt die Spiellogik fest.
- Der Prestige-Ring und die Prestige-Zahl verwenden immer `rank-prestige` (Patina). Nach einem Prestige beginnt das Badge wieder bei Bronze, der Ring bleibt.
- Die Zahl steht in Space Mono, Farbe `ink-inverse` (≥4.8:1 auf jeder Rangfarbe, in beiden Modi).
- Größen: Standard 40px (Tab-Leiste, Listen), `.lh-badge--lg` 72px (Profil, Level-Up).
- Für Screenreader immer ein Label mitgeben, z. B. „Level 21, Bronze, Prestige 1“.

**Die App liefert:** Level, Rang und Prestige-Stufe.
