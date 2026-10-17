<script setup>
// Login mit Schul-Mail und Passwort. Neu hier? Dann über /registrieren (E-025).
import { ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import LhButton from '../components/ui/LhButton.vue'
import LhTextField from '../components/ui/LhTextField.vue'
import { useAuthStore } from '../stores/auth.js'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const email = ref('')
const password = ref('')
const sending = ref(false)
// Nach einer Sperre schickt main.js mit ?gesperrt=1 hierher (E-027)
const error = ref(route.query.gesperrt ? 'Dein Konto ist gesperrt. Wende dich an deine Lehrkraft.' : '')
// Bestätigung nach „Passwort vergessen“ oder „Konto löschen“
const NOTICES = { passwort: 'Dein Passwort ist neu. Log dich damit ein.', geloescht: 'Dein Konto und dein Lernverlauf sind gelöscht. Mach’s gut!' }
const notice = NOTICES[route.query.hinweis] ?? ''

// Nur Pfade innerhalb der App, kein //andere-seite.at
function target() {
  const redirect = route.query.redirect
  return typeof redirect === 'string' && redirect.startsWith('/') && !redirect.startsWith('//') ? redirect : '/'
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
    <p v-if="notice" class="login__notice" role="status">{{ notice }}</p>
    <form class="login__form" novalidate @submit.prevent="submit">
      <LhTextField v-model="email" label="E-Mail" type="email" autocomplete="username" :error="error" />
      <LhTextField v-model="password" label="Passwort" type="password" autocomplete="current-password" />
      <LhButton type="submit" block :disabled="sending || !email || !password">Einloggen</LhButton>
      <LhButton variant="ghost" to="/passwort-vergessen">Passwort vergessen?</LhButton>
    </form>
    <p class="login__text">Neu hier? <RouterLink to="/registrieren">Registrieren</RouterLink></p>
    <nav class="login__legal" aria-label="Rechtliches">
      <RouterLink to="/datenschutz">Datenschutz</RouterLink>
      <RouterLink to="/impressum">Impressum</RouterLink>
    </nav>
  </main>
</template>

<style scoped>
.login { max-width: var(--lesson-max); margin-inline: auto; padding: var(--space-8) var(--space-4); display: grid; gap: var(--space-2); }
.login__title { margin: 0; font: 700 26px/32px var(--font-text); color: var(--ink); }
.login__text { margin: 0 0 var(--space-4); font: 400 16px/24px var(--font-text); color: var(--ink-muted); }
.login__form { display: grid; gap: var(--space-4); }
.login__notice { margin: 0 0 var(--space-4); padding: var(--space-3) var(--space-4); border-radius: var(--radius-md); background: var(--surface-sunken); font: 400 16px/24px var(--font-text); color: var(--ink); }
.login__text a, .login__legal a { color: var(--ink); }
.login__legal { display: flex; gap: var(--space-4); margin-top: var(--space-2); font: 400 14px/20px var(--font-text); }
</style>
