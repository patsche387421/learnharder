import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { useAuthStore } from '../stores/auth.js'
import { mockFetch, setXsrfCookie } from '../test/api.js'
import { mountRoute } from '../test/router.js'
import LessonView from './LessonView.vue'

const topic = { id: 't1', slug: 'schleifen', title: 'Schleifen', questions_count: 2, locked: false, theory: [] }
const lesson = {
  id: 'a1',
  questions: [
    { id: 'q1', type: 'single_choice', text: 'Wie oft läuft die Schleife?', xp_reward: 2, options: [{ id: 'o1', text: 'Dreimal' }, { id: 'o2', text: 'Nie' }] },
    { id: 'q2', type: 'multiple_choice', text: 'Welche sind Schleifen?', xp_reward: 2, options: [{ id: 'o3', text: 'for' }, { id: 'o4', text: 'while' }, { id: 'o5', text: 'if' }] },
  ],
}
const answer = (extra) => ({ correct: true, correct_option_ids: [], explanation: 'Weil.', xp: 0, repeat: false, ...extra })
const finished = {
  xp_earned: 2, correct_count: 1, total_questions: 2, level_up: true,
  before: { level: 1, rank: 'bronze', prestige: 0, xp: { total: 7, level_start: 0, next_level: 8 } },
  progress: { level: 2, rank: 'bronze', prestige: 0, xp: { total: 9, level_start: 8, next_level: 23 } },
  streak: { current: 3, longest: 5 },
}
const subject = { code: 'pos', name: 'POS', topics: [{ slug: 'schleifen', locked: false }, { slug: 'arrays', locked: false }] }

const ok = (data) => ({ status: 200, body: { data } })

async function show(...responses) {
  const fetch = mockFetch(ok(topic), { status: 201, body: { data: lesson } }, ...responses)
  const { wrapper } = await mountRoute(mount, LessonView, '/learn/:subject/:topic/lesson', '/learn/pos/schleifen/lesson')
  await flushPromises()
  return { wrapper, fetch }
}

const options = (wrapper) => wrapper.findAll('.lh-answer')
const primary = (wrapper) => wrapper.find('.lh-feedback .lh-btn, .lh-screen__foot .lh-btn')

async function click(element) {
  await element.trigger('click')
  await flushPromises()
}

describe('LessonView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    setXsrfCookie('t')
    useAuthStore().me = { gamer_tag: 'lena42', level: 1, rank: 'bronze', prestige: 0 }
  })
  afterEach(() => {
    vi.unstubAllGlobals()
    setXsrfCookie(null)
  })

  it('startet die Lerneinheit zum Thema und zeigt die erste Frage', async () => {
    const { wrapper, fetch } = await show()

    expect(fetch.mock.calls[1][0]).toContain('/api/v1/topics/t1/attempts')
    expect(wrapper.find('.lesson__question').text()).toBe('Wie oft läuft die Schleife?')
    expect(options(wrapper).map((o) => o.find('.lh-answer__key').text())).toEqual(['A', 'B'])
    expect(wrapper.findAll('.lh-steps i')).toHaveLength(2)
    expect(wrapper.find('.lh-lessonbar a').attributes('href')).toBe('/learn/pos/schleifen')
  })

  it('Fragetext, Antworten und Erklärung behalten ihre Zeilenumbrüche', async () => {
    const code = 'Was gibt das aus?\nstring name = "max";\nConsole.WriteLine(name);'
    mockFetch(ok(topic), { status: 201, body: { data: { id: 'a1', questions: [{ ...lesson.questions[0], text: code }] } } }, ok(answer({ correct_option_ids: ['o1'], explanation: 'Zeile 1\nZeile 2' })))
    const { wrapper } = await mountRoute(mount, LessonView, '/learn/:subject/:topic/lesson', '/learn/pos/schleifen/lesson')
    await flushPromises()

    const question = wrapper.find('.lesson__question')
    expect(question.text()).toBe(code)
    expect(question.classes()).toContain('lesson__pre')
    expect(options(wrapper)[0].find('.lesson__pre').text()).toBe('Dreimal')

    await click(options(wrapper)[0])
    await click(primary(wrapper))
    expect(wrapper.find('.lh-feedback__text.lesson__pre').text()).toBe('Zeile 1\nZeile 2')
  })

  it('Prüfen geht erst nach einer Auswahl, bei einer richtigen ersetzt die Auswahl', async () => {
    const { wrapper } = await show()

    expect(primary(wrapper).attributes('disabled')).toBeDefined()
    await click(options(wrapper)[0])
    await click(options(wrapper)[1])
    expect(options(wrapper).map((o) => o.attributes('aria-pressed'))).toEqual(['false', 'true'])
    expect(primary(wrapper).attributes('disabled')).toBeUndefined()
  })

  it('ganzer Ablauf: Fehler, Wiederholung am Ende, Ergebnis mit Level-Aufstieg', async () => {
    const { wrapper, fetch } = await show(
      ok(answer({ correct: false, correct_option_ids: ['o1'] })),
      ok(answer({ correct_option_ids: ['o3', 'o4'], xp: 2 })),
      ok(answer({ correct_option_ids: ['o1'], repeat: true })),
      ok(finished),
      ok(subject),
    )

    // Frage 1 falsch
    await click(options(wrapper)[1])
    await click(primary(wrapper))
    expect(JSON.parse(fetch.mock.calls[2][1].body)).toEqual({ question_id: 'q1', option_ids: ['o2'] })
    expect(wrapper.find('.lh-feedback--wrong').text()).toContain('Nicht ganz. Richtig ist A.')
    expect(wrapper.find('.lh-feedback').text()).toContain('Weil.')
    expect(wrapper.find('.lh-feedback').text()).toContain('Kommt am Ende noch einmal.')
    expect(options(wrapper)[0].classes()).toContain('lh-answer--correct')
    expect(options(wrapper)[1].classes()).toContain('lh-answer--wrong')
    expect(options(wrapper).every((o) => o.attributes('disabled') !== undefined)).toBe(true)
    expect(wrapper.findAll('.lh-steps i')).toHaveLength(3)

    // Frage 2: mehrere richtig, beide gewählt
    await click(primary(wrapper))
    expect(wrapper.text()).toContain('Mehrere richtig')
    await click(options(wrapper)[0])
    await click(options(wrapper)[1])
    await click(primary(wrapper))
    expect(JSON.parse(fetch.mock.calls[3][1].body).option_ids).toEqual(['o3', 'o4'])
    expect(wrapper.find('.lh-feedback--correct').text()).toContain('Richtig!')
    expect(wrapper.find('.lh-feedback').text()).toContain('+2 EP')

    // Wiederholung von Frage 1: richtig, aber ohne EP-Hinweis
    await click(primary(wrapper))
    expect(wrapper.text()).toContain('Noch einmal')
    await click(options(wrapper)[0])
    await click(primary(wrapper))
    expect(wrapper.find('.lh-feedback').text()).not.toContain('EP')
    expect(primary(wrapper).text()).toBe('Ergebnis ansehen')

    // Ergebnis
    await click(primary(wrapper))
    expect(fetch.mock.calls[5][0]).toContain('/api/v1/attempts/a1/finish')
    expect(wrapper.find('.lh-result__gain').text()).toBe('+2 EP')
    expect(wrapper.text()).toContain('Level 2!')
    expect(wrapper.text()).toContain('Schleifen geschafft · 1 von 2 richtig')
    expect(wrapper.find('.lh-stats').text()).toContain('Fehler geübt')
    expect(wrapper.find('.lh-xp').attributes('aria-valuenow')).toBe('1')
    expect(wrapper.find('.lh-xp').attributes('aria-valuemax')).toBe('15')
    expect(useAuthStore().player.level).toBe(2)
    expect(useAuthStore().me.streak).toEqual({ current: 3, longest: 5 })
    const [next, back] = wrapper.findAll('.lesson__actions .lh-btn')
    expect(next.text()).toBe('Nächstes Thema')
    expect(next.attributes('href')).toBe('/learn/pos/arrays')
    expect(back.attributes('href')).toBe('/learn/pos')
  })

  it('heute schon gesammelte EP werden ehrlich angezeigt', async () => {
    const { wrapper } = await show(ok(answer({ correct_option_ids: ['o1'], xp: 0 })))

    await click(options(wrapper)[0])
    await click(primary(wrapper))
    expect(wrapper.find('.lh-feedback').text()).toContain('Heute schon gesammelt')
  })

  it('ohne Netz beim Prüfen: Meldung und nochmal versuchen, ohne Fortschritt zu verlieren', async () => {
    const { wrapper } = await show(new TypeError('offline'), ok(answer({ correct_option_ids: ['o1'], xp: 2 })))

    await click(options(wrapper)[0])
    await click(primary(wrapper))
    expect(wrapper.find('[role="alert"]').text()).toContain('Prüf deine Verbindung')
    expect(primary(wrapper).text()).toBe('Nochmal versuchen')
    expect(options(wrapper)[0].attributes('aria-pressed')).toBe('true')

    await click(primary(wrapper))
    expect(wrapper.find('.lh-feedback--correct').exists()).toBe(true)
  })

  it('ein gesperrtes Thema startet keine Lerneinheit und nennt das Level', async () => {
    const fetch = mockFetch(ok({ ...topic, locked: true, level_required: 5, theory: [{ title: 'Grundlagen', html: '<p>…</p>' }] }))
    const { wrapper } = await mountRoute(mount, LessonView, '/learn/:subject/:topic/lesson', '/learn/pos/schleifen/lesson')
    await flushPromises()

    expect(fetch).toHaveBeenCalledTimes(1)
    expect(wrapper.find('.lh-empty').text()).toContain('Noch gesperrt.')
    expect(wrapper.find('.lh-empty').text()).toContain('Fragen gibt es ab Level 5. Die Theorie kannst du schon lesen.')
    expect(wrapper.find('.lh-empty .lh-btn').attributes('href')).toBe('/learn/pos/schleifen/theory')
  })

  it('sagt der Server 403, ist das Thema gesperrt statt ein Fehler', async () => {
    mockFetch(ok({ ...topic, level_required: 5 }), { status: 403, body: { message: 'This action is unauthorized.' } })
    const { wrapper } = await mountRoute(mount, LessonView, '/learn/:subject/:topic/lesson', '/learn/pos/schleifen/lesson')
    await flushPromises()

    expect(wrapper.find('.lh-answer').exists()).toBe(false)
    expect(wrapper.find('.lh-empty').text()).toContain('Noch gesperrt.')
    expect(wrapper.find('.lh-empty').text()).not.toContain('Theorie')
    expect(wrapper.find('.lh-empty .lh-btn').attributes('href')).toBe('/learn/pos/schleifen')
  })
})
