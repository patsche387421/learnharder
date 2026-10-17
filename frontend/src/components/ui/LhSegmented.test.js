import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import LhSegmented from './LhSegmented.vue'

const options = [
  { value: 'system', label: 'Wie Gerät' },
  { value: 'light', label: 'Hell' },
  { value: 'dark', label: 'Dunkel' },
]

describe('LhSegmented', () => {
  it('zeigt Legende und Optionen als Radio-Buttons einer Gruppe, die gewählte angehakt', () => {
    const w = mount(LhSegmented, { props: { legend: 'Darstellung', options, modelValue: 'light' } })
    expect(w.find('fieldset legend').text()).toBe('Darstellung')
    expect(w.findAll('label.lh-segmented__option').map((l) => l.text())).toEqual(['Wie Gerät', 'Hell', 'Dunkel'])

    const inputs = w.findAll('input')
    expect(inputs.every((i) => i.attributes('type') === 'radio')).toBe(true)
    expect(new Set(inputs.map((i) => i.attributes('name'))).size).toBe(1)
    expect(inputs.map((i) => i.element.checked)).toEqual([false, true, false])
  })

  it('gibt die Wahl per v-model weiter', async () => {
    const w = mount(LhSegmented, { props: { legend: 'Darstellung', options, modelValue: 'system' } })
    await w.findAll('input')[2].setValue()
    expect(w.emitted('update:modelValue')[0]).toEqual(['dark'])
  })

  it('verbindet den Hinweis mit der Gruppe, ohne Hinweis gibt es keinen', () => {
    const w = mount(LhSegmented, { props: { legend: 'Darstellung', options, hint: 'Gilt nur auf diesem Gerät.' } })
    const hint = w.find('.lh-segmented__hint')
    expect(hint.text()).toBe('Gilt nur auf diesem Gerät.')
    expect(w.find('fieldset').attributes('aria-describedby')).toBe(hint.attributes('id'))

    const plain = mount(LhSegmented, { props: { legend: 'Sprache', options: options.slice(0, 2) } })
    expect(plain.find('.lh-segmented__hint').exists()).toBe(false)
    expect(plain.find('fieldset').attributes('aria-describedby')).toBeUndefined()
  })

  it('zwei Gruppen auf einer Seite kommen sich nicht in die Quere', () => {
    const w = mount({
      components: { LhSegmented },
      setup: () => ({ options }),
      template: '<LhSegmented legend="A" :options="options" /><LhSegmented legend="B" :options="options" />',
    })
    const [a, b] = w.findAll('fieldset')
    expect(a.find('input').attributes('name')).not.toBe(b.find('input').attributes('name'))
  })
})
