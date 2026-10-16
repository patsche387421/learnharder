import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { mockFetch, setXsrfCookie } from '../test/api.js'
import { testRouter } from '../test/router.js'
import LoginView from './LoginView.vue'

async function view(path = '/login') {
  const router = testRouter()
  router.push(path)
  await router.isReady()
  const wrapper = mount(LoginView, { global: { plugins: [router] } })
  return { wrapper, router }
}

async function fill(wrapper) {
  const [email, password] = wrapper.findAll('input')
  await email.setValue('lena@htl.at')
  await password.setValue('geheim')
}

describe('LoginView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    setXsrfCookie('t')
  })
  afterEach(() => {
    vi.unstubAllGlobals()
    setXsrfCookie(null)
  })

  it('Einloggen geht erst mit E-Mail und Passwort', async () => {
    const { wrapper } = await view()
    const button = wrapper.find('button[type="submit"]')
    expect(button.attributes('disabled')).toBeDefined()

    await fill(wrapper)
    expect(button.attributes('disabled')).toBeUndefined()
  })

  it('nach dem Login geht es zu Lernen', async () => {
    mockFetch({ status: 204 }, { status: 200, body: { data: { gamer_tag: 'lena42' } } })
    const { wrapper, router } = await view()

    await fill(wrapper)
    await wrapper.find('form').trigger('submit')
    await flushPromises()
    expect(router.currentRoute.value.path).toBe('/learn')
  })

  it('führt zurück zur Seite, von der man kam, aber nie auf eine fremde Seite', async () => {
    mockFetch({ status: 204 }, { status: 200, body: { data: {} } })
    let { wrapper, router } = await view('/login?redirect=/profile')
    await fill(wrapper)
    await wrapper.find('form').trigger('submit')
    await flushPromises()
    expect(router.currentRoute.value.path).toBe('/profile')

    mockFetch({ status: 204 }, { status: 200, body: { data: {} } })
    ;({ wrapper, router } = await view('/login?redirect=//boese.at'))
    await fill(wrapper)
    await wrapper.find('form').trigger('submit')
    await flushPromises()
    expect(router.currentRoute.value.path).toBe('/learn')
  })

  it('zeigt die Meldung des Servers am E-Mail-Feld', async () => {
    mockFetch({ status: 204 }, { status: 422, body: { errors: { email: ['E-Mail oder Passwort stimmt nicht.'] } } })
    const { wrapper, router } = await view()

    await fill(wrapper)
    await wrapper.find('form').trigger('submit')
    await flushPromises()
    expect(wrapper.find('.lh-field--error').text()).toContain('E-Mail oder Passwort stimmt nicht.')
    expect(router.currentRoute.value.path).toBe('/login')
  })

  it('verlinkt Registrierung und Rechtstexte', async () => {
    const { wrapper } = await view()
    const links = wrapper.findAll('a').map((a) => a.attributes('href'))
    expect(links).toEqual(expect.arrayContaining(['/registrieren', '/datenschutz', '/impressum']))
  })

  it('erklärt nach einer Sperre, was los ist', async () => {
    const { wrapper } = await view('/login?gesperrt=1')
    expect(wrapper.find('.lh-field--error').text()).toContain('Dein Konto ist gesperrt. Wende dich an deine Lehrkraft.')
  })
})
