import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import { icons } from './icons.js'
import LhAchievement from './LhAchievement.vue'

const tile = (props) => mount(LhAchievement, { props: { title: 'Eine Woche', description: '7 Tage in Folge lernen', icon: 'streak', ...props } })

describe('LhAchievement', () => {
  it('zeigt einen erreichten Erfolg farbig mit kurzem Datum', () => {
    const w = tile({ level: 7, unlockedAt: '2026-10-12T08:00:00+00:00' })
    expect(w.element.tagName).toBe('LI')
    expect(w.classes()).toEqual(['lh-achievement'])
    expect(w.find('img').attributes('src')).toBe(icons['icon-streak'])
    expect(w.find('.lh-achievement__level').text()).toBe('7')
    expect(w.find('.lh-achievement__title').text()).toBe('Eine Woche')
    expect(w.find('.lh-achievement__date').text()).toBe('12. Okt')
    expect(w.find('.lh-achievement__text').exists()).toBe(false)
    expect(w.attributes('aria-label')).toBe('Eine Woche, erreicht am 12. Oktober')
  })

  it('zeigt einen fehlenden Erfolg grau mit lesbarer Bedingung', () => {
    const w = tile({ title: 'Ein Monat', description: '30 Tage in Folge lernen', level: 30 })
    expect(w.classes()).toContain('lh-achievement--locked')
    expect(w.find('.lh-achievement__text').text()).toBe('30 Tage in Folge lernen')
    expect(w.find('.lh-achievement__date').exists()).toBe(false)
    expect(w.attributes('aria-label')).toBe('Ein Monat, noch nicht erreicht: 30 Tage in Folge lernen')
  })

  it('zeigt einen geheimen Erfolg nur als Fragezeichen', () => {
    const w = mount(LhAchievement, { props: { secret: true, title: null, description: null, icon: null } })
    expect(w.classes()).toContain('lh-achievement--secret')
    expect(w.classes()).not.toContain('lh-achievement--locked')
    expect(w.find('.lh-achievement__secret').text()).toBe('?')
    expect(w.find('img').exists()).toBe(false)
    expect(w.find('.lh-achievement__title').text()).toBe('Geheimer Erfolg')
    expect(w.find('.lh-achievement__text').exists()).toBe(false)
    expect(w.attributes('aria-label')).toBe('Geheimer Erfolg')
  })

  it('zeigt einen erreichten geheimen Erfolg wie jeden anderen', () => {
    const w = tile({ secret: true, title: 'Allrounder', icon: 'achievement', unlockedAt: '2026-10-12T08:00:00+00:00' })
    expect(w.classes()).toEqual(['lh-achievement'])
    expect(w.find('.lh-achievement__title').text()).toBe('Allrounder')
    expect(w.find('img').attributes('src')).toBe(icons['icon-achievement'])
  })

  it('zeigt beim Fach-Level das Icon des Fachs und keine Stufe bei 1', () => {
    const w = tile({ subject: 'pos', level: null })
    expect(w.find('img').attributes('src')).toBe(icons['subject-pos'])
    expect(w.find('.lh-achievement__level').exists()).toBe(false)
    expect(tile({ level: 1 }).find('.lh-achievement__level').exists()).toBe(false)
  })

  it('nennt das Datum in Wiener Zeit', () => {
    // 23:30 UTC am 11. ist in Wien schon der 12.
    expect(tile({ unlockedAt: '2026-10-11T23:30:00+00:00' }).find('.lh-achievement__date').text()).toBe('12. Okt')
  })
})
