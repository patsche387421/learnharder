import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { icons } from '../components/ui/icons.js'
import { useAuthStore } from '../stores/auth.js'
import { mockFetch } from '../test/api.js'
import { mountRoute } from '../test/router.js'
import HubView from './HubView.vue'

const show = () => mountRoute(mount, HubView, '/', '/')

const ME = {
  gamer_tag: 'lena42',
  roles: ['student'],
  school_class: { name: '2AHIT' },
  level: 7,
  rank: 'silver',
  prestige: 0,
  xp: { total: 136, level_start: 72, next_level: 192 },
  brainpower: { current: 80, max: 100 },
}

const NEXT = { slug: 'schleifen', title: 'Schleifen', subject: { code: 'pos', name: 'Programmieren' } }

/** Uhrzeit für die Begrüßung, nur Date wird ersetzt */
function at(hour) {
  vi.useFakeTimers({ toFake: ['Date'] })
  vi.setSystemTime(new Date(2026, 9, 17, hour, 30))
}

describe('HubView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    useAuthStore().me = { ...ME }
  })
  afterEach(() => {
    vi.useRealTimers()
    vi.unstubAllGlobals()
  })

  it.each([
    [7, 'Guten Morgen, lena42!'],
    [14, 'Hey lena42!'],
    [20, 'Guten Abend, lena42!'],
    [23, 'Noch wach, lena42?'],
    [3, 'Noch wach, lena42?'],
  ])('begrüßt um %i Uhr mit „%s“', async (hour, text) => {
    at(hour)
    mockFetch({ status: 200, body: { data: { next_topic: NEXT } } })
    const { wrapper } = await show()

    expect(wrapper.find('h1').text()).toBe(text)
  })

  it('zeigt Level, EP bis zum nächsten Level und Brainpower aus /me', async () => {
    mockFetch({ status: 200, body: { data: { next_topic: NEXT } } })
    const { wrapper } = await show()

    expect(wrapper.find('.lh-badge').attributes('aria-label')).toBe('Level 7, Silber')
    expect(wrapper.text()).toContain('Level 7 · Silber')
    expect(wrapper.find('.lh-xp').attributes('aria-valuenow')).toBe('64')
    expect(wrapper.find('.lh-xp').attributes('aria-valuemax')).toBe('120')
    expect(wrapper.text()).toContain('64 / 120 EP')
    expect(wrapper.text()).toContain('56 EP bis Level 8')
    expect(wrapper.find('.lh-chip').attributes('aria-label')).toBe('80 Brainpower')
  })

  it('„Weiterlernen“ führt zum nächsten offenen Thema', async () => {
    const fetch = mockFetch({ status: 200, body: { data: { next_topic: NEXT } } })
    const { wrapper } = await show()
    await flushPromises()

    expect(fetch.mock.calls[0][0]).toMatch(/\/hub$/)
    const card = wrapper.find('.lh-card--featured')
    expect(card.attributes('href')).toBe('/learn/pos/schleifen')
    expect(card.find('.lh-card__eyebrow').text()).toBe('Weiterlernen')
    expect(card.find('.lh-card__title').text()).toBe('Schleifen')
    expect(card.find('.lh-card__text').text()).toBe('Programmieren')
    expect(card.find('img').attributes('src')).toBe(icons['subject-pos'])
    expect(wrapper.find('.hub__sub').text()).toBe('Weiter mit Schleifen? 5 Min. reichen.')
  })

  it('ist alles erledigt, schlägt der Hub Wiederholen vor', async () => {
    mockFetch({ status: 200, body: { data: { next_topic: null } } })
    const { wrapper } = await show()
    await flushPromises()

    const card = wrapper.find('.lh-card--featured')
    expect(card.attributes('href')).toBe('/learn')
    expect(card.text()).toContain('Alles erledigt.')
    expect(card.text()).toContain('Wiederhol ein Thema')
    expect(wrapper.find('.hub__sub').exists()).toBe(false)
  })

  it('ohne Klasse erklärt der Hub, warum es nichts gibt, und fragt den Server nicht', async () => {
    useAuthStore().me = { ...ME, school_class: null }
    const fetch = mockFetch()
    const { wrapper } = await show()
    await flushPromises()

    expect(fetch).not.toHaveBeenCalled()
    expect(wrapper.find('.lh-empty').text()).toContain('Noch keiner Klasse zugeordnet.')
    expect(wrapper.find('.lh-card--featured').exists()).toBe(false)
  })

  it('bei einem Fehler lässt sich neu laden', async () => {
    mockFetch(new TypeError('offline'), { status: 200, body: { data: { next_topic: NEXT } } })
    const { wrapper } = await show()
    await flushPromises()

    expect(wrapper.text()).toContain('Prüf deine Verbindung')
    await wrapper.find('.lh-empty button').trigger('click')
    await flushPromises()
    expect(wrapper.find('.lh-card--featured').attributes('href')).toBe('/learn/pos/schleifen')
  })

  it('hält Platz für Avatar und kommende Spiel-Elemente frei', async () => {
    mockFetch({ status: 200, body: { data: { next_topic: NEXT } } })
    const { wrapper } = await show()

    expect(wrapper.find('.hub__avatar').attributes('aria-label')).toBe('Avatar, kommt bald')
    const soon = wrapper.findAll('.hub__soonitem').map((item) => item.text())
    expect(soon).toEqual(['Streak', 'Herausforderung', 'Spielhalle', 'Liga-Platz', 'Shop', 'Erfolge'])
    expect(wrapper.find('.hub__soon a').exists()).toBe(false)
  })
})
