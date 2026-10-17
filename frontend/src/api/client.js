// Einziger Ort, der mit dem Laravel-Backend spricht. Sanctum im SPA-Modus: Session-Cookie plus XSRF-Token.
// Die App holt einmal /sanctum/csrf-cookie und schickt danach den Header X-XSRF-TOKEN aus dem Cookie mit.

const BASE = import.meta.env.VITE_API_URL ?? 'http://localhost'

// Texte nach docs/design/Sprache.md: sagen, was passiert ist und was jetzt zu tun ist
export const MESSAGES = {
  network: 'Das hat nicht geklappt. Prüf deine Verbindung und versuch’s nochmal.',
  tooMany: 'Zu viele Versuche. Warte kurz und versuch’s dann nochmal.',
  generic: 'Das hat nicht geklappt. Versuch’s nochmal.',
}

export class ApiError extends Error {
  constructor(status, message, errors = {}) {
    super(message)
    this.status = status
    this.errors = errors
  }
}

let unauthorizedHandler = () => {}

/**
 * Wird bei jeder 401 aufgerufen (Session abgelaufen) und bei einem gesperrten Konto (403 mit reason, E-027).
 * main.js leert dann den Store und zeigt /login. Der Handler bekommt den Grund oder null.
 */
export function onUnauthorized(handler) {
  unauthorizedHandler = handler
}

function xsrfToken() {
  const cookie = document.cookie.split('; ').find((c) => c.startsWith('XSRF-TOKEN='))
  return cookie ? decodeURIComponent(cookie.slice('XSRF-TOKEN='.length)) : null
}

// Laufen mehrere Anfragen gleichzeitig in eine 419, teilen sie sich diesen einen Refresh
let csrfRefresh = null

export function refreshCsrf() {
  csrfRefresh ??= fetch(`${BASE}/sanctum/csrf-cookie`, { credentials: 'include', headers: { Accept: 'application/json' } })
    .catch(() => {
      throw new ApiError(0, MESSAGES.network)
    })
    .finally(() => {
      csrfRefresh = null
    })
  return csrfRefresh
}

async function send(path, method, body) {
  const headers = { Accept: 'application/json' }
  if (body !== undefined) headers['Content-Type'] = 'application/json'
  const token = xsrfToken()
  if (token) headers['X-XSRF-TOKEN'] = token

  try {
    return await fetch(`${BASE}/api/v1${path}`, {
      method,
      headers,
      credentials: 'include',
      body: body === undefined ? undefined : JSON.stringify(body),
    })
  } catch {
    throw new ApiError(0, MESSAGES.network)
  }
}

function errorMessage(status, data) {
  if (status === 422 && data?.errors) return Object.values(data.errors)[0][0]
  if (data?.reason === 'account_deactivated') return data.message
  // 409 kommt nur aus der eigenen Spiellogik und ist deutsch formuliert („Es läuft schon ein Booster.“)
  if (status === 409 && data?.message) return data.message
  if (status === 429) return MESSAGES.tooMany
  return MESSAGES.generic
}

/**
 * Anfrage an /api/v1. Liefert das JSON der Antwort (oder null bei 204), sonst ApiError.
 * 419 (CSRF-Token abgelaufen): einmal neu holen und genau einmal wiederholen, keine Schleife.
 */
export async function api(path, { method = 'GET', body } = {}) {
  if (method !== 'GET' && !xsrfToken()) await refreshCsrf()

  let response = await send(path, method, body)
  if (response.status === 419) {
    await refreshCsrf()
    response = await send(path, method, body)
  }

  if (response.status === 204) return null
  const data = await response.json().catch(() => null)
  if (response.ok) return data

  if (response.status === 401) unauthorizedHandler(null)
  if (response.status === 403 && data?.reason === 'account_deactivated') unauthorizedHandler(data.reason)
  throw new ApiError(response.status, errorMessage(response.status, data), data?.errors ?? {})
}
