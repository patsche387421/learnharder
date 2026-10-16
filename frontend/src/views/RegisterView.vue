<script setup>
// Registrierung in drei Schritten (E-025): Schul-Mail → Code aus der Mail → Gamer-Tag, Passwort und Zustimmung.
// Was gilt (Domain, Alter, Gamer-Tag), prüft der Server. Die App zeigt seine Meldungen am passenden Feld.
import { computed, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import LhButton from '../components/ui/LhButton.vue'
import LhCheckbox from '../components/ui/LhCheckbox.vue'
import LhTextField from '../components/ui/LhTextField.vue'
import { requestRegistrationCode, verifyRegistrationCode } from '../api/account.js'
import { useAuthStore } from '../stores/auth.js'

const auth = useAuthStore()
const router = useRouter()

const step = ref('email')
const email = ref('')
const code = ref('')
const token = ref('')
const form = ref({ gamer_tag: '', password: '', password_confirmation: '', is_14: false, accepts_terms: false })
const sending = ref(false)
const errors = ref({})
const notice = ref('')
const under14 = ref(false)

const error = (field) => errors.value[field]?.[0] ?? ''
const profileComplete = computed(() => form.value.gamer_tag && form.value.password && form.value.password_confirmation)

/** Führt eine Anfrage aus und verteilt Fehler auf die Felder. Meldungen ohne Feld landen beim ersten Feld des Schritts. */
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
    await requestRegistrationCode(email.value)
    code.value = ''
    notice.value = ''
    step.value = 'code'
  }, 'email')
}

function checkCode() {
  return run(async () => {
    token.value = await verifyRegistrationCode(email.value, code.value)
    step.value = 'profile'
  }, 'code')
}

function register() {
  return run(async () => {
    try {
      await auth.register({ token: token.value, ...form.value })
    } catch (e) {
      // Bestätigung abgelaufen: noch einmal mit der E-Mail anfangen
      if (e.errors?.token) {
        step.value = 'email'
        notice.value = e.errors.token[0]
        return
      }
      throw e
    }
    await router.replace('/learn')
  }, 'gamer_tag')
}

function changeEmail() {
  errors.value = {}
  step.value = 'email'
}
</script>

<template>
  <main class="register">
    <h1 class="register__title">Willkommen!</h1>

    <form v-if="step === 'email'" class="register__form" novalidate @submit.prevent="sendCode">
      <p class="register__text">Registrier dich mit deiner Schul-Mail. Wir schicken dir einen Code.</p>
      <p v-if="notice" class="register__notice" role="status">{{ notice }}</p>
      <LhTextField
        v-model="email"
        label="Schul-Mail"
        type="email"
        autocomplete="email"
        placeholder="2026034@htl-md.schule"
        :error="error('email')"
      />
      <LhButton type="submit" block :disabled="sending || !email">Code schicken</LhButton>
    </form>

    <form v-else-if="step === 'code'" class="register__form" novalidate @submit.prevent="checkCode">
      <p class="register__text">Wir haben dir einen Code an <strong>{{ email }}</strong> geschickt. Er gilt 10 Minuten.</p>
      <LhTextField
        v-model="code"
        label="Code aus der Mail"
        inputmode="numeric"
        autocomplete="one-time-code"
        maxlength="6"
        :error="error('code')"
      />
      <LhButton type="submit" block :disabled="sending || code.length < 6">Weiter</LhButton>
      <div class="register__row">
        <LhButton variant="ghost" :disabled="sending" @click="sendCode">Neuen Code schicken</LhButton>
        <LhButton variant="ghost" :disabled="sending" @click="changeEmail">Andere E-Mail</LhButton>
      </div>
    </form>

    <form v-else class="register__form" novalidate @submit.prevent="register">
      <p class="register__text">Fast geschafft. Wähl deinen Gamer-Tag und ein Passwort.</p>
      <LhTextField
        v-model="form.gamer_tag"
        label="Gamer-Tag"
        autocomplete="username"
        hint="3 bis 20 Zeichen: Buchstaben, Ziffern, _ und -. Kein echter Name, den sehen später andere."
        :error="error('gamer_tag')"
      />
      <LhTextField
        v-model="form.password"
        label="Passwort"
        type="password"
        autocomplete="new-password"
        hint="Mindestens 8 Zeichen"
        :error="error('password')"
      />
      <LhTextField v-model="form.password_confirmation" label="Passwort wiederholen" type="password" autocomplete="new-password" />
      <LhCheckbox v-model="form.is_14" :error="error('is_14')">Ich bin mindestens 14 Jahre alt.</LhCheckbox>
      <LhCheckbox v-model="form.accepts_terms" :error="error('accepts_terms')">
        Ich stimme der <a href="/datenschutz" target="_blank">Datenschutzerklärung</a> und den
        <a href="/nutzungsregeln" target="_blank">Nutzungsregeln</a> zu.
      </LhCheckbox>
      <LhButton type="submit" block :disabled="sending || !profileComplete">Konto anlegen</LhButton>
      <LhButton variant="ghost" @click="under14 = !under14">Noch nicht 14?</LhButton>
      <p v-if="under14" class="register__text">
        Dann brauchst du die Einwilligung deiner Eltern. Druck das
        <a href="/einwilligung-eltern" target="_blank">Formular für die Eltern</a> aus und gib es unterschrieben deiner Lehrkraft.
        Dein Konto legt dann ein Admin an.
      </p>
    </form>

    <p class="register__text">Schon registriert? <RouterLink to="/login">Zum Login</RouterLink></p>
    <nav class="register__legal" aria-label="Rechtliches">
      <RouterLink to="/datenschutz">Datenschutz</RouterLink>
      <RouterLink to="/impressum">Impressum</RouterLink>
    </nav>
  </main>
</template>

<style scoped>
.register { max-width: var(--lesson-max); margin-inline: auto; padding: var(--space-8) var(--space-4); display: grid; gap: var(--space-4); }
.register__title { margin: 0; font: 700 26px/32px var(--font-text); color: var(--ink); }
.register__text { margin: 0; font: 400 16px/24px var(--font-text); color: var(--ink-muted); }
.register__text a, .register__legal a { color: var(--ink); }
.register__notice { margin: 0; font: 400 16px/24px var(--font-text); color: var(--wrong); }
.register__form { display: grid; gap: var(--space-4); }
.register__row { display: flex; flex-wrap: wrap; gap: var(--space-2); }
.register__legal { display: flex; gap: var(--space-4); font: 400 14px/20px var(--font-text); }
</style>
