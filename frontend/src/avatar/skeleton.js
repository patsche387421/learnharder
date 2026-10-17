// Skelett des Avatar-Roboters (Konzept Spielwelt §7, E-031). Alle Teile liegen auf derselben Fläche,
// die Steckplätze sind die Drehpunkte für Bewegung und Posen. Ändert sich ein Wert, passt kein Teil mehr:
// dann auch src/assets/avatar/vorlage.svg und alle Teile anpassen.
export const VIEWBOX = '0 0 200 240'

export const SOCKETS = {
  top: [100, 18],
  neck: [100, 108],
  shoulderLeft: [58, 124],
  shoulderRight: [142, 124],
  handRight: [155, 173],
  hip: [100, 178],
}

// Flächen, die die Engine selbst zeichnet. Teile lassen sie frei.
export const SCREEN = { x: 56, y: 36, width: 88, height: 60, rx: 10 }
export const BATTERY = { x: 84, y: 156, width: 32, height: 12 }
