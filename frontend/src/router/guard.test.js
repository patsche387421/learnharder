import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { mockFetch, setXsrfCookie } from '../test/api.js'
import { useAuthStore } from '../stores/auth.js'
import { authGuard } from './index.js'

const route = (path, meta = {}) => ({ path, fullPath: path, meta })

describe('authGuard', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    setXsrfCookie('t')
  })
  afterEach(() => {
    vi.unstubAllGlobals()
    setXsrfCookie(null)
  })

  it('ohne Session geht es zum Login und danach zurück', async () => {
    mockFetch({ status: 401 })

    expect(await authGuard(route('/learn'))).toEqual({ path: '/login', query: { redirect: '/learn' } })
  })

  it('fragt /me nur beim ersten Mal', async () => {
    const fetch = mockFetch({ status: 200, body: { data: { gamer_tag: 'x' } } })

    expect(await authGuard(route('/learn'))).toBe(true)
    expect(await authGuard(route('/profile'))).toBe(true)
    expect(fetch).toHaveBeenCalledOnce()
  })

  it('wer eingeloggt ist, sieht Login und Registrierung nicht mehr, Rechtstexte aber schon', async () => {
    mockFetch({ status: 200, body: { data: { gamer_tag: 'x' } } })

    expect(await authGuard(route('/login', { public: true, guest: true }))).toBe('/')
    expect(await authGuard(route('/registrieren', { public: true, guest: true }))).toBe('/')
    expect(await authGuard(route('/datenschutz', { public: true }))).toBe(true)
  })

  it('öffentliche Seiten gehen auch ohne Session', async () => {
    mockFetch({ status: 401 })

    expect(await authGuard(route('/login', { public: true, guest: true }))).toBe(true)
    expect(await authGuard(route('/impressum', { public: true }))).toBe(true)
  })

  it('ist der Server nicht erreichbar, geht es zum Login', async () => {
    mockFetch(new TypeError('offline'))

    expect(await authGuard(route('/'))).toEqual({ path: '/login', query: {} })
    expect(useAuthStore().loaded).toBe(true)
  })
})
