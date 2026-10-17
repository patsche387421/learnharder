import { describe, expect, it } from 'vitest'
import { BASIC_KIT, PARTS, SLOTS, partFiles, partPath, partSvg } from './catalog.js'
import { PAINTS } from './paints.js'

// Format der Teile (README.md in src/assets/avatar/): Neue Teile von Patsche müssen hier durchkommen.
describe('Avatar-Teile', () => {
  const files = Object.entries(partFiles)

  it.each(files)('%s hält das Format ein', (path, svg) => {
    expect(svg).toMatch(/viewBox="0 0 200 240"/)
    expect(svg.length).toBeLessThan(3 * 1024)
    expect(svg).not.toMatch(/#[0-9a-f]{3,8}\b/i)
    expect(svg).not.toMatch(/<(text|image|filter|linearGradient|radialGradient|style|script)\b/i)
    expect(svg).not.toMatch(/\s(style|fill|stroke|on\w+)=/i)
    for (const [, name] of svg.matchAll(/class="([^"]+)"/g)) {
      expect(['p-main', 'p-trim', 'p-accent', 'p-ink', 'p-line']).toContain(name)
    }
  })

  it('jede Datei steht im Katalog und jeder Eintrag hat eine Datei', () => {
    const listed = Object.entries(PARTS).flatMap(([slot, keys]) => Object.keys(keys).map((key) => partPath(slot, key)))
    expect(files.map(([path]) => path).sort()).toEqual(listed.sort())
  })

  it('die Grundausstattung besteht aus vorhandenen Teilen, die Hand ist leer', () => {
    for (const { key } of SLOTS) expect(BASIC_KIT).toHaveProperty(key)
    for (const [slot, key] of Object.entries(BASIC_KIT)) {
      if (slot === 'paint') expect(PAINTS).toHaveProperty(key)
      else if (key) expect(PARTS[slot]).toHaveProperty(key)
    }
    expect(BASIC_KIT.tool).toBeNull()
  })

  it('liefert den Inhalt ohne äußere Hülle', () => {
    const head = partSvg('head', 'standard')
    expect(head.startsWith('<rect')).toBe(true)
    expect(head).not.toContain('<svg')
    expect(partSvg('head', 'gibt-es-nicht')).toBe('')
  })
})
