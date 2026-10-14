import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import { testRouter } from '../../test/router.js'
import LhAppLayout from './LhAppLayout.vue'

async function layout(props) {
  const router = testRouter()
  router.push('/')
  await router.isReady()
  return mount(LhAppLayout, { props: { level: 7, ...props }, slots: { default: '<p class="page">Inhalt</p>' }, global: { plugins: [router] } })
}

describe('LhAppLayout', () => {
  it('rendert Inhalt und Hauptnavigation', async () => {
    const w = await layout()
    expect(w.classes()).toContain('lh-app')
    expect(w.find('main.lh-app__main .page').exists()).toBe(true)
    expect(w.find('main').classes()).not.toContain('lh-app__main--lesson')
    expect(w.find('nav.lh-tabbar').exists()).toBe(true)
  })

  it('setzt die Rangfarbe als accent für alles darin', async () => {
    expect((await layout()).attributes('style')).toContain('--accent: var(--rank-bronze)')
    expect((await layout({ rank: 'platin' })).attributes('style')).toContain('--accent: var(--rank-platin)')
  })

  it('zeigt im Fokus-Modus keine Navigation und nutzt die schmale Breite', async () => {
    const w = await layout({ focus: true })
    expect(w.classes()).toContain('lh-app--focus')
    expect(w.find('nav').exists()).toBe(false)
    expect(w.find('main').classes()).toContain('lh-app__main--lesson')
  })

  it('reicht Level, Rang und Prestige an das Hub-Badge weiter', async () => {
    const w = await layout({ level: 58, rank: 'gold', prestige: 2 })
    const badge = w.find('.lh-tab--hub .lh-badge')
    expect(badge.attributes('data-rank')).toBe('gold')
    expect(badge.find('.lh-badge__hex').text()).toBe('58')
    expect(badge.find('.lh-badge__prestige').text()).toBe('2')
  })
})
