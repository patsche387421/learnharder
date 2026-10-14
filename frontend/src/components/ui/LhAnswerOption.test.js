import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import { icons } from './icons.js'
import LhAnswerOption from './LhAnswerOption.vue'

const answer = (props = {}) => mount(LhAnswerOption, { props: { letter: 'B', ...props }, slots: { default: '10-mal' } })

describe('LhAnswerOption', () => {
  it('zeigt Buchstabe und Antworttext, nicht ausgewählt', () => {
    const w = answer()
    expect(w.attributes('type')).toBe('button')
    expect(w.find('.lh-answer__key').text()).toBe('B')
    expect(w.find('.lh-answer__text').text()).toBe('10-mal')
    expect(w.attributes('aria-pressed')).toBe('false')
  })

  it('meldet die Auswahl über aria-pressed', () => {
    expect(answer({ selected: true }).attributes('aria-pressed')).toBe('true')
  })

  it('zeigt richtig und falsch mit Wort und Icon, nie nur über die Farbe', () => {
    const right = answer({ selected: true, result: 'correct' })
    expect(right.classes()).toContain('lh-answer--correct')
    expect(right.attributes('aria-pressed')).toBe('false')
    expect(right.text()).toContain('Richtig')
    expect(right.find('.lh-icon').attributes('style')).toContain(icons['ui-correct'])

    const wrong = answer({ result: 'wrong' })
    expect(wrong.classes()).toContain('lh-answer--wrong')
    expect(wrong.text()).toContain('Falsch')
    expect(wrong.find('.lh-icon').attributes('style')).toContain(icons['ui-wrong'])
  })

  it('kann nach dem Prüfen deaktiviert werden', () => {
    expect(answer({ disabled: true }).attributes('disabled')).toBeDefined()
  })
})
