// Darstellung (E-038): Wie Gerät, Hell oder Dunkel. Gespeichert nur in diesem Browser, nie im Konto.
// Beim Öffnen setzt das kleine Skript in index.html das Theme schon vor dem ersten Bild. Hier wird nur umgeschaltet.
import { ref, watch } from 'vue'

export const THEME_KEY = 'lh-theme'
export const THEMES = [
  { value: 'system', label: 'Wie Gerät' },
  { value: 'light', label: 'Hell' },
  { value: 'dark', label: 'Dunkel' },
]

const fixed = (theme) => theme === 'light' || theme === 'dark'

/** Gespeicherte Wahl, ohne (oder ohne Speicher, z. B. privates Fenster) folgt die App dem Gerät. */
export function readTheme() {
  try {
    const theme = localStorage.getItem(THEME_KEY)
    return fixed(theme) ? theme : 'system'
  } catch {
    return 'system'
  }
}

/** Setzt data-theme auf <html> (tokens.css), „Wie Gerät“ nimmt es weg. */
export function applyTheme(theme) {
  if (fixed(theme)) document.documentElement.setAttribute('data-theme', theme)
  else document.documentElement.removeAttribute('data-theme')
}

export function setTheme(theme) {
  applyTheme(theme)
  try {
    if (fixed(theme)) localStorage.setItem(THEME_KEY, theme)
    else localStorage.removeItem(THEME_KEY)
  } catch {
    // Ohne Speicher gilt die Wahl bis zum Neuladen
  }
}

/** Die Wahl als ref: Ändern schaltet sofort um und merkt es sich. */
export function useTheme() {
  const theme = ref(readTheme())
  watch(theme, setTheme)
  return theme
}
