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
    await wrapper.find('button').trigger('click')
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
})
