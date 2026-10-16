import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import { icons } from './icons.js'
import LhCheckbox from './LhCheckbox.vue'

describe('LhCheckbox', () => {
  it('ist leer, zeigt den Text und gibt das Häkchen per v-model weiter', async () => {
    const w = mount(LhCheckbox, { slots: { default: 'Ich bin mindestens 14 Jahre alt.' } })
    const input = w.find('input')
    expect(input.attributes('type')).toBe('checkbox')
    expect(input.element.checked).toBe(false)
    expect(w.find('.lh-check__label').text()).toBe('Ich bin mindestens 14 Jahre alt.')

    await input.setValue(true)
    expect(w.emitted('update:modelValue')[0]).toEqual([true])
  })

  it('erlaubt Links im Text, und die ganze Zeile ist das Label', () => {
    const w = mount(LhCheckbox, { slots: { default: 'Ich stimme der <a href="/datenschutz">Datenschutzerklärung</a> zu.' } })
    expect(w.find('label.lh-check__row input').exists()).toBe(true)
    expect(w.find('.lh-check__label a').attributes('href')).toBe('/datenschutz')
  })

  it('gibt zusätzliche Attribute ans <input>', () => {
    const w = mount(LhCheckbox, { attrs: { name: 'is_14' } })
    expect(w.find('input').attributes('name')).toBe('is_14')
    expect(w.attributes('name')).toBeUndefined()
  })

  it('zeigt einen Fehler mit Icon, Fehlerklasse und aria-invalid', () => {
    const w = mount(LhCheckbox, { props: { error: 'Bestätige, dass du mindestens 14 bist.' } })
    const hint = w.find('.lh-check__hint')
    expect(w.classes()).toContain('lh-check--error')
    expect(hint.text()).toBe('Bestätige, dass du mindestens 14 bist.')
    expect(hint.find('.lh-icon').attributes('style')).toContain(icons['ui-wrong'])
    expect(w.find('input').attributes('aria-invalid')).toBe('true')
    expect(w.find('input').attributes('aria-describedby')).toBe(hint.attributes('id'))
  })

  it('kann deaktiviert werden und hat ohne Fehler keinen Hinweis', () => {
    const w = mount(LhCheckbox, { props: { disabled: true } })
    expect(w.find('input').attributes('disabled')).toBeDefined()
    expect(w.find('.lh-check__hint').exists()).toBe(false)
    expect(w.find('input').attributes('aria-invalid')).toBeUndefined()
  })
})
