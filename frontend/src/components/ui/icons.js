// Alle Icons aus src/assets/icons/ als URL. Schlüssel ist der Dateiname ohne .svg (z. B. "ui-learn", "subject-pos").
const files = import.meta.glob('../../assets/icons/*/*.svg', { eager: true, query: '?url', import: 'default' })

export const icons = Object.fromEntries(
  Object.entries(files).map(([path, url]) => [path.split('/').pop().replace(/\.svg$/, ''), url]),
)

export function iconUrl(name) {
  const url = icons[name]
  if (!url && import.meta.env.DEV) console.warn(`[lh] Icon „${name}“ gibt es nicht in src/assets/icons/`)
  return url
}
