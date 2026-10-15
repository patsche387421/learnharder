<script setup>
// Login mit Schul-Mail und Passwort. Registrieren kommt in M5, bis dahin legt der Admin Accounts an.
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import LhButton from '../components/ui/LhButton.vue'
import LhTextField from '../components/ui/LhTextField.vue'
import { useAuthStore } from '../stores/auth.js'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const email = ref('')
const password = ref('')
const sending = ref(false)
const error = ref('')

// Nur Pfade innerhalb der App, kein //andere-seite.at
function target() {
  const redirect = route.query.redirect
  return typeof redirect === 'string' && redirect.startsWith('/') && !redirect.startsWith('//') ? redirect : '/learn'
}

async function submit() {
  sending.value = true
  error.value = ''
  try {
    await auth.login(email.value, password.value)
    await router.replace(target())
  } catch (e) {
    error.value = e.message
  } finally {
    sending.value = false
  }
}
</script>

<template>
  <main class="login">
    <h1 class="login__title">Hey!</h1>
    <p class="login__text">Log dich mit deiner Schul-Mail ein.</p>
    <form class="login__form" novalidate @submit.prevent="submit">
      <LhTextField v-model="email" label="E-Mail" type="email" autocomplete="username" :error="error" />
      <LhTextField v-model="password" label="Passwort" type="password" autocomplete="current-password" />
      <LhButton type="submit" block :disabled="sending || !email || !password">Einloggen</LhButton>
    </form>
  </main>
</template>

<style scoped>
.login { max-width: var(--lesson-max); margin-inline: auto; padding: var(--space-8) var(--space-4); display: grid; gap: var(--space-2); }
.login__title { margin: 0; font: 700 26px/32px var(--font-text); color: var(--ink); }
.login__text { margin: 0 0 var(--space-4); font: 400 16px/24px var(--font-text); color: var(--ink-muted); }
.login__form { display: grid; gap: var(--space-4); }
</style>
