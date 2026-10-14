import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import LhLevelBadge from './LhLevelBadge.vue'

describe('LhLevelBadge', () => {
  it('zeigt Level und Rang, mit Label für Screenreader', () => {
    const w = mount(LhLevelBadge, { props: { level: 34, rank: 'silver' } })
    expect(w.attributes('data-rank')).toBe('silver')
    expect(w.attributes('aria-label')).toBe('Level 34, Silber')
    expect(w.find('.lh-badge__hex').text()).toBe('34')
    expect(w.find('.lh-badge__prestige').exists()).toBe(false)
    expect(w.classes()).not.toContain('lh-badge--prestige')
  })

  it('bekommt ab Prestige 1 Ring und Prestige-Zahl', () => {
    const w = mount(LhLevelBadge, { props: { level: 4, prestige: 2 } })
    expect(w.attributes('data-rank')).toBe('bronze')
    expect(w.classes()).toContain('lh-badge--prestige')
    expect(w.find('.lh-badge__prestige').text()).toBe('2')
    expect(w.attributes('aria-label')).toBe('Level 4, Bronze, Prestige 2')
  })

  it('hat eine große Variante', () => {
    const w = mount(LhLevelBadge, { props: { level: 58, large: true } })
    expect(w.classes()).toContain('lh-badge--lg')
  })
})
