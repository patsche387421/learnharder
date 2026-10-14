import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import { icons } from './icons.js'
import { testRouter } from '../../test/router.js'
import LhCard from './LhCard.vue'

describe('LhCard', () => {
  it('ist ohne Link eine reine Inhaltskarte mit Slot', () => {
    const w = mount(LhCard, { props: { eyebrow: 'Fr, 17. Okt' }, slots: { default: '<p>Inhalt</p>' } })
    expect(w.element.tagName).toBe('DIV')
    expect(w.classes()).toEqual(['lh-card'])
    expect(w.find('.lh-card__eyebrow').text()).toBe('Fr, 17. Okt')
    expect(w.html()).toContain('<p>Inhalt</p>')
  })

  it('ist mit to als Ganzes antippbar, mit Titel, Text und Pfeil', async () => {
    const router = testRouter()
    const w = mount(LhCard, {
      props: { to: '/challenge', title: 'Herausforderung', text: 'Zufällige Fragen' },
      slots: { icon: '<img class="test-icon">' },
      global: { plugins: [router] },
    })
    await router.isReady()
    expect(w.element.tagName).toBe('A')
    expect(w.classes()).toContain('lh-card--link')
    expect(w.find('.test-icon').exists()).toBe(true)
    expect(w.find('.lh-card__title').text()).toBe('Herausforderung')
    expect(w.find('.lh-card__text').text()).toBe('Zufällige Fragen')
    expect(w.find('.lh-icon').attributes('style')).toContain(icons['ui-next'])
  })

  it('markiert die hervorgehobene Karte', () => {
    const w = mount(LhCard, { props: { featured: true } })
    expect(w.classes()).toContain('lh-card--featured')
  })
})
