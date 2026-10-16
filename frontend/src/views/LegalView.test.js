import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { mockFetch, setXsrfCookie } from '../test/api.js'
import { testRouter } from '../test/router.js'
import LegalView from './LegalView.vue'

async function view(slug) {
  const router = testRouter()
  router.push(`/${slug}`)
  await router.isReady()
  const wrapper = mount(LegalView, { props: { slug }, global: { plugins: [router] } })
  await flushPromises()
  return { wrapper, router }
}

describe('LegalView', () => {
  beforeEach(() => setXsrfCookie('t'))
  afterEach(() => {
    vi.unstubAllGlobals()
    setXsrfCookie(null)
  })

  it('zeigt Titel und Text vom Server', async () => {
    const fetch = mockFetch({ status: 200, body: { data: { title: 'Datenschutzerklärung', version: '2026-10-entwurf', html: '<p>Was gespeichert wird</p>' } } })
    const { wrapper } = await view('datenschutz')

    expect(fetch.mock.calls[0][0]).toMatch(/\/api\/v1\/legal\/datenschutz$/)
    expect(wrapper.find('h1').text()).toBe('Datenschutzerklärung')
    expect(wrapper.find('.legal__body').html()).toContain('<p>Was gespeichert wird</p>')
  })

  it('direkt geöffnet führt Zurück zum Login', async () => {
    mockFetch({ status: 200, body: { data: { title: 'Impressum', html: '' } } })
    const { wrapper, router } = await view('impressum')

    await wrapper.find('.legal__back').trigger('click')
    await flushPromises()
    expect(router.currentRoute.value.path).toBe('/login')
  })

  it('zeigt einen Fehler mit „Nochmal versuchen“', async () => {
    mockFetch(new TypeError('offline'), { status: 200, body: { data: { title: 'Impressum', html: '<p>Hallo</p>' } } })
    const { wrapper } = await view('impressum')

    expect(wrapper.text()).toContain('Das hat nicht geklappt.')
    await wrapper.findAll('button').find((b) => b.text() === 'Nochmal versuchen').trigger('click')
    await flushPromises()
    expect(wrapper.find('h1').text()).toBe('Impressum')
  })
})
