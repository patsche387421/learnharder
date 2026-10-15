// Eingeloggte Person und ihr Fortschritt. Level, Rang und Prestige rechnet der Server, der Store merkt sie sich nur.
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { api, refreshCsrf } from '../api/client.js'

export const useAuthStore = defineStore('auth', () => {
  const me = ref(null)
  // true, sobald einmal geprüft wurde, ob eine Session besteht
  const loaded = ref(false)

  const player = computed(() => ({
    level: me.value?.level ?? 1,
    rank: me.value?.rank ?? 'bronze',
    prestige: me.value?.prestige ?? 0,
  }))

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

  return { me, loaded, player, fetchMe, login, logout, applyProgress, clear }
})
