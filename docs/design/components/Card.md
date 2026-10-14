# Card

Die Grundfläche für zusammengehörige Inhalte: eine Fläche in `surface-raised` mit 1px-Rand (`line`) und `radius-lg`.

**Varianten:**
- `.lh-card`: reiner Inhalt, nicht antippbar.
- `.lh-card--link`: als Ganzes antippbar (`a` oder `button`). Links ein illustriertes Icon (40px), in der Mitte Titel und Text, rechts der Pfeil `ui-next`. Mindestens 64px hoch. Ein `button` ist es, wenn die Karte etwas auslöst, statt zu einer Seite zu führen. Die Klasse setzt dann Rahmen, Schrift, Breite und Ausrichtung des Browsers zurück.
- `.lh-card--featured`: 2px-Rahmen in `ink` für **das eine** wichtigste Element am Screen, z. B. die Prüfungs-Karte im Hub. Höchstens eine pro Screen.

Regeln:
- Karten verwenden keine Schatten. Die Tiefe entsteht über Fläche und Rand.
- Eine Karte, ein Thema. Karten nicht ineinander verschachteln.
- Abstand zwischen Karten: `space-3` (12px) in Listen, `space-4` (16px) zwischen Abschnitten.
