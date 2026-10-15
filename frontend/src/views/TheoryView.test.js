import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { mockFetch } from '../test/api.js'
import { mountRoute } from '../test/router.js'
import TheoryView from './TheoryView.vue'

const words = (n) => Array.from({ length: n }, () => 'Wort').join(' ')
const topic = (extra = {}) => ({
  slug: 'schleifen', title: 'Schleifen', questions_count: 5, locked: false,
  theory: [
    { title: 'for-Schleife', html: `<p>Eine <strong>for</strong>-Schleife. ${words(300)}</p>` },
    { title: 'Beispiel', html: '<pre><code class="language-java">for (;;) {}</code></pre>' },
  ],
  ...extra,
})

async function show(data) {
  mockFetch({ status: 200, body: { data } })
  const { wrapper } = await mountRoute(mount, TheoryView, '/learn/:subject/:topic/theory', '/learn/pos/schleifen/theory')
  await flushPromises()
  return wrapper
}

describe('TheoryView', () => {
  afterEach(() => vi.unstubAllGlobals())

  it('zeigt die Theorie als Artikel mit Lesezeit', async () => {
    const wrapper = await show(topic())

    expect(wrapper.find('h1').text()).toBe('Schleifen')
    expect(wrapper.text()).toContain('2 Min. Lesezeit')
    expect(wrapper.findAll('h2').map((h) => h.text())).toEqual(['for-Schleife', 'Beispiel'])
    expect(wrapper.find('.theory__body strong').text()).toBe('for')
    expect(wrapper.find('.theory__body pre code').text()).toBe('for (;;) {}')
  })

  it('Schließen führt zurück zum Thema, am Ende geht es ins Üben', async () => {
    const wrapper = await show(topic())

    expect(wrapper.find('.lh-lessonbar a').attributes('href')).toBe('/learn/pos/schleifen')
    expect(wrapper.find('.lh-lessonbar a').attributes('aria-label')).toBe('Schließen')
    const action = wrapper.find('.theory__actions .lh-btn')
    expect(action.text()).toBe('Jetzt üben')
    expect(action.attributes('href')).toBe('/learn/pos/schleifen/lesson')
  })

  it('gesperrt oder ohne Fragen: zurück zum Thema statt üben', async () => {
    for (const extra of [{ locked: true }, { questions_count: 0 }]) {
      const wrapper = await show(topic(extra))
      const action = wrapper.find('.theory__actions .lh-btn')
      expect(action.text()).toBe('Zurück zum Thema')
      expect(action.attributes('href')).toBe('/learn/pos/schleifen')
    }
  })

  it('ohne Theorie ein klarer Hinweis', async () => {
    const wrapper = await show(topic({ theory: [] }))

    expect(wrapper.text()).toContain('noch keine Theorie')
    expect(wrapper.text()).not.toContain('Lesezeit')
  })
})
