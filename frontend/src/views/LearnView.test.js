import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { mockFetch } from '../test/api.js'
import { mountRoute } from '../test/router.js'
import LearnView from './LearnView.vue'

const show = () => mountRoute(mount, LearnView, '/learn', '/learn')

describe('LearnView', () => {
  afterEach(() => vi.unstubAllGlobals())

  it('zeigt jedes Fach mit erledigten Themen und Link', async () => {
    mockFetch({ status: 200, body: { data: [
      { code: 'pos', name: 'Programmieren', topics_count: 11, topics_done: 3, level: 4 },
      { code: 'dbi', name: 'Datenbanken', topics_count: 21, topics_done: 0, level: 1 },
    ] } })
    const { wrapper } = await show()
    await flushPromises()

    const cards = wrapper.findAll('.lh-subject')
    expect(cards).toHaveLength(2)
    expect(cards[0].find('.lh-subject__title').text()).toBe('POS')
    expect(cards[0].attributes('href')).toBe('/learn/pos')
    expect(cards[0].text()).toContain('3 / 11 Themen')
    expect(cards[0].text()).toContain('Fach-Level 4')
    expect(cards[1].text()).toContain('Noch nicht begonnen')
  })

  it('ohne Fächer kommt ein leerer Zustand statt einer leeren Seite', async () => {
    mockFetch({ status: 200, body: { data: [] } })
    const { wrapper } = await show()
    await flushPromises()

    expect(wrapper.find('.lh-empty').text()).toContain('Noch keine Fächer.')
  })

  it('bei einem Fehler lässt sich neu laden', async () => {
    mockFetch(new TypeError('offline'), { status: 200, body: { data: [{ code: 'pos', name: 'POS', topics_count: 1, topics_done: 0 }] } })
    const { wrapper } = await show()
    await flushPromises()

    expect(wrapper.text()).toContain('Prüf deine Verbindung')
    await wrapper.find('.lh-empty button').trigger('click')
    await flushPromises()
    expect(wrapper.findAll('.lh-subject')).toHaveLength(1)
  })
})
