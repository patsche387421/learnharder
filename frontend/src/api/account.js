// Registrierung und Rechtstexte (E-023, E-025). Was erlaubt ist, entscheidet der Server, die App zeigt seine Meldungen.
import { api } from './client.js'

export async function requestRegistrationCode(email) {
  return api('/register/code', { method: 'POST', body: { email } })
}

/** Liefert den Einmal-Token für den letzten Schritt. */
export async function verifyRegistrationCode(email, code) {
  return (await api('/register/verify', { method: 'POST', body: { email, code } })).token
}

export async function getLegal(slug) {
  return (await api(`/legal/${encodeURIComponent(slug)}`)).data
}
