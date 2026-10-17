# Segmented

Eine Wahl aus zwei bis vier kurzen Optionen, die sofort gilt, z. B. die **Darstellung** im Profil: Wie Gerät · Hell · Dunkel.

**Aufbau:** `fieldset.lh-segmented` > `legend.lh-segmented__legend` · `div.lh-segmented__options` > je Option `label.lh-segmented__option` > `input.lh-segmented__input[type=radio]` · Text. Optional `p.lh-segmented__hint` darunter.

- Echte Radio-Buttons mit gemeinsamem `name`: Pfeiltasten wechseln, der Screenreader sagt die Gruppe und „1 von 3“. Kein Speichern-Button, die Wahl gilt sofort.
- Die Optionen sind gleich breit und höchstens zwei Wörter lang. Gewählt: Fläche `surface-raised`, Rand `line-strong`, Text fett in `ink`. Nicht gewählt: Text `ink-muted` auf `surface-sunken`.
- Jede Option ist 40px hoch, mit dem Rand der Leiste ist die Tap-Fläche 48px (`tap-target`).
- Fokus: 2px-Rahmen in `ink` um die Option.
- Für Zustände, die mehr als einen Satz Erklärung brauchen, oder für mehr als vier Optionen ist Segmented das falsche Bauteil.

## Darstellung im Profil

- Eigene Karte unter „Dein Roboter“, vor „Abmelden“. Optionen in dieser Reihenfolge: **Wie Gerät** (Standard), **Hell**, **Dunkel**.
- „Wie Gerät“ folgt der Einstellung des Handys oder Laptops (`prefers-color-scheme`), Hell und Dunkel setzen `data-theme` auf `<html>`.
- Hinweis darunter: „Gilt nur auf diesem Gerät.“ Gespeichert wird nur im Browser, nicht im Konto (E-038).
- Die Wahl gilt schon beim nächsten Öffnen vor dem ersten Bild, ohne kurzes Aufblitzen im falschen Theme.

**Die App liefert:** die gewählte Option.
