import { nextTick } from 'vue'
import { afterEach, describe, expect, it, vi } from 'vitest'
import indexHtml from '../../index.html?raw'
import { readTheme, setTheme, THEME_KEY, useTheme } from './useTheme.js'

const html = document.documentElement

// Das Skript aus index.html, das vor dem ersten Bild läuft
const early = indexHtml.match(/<script>([\s\S]*?)<\/script>/)[1]
const runEarly = () => new Function(early)()

describe('useTheme', () => {
  afterEach(() => {
    localStorage.clear()
    html.removeAttribute('data-theme')
    vi.restoreAllMocks()
  })

  it('folgt ohne gespeicherte Wahl dem Gerät', () => {
    expect(readTheme()).toBe('system')
    expect(useTheme().value).toBe('system')
  })

  it('Hell und Dunkel setzen data-theme und bleiben gespeichert, Wie Gerät nimmt beides weg', async () => {
    const theme = useTheme()
    theme.value = 'dark'
    await nextTick()
    expect(html.getAttribute('data-theme')).toBe('dark')
    expect(localStorage.getItem(THEME_KEY)).toBe('dark')
    expect(useTheme().value).toBe('dark')

    theme.value = 'system'
    await nextTick()
    expect(html.hasAttribute('data-theme')).toBe(false)
    expect(localStorage.getItem(THEME_KEY)).toBeNull()
  })

  it('unbekannte Werte im Speicher gelten als Wie Gerät', () => {
    localStorage.setItem(THEME_KEY, 'neon')
    expect(readTheme()).toBe('system')
    runEarly()
    expect(html.hasAttribute('data-theme')).toBe(false)
  })

  it('ohne Speicher (privates Fenster) schaltet es trotzdem um, bis zum Neuladen', () => {
    vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => { throw new Error('blockiert') })
    vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => { throw new Error('blockiert') })
    expect(readTheme()).toBe('system')
    expect(() => setTheme('light')).not.toThrow()
    expect(html.getAttribute('data-theme')).toBe('light')
    expect(runEarly).not.toThrow()
  })

  it('das Skript in index.html setzt die gespeicherte Wahl schon vor dem Start der App', () => {
    localStorage.setItem(THEME_KEY, 'light')
    runEarly()
    expect(html.getAttribute('data-theme')).toBe('light')
  })
})
