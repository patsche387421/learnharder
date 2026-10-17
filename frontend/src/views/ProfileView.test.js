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

  it('Konto löschen nur für Schüler:innen, Rechtstexte für alle', async () => {
    const router = testRouter()
    router.push('/profile')
    await router.isReady()

    useAuthStore().me = { gamer_tag: 'lena42', roles: ['student'] }
    let links = mount(ProfileView, { global: { plugins: [router] } }).findAll('a').map((a) => a.attributes('href'))
    expect(links).toEqual(expect.arrayContaining(['/profile/delete', '/datenschutz', '/nutzungsregeln', '/impressum']))

    useAuthStore().me = { gamer_tag: 'ProfMuster', roles: ['teacher'] }
    links = mount(ProfileView, { global: { plugins: [router] } }).findAll('a').map((a) => a.attributes('href'))
    expect(links).not.toContain('/profile/delete')
    expect(links).toContain('/datenschutz')
  })
})
