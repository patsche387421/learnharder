import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { useAuthStore } from '../stores/auth.js'
import { mockFetch, setXsrfCookie } from '../test/api.js'
import { testRouter } from '../test/router.js'
import ProfileView from './ProfileView.vue'

describe('ProfileView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    setXsrfCookie('t')
  })
  afterEach(() => {
    vi.unstubAllGlobals()
    setXsrfCookie(null)
    localStorage.clear()
    document.documentElement.removeAttribute('data-theme')
  })

  it('zeigt den Gamer-Tag und meldet ab', async () => {
    useAuthStore().me = { gamer_tag: 'lena42' }
    mockFetch({ status: 204 })
    const router = testRouter()
    router.push('/profile')
    await router.isReady()
    const wrapper = mount(ProfileView, { global: { plugins: [router] } })

    expect(wrapper.text()).toContain('lena42')
    expect(wrapper.text()).not.toContain('Klasse')
    await wrapper.findAll('button').find((b) => b.text() === 'Abmelden').trigger('click')
    await flushPromises()
    expect(useAuthStore().me).toBeNull()
    expect(router.currentRoute.value.path).toBe('/login')
  })

  it('zeigt die eigene Klasse, wenn es eine gibt', async () => {
    useAuthStore().me = { gamer_tag: 'lena42', school_class: { name: '2AHIT' } }
    mockFetch({ status: 200, body: { data: [] } })
    const router = testRouter()
    router.push('/profile')
    await router.isReady()
    const wrapper = mount(ProfileView, { global: { plugins: [router] } })

    expect(wrapper.findAll('.lh-card')[1].text()).toContain('Klasse')
    expect(wrapper.findAll('.lh-card')[1].text()).toContain('2AHIT')
  })

  it('zeigt den Roboter mit Grundausstattung, Pose und Lackierung als Vorschau', async () => {
    useAuthStore().me = { gamer_tag: 'lena42', brainpower: { current: 50, max: 100 } }
    const router = testRouter()
    router.push('/profile')
    await router.isReady()
    const wrapper = mount(ProfileView, { global: { plugins: [router] } })

    const section = wrapper.find('.profile__robot')
    expect(section.find('.robot').attributes('aria-label')).toBe('Dein Roboter')
    expect(section.find('.robot__charge').attributes('width')).toBe('14')
    expect(section.text()).toContain('Antrieb')
    expect(section.text()).toContain('Beine')
    expect(section.text()).toContain('Leer, das erste kommt mit Fach-Level 3')

    const choose = (name) => section.findAll('button').find((b) => b.text() === name)
    await choose('Jubeln').trigger('click')
    expect(choose('Jubeln').attributes('aria-pressed')).toBe('true')
    expect(section.find('.robot__arm--left').element.style.getPropertyValue('--pose')).toBe('140deg')

    await choose('Graphit').trigger('click')
    expect(section.find('.robot').element.style.getPropertyValue('--paint-main')).toBe('#5F5A52')
    expect(section.text()).toContain('Graphit')
  })

  it('zeigt Level, Rang, EP gesamt und die Fach-Level', async () => {
    useAuthStore().me = {
      gamer_tag: 'lena42', roles: ['student'], school_class: { name: '2AHIT' },
      level: 25, rank: 'silver', prestige: 1, xp: { total: 8941, level_start: 8941, next_level: 9000 },
      streak: { current: 0, longest: 12 },
    }
    const fetch = mockFetch({ status: 200, body: { data: [
      { code: 'pos', name: 'Programmieren', level: 4 },
      { code: 'dbi', name: 'Datenbanken', level: 1 },
    ] } })
    const router = testRouter()
    router.push('/profile')
    await router.isReady()
    const wrapper = mount(ProfileView, { global: { plugins: [router] } })
    await flushPromises()

    expect(fetch.mock.calls[0][0]).toMatch(/\/subjects$/)
    const stats = wrapper.find('.profile__stats')
    expect(stats.find('h2').text()).toBe('Fortschritt')
    expect(stats.find('.lh-badge').attributes('data-rank')).toBe('silver')
    expect(stats.text()).toContain('Level 25 · Silber · Prestige 1')
    expect(stats.find('.profile__xp').text()).toBe('8.941 EP gesamt')
    const rows = stats.findAll('.profile__slot').map((row) => [row.find('dt').text(), row.find('dd').text()])
    expect(rows).toEqual([['Programmieren', 'Fach-Level 4'], ['Datenbanken', 'Fach-Level 1']])
    expect(stats.find('.profile__streak').text()).toBe('Längster Streak: 12 Tage')
  })

  it('ohne Klasse fragt das Profil keine Fächer ab und lässt Prestige 0 weg', async () => {
    useAuthStore().me = { gamer_tag: 'lena42', roles: ['student'], level: 3, rank: 'bronze', prestige: 0, xp: { total: 11 } }
    const fetch = mockFetch({ status: 200, body: { data: [] } })
    const router = testRouter()
    router.push('/profile')
    await router.isReady()
    const wrapper = mount(ProfileView, { global: { plugins: [router] } })
    await flushPromises()

    // Nur die Erfolge, die gibt es auch ohne Klasse
    expect(fetch.mock.calls.map(([url]) => new URL(url).pathname)).toEqual(['/api/v1/achievements'])
    expect(wrapper.find('.profile__stats').text()).toContain('Level 3 · Bronze')
    expect(wrapper.find('.profile__stats').text()).not.toContain('Prestige')
    expect(wrapper.find('.profile__xp').text()).toBe('11 EP gesamt')
    expect(wrapper.find('.profile__stats dl').exists()).toBe(false)
  })

  it.each([
    [undefined, 'Noch kein Streak'],
    [{ current: 0, longest: 0 }, 'Noch kein Streak'],
    [{ current: 1, longest: 1 }, 'Längster Streak: 1 Tag'],
  ])('längster Streak %j: „%s“', async (streak, text) => {
    useAuthStore().me = { gamer_tag: 'lena42', roles: ['student'], streak }
    const router = testRouter()
    router.push('/profile')
    await router.isReady()
    const wrapper = mount(ProfileView, { global: { plugins: [router] } })

    expect(wrapper.find('.profile__streak').text()).toBe(text)
  })

  it('lässt die Fach-Level nach einem Fehler neu laden', async () => {
    useAuthStore().me = { gamer_tag: 'lena42', roles: ['student'], school_class: { name: '2AHIT' } }
    // Fächer, Erfolge (leer), dann die Fächer noch einmal
    mockFetch(new TypeError('offline'), { status: 200, body: { data: [] } }, { status: 200, body: { data: [{ code: 'pos', name: 'Programmieren', level: 4 }] } })
    const router = testRouter()
    router.push('/profile')
    await router.isReady()
    const wrapper = mount(ProfileView, { global: { plugins: [router] } })
    await flushPromises()

    expect(wrapper.find('.profile__stats').text()).toContain('Prüf deine Verbindung')
    await wrapper.find('.profile__stats .lh-empty button').trigger('click')
    await flushPromises()
    expect(wrapper.find('.profile__slot').text()).toContain('Fach-Level 4')
  })

  it('Konto löschen nur für Schüler:innen, Rechtstexte für alle', async () => {
    const router = testRouter()
    router.push('/profile')
    await router.isReady()

    useAuthStore().me = { gamer_tag: 'lena42', roles: ['student'] }
    let links = mount(ProfileView, { global: { plugins: [router] } }).findAll('a').map((a) => a.attributes('href'))
    expect(links).toEqual(expect.arrayContaining(['/profile/delete', '/datenschutz', '/nutzungsregeln', '/impressum']))

    useAuthStore().me = { gamer_tag: 'ProfMuster', roles: ['teacher'] }
    mockFetch({ status: 200, body: { data: [] } })
    links = mount(ProfileView, { global: { plugins: [router] } }).findAll('a').map((a) => a.attributes('href'))
    expect(links).not.toContain('/profile/delete')
    expect(links).toContain('/datenschutz')
  })

  it('Darstellung: folgt zuerst dem Gerät, Dunkel gilt sofort und bleibt auf dem Gerät', async () => {
    useAuthStore().me = { gamer_tag: 'lena42', roles: ['student'] }
    const router = testRouter()
    router.push('/profile')
    await router.isReady()
    const wrapper = mount(ProfileView, { global: { plugins: [router] } })

    const group = wrapper.findAll('fieldset').find((f) => f.find('legend').text() === 'Darstellung')
    expect(group.findAll('label').map((l) => l.text())).toEqual(['Wie Gerät', 'Hell', 'Dunkel'])
    expect(group.find('input:checked').element.value).toBe('system')
    expect(group.text()).toContain('Gilt nur auf diesem Gerät.')

    await group.findAll('input')[2].setValue()
    expect(document.documentElement.getAttribute('data-theme')).toBe('dark')
    expect(localStorage.getItem('lh-theme')).toBe('dark')
  })

  it('zeigt die Erfolge als Raster und danach die Trophäen frisch vom Server', async () => {
    useAuthStore().me = { gamer_tag: 'lena42', roles: ['student'], trophies: 0 }
    const fetch = mockFetch(
      { status: 200, body: { data: [
        { id: 'a', secret: false, title: 'Eine Woche', description: '7 Tage in Folge lernen', icon_name: 'streak', subject: null, level: 7, trophies: 2, unlocked_at: '2026-10-12T08:00:00+00:00' },
        { id: 'b', secret: false, title: 'Ein Monat', description: '30 Tage in Folge lernen', icon_name: 'streak', subject: null, level: 30, trophies: 5, unlocked_at: null },
        { id: 'c', secret: true, title: null, description: null, icon_name: null, subject: null, level: null, trophies: null, unlocked_at: null },
      ] } },
      { status: 200, body: { data: { gamer_tag: 'lena42', roles: ['student'], trophies: 2 } } },
    )
    const router = testRouter()
    router.push('/profile')
    await router.isReady()
    const wrapper = mount(ProfileView, { global: { plugins: [router] } })
    await flushPromises()

    expect(fetch.mock.calls.map(([url]) => new URL(url).pathname)).toEqual(['/api/v1/achievements', '/api/v1/me'])
    const section = wrapper.find('.profile__achievements')
    expect(section.find('h2').text()).toBe('Erfolge · 1 / 3')
    expect(section.find('.lh-chip').attributes('aria-label')).toBe('2 Trophäen')
    const tiles = section.findAll('.lh-achievement')
    expect(tiles.map((tile) => tile.attributes('aria-label'))).toEqual([
      'Eine Woche, erreicht am 12. Oktober',
      'Ein Monat, noch nicht erreicht: 30 Tage in Folge lernen',
      'Geheimer Erfolg',
    ])
  })

  it('lässt die Erfolge nach einem Fehler neu laden und blendet sie ohne Katalog aus', async () => {
    useAuthStore().me = { gamer_tag: 'lena42', roles: ['student'] }
    mockFetch(new TypeError('offline'), { status: 200, body: { data: [] } })
    const router = testRouter()
    router.push('/profile')
    await router.isReady()
    const wrapper = mount(ProfileView, { global: { plugins: [router] } })
    await flushPromises()

    expect(wrapper.find('.profile__achievements').text()).toContain('Prüf deine Verbindung')
    await wrapper.find('.profile__achievements .lh-empty button').trigger('click')
    await flushPromises()
    expect(wrapper.find('.profile__achievements').exists()).toBe(false)
  })
})
