import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { mockFetch, setXsrfCookie } from '../test/api.js'
import { api, ApiError, MESSAGES, onUnauthorized } from './client.js'

const urls = (fetch) => fetch.mock.calls.map(([url]) => url)

describe('api', () => {
  beforeEach(() => setXsrfCookie('token=1'))
  afterEach(() => {
    vi.unstubAllGlobals()
    setXsrfCookie(null)
    onUnauthorized(() => {})
  })

  it('schickt Cookies, Accept und den XSRF-Header aus dem Cookie mit', async () => {
    const fetch = mockFetch({ status: 200, body: { data: { ok: true } } })

    expect(await api('/subjects')).toEqual({ data: { ok: true } })
    const [url, init] = fetch.mock.calls[0]
    expect(url).toBe('http://localhost/api/v1/subjects')
    expect(init.credentials).toBe('include')
    expect(init.headers.Accept).toBe('application/json')
    expect(init.headers['X-XSRF-TOKEN']).toBe('token=1')
  })

  it('holt vor dem ersten POST ohne Cookie zuerst das CSRF-Cookie', async () => {
    setXsrfCookie(null)
    const fetch = mockFetch({ status: 204 }, { status: 204 })

    expect(await api('/logout', { method: 'POST' })).toBeNull()
    expect(urls(fetch)).toEqual(['http://localhost/sanctum/csrf-cookie', 'http://localhost/api/v1/logout'])
  })

  it('schickt den Body als JSON', async () => {
    const fetch = mockFetch({ status: 200, body: {} })

    await api('/login', { method: 'POST', body: { email: 'a@b.at' } })
    expect(fetch.mock.calls[0][1].body).toBe('{"email":"a@b.at"}')
    expect(fetch.mock.calls[0][1].headers['Content-Type']).toBe('application/json')
  })

  it('bei 419 einmal das Cookie erneuern und genau einmal wiederholen', async () => {
    const fetch = mockFetch({ status: 419 }, { status: 204 }, { status: 200, body: { data: 1 } })

    expect(await api('/attempts/1/finish', { method: 'POST' })).toEqual({ data: 1 })
    expect(urls(fetch)).toEqual([
      'http://localhost/api/v1/attempts/1/finish',
      'http://localhost/sanctum/csrf-cookie',
      'http://localhost/api/v1/attempts/1/finish',
    ])
  })

  it('zweimal 419 ist ein Fehler, ohne Schleife', async () => {
    const fetch = mockFetch({ status: 419 }, { status: 204 }, { status: 419 })

    await expect(api('/x', { method: 'POST' })).rejects.toMatchObject({ status: 419, message: MESSAGES.generic })
    expect(fetch).toHaveBeenCalledTimes(3)
  })

  it('zwei gleichzeitige 419 teilen sich einen Refresh', async () => {
    const fetch = mockFetch({ status: 419 }, { status: 419 }, { status: 204 }, { status: 200, body: { n: 1 } }, { status: 200, body: { n: 2 } })

    await Promise.all([api('/a', { method: 'POST' }), api('/b', { method: 'POST' })])
    expect(urls(fetch).filter((u) => u.endsWith('/sanctum/csrf-cookie'))).toHaveLength(1)
    expect(fetch).toHaveBeenCalledTimes(5)
  })

  it('401 ruft den Abmelde-Handler und wirft', async () => {
    const handler = vi.fn()
    onUnauthorized(handler)
    mockFetch({ status: 401, body: { message: 'Unauthenticated.' } })

    await expect(api('/me')).rejects.toMatchObject({ status: 401 })
    expect(handler).toHaveBeenCalledOnce()
  })

  it('422 zeigt die erste Meldung des Servers', async () => {
    mockFetch({ status: 422, body: { errors: { email: ['E-Mail oder Passwort stimmt nicht.'] } } })

    const error = await api('/login', { method: 'POST', body: {} }).catch((e) => e)
    expect(error).toBeInstanceOf(ApiError)
    expect(error.message).toBe('E-Mail oder Passwort stimmt nicht.')
    expect(error.errors.email).toEqual(['E-Mail oder Passwort stimmt nicht.'])
  })

  it('ohne Netz und bei 429 kommen verständliche Texte', async () => {
    mockFetch(new TypeError('Failed to fetch'))
    await expect(api('/me')).rejects.toMatchObject({ status: 0, message: MESSAGES.network })

    mockFetch({ status: 429 })
    await expect(api('/me')).rejects.toMatchObject({ status: 429, message: MESSAGES.tooMany })

    mockFetch({ status: 500 })
    await expect(api('/me')).rejects.toMatchObject({ status: 500, message: MESSAGES.generic })
  })
})
