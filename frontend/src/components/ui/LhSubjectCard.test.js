import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import { icons } from './icons.js'
import { testRouter } from '../../test/router.js'
import LhSubjectCard from './LhSubjectCard.vue'

const pos = { code: 'POS', name: 'Programmieren und Software-Engineering' }

describe('LhSubjectCard', () => {
  it('verlinkt ein begonnenes Fach mit Icon, Fach-Level und Themen', async () => {
    const router = testRouter()
    const w = mount(LhSubjectCard, {
      props: { ...pos, level: 4, done: 12, total: 19, to: '/subjects/pos' },
      global: { plugins: [router] },
    })
    await router.isReady()
    expect(w.element.tagName).toBe('A')
    expect(w.attributes('href')).toBe('/subjects/pos')
    expect(w.find('img').attributes('src')).toBe(icons['subject-pos'])
    expect(w.find('.lh-subject__progress').attributes('aria-label')).toBe('Fortschritt POS')
    expect(w.find('.lh-xp__meta').text()).toContain('Fach-Level 4')
    expect(w.find('.lh-xp__meta').text()).toContain('12 / 19 Themen')
  })

  it('begonnen ohne Fach-Level zeigt nur die erledigten Themen', () => {
    const w = mount(LhSubjectCard, { props: { ...pos, done: 3, total: 14 } })
    expect(w.find('.lh-xp__meta').text()).not.toContain('Noch nicht begonnen')
    expect(w.find('.lh-xp__meta').text()).toContain('3 / 14 Themen')
  })

  it('zeigt „Noch nicht begonnen“ statt 0 %', () => {
    const w = mount(LhSubjectCard, { props: { ...pos, total: 14 } })
    expect(w.find('.lh-xp__meta').text()).toContain('Noch nicht begonnen')
    expect(w.find('.lh-xp__meta').text()).toContain('0 / 14 Themen')
  })

  it('ist gesperrt kein Link und sagt, warum', () => {
    const w = mount(LhSubjectCard, { props: { ...pos, locked: true, lockedText: 'Wird nach „Schleifen“ freigeschaltet.', href: '/x' } })
    expect(w.element.tagName).toBe('DIV')
    expect(w.classes()).toContain('lh-subject--locked')
    expect(w.attributes('aria-disabled')).toBe('true')
    expect(w.find('.lh-xp').exists()).toBe(false)
    expect(w.find('.lh-subject__lock').text()).toBe('Wird nach „Schleifen“ freigeschaltet.')
    expect(w.find('.lh-subject__lock .lh-icon').classes()).toContain('lh-icon--sm')
  })
})
