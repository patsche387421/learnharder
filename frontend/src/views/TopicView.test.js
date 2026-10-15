import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { mockFetch } from '../test/api.js'
import { mountRoute } from '../test/router.js'
import TopicView from './TopicView.vue'

const topic = (extra = {}) => ({
  slug: 'schleifen', title: 'Schleifen', description: 'for und while', questions_count: 5, xp_total: 10,
  locked: false, level_required: 1, theory: [{ title: 'for', html: '<p>x</p>' }], ...extra,
})

async function show(data) {
  mockFetch({ status: 200, body: { data } })
  const { wrapper } = await mountRoute(mount, TopicView, '/learn/:subject/:topic', '/learn/pos/schleifen')
  await flushPromises()
  return wrapper
}

const buttons = (wrapper) => wrapper.findAll('.topic__actions .lh-btn')

describe('TopicView', () => {
  it('zeigt Fragen und EP, Los geht’s ist der Hauptweg', async () => {
    const wrapper = await show(topic())

    expect(wrapper.find('h1').text()).toBe('Schleifen')
    expect(wrapper.find('.lh-stats').text()).toContain('5')
    expect(wrapper.find('.lh-stats').text()).toContain('+10')
    const [start, theory] = buttons(wrapper)
    expect(start.text()).toBe('Los geht’s')
    expect(start.classes()).toContain('lh-btn--primary')
    expect(start.attributes('href')).toBe('/learn/pos/schleifen/lesson')
    expect(theory.classes()).toContain('lh-btn--secondary')
    expect(theory.attributes('href')).toBe('/learn/pos/schleifen/theory')
  })

  it('gesperrt: nur Theorie, mit Grund', async () => {
    const wrapper = await show(topic({ locked: true, level_required: 3 }))

    expect(wrapper.text()).toContain('Fragen ab Level 3')
    expect(buttons(wrapper)).toHaveLength(1)
    expect(buttons(wrapper)[0].classes()).toContain('lh-btn--primary')
    expect(buttons(wrapper)[0].text()).toBe('Nur Theorie lesen')
  })

  it('ohne Fragen: nur Theorie', async () => {
    const wrapper = await show(topic({ questions_count: 0, xp_total: 0 }))

    expect(wrapper.find('.lh-stats').exists()).toBe(false)
    expect(wrapper.text()).toContain('noch keine Fragen')
    expect(buttons(wrapper).map((b) => b.text())).toEqual(['Nur Theorie lesen'])
  })

  afterEach(() => vi.unstubAllGlobals())
})
