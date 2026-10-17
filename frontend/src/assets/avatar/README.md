# Avatar-Teile

Teile des Avatar-Roboters (Konzept Spielwelt §7, E-031). Die Engine liegt in `src/avatar/`, sie legt die Teile übereinander.
Die jetzigen Teile sind Platzhalter von Claude, bis Patsches Teile kommen.

## Format

- **Eine Datei je Teil:** `<slot>/<name>.svg`, Slots: `head`, `topper`, `chassis`, `arms`, `locomotion`, `tool`, `badge`. Neue Teile auch in `PARTS` in `src/avatar/catalog.js` eintragen.
- **Gleiche Fläche:** `viewBox="0 0 200 240"`. Das Teil liegt genau dort, wo es am fertigen Roboter sitzt. `vorlage.svg` zeigt Grundausstattung, Steckplätze (rot) und die Flächen der Engine (gestrichelt): Display, Akku, Abzeichen-Platz.
- **Arme:** nur den linken Arm zeichnen (von vorn gesehen links), die Engine spiegelt ihn. Werkzeuge sitzen an der rechten Hand.
- **Farben nur als Klasse:** `p-main` (Grundfarbe), `p-trim` (Gelenke, Kanten), `p-accent` (Akzent), `p-ink` (dunkle Fläche), `p-line` (nur Linie, ohne Füllung). Keine Hex-Werte, kein `fill`, `stroke` oder `style`. Die Kontur setzt die Engine.
- **Verboten:** Text, Bilder, Filter, Verläufe, Skripte. Unter 3 KB je Datei.
- **Nicht zeichnen:** Display-Gesicht und Akku. Beides kommt von der Engine.

`src/avatar/catalog.test.js` prüft das Format bei jedem Test-Lauf.

## Vom KI-Bild zur Datei

1. Das Teil einzeln erzeugen lassen („only the head, same style, isolated“).
2. In Inkscape `vorlage.svg` öffnen, das PNG darüber legen, *Pfad → Bitmap nachzeichnen*, auf die Vorlage schieben, vereinfachen.
3. Jeder Form unter *Objekt → Objekteigenschaften* eine Klasse geben, Farben entfernen.
4. *Speichern unter → Optimiertes SVG*, Vorlage-Ebene vorher löschen.

Oder das PNG an Claude geben, der zeichnet es auf der Vorlage nach.
