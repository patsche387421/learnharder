import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import { icons } from './icons.js'
import LhIcon from './LhIcon.vue'

describe('LhIcon', () => {
  it('setzt die Icon-URL als Maske und ist ohne Label dekorativ', () => {
    const w = mount(LhIcon, { props: { name: 'ui-learn' } })
    expect(w.classes()).toContain('lh-icon')
    expect(w.attributes('style')).toContain(`--icon: url("${icons['ui-learn']}")`)
    expect(w.attributes('aria-hidden')).toBe('true')
    expect(w.attributes('role')).toBeUndefined()
  })

  it('wird mit Label für Screenreader sichtbar', () => {
    const w = mount(LhIcon, { props: { name: 'ui-close', label: 'Schließen' } })
    expect(w.attributes('role')).toBe('img')
    expect(w.attributes('aria-label')).toBe('Schließen')
    expect(w.attributes('aria-hidden')).toBeUndefined()
  })

  it('warnt bei einem unbekannten Icon-Namen', () => {
    const warn = vi.spyOn(console, 'warn').mockImplementation(() => {})
    mount(LhIcon, { props: { name: 'ui-gibtsnicht' } })
    expect(warn).toHaveBeenCalledWith(expect.stringContaining('ui-gibtsnicht'))
    warn.mockRestore()
  })
})
