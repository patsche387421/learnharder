import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import LhXpBar from './LhXpBar.vue'

describe('LhXpBar', () => {
  it('ist eine Fortschrittsleiste mit Werten für Screenreader', () => {
    const w = mount(LhXpBar, { props: { value: 154, max: 200 } })
    expect(w.attributes('role')).toBe('progressbar')
    expect(w.attributes('aria-label')).toBe('Erfahrungspunkte')
    expect(w.attributes('aria-valuenow')).toBe('154')
    expect(w.attributes('aria-valuemax')).toBe('200')
    expect(w.find('.lh-xp__fill').attributes('style')).toContain('--value: 77%')
  })

  it('begrenzt die Füllung auf 0–100 % und verträgt max = 0', () => {
    expect(mount(LhXpBar, { props: { value: 250, max: 200 } }).find('.lh-xp__fill').attributes('style')).toContain('--value: 100%')
    expect(mount(LhXpBar, { props: { value: 5, max: 0 } }).find('.lh-xp__fill').attributes('style')).toContain('--value: 0%')
  })

  it('zeigt Textzeilen nur, wenn Slots gefüllt sind', () => {
    const bare = mount(LhXpBar, { props: { value: 1, max: 2 } })
    expect(bare.findAll('.lh-xp__meta')).toHaveLength(0)

    const full = mount(LhXpBar, {
      props: { value: 1, max: 2 },
      slots: { top: '<span>Level 21</span>', bottom: '<span>46 EP bis Level 22</span>' },
    })
    const meta = full.findAll('.lh-xp__meta')
    expect(meta.map((m) => m.text())).toEqual(['Level 21', '46 EP bis Level 22'])
  })
})
