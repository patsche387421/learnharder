// Fächer, Themen und Theorie. Alle Werte (Fortschritt, gesperrt, EP) kommen fertig vom Server.
import { api } from './client.js'

const part = encodeURIComponent

export async function getSubjects() {
  return (await api('/subjects')).data
}

export async function getSubject(code) {
  return (await api(`/subjects/${part(code)}`)).data
}

export async function getTopic(code, slug) {
  return (await api(`/subjects/${part(code)}/topics/${part(slug)}`)).data
}
