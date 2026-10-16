import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { useAuthStore } from '../stores/auth.js'
import { mockFetch, setXsrfCookie } from '../test/api.js'
import { testRouter } from '../test/router.js'
import DeleteAccountView from './DeleteAccountView.vue'

async function view(roles = ['student']) {
  useAuthStore().me = { gamer_tag: 'lena42', roles }
  const router = testRouter()
  router.push('/profile/delete')
  await router.isReady()
  const wrapper = mount(DeleteAccountView, { global: { plugins: [router] } })
  return { wrapper, router }
}

async function confirm(wrapper, password) {
  await wrapper.find('input[type="password"]').setValue(password)
  await wrapper.find('form').trigger('submit')
  await flushPromises()
}

describe('DeleteAccountView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    setXsrfCookie('t')
  })
  afterEach(() => {
    vi.unstubAllGlobals()
    setXsrfCookie(null)
  })

  it('löscht erst mit Passwort und führt zum Login mit Hinweis', async () => {
    const fetch = mockFetch({ status: 204 })
    const { wrapper, router } = await view()

    const button = wrapper.find('button[type="submit"]')
    expect(button.classes()).toContain('lh-btn--danger')
    expect(button.attributes('disabled')).toBeDefined()

    await confirm(wrapper, 'geheim123')
    expect(fetch.mock.calls[0][0]).toMatch(/\/api\/v1\/me$/)
    expect(fetch.mock.calls[0][1].method).toBe('DELETE')
    expect(JSON.parse(fetch.mock.calls[0][1].body)).toEqual({ password: 'geheim123' })
    expect(useAuthStore().me).toBeNull()
    expect(router.currentRoute.value.fullPath).toBe('/login?hinweis=geloescht')
  })

  it('falsches Passwort: Meldung am Feld, Konto bleibt angemeldet', async () => {
    mockFetch({ status: 422, body: { errors: { password: ['Das Passwort stimmt nicht.'] } } })
    const { wrapper, router } = await view()

    await confirm(wrapper, 'falsch')
    expect(wrapper.find('.lh-field--error').text()).toContain('Das Passwort stimmt nicht.')
    expect(useAuthStore().me).not.toBeNull()
    expect(router.currentRoute.value.path).toBe('/profile/delete')
  })

  it('Personal sieht kein Formular, nur den Weg über den Admin', async () => {
    const { wrapper } = await view(['teacher'])

    expect(wrapper.find('form').exists()).toBe(false)
    expect(wrapper.text()).toContain('Dein Konto kann nur ein Admin sperren oder löschen.')
  })
})
