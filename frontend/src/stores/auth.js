// Eingeloggte Person und ihr Fortschritt. Level, Rang und Prestige rechnet der Server, der Store merkt sie sich nur.
import { defineStore } from 'pinia'
import { computed, ref, watch } from 'vue'
import { api, refreshCsrf } from '../api/client.js'

// Diese Rollen sehen alle Fächer, alle anderen nur den Lehrplan ihrer Klasse (E-024, entscheidet der Server)
const STAFF_ROLES = ['admin', 'autor', 'tester', 'teacher']

export const useAuthStore = defineStore('auth', () => {
  const me = ref(null)
  // true, sobald einmal geprüft wurde, ob eine Session besteht
  const loaded = ref(false)
  // Aufstieg aus der letzten Lerneinheit, den der Hub noch nicht gezeigt hat. Nur im Speicher der App, der Server merkt sich nichts.
  const unseenLevelUp = ref(false)

  watch(me, (person) => {
    if (!person) unseenLevelUp.value = false
  }, { flush: 'sync' })

  const player = computed(() => ({
    level: me.value?.level ?? 1,
    rank: me.value?.rank ?? 'bronze',
    prestige: me.value?.prestige ?? 0,
  }))

  // Personal löscht sich nicht selbst und braucht keine Klasse (E-024, E-027)
  const isStaff = computed(() => STAFF_ROLES.some((role) => me.value?.roles?.includes(role)))

  // Ohne Klasse liefert der Server keine Fächer. Die App erklärt dann, warum.
  const waitingForClass = computed(() => !!me.value && !me.value.school_class && !isStaff.value)

  async function fetchMe() {
    try {
      me.value = (await api('/me')).data
    } catch (error) {
      if (error.status !== 401) throw error
      me.value = null
    } finally {
      loaded.value = true
    }
  }

  async function login(email, password) {
    await refreshCsrf()
    me.value = (await api('/login', { method: 'POST', body: { email, password } })).data
    loaded.value = true
  }

  /** Letzter Schritt der Registrierung: legt das Konto an und meldet an (E-025). */
  async function register(fields) {
    me.value = (await api('/register', { method: 'POST', body: fields })).data
    loaded.value = true
  }

  /** Eigenes Konto löschen, mit Passwort (E-027). Danach gibt es niemanden mehr, der angemeldet ist. */
  async function deleteAccount(password) {
    await api('/me', { method: 'DELETE', body: { password } })
    me.value = null
  }

  async function logout() {
    try {
      await api('/logout', { method: 'POST' })
    } finally {
      me.value = null
    }
  }

  /** Nach dem Abschluss einer Lerneinheit: neuer Fortschritt vom Server (level, rank, prestige, xp, streak). */
  function applyProgress(progress, levelUp = false) {
    if (me.value) me.value = { ...me.value, ...progress }
    if (levelUp) unseenLevelUp.value = true
  }

  /** Der Hub zeigt einen Aufstieg einmal. Danach gilt er als gesehen. */
  function takeLevelUp() {
    const seen = unseenLevelUp.value
    unseenLevelUp.value = false
    return seen
  }

  function clear() {
    me.value = null
  }

  return { me, loaded, player, isStaff, waitingForClass, fetchMe, login, register, deleteAccount, logout, applyProgress, takeLevelUp, clear }
})
