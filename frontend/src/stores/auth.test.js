import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { mockFetch, setXsrfCookie } from '../test/api.js'
import { useAuthStore } from './auth.js'

const me = { gamer_tag: 'lena42', level: 15, rank: 'silver', prestige: 1, xp: { total: 8450, level_start: 8419, next_level: 8465 } }

describe('auth-Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    setXsrfCookie('t')
  })
  afterEach(() => {
    vi.unstubAllGlobals()
    setXsrfCookie(null)
  })

  it('startet ohne Person bei Level 1 und Bronze', () => {
    const auth = useAuthStore()
    expect(auth.me).toBeNull()
    expect(auth.loaded).toBe(false)
    expect(auth.player).toEqual({ level: 1, rank: 'bronze', prestige: 0 })
  })

  it('fetchMe übernimmt die Werte vom Server', async () => {
    mockFetch({ status: 200, body: { data: me } })
    const auth = useAuthStore()

    await auth.fetchMe()
    expect(auth.loaded).toBe(true)
    expect(auth.player).toEqual({ level: 15, rank: 'silver', prestige: 1 })
  })

  it('fetchMe ohne Session ist kein Fehler', async () => {
    mockFetch({ status: 401 })
    const auth = useAuthStore()

    await auth.fetchMe()
    expect(auth.me).toBeNull()
    expect(auth.loaded).toBe(true)
  })

  it('login holt erst das CSRF-Cookie und merkt sich die Person', async () => {
    const fetch = mockFetch({ status: 204 }, { status: 200, body: { data: me } })
    const auth = useAuthStore()

    await auth.login('lena@htl.at', 'geheim')
    expect(fetch.mock.calls[0][0]).toContain('/sanctum/csrf-cookie')
    expect(auth.me.gamer_tag).toBe('lena42')
  })

  it('logout vergisst die Person auch bei einem Fehler', async () => {
    mockFetch({ status: 200, body: { data: me } }, new TypeError('offline'))
    const auth = useAuthStore()
    await auth.fetchMe()

    await expect(auth.logout()).rejects.toBeTruthy()
    expect(auth.me).toBeNull()
  })

  it('applyProgress übernimmt den neuen Fortschritt nach einer Lerneinheit', async () => {
    mockFetch({ status: 200, body: { data: me } })
    const auth = useAuthStore()
    await auth.fetchMe()

    auth.applyProgress({ level: 25, rank: 'silver', prestige: 1, xp: { total: 8941, level_start: 8941, next_level: 9000 } })
    expect(auth.player.level).toBe(25)
    expect(auth.me.gamer_tag).toBe('lena42')
  })
})
