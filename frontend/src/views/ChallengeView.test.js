import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { useAuthStore } from '../stores/auth.js'
import { mockFetch, setXsrfCookie } from '../test/api.js'
import { mountRoute } from '../test/router.js'
import ChallengeView from './ChallengeView.vue'

const status = (extra = {}) => ({
  cost: 25, questions: 10, xp_per_correct: 5, bonus: [{ from: 8, xp: 20 }, { from: 10, xp: 50 }],
  perfect_trophies: 1, left_today: 2, open: null, locked: false, ...extra,
})
const hub = (extra) => ({ status: 200, body: { data: { next_topic: null, last_study_at: null, challenge: status(extra) } } })
const question = (id, slug) => ({ id, type: 'single_choice', text: `Frage ${id}`, xp_reward: 5, subject: { slug, name: slug }, options: [{ id: `${id}a`, text: 'A' }, { id: `${id}b`, text: 'B' }] })
const challenge = { id: 'c1', questions: [question('q1', 'dbi'), question('q2', 'pos')], answered: [], brainpower: { current: 55, max: 100 } }
const answer = (extra) => ({ correct: true, correct_option_ids: [], explanation: 'Weil.', xp: 5, multiplier: 1, repeat: false, ...extra })
const finished = {
  xp_earned: 60, xp_bonus: 50, xp_from_booster: 0, correct_count: 2, total_questions: 2, level_up: false,
  before: { level: 3, rank: 'bronze', prestige: 0, xp: { total: 40, level_start: 23, next_level: 64 } },
  progress: { level: 4, rank: 'bronze', prestige: 0, xp: { total: 100, level_start: 64, next_level: 101 } },
  streak: { current: 4, longest: 4, today: true },
  achievements_unlocked: [{ slug: 'first-challenge', title: 'Herausforderung angenommen', icon_name: 'challenge', trophies: 1 }],
  trophies_earned: 2,
}
const ok = (data) => ({ status: 200, body: { data } })

async function show(...responses) {
  const fetch = mockFetch(...responses)
  const { wrapper } = await mountRoute(mount, ChallengeView, '/challenge', '/challenge')
  await flushPromises()
  return { wrapper, fetch }
}

const primary = (wrapper) => wrapper.find('.lh-feedback .lh-btn, .lh-screen__foot .lh-btn')

async function click(element) {
  await element.trigger('click')
  await flushPromises()
}

describe('ChallengeView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    setXsrfCookie('t')
    useAuthStore().me = { gamer_tag: 'lena42', level: 3, rank: 'bronze', prestige: 0, brainpower: { current: 80, max: 100 } }
  })
  afterEach(() => {
    vi.unstubAllGlobals()
    setXsrfCookie(null)
  })

  it('zeigt vor dem Start Kosten, Regeln und was heute noch geht', async () => {
    const { wrapper } = await show(hub())

    expect(wrapper.find('h1').text()).toBe('Herausforderung')
    expect(wrapper.find('.lh-stats').text()).toContain('10Fragen')
    expect(wrapper.find('.lh-stats').text()).toContain('5EP je Frage')
    expect(wrapper.find('.lh-stats').text()).toContain('+50Bonus')
    expect(wrapper.find('.lh-note').text()).toContain('Ab 8 richtig gibt es +20 EP Bonus, bei allen 10 +50 EP und eine Trophäe.')
    expect(wrapper.find('.lh-note').text()).toContain('bis Mitternacht weitermachen')
    expect(primary(wrapper).text()).toBe('Starten · 25')
    expect(primary(wrapper).attributes('disabled')).toBeUndefined()
    expect(wrapper.find('.lh-screen__foot').text()).toContain('Noch 2 heute · Zeit bis Mitternacht')
    expect(wrapper.find('.lh-lessonbar a').attributes('href')).toBe('/')
  })

  it('reicht die Brainpower nicht, sagt der Start, wie viel fehlt', async () => {
    useAuthStore().me.brainpower = { current: 15, max: 100 }
    const { wrapper } = await show(hub())

    expect(primary(wrapper).attributes('disabled')).toBeDefined()
    expect(wrapper.find('.lh-screen__foot').text()).toContain('Noch 10 Brainpower. Morgen lädt sie sich wieder auf.')
  })

  it('am Tageslimit heißt der Button „Morgen wieder“', async () => {
    const { wrapper } = await show(hub({ left_today: 0 }))

    expect(primary(wrapper).text()).toBe('Morgen wieder')
    expect(primary(wrapper).attributes('disabled')).toBeDefined()
  })

  it('ohne genug gelernte Themen gesperrt mit Weg zum Lernen', async () => {
    const { wrapper } = await show(hub({ locked: true }))

    expect(wrapper.find('.lh-empty').text()).toContain('Schließe zuerst ein paar Themen ab.')
    expect(wrapper.find('.lh-empty .lh-btn').attributes('href')).toBe('/learn')
    expect(wrapper.find('.lh-screen__foot').exists()).toBe(false)
  })

  it('ganzer Ablauf: starten, Fragen mit Fach, kein „noch einmal“, Ergebnis mit Bonus, Trophäen und Erfolg', async () => {
    const { wrapper, fetch } = await show(
      hub(),
      { status: 201, body: { data: challenge } },
      ok(answer({ correct_option_ids: ['q1a'] })),
      ok(answer({ correct: false, xp: 0, correct_option_ids: ['q2a'] })),
      ok(finished),
    )

    await click(primary(wrapper))
    expect(fetch.mock.calls[1][0]).toMatch(/\/challenges$/)
    expect(useAuthStore().me.brainpower.current).toBe(55)
    expect(wrapper.find('.challenge__eyebrow').text()).toBe('Frage 1 / 2 DBI')
    expect(wrapper.findAll('.lh-steps i')).toHaveLength(2)

    await click(wrapper.findAll('.lh-answer')[0])
    await click(primary(wrapper))
    expect(wrapper.find('.lh-feedback .lesson__xp').text()).toBe('+5 EP')
    expect(wrapper.find('.lh-feedback .lh-tag--boost').exists()).toBe(false)

    await click(primary(wrapper))
    expect(wrapper.find('.challenge__eyebrow').text()).toBe('Frage 2 / 2 POS')
    await click(wrapper.findAll('.lh-answer')[1])
    await click(primary(wrapper))
    expect(wrapper.find('.lh-feedback--wrong').exists()).toBe(true)
    expect(wrapper.find('.lh-feedback').text()).not.toContain('noch einmal')
    expect(primary(wrapper).text()).toBe('Ergebnis ansehen')

    await click(primary(wrapper))
    expect(fetch.mock.calls[4][0]).toMatch(/\/attempts\/c1\/finish$/)
    expect(wrapper.find('h1').text()).toBe('2 von 2 richtig')
    expect(wrapper.find('.lh-result__gain').text()).toBe('+60 EP')
    expect(wrapper.find('.lh-result').text()).toContain('davon +50 Bonus')
    expect(wrapper.find('.lh-stats').text()).toContain('4Tage Streak')
    expect(wrapper.find('.lh-stats').text()).toContain('+2Trophäen')
    expect(wrapper.find('.new-achievements').text()).toContain('Herausforderung angenommen')
    expect(primary(wrapper).attributes('href')).toBe('/')
    expect(useAuthStore().me.level).toBe(4)
  })

  it('setzt eine offene Herausforderung bei der ersten unbeantworteten Frage fort', async () => {
    const { wrapper, fetch } = await show(
      hub({ open: { id: 'c1', answered: 1, total: 2 } }),
      ok({ ...challenge, answered: [{ question_id: 'q1', correct: true }] }),
    )

    expect(fetch.mock.calls[1][0]).toMatch(/\/challenges\/current$/)
    expect(wrapper.find('.challenge__eyebrow').text()).toBe('Frage 2 / 2 POS')
    expect(wrapper.findAll('.lh-steps i.is-done')).toHaveLength(1)
  })

  it('ist die offene inzwischen abgelaufen, geht es zum Start', async () => {
    const { wrapper } = await show(hub({ open: { id: 'c1', answered: 1, total: 2 } }), ok(null))

    expect(primary(wrapper).text()).toBe('Starten · 25')
  })

  it('zeigt die Meldung des Servers, wenn der Start scheitert', async () => {
    const { wrapper } = await show(hub(), { status: 409, body: { message: 'Du hast noch eine offene Herausforderung.' } })

    await click(primary(wrapper))
    expect(wrapper.find('[role="alert"]').text()).toBe('Du hast noch eine offene Herausforderung.')
  })
})
