// Eingeloggte Person und ihr Fortschritt. Level, Rang und Prestige rechnet der Server, der Store merkt sie sich nur.
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { api, refreshCsrf } from '../api/client.js'

// Diese Rollen sehen alle Fächer, alle anderen nur den Lehrplan ihrer Klasse (E-024, entscheidet der Server)
const STAFF_ROLES = ['admin', 'autor', 'tester', 'teacher']

export const useAuthStore = defineStore('auth', () => {
  const me = ref(null)
  // true, sobald einmal geprüft wurde, ob eine Session besteht
  const loaded = ref(false)

  const player = computed(() => ({
    level: me.value?.level ?? 1,
    rank: me.value?.rank ?? 'bronze',
    prestige: me.value?.prestige ?? 0,
  }))

  // Ohne Klasse liefert der Server keine Fächer. Die App erklärt dann, warum.
  const waitingForClass = computed(() => !!me.value && !me.value.school_class
    && !STAFF_ROLES.some((role) => me.value.roles?.includes(role)))

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

  async function logout() {
    try {
      await api('/logout', { method: 'POST' })
    } finally {
      me.value = null
    }
  }

  /** Nach dem Abschluss einer Lerneinheit: neuer Fortschritt vom Server (level, rank, prestige, xp). */
  function applyProgress(progress) {
    if (me.value) me.value = { ...me.value, ...progress }
  }

  function clear() {
    me.value = null
  }

  return { me, loaded, player, waitingForClass, fetchMe, login, register, logout, applyProgress, clear }
})
