import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { icons } from '../components/ui/icons.js'
import { useAuthStore } from '../stores/auth.js'
import { mockFetch, setXsrfCookie } from '../test/api.js'
import { mountRoute } from '../test/router.js'
import ShopView from './ShopView.vue'

const BOOSTERS = [
  { kind: 'x1_5_30', multiplier: 1.5, minutes: 30, cost: 20 },
  { kind: 'x2_30', multiplier: 2, minutes: 30, cost: 40 },
  { kind: 'x2_60', multiplier: 2, minutes: 60, cost: 70 },
]
const shop = (extra) => ({
  status: 200,
  body: { data: { brainpower: { current: 60, max: 100 }, booster: null, boosters: BOOSTERS, xp_left_today: true, ...extra } },
})

async function show(...responses) {
  const fetch = mockFetch(...responses)
  const { wrapper } = await mountRoute(mount, ShopView, '/shop', '/shop')
  await flushPromises()
  return { wrapper, fetch }
}

const offers = (wrapper) =>
  wrapper.findAll('.lh-offer').map((o) => ({
    name: o.find('.lh-offer__name').text(),
    meta: o.find('.lh-offer__meta').text(),
    button: o.find('button').text(),
    disabled: o.find('button').attributes('disabled') !== undefined,
    why: o.find('.lh-offer__why').exists() ? o.find('.lh-offer__why').text() : '',
  }))

describe('ShopView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    useAuthStore().me = { gamer_tag: 'lena42', brainpower: { current: 60, max: 100 }, booster: null }
    setXsrfCookie('t')
    vi.useFakeTimers({ toFake: ['Date', 'setInterval', 'clearInterval'] })
    vi.setSystemTime(new Date('2026-10-17T12:07:00Z'))
  })
  afterEach(() => {
    vi.useRealTimers()
    vi.unstubAllGlobals()
    setXsrfCookie(null)
  })

  it('zeigt Brainpower und die Angebote vom Server, zu teure mit Grund', async () => {
    const { wrapper, fetch } = await show(shop())

    expect(fetch.mock.calls[0][0]).toMatch(/\/shop$/)
    expect(wrapper.find('h1').text()).toBe('Shop')
    expect(wrapper.find('.lh-chip').attributes('aria-label')).toBe('60 Brainpower')
    expect(wrapper.find('h2').text()).toBe('EP-Booster')
    expect(wrapper.find('.lh-shop__intro').text()).toContain('Gilt für Fragen, die dir heute noch EP bringen.')
    expect(offers(wrapper)).toEqual([
      { name: '1,5× EP', meta: '30 Min.', button: 'Starten · 20', disabled: false, why: '' },
      { name: '2× EP', meta: '30 Min.', button: 'Starten · 40', disabled: false, why: '' },
      { name: '2× EP', meta: '60 Min.', button: 'Starten · 70', disabled: true, why: 'Noch 10 Brainpower' },
    ])
    expect(wrapper.find('.lh-offer img').attributes('src')).toBe(icons['icon-booster'])
    expect(wrapper.find('.lh-shop__running').exists()).toBe(false)
    expect(wrapper.find('.shop__no-xp').exists()).toBe(false)
  })

  it('sagt ehrlich, wenn heute keine Frage mehr EP bringt, und lässt trotzdem kaufen', async () => {
    const { wrapper } = await show(shop({ xp_left_today: false }))

    expect(wrapper.find('.shop__no-xp').text()).toBe('Heute bringen deine Themen keine EP mehr. Morgen lohnt sich ein Booster wieder.')
    expect(offers(wrapper)[0]).toMatchObject({ disabled: false, why: '' })
  })

  it('startet einen Booster und übernimmt Brainpower und Booster vom Server', async () => {
    const started = { brainpower: { current: 20, max: 100 }, booster: { multiplier: 2, ends_at: '2026-10-17T12:37:00+00:00' } }
    const { wrapper, fetch } = await show(shop(), { status: 201, body: { data: started } })

    await wrapper.findAll('.lh-offer button')[1].trigger('click')
    await flushPromises()

    expect(fetch.mock.calls[1][0]).toMatch(/\/boosters$/)
    expect(JSON.parse(fetch.mock.calls[1][1].body)).toEqual({ kind: 'x2_30' })
    expect(wrapper.find('.lh-chip').attributes('aria-label')).toBe('20 Brainpower')
    expect(wrapper.find('.lh-shop__running').text()).toBe('2× EP läuft · noch 30 Min.Danach kannst du den nächsten starten.')
    expect(offers(wrapper).every((o) => o.disabled && o.why === '')).toBe(true)
    expect(wrapper.findAll('.lh-offer button').map((b) => b.attributes('aria-describedby'))).toEqual(Array(3).fill('shop-running'))
    expect(wrapper.find('#shop-running').exists()).toBe(true)
    expect(useAuthStore().me.brainpower).toEqual({ current: 20, max: 100 })
    expect(useAuthStore().me.booster).toEqual(started.booster)
  })

  it('zählt die Restzeit in Minuten und gibt die Angebote nach Ablauf wieder frei', async () => {
    const { wrapper } = await show(shop({ booster: { multiplier: 1.5, ends_at: '2026-10-17T12:09:30+00:00' } }))

    expect(wrapper.find('.lh-shop__running').text()).toContain('1,5× EP läuft · noch 3 Min.')
    await vi.advanceTimersByTimeAsync(60_000)
    expect(wrapper.find('.lh-shop__running').text()).toContain('1,5× EP läuft · noch 2 Min.')

    await vi.advanceTimersByTimeAsync(3 * 60_000)
    expect(wrapper.find('.lh-shop__running').exists()).toBe(false)
    expect(offers(wrapper)[0]).toMatchObject({ disabled: false, why: '' })
    expect(wrapper.find('.lh-offer button').attributes('aria-describedby')).toBeUndefined()
  })

  it('zeigt die Meldung des Servers, wenn der Kauf nicht klappt', async () => {
    const { wrapper } = await show(shop(), { status: 409, body: { message: 'Es läuft schon ein Booster.' } })

    await wrapper.find('.lh-offer button').trigger('click')
    await flushPromises()

    expect(wrapper.find('[role="alert"]').text()).toBe('Es läuft schon ein Booster.')
    expect(wrapper.find('.lh-chip').attributes('aria-label')).toBe('60 Brainpower')
  })

  it('bei einem Ladefehler lässt sich neu laden', async () => {
    const { wrapper } = await show(new TypeError('offline'), shop())

    expect(wrapper.text()).toContain('Prüf deine Verbindung')
    await wrapper.find('.lh-empty button').trigger('click')
    await flushPromises()
    expect(wrapper.findAll('.lh-offer')).toHaveLength(3)
  })
})
