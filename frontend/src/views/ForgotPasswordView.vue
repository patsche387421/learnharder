<script setup>
// Passwort vergessen in zwei Schritten (E-025): Schul-Mail → Code aus der Mail und neues Passwort.
// Auch der Weg zum ersten Passwort für Konten, die ein Admin angelegt hat. Danach geht es zum Login.
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import LhButton from '../components/ui/LhButton.vue'
import LhTextField from '../components/ui/LhTextField.vue'
import { requestPasswordCode, resetPassword } from '../api/account.js'

const router = useRouter()

const step = ref('email')
const email = ref('')
const form = ref({ code: '', password: '', password_confirmation: '' })
const sending = ref(false)
const errors = ref({})

const error = (field) => errors.value[field]?.[0] ?? ''

async function run(action, fallbackField) {
  sending.value = true
  errors.value = {}
  try {
    await action()
  } catch (e) {
    errors.value = Object.keys(e.errors ?? {}).length ? e.errors : { [fallbackField]: [e.message] }
  } finally {
    sending.value = false
  }
}

function sendCode() {
  return run(async () => {
    await requestPasswordCode(email.value)
    step.value = 'reset'
  }, 'email')
}

function reset() {
  return run(async () => {
    await resetPassword({ email: email.value, ...form.value })
    await router.replace({ path: '/login', query: { hinweis: 'passwort' } })
  }, 'code')
}
</script>

<template>
  <main class="forgot">
    <h1 class="forgot__title">Neues Passwort</h1>

    <form v-if="step === 'email'" class="forgot__form" novalidate @submit.prevent="sendCode">
      <p class="forgot__text">Gib deine Schul-Mail ein. Wir schicken dir einen Code, mit dem du ein neues Passwort festlegst.</p>
      <LhTextField v-model="email" label="Schul-Mail" type="email" autocomplete="email" :error="error('email')" />
      <LhButton type="submit" block :disabled="sending || !email">Code schicken</LhButton>
    </form>

    <form v-else class="forgot__form" novalidate @submit.prevent="reset">
      <p class="forgot__text">Wenn es zu <strong>{{ email }}</strong> ein Konto gibt, kommt gleich ein Code. Er gilt 10 Minuten.</p>
      <LhTextField
        v-model="form.code"
        label="Code aus der Mail"
        inputmode="numeric"
        autocomplete="one-time-code"
        maxlength="6"
        :error="error('code')"
      />
      <LhTextField
        v-model="form.password"
        label="Neues Passwort"
        type="password"
        autocomplete="new-password"
        hint="Mindestens 8 Zeichen. Danach bist du auf allen anderen Geräten abgemeldet."
        :error="error('password')"
      />
      <LhTextField v-model="form.password_confirmation" label="Passwort wiederholen" type="password" autocomplete="new-password" />
      <LhButton type="submit" block :disabled="sending || form.code.length < 6 || !form.password">Passwort speichern</LhButton>
      <LhButton variant="ghost" :disabled="sending" @click="sendCode">Neuen Code schicken</LhButton>
    </form>

    <p class="forgot__text"><RouterLink to="/login">Zurück zum Login</RouterLink></p>
  </main>
</template>

<style scoped>
.forgot { max-width: var(--lesson-max); margin-inline: auto; padding: var(--space-8) var(--space-4); display: grid; gap: var(--space-4); }
.forgot__title { margin: 0; font: 700 26px/32px var(--font-text); color: var(--ink); }
.forgot__text { margin: 0; font: 400 16px/24px var(--font-text); color: var(--ink-muted); }
.forgot__text a { color: var(--ink); }
.forgot__form { display: grid; gap: var(--space-4); }
</style>
