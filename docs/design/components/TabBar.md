# TabBar

Die Hauptnavigation am Handy: fünf Ziele unten am Bildschirmrand, in der Mitte der Hub mit dem eigenen Level-Badge.

**Aufbau:** `nav.lh-tabbar` mit fünf `a.lh-tab`. Reihenfolge immer **Lernen · Team · Hub · Liga · Profil**.

- Aktiver Tab: `aria-current="page"`, `-active`-Icon (gefüllt), Farbe `ink`, Label fett. Inaktiv: Linien-Icon in `ink-muted`.
- Der Hub (`.lh-tab--hub`) hat kein Label. Er zeigt das `LevelBadge` der Person und braucht ein `aria-label` wie „Hub, Level 21, Prestige 1“.
- Labels: ein Wort mit höchstens 7 Zeichen, in jeder Sprache.
- Jeder Tab ist mindestens 56px hoch. Unten wird der Abstand für die Home-Leiste am iPhone (`safe-area-inset-bottom`) dazugerechnet.
- Ab Tablet (≥768px) wird die Leiste zur Rail links, ab 1200px zur Seitenleiste mit Text (siehe `AppLayout`). Dort steht der Hub **oben**, die anderen Ziele folgen in derselben Reihenfolge.

**Die App liefert:** die aktuelle Seite (`aria-current`), Level, Rang und Prestige für das Badge.
