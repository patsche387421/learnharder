<script setup>
// Eigenes Konto löschen (E-027): sofort und vollständig, nur mit Passwort. Personal löscht sich nicht selbst.
// Gefahr-Button nie allein: daneben „Abbrechen“, davor der Satz, was verloren geht (docs/design/components/Button.md).
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import LhButton from '../components/ui/LhButton.vue'
import LhIcon from '../components/ui/LhIcon.vue'
import LhTextField from '../components/ui/LhTextField.vue'
import { useAuthStore } from '../stores/auth.js'

const auth = useAuthStore()
const router = useRouter()

const password = ref('')
const sending = ref(false)
const error = ref('')

async function remove() {
  sending.value = true
  error.value = ''
  try {
    await auth.deleteAccount(password.value)
    await router.replace({ path: '/login', query: { hinweis: 'geloescht' } })
  } catch (e) {
    error.value = e.message
  } finally {
    sending.value = false
  }
}
</script>

<template>
  <div class="delete">
    <div class="lh-lessonbar">
      <RouterLink class="lh-iconbtn" to="/profile" aria-label="Schließen"><LhIcon name="ui-close" /></RouterLink>
    </div>
    <h1 class="delete__title">Konto löschen</h1>

    <p v-if="auth.isStaff" class="delete__text">Dein Konto kann nur ein Admin sperren oder löschen.</p>

    <form v-else class="delete__form" novalidate @submit.prevent="remove">
      <p class="delete__text">
        Dein Konto und alles, was dazugehört, sind sofort weg: Gamer-Tag, Level, Erfahrungspunkte, Fortschritt und deine Klasse.
        Das lässt sich nicht rückgängig machen.
      </p>
      <p class="delete__text">
        In Sicherungskopien liegt es noch kurz, bis sie überschrieben werden. Mehr in der
        <RouterLink to="/datenschutz">Datenschutzerklärung</RouterLink>.
      </p>
      <LhTextField v-model="password" label="Dein Passwort" type="password" autocomplete="current-password" :error="error" />
      <LhButton type="submit" variant="danger" block :disabled="sending || !password">Konto endgültig löschen</LhButton>
      <LhButton variant="ghost" block to="/profile">Abbrechen</LhButton>
    </form>
  </div>
</template>

<style scoped>
.delete { display: grid; gap: var(--space-4); }
.delete__title { margin: 0; font: 700 26px/32px var(--font-text); color: var(--ink); }
.delete__text { margin: 0; font: 400 16px/24px var(--font-text); color: var(--ink); }
.delete__text a { color: var(--ink); }
.delete__form { display: grid; gap: var(--space-4); }
</style>
