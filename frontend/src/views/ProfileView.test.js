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
    await wrapper.find('button').trigger('click')
    await flushPromises()
    expect(useAuthStore().me).toBeNull()
    expect(router.currentRoute.value.path).toBe('/login')
  })
})
