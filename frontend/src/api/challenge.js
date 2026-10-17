// Herausforderung (E-045): starten und fortsetzen. Antworten und Abschluss laufen über die Routen der Lerneinheit
// (answerQuestion, finishLesson in learn.js). Kosten, Regeln und Stand kommen aus GET /hub (challenge).
import { api } from './client.js'

/** Startet eine Herausforderung für Brainpower. Liefert Fragen (mit Fach) und die neue Brainpower. */
export async function startChallenge() {
  return (await api('/challenges', { method: 'POST' })).data
}

/** Offene Herausforderung von heute mit den schon beantworteten Fragen, sonst null. */
export async function getCurrentChallenge() {
  return (await api('/challenges/current')).data
}
