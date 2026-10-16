import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { mockFetch, setXsrfCookie } from '../test/api.js'
import { testRouter } from '../test/router.js'
import ForgotPasswordView from './ForgotPasswordView.vue'

async function view() {
  const router = testRouter()
  router.push('/passwort-vergessen')
  await router.isReady()
  const wrapper = mount(ForgotPasswordView, { global: { plugins: [router] } })
  return { wrapper, router }
}

async function submit(wrapper) {
  await wrapper.find('form').trigger('submit')
  await flushPromises()
}

async function toReset(wrapper) {
  await wrapper.find('input[type="email"]').setValue('2026034@htl-md.schule')
  await submit(wrapper)
  await wrapper.find('input[autocomplete="one-time-code"]').setValue('123456')
  const [password, confirmation] = wrapper.findAll('input[type="password"]')
  await password.setValue('neu-geheim1')
  await confirmation.setValue('neu-geheim1')
}

describe('ForgotPasswordView', () => {
  beforeEach(() => setXsrfCookie('t'))
  afterEach(() => {
    vi.unstubAllGlobals()
    setXsrfCookie(null)
  })

  it('Code anfordern, neues Passwort setzen, dann zum Login mit Hinweis', async () => {
    const fetch = mockFetch({ status: 202, body: { message: 'ok' } }, { status: 204 })
    const { wrapper, router } = await view()

    await toReset(wrapper)
    expect(wrapper.text()).toContain('Wenn es zu 2026034@htl-md.schule ein Konto gibt, kommt gleich ein Code.')
    await submit(wrapper)

    expect(JSON.parse(fetch.mock.calls[0][1].body)).toEqual({ email: '2026034@htl-md.schule' })
    expect(JSON.parse(fetch.mock.calls[1][1].body)).toEqual({
      email: '2026034@htl-md.schule', code: '123456', password: 'neu-geheim1', password_confirmation: 'neu-geheim1',
    })
    expect(router.currentRoute.value.fullPath).toBe('/login?hinweis=passwort')
  })

  it('zeigt einen falschen Code am Code-Feld und bleibt auf der Seite', async () => {
    mockFetch({ status: 202, body: {} }, { status: 422, body: { errors: { code: ['Der Code stimmt nicht oder ist abgelaufen.'] } } })
    const { wrapper, router } = await view()

    await toReset(wrapper)
    await submit(wrapper)
    expect(wrapper.find('.lh-field--error').text()).toContain('Der Code stimmt nicht oder ist abgelaufen.')
    expect(router.currentRoute.value.path).toBe('/passwort-vergessen')
  })
})
