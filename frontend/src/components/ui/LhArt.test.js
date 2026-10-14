import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import { icons } from './icons.js'
import LhArt from './LhArt.vue'

describe('LhArt', () => {
  it('rendert das illustrierte Icon als dekoratives <img> in 48px', () => {
    const img = mount(LhArt, { props: { name: 'subject-pos' } })
    expect(img.element.tagName).toBe('IMG')
    expect(img.attributes('src')).toBe(icons['subject-pos'])
    expect(img.attributes('width')).toBe('48')
    expect(img.attributes('alt')).toBe('')
  })

  it('übernimmt Größe und Alt-Text', () => {
    const img = mount(LhArt, { props: { name: 'icon-xp', size: 32, alt: 'EP' } })
    expect(img.attributes('height')).toBe('32')
    expect(img.attributes('alt')).toBe('EP')
  })
})
