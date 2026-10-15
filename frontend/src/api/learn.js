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

// Lerneinheit: starten, Antwort prüfen lassen, abschließen. Ob richtig und wie viele EP, sagt nur der Server.
export async function startLesson(topicId) {
  return (await api(`/topics/${part(topicId)}/attempts`, { method: 'POST' })).data
}

export async function answerQuestion(attemptId, questionId, optionIds) {
  return (await api(`/attempts/${part(attemptId)}/answers`, { method: 'POST', body: { question_id: questionId, option_ids: optionIds } })).data
}

export async function finishLesson(attemptId) {
  return (await api(`/attempts/${part(attemptId)}/finish`, { method: 'POST' })).data
}
