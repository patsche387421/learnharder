import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import { testRouter } from '../../test/router.js'
import LhButton from './LhButton.vue'

describe('LhButton', () => {
  it('ist standardmäßig ein primärer <button type="button">', () => {
    const w = mount(LhButton, { slots: { default: 'Weiter' } })
    expect(w.element.tagName).toBe('BUTTON')
    expect(w.attributes('type')).toBe('button')
    expect(w.classes()).toEqual(['lh-btn', 'lh-btn--primary'])
    expect(w.text()).toBe('Weiter')
  })

  it('setzt Variante und volle Breite als Modifier', () => {
    const w = mount(LhButton, { props: { variant: 'ghost', block: true } })
    expect(w.classes()).toContain('lh-btn--ghost')
    expect(w.classes()).toContain('lh-btn--block')
  })

  it('kennt die Variante Gefahr für Unumkehrbares', () => {
    const w = mount(LhButton, { props: { variant: 'danger' } })
    expect(w.classes()).toEqual(['lh-btn', 'lh-btn--danger'])
    expect(LhButton.props.variant.validator('danger')).toBe(true)
    expect(LhButton.props.variant.validator('rot')).toBe(false)
  })

  it('wird mit to zum Router-Link und mit href zum normalen Link', async () => {
    const router = testRouter()
    const internal = mount(LhButton, { props: { to: '/hub' }, global: { plugins: [router] } })
    await router.isReady()
    expect(internal.element.tagName).toBe('A')
    expect(internal.attributes('href')).toBe('/hub')

    const external = mount(LhButton, { props: { href: 'https://example.org' } })
    expect(external.element.tagName).toBe('A')
    expect(external.attributes('href')).toBe('https://example.org')
  })

  it('bleibt deaktiviert ein <button disabled>, auch mit Link', () => {
    const w = mount(LhButton, { props: { href: '/x', disabled: true } })
    expect(w.element.tagName).toBe('BUTTON')
    expect(w.attributes('disabled')).toBeDefined()
  })
})
