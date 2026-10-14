import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import { icons } from './icons.js'
import LhTextField from './LhTextField.vue'

describe('LhTextField', () => {
  it('zeigt das Label und gibt Eingaben per v-model weiter', async () => {
    const w = mount(LhTextField, { props: { label: 'E-Mail', modelValue: '' } })
    expect(w.find('.lh-field__label').text()).toBe('E-Mail')
    await w.find('input').setValue('lena@htl.at')
    expect(w.emitted('update:modelValue')[0]).toEqual(['lena@htl.at'])
  })

  it('gibt zusätzliche Attribute ans <input>, nicht ans Label', () => {
    const w = mount(LhTextField, { props: { label: 'E-Mail', type: 'email' }, attrs: { autocomplete: 'email' } })
    const input = w.find('input')
    expect(input.attributes('type')).toBe('email')
    expect(input.attributes('autocomplete')).toBe('email')
    expect(w.attributes('autocomplete')).toBeUndefined()
  })

  it('verknüpft den Hinweis mit dem Feld', () => {
    const w = mount(LhTextField, { props: { label: 'Gamer-Tag', hint: 'Den sehen alle.' } })
    const hint = w.find('.lh-field__hint')
    expect(hint.text()).toBe('Den sehen alle.')
    expect(w.find('input').attributes('aria-describedby')).toBe(hint.attributes('id'))
    expect(w.find('input').attributes('aria-invalid')).toBeUndefined()
  })

  it('zeigt einen Fehler mit Icon, Fehlerklasse und aria-invalid', () => {
    const w = mount(LhTextField, { props: { label: 'E-Mail', hint: 'Hinweis', error: 'Da fehlt noch etwas.' } })
    expect(w.classes()).toContain('lh-field--error')
    expect(w.find('input').attributes('aria-invalid')).toBe('true')
    expect(w.find('.lh-field__hint').text()).toBe('Da fehlt noch etwas.')
    expect(w.find('.lh-field__hint .lh-icon').attributes('style')).toContain(icons['ui-wrong'])
  })

  it('kann deaktiviert werden', () => {
    const w = mount(LhTextField, { props: { label: 'Klasse', disabled: true } })
    expect(w.find('input').attributes('disabled')).toBeDefined()
  })
})
