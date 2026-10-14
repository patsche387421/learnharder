import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import LhEmptyState from './LhEmptyState.vue'

describe('LhEmptyState', () => {
  it('zeigt Icon, Titel, Text und den Button aus dem Slot', () => {
    const w = mount(LhEmptyState, {
      props: { art: 'subject-pos', title: 'Noch keine Themen gelernt', text: 'Fang mit POS an.' },
      slots: { default: '<button>Weiterlernen</button>' },
    })
    expect(w.find('img.lh-art').attributes('width')).toBe('56')
    expect(w.find('.lh-empty__title').text()).toBe('Noch keine Themen gelernt')
    expect(w.find('.lh-empty__text').text()).toBe('Fang mit POS an.')
    expect(w.find('button').text()).toBe('Weiterlernen')
  })

  it('lässt Icon und Text weg, wenn sie fehlen', () => {
    const w = mount(LhEmptyState, { props: { title: 'Leer' } })
    expect(w.find('img').exists()).toBe(false)
    expect(w.find('.lh-empty__text').exists()).toBe(false)
  })
})
