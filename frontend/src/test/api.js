// fetch-Attrappe für Tests: Antworten der Reihe nach, jede als { status, body }.
import { vi } from 'vitest'

export function jsonResponse(status, body) {
  return new Response(status === 204 ? null : JSON.stringify(body ?? {}), {
    status,
    headers: { 'Content-Type': 'application/json' },
  })
}

export function mockFetch(...responses) {
  const fn = vi.fn()
  for (const r of responses) fn.mockImplementationOnce(async () => (r instanceof Error ? Promise.reject(r) : jsonResponse(r.status, r.body)))
  vi.stubGlobal('fetch', fn)
  return fn
}

export function setXsrfCookie(value) {
  document.cookie = value ? `XSRF-TOKEN=${encodeURIComponent(value)}; path=/` : 'XSRF-TOKEN=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/'
}
