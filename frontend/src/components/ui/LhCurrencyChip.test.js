import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import LhCurrencyChip from './LhCurrencyChip.vue'

const chip = (value, extra = {}) => mount(LhCurrencyChip, { props: { icon: 'icon-xp', label: 'EP', value, ...extra } })

describe('LhCurrencyChip', () => {
  it('zeigt Zahlen unter 10.000 voll', () => {
    expect(chip(1240).text()).toBe('1240')
    expect(chip(9999).text()).toBe('9999')
  })

  it('kürzt ab 10.000 deutsch ab', () => {
    expect(chip(10000).text()).toBe('10k')
    expect(chip(12400).text()).toBe('12,4k')
  })

  it('gibt dem Screenreader immer die volle Zahl mit Begriff', () => {
    const w = chip(12400)
    expect(w.attributes('role')).toBe('img')
    expect(w.attributes('aria-label')).toBe('12400 EP')
  })

  it('hat eine große Variante mit 32px-Icon', () => {
    const w = chip(5, { large: true })
    expect(w.classes()).toContain('lh-chip--lg')
    expect(w.find('img').attributes('width')).toBe('32')
  })
})
