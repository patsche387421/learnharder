import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { mockFetch, setXsrfCookie } from '../test/api.js'
import { testRouter } from '../test/router.js'
import { useAuthStore } from '../stores/auth.js'
import RegisterView from './RegisterView.vue'

async function view() {
  const router = testRouter()
  router.push('/registrieren')
  await router.isReady()
  const wrapper = mount(RegisterView, { global: { plugins: [router] } })
  return { wrapper, router }
}

const body = (fetch, call) => JSON.parse(fetch.mock.calls[call][1].body)

async function submit(wrapper) {
  await wrapper.find('form').trigger('submit')
  await flushPromises()
}

async function toProfile(wrapper) {
  await wrapper.find('input[type="email"]').setValue('2026034@htl-md.schule')
  await submit(wrapper)
  await wrapper.find('input[autocomplete="one-time-code"]').setValue('123456')
  await submit(wrapper)
}

async function fillProfile(wrapper) {
  await wrapper.find('input[autocomplete="username"]').setValue('Neo_2026')
  const [password, confirmation] = wrapper.findAll('input[type="password"]')
  await password.setValue('geheim123')
  await confirmation.setValue('geheim123')
}

describe('RegisterView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    setXsrfCookie('t')
  })
  afterEach(() => {
    vi.unstubAllGlobals()
    setXsrfCookie(null)
  })

  it('führt in drei Schritten zum Konto und dann zu Lernen', async () => {
    const fetch = mockFetch(
      { status: 202, body: { message: 'ok' } },
      { status: 200, body: { token: 'einmal' } },
      { status: 201, body: { data: { gamer_tag: 'Neo_2026', roles: ['student'], school_class: null } } },
    )
    const { wrapper, router } = await view()

    await toProfile(wrapper)
    expect(body(fetch, 0)).toEqual({ email: '2026034@htl-md.schule' })
    expect(body(fetch, 1)).toEqual({ email: '2026034@htl-md.schule', code: '123456' })

    await fillProfile(wrapper)
    const [is14, terms] = wrapper.findAll('input[type="checkbox"]')
    expect(is14.element.checked).toBe(false)
    expect(terms.element.checked).toBe(false)
    await is14.setValue(true)
    await terms.setValue(true)
    await submit(wrapper)

    expect(body(fetch, 2)).toEqual({
      token: 'einmal', gamer_tag: 'Neo_2026', password: 'geheim123', password_confirmation: 'geheim123', is_14: true, accepts_terms: true,
    })
    expect(useAuthStore().me.gamer_tag).toBe('Neo_2026')
    expect(router.currentRoute.value.path).toBe('/')
  })

  it('zeigt die Meldung, wenn die Mail keine Schul-Mail ist', async () => {
    mockFetch({ status: 422, body: { errors: { email: ['Registrieren geht nur mit deiner Schul-Mail.'] } } })
    const { wrapper } = await view()

    await wrapper.find('input[type="email"]').setValue('lena@gmail.com')
    await submit(wrapper)
    expect(wrapper.find('.lh-field--error').text()).toContain('Registrieren geht nur mit deiner Schul-Mail.')
    expect(wrapper.find('input[autocomplete="one-time-code"]').exists()).toBe(false)
  })

  it('zeigt Fehler zu Alter und Zustimmung an den Häkchen', async () => {
    mockFetch(
      { status: 202, body: {} },
      { status: 200, body: { token: 'einmal' } },
      { status: 422, body: { errors: { is_14: ['Bestätige, dass du mindestens 14 bist.'], accepts_terms: ['Stimm bitte der Datenschutzerklärung und den Nutzungsregeln zu.'] } } },
    )
    const { wrapper, router } = await view()

    await toProfile(wrapper)
    await fillProfile(wrapper)
    await submit(wrapper)

    const hints = wrapper.findAll('.lh-check--error .lh-check__hint').map((h) => h.text())
    expect(hints).toEqual(['Bestätige, dass du mindestens 14 bist.', 'Stimm bitte der Datenschutzerklärung und den Nutzungsregeln zu.'])
    expect(router.currentRoute.value.path).toBe('/registrieren')
  })

  it('ist die Bestätigung abgelaufen, beginnt es wieder bei der E-Mail', async () => {
    mockFetch(
      { status: 202, body: {} },
      { status: 200, body: { token: 'alt' } },
      { status: 422, body: { errors: { token: ['Die Bestätigung ist abgelaufen. Fang bitte nochmal mit deiner E-Mail an.'] } } },
    )
    const { wrapper } = await view()

    await toProfile(wrapper)
    await fillProfile(wrapper)
    await submit(wrapper)

    expect(wrapper.find('input[type="email"]').element.value).toBe('2026034@htl-md.schule')
    expect(wrapper.find('.register__notice').text()).toBe('Die Bestätigung ist abgelaufen. Fang bitte nochmal mit deiner E-Mail an.')
  })

  it('erklärt den Weg für unter 14 mit dem Elternformular', async () => {
    mockFetch({ status: 202, body: {} }, { status: 200, body: { token: 'einmal' } })
    const { wrapper } = await view()

    await toProfile(wrapper)
    const toggle = wrapper.findAll('button').find((b) => b.text() === 'Noch nicht 14?')
    await toggle.trigger('click')
    expect(wrapper.find('a[href="/einwilligung-eltern"]').exists()).toBe(true)
    expect(wrapper.find('a[href="/datenschutz"][target="_blank"]').exists()).toBe(true)
  })
})
