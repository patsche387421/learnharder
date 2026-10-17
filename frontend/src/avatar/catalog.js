// Avatar-Teile (Konzept Spielwelt §7, E-031). Jedes Teil ist eine SVG-Datei auf der Fläche des Skeletts
// in src/assets/avatar/<slot>/<key>.svg, Regeln im README.md dort. Später liefert der Server, was jemand besitzt.
const files = import.meta.glob('../assets/avatar/*/*.svg', { query: '?raw', import: 'default', eager: true })

export const SLOTS = [
  { key: 'head', name: 'Kopf' },
  { key: 'topper', name: 'Aufsatz' },
  { key: 'chassis', name: 'Rumpf' },
  { key: 'arms', name: 'Arme' },
  { key: 'locomotion', name: 'Antrieb' },
  { key: 'tool', name: 'Werkzeug' },
  { key: 'badge', name: 'Abzeichen' },
  { key: 'paint', name: 'Lackierung' },
]

// Platzhalter von Claude, bis Patsches Teile kommen. Die Starter-Kits je Abteilung fehlen noch.
export const PARTS = {
  head: { standard: 'Standard' },
  topper: { antenna: 'Antenne' },
  chassis: { standard: 'Standard' },
  arms: { standard: 'Standard' },
  locomotion: { legs: 'Beine' },
  tool: { 'soldering-iron-1': 'Lötkolben Mk I' },
  badge: { department: 'Abteilung' },
}

// Grundausstattung: kostenlos, die Hand ist leer, bis das erste Werkzeug kommt
export const BASIC_KIT = {
  head: 'standard',
  topper: 'antenna',
  chassis: 'standard',
  arms: 'standard',
  locomotion: 'legs',
  tool: null,
  badge: 'department',
  paint: 'workshop',
}

export const partPath = (slot, key) => `../assets/avatar/${slot}/${key}.svg`

/** Inhalt eines Teils ohne die äußere <svg>-Hülle, leer, wenn es das Teil nicht gibt */
export function partSvg(slot, key) {
  const raw = files[partPath(slot, key)]
  return raw ? raw.replace(/^[\s\S]*?<svg[^>]*>/, '').replace(/<\/svg>\s*$/, '').trim() : ''
}

export const partFiles = files
