// Prüft, ob src/styles/tokens.css dieselben Werte hat wie src/styles/tokens.json (Kopie aus dem Design-Artefakt).
// Aufruf: npm run check:tokens. Exit-Code 1 bei Abweichungen.
import { readFileSync } from 'node:fs'

const styles = new URL('../src/styles/', import.meta.url)
const json = JSON.parse(readFileSync(new URL('tokens.json', styles), 'utf8'))
const css = readFileSync(new URL('tokens.css', styles), 'utf8').replace(/\/\*[\s\S]*?\*\//g, '')

// Blöcke „Selektor { Deklarationen }“ einlesen. Der @media-Rahmen fällt dabei weg, der innere Block bleibt.
const blocks = [...css.matchAll(/([^{}]+)\{([^{}]*)\}/g)].map(([, sel, body]) => ({
  sel: sel.trim(),
  vars: Object.fromEntries([...body.matchAll(/--([\w-]+)\s*:\s*([^;]+);/g)].map(([, k, v]) => [k, v.trim()])),
  font: body.match(/font\s*:\s*([^;]+);/)?.[1].trim(),
}))
const merged = (pick) => Object.assign({}, ...blocks.filter(pick).map((b) => b.vars))

const scopes = {
  light: merged((b) => b.sel.includes('[data-theme="light"]') && !b.sel.includes(':not(')),
  dark: merged((b) => b.sel === '[data-theme="dark"]'),
  'dark (Systemeinstellung)': merged((b) => b.sel === ':root:not([data-theme="light"])'),
  base: merged((b) => b.sel === ':root'),
}

const errors = []
const expect = (scope, name, want) => {
  const got = scopes[scope][name]
  if (got === undefined) errors.push(`${scope}: --${name} fehlt (erwartet ${want})`)
  else if (got.toLowerCase() !== want.toLowerCase()) errors.push(`${scope}: --${name} ist ${got}, erwartet ${want}`)
}
const cssValue = (v) => (/^\{.+\}$/.test(v) ? `var(--${v.slice(1, -1)})` : v)

// Farben: ein Wert pro Theme. Fehlt ein Theme, gilt der Wert des ersten Themes (wie im Artefakt).
const [first] = json.color.themes.map((t) => t.id)
const colorNames = new Set()
for (const t of json.color.tokens) {
  colorNames.add(t.name)
  const values = typeof t.value === 'string' ? { [first]: t.value } : t.value
  for (const { id } of json.color.themes) {
    const want = cssValue(values[id] ?? values[first])
    if (id === 'dark') {
      expect('dark', t.name, want)
      expect('dark (Systemeinstellung)', t.name, want)
    } else expect(id, t.name, want)
  }
}

// Abstand, Radius, Layout und Schriftfamilien liegen im :root-Block.
const baseNames = new Set()
for (const family of ['spacing', 'radius', 'layout']) {
  for (const t of json[family]?.tokens ?? []) {
    baseNames.add(t.name)
    expect('base', t.name, String(t.value))
  }
}
for (const [key, stack] of Object.entries(json.type.families)) {
  baseNames.add(`font-${key}`)
  expect('base', `font-${key}`, stack)
}

// Text-Stile als Klassen: .name { font: Gewicht Größe/Zeilenhöhe var(--font-familie) }
for (const group of json.type.groups) {
  for (const s of group.styles) {
    const want = `${s.fontWeight} ${s.fontSize}/${s.lineHeight} var(--font-${group.family})`
    const got = blocks.find((b) => b.sel === `.${s.name}`)?.font
    if (got === undefined) errors.push(`Text-Stil .${s.name} fehlt (erwartet font: ${want})`)
    else if (got !== want) errors.push(`Text-Stil .${s.name} ist „${got}“, erwartet „${want}“`)
  }
}

// Umgekehrt: Was nur in tokens.css steht, ist entweder ein Übergangs-Alias (var(--bekannt)) oder ein Fehler.
for (const scope of ['light', 'dark', 'dark (Systemeinstellung)']) {
  for (const name of Object.keys(scopes[scope])) {
    if (!colorNames.has(name)) errors.push(`${scope}: --${name} steht nicht in tokens.json`)
  }
}
const known = new Set([...colorNames, ...baseNames])
let aliases = 0
for (const [name, value] of Object.entries(scopes.base)) {
  if (baseNames.has(name)) continue
  const target = value.match(/^var\(--([\w-]+)\)$/)?.[1]
  if (!target) errors.push(`base: --${name} steht nicht in tokens.json`)
  else if (!known.has(target)) errors.push(`Alias --${name} zeigt auf --${target}, das es nicht gibt`)
  else aliases++
}

if (errors.length) {
  console.error(`tokens.css weicht von tokens.json ab (${errors.length}):`)
  for (const e of errors) console.error(`  - ${e}`)
  process.exit(1)
}
console.log(`tokens.css passt zu tokens.json: ${colorNames.size} Farben, ${baseNames.size} weitere Tokens, ` +
  `${json.type.groups.reduce((n, g) => n + g.styles.length, 0)} Text-Stile, ${aliases} Übergangs-Aliase.`)
