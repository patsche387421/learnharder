import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import { icons } from './icons.js'
import { testRouter } from '../../test/router.js'
import LhTabBar from './LhTabBar.vue'

async function tabbarAt(path, props = { level: 21, prestige: 1 }) {
  const router = testRouter()
  router.push(path)
  await router.isReady()
  return mount(LhTabBar, { props, global: { plugins: [router] } })
}

describe('LhTabBar', () => {
  it('hat fünf Ziele in fester Reihenfolge mit dem Hub in der Mitte', async () => {
    const w = await tabbarAt('/learn')
    const tabs = w.findAll('a.lh-tab')
    expect(tabs.map((t) => t.attributes('href'))).toEqual(['/learn', '/team', '/', '/league', '/profile'])
    expect([0, 1, 3, 4].map((i) => tabs[i].text())).toEqual(['Lernen', 'Team', 'Liga', 'Profil'])
    expect(tabs[2].classes()).toContain('lh-tab--hub')
    expect(w.attributes('aria-label')).toBe('Hauptnavigation')
  })

  it('markiert den aktiven Tab mit aria-current und gefülltem Icon', async () => {
    const w = await tabbarAt('/league')
    const tabs = w.findAll('a.lh-tab')
    expect(tabs.map((t) => t.attributes('aria-current') ?? null)).toEqual([null, null, null, 'page', null])
    expect(tabs[3].find('.lh-icon').attributes('style')).toContain(icons['ui-league-active'])
    expect(tabs[0].find('.lh-icon').attributes('style')).toContain(icons['ui-learn'])
  })

  it('zeigt im Hub das Level-Badge mit Label für Screenreader', async () => {
    const w = await tabbarAt('/', { level: 21, rank: 'gold', prestige: 1 })
    const hub = w.find('a.lh-tab--hub')
    expect(hub.attributes('aria-current')).toBe('page')
    expect(hub.attributes('aria-label')).toBe('Hub, Level 21, Prestige 1')
    const badge = hub.find('.lh-badge')
    expect(badge.attributes('data-rank')).toBe('gold')
    expect(badge.attributes('aria-hidden')).toBe('true')
  })

  it('lässt Prestige im Hub-Label weg, solange es 0 ist', async () => {
    const w = await tabbarAt('/', { level: 3 })
    expect(w.find('a.lh-tab--hub').attributes('aria-label')).toBe('Hub, Level 3')
  })
})
