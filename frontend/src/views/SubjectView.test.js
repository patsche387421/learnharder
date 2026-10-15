import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { icons } from '../components/ui/icons.js'
import { mockFetch } from '../test/api.js'
import { mountRoute } from '../test/router.js'
import SubjectView from './SubjectView.vue'

const topic = (slug, extra = {}) => ({ slug, title: slug, questions_count: 5, locked: false, done: false, level_required: 1, ...extra })
const show = () => mountRoute(mount, SubjectView, '/learn/:subject', '/learn/pos')

describe('SubjectView', () => {
  afterEach(() => vi.unstubAllGlobals())

  it('listet die Themen, erledigte und das nächste offene sind markiert', async () => {
    const fetch = mockFetch({ status: 200, body: { data: { code: 'pos', name: 'Programmieren', topics: [
      topic('variablen', { done: true }),
      topic('schleifen'),
      topic('theorie', { questions_count: 0 }),
    ] } } })
    const { wrapper } = await show()
    await flushPromises()

    expect(fetch.mock.calls[0][0]).toContain('/api/v1/subjects/pos')
    expect(wrapper.find('h1').text()).toBe('Programmieren')
    expect(wrapper.find('.subject__head img').attributes('src')).toBe(icons['subject-pos'])
    const items = wrapper.findAll('.lh-topic')
    expect(items).toHaveLength(3)
    expect(items[0].classes()).toContain('lh-topic--done')
    expect(items[0].text()).toContain('Erledigt · 5 Fragen')
    expect(items[1].classes()).toContain('lh-topic--now')
    expect(items[1].attributes('href')).toBe('/learn/pos/schleifen')
    expect(items[2].text()).toContain('Nur Theorie')
  })

  it('gesperrte Themen sagen, ab welchem Level', async () => {
    mockFetch({ status: 200, body: { data: { code: 'pos', name: 'POS', topics: [topic('rekursion', { locked: true, level_required: 5 })] } } })
    const { wrapper } = await show()
    await flushPromises()

    const item = wrapper.find('.lh-topic')
    expect(item.classes()).toContain('lh-topic--locked')
    expect(item.classes()).not.toContain('lh-topic--now')
    expect(item.text()).toContain('Ab Level 5')
  })

  it('ein unbekanntes Fach führt zurück zu den Fächern', async () => {
    mockFetch({ status: 404, body: { message: 'Not Found' } })
    const { wrapper } = await show()
    await flushPromises()

    expect(wrapper.find('.lh-empty').text()).toContain('Gibt’s hier nicht (mehr).')
    expect(wrapper.find('.lh-empty a').attributes('href')).toBe('/learn')
  })
})
