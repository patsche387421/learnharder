<script setup>
// Profil: Gamer-Tag, Klasse, Abmelden, Rechtstexte und „Konto löschen“ (nur Schüler:innen, E-027). Klarnamen und E-Mail zeigt die App nie.
import { RouterLink, useRouter } from 'vue-router'
import LhButton from '../components/ui/LhButton.vue'
import LhCard from '../components/ui/LhCard.vue'
import { useAuthStore } from '../stores/auth.js'

const auth = useAuthStore()
const router = useRouter()

// Auch ohne Netz abmelden: Der Store vergisst die Person in jedem Fall
async function logout() {
  await auth.logout().catch(() => {})
  await router.replace('/login')
}
</script>

<template>
  <div class="profile">
    <LhCard eyebrow="Gamer-Tag">
      <p class="profile__tag">{{ auth.me?.gamer_tag }}</p>
    </LhCard>
    <LhCard v-if="auth.me?.school_class" eyebrow="Klasse">
      <p class="profile__tag">{{ auth.me.school_class.name }}</p>
    </LhCard>
    <LhButton variant="secondary" block @click="logout">Abmelden</LhButton>
    <LhButton v-if="!auth.isStaff" variant="ghost" block to="/profile/delete">Konto löschen</LhButton>
    <nav class="profile__legal" aria-label="Rechtliches">
      <RouterLink to="/datenschutz">Datenschutz</RouterLink>
      <RouterLink to="/nutzungsregeln">Nutzungsregeln</RouterLink>
      <RouterLink to="/impressum">Impressum</RouterLink>
    </nav>
  </div>
</template>

<style scoped>
.profile { display: grid; gap: var(--space-4); }
.profile__tag { margin: 0; font: 700 18px/24px var(--font-text); color: var(--ink); }
.profile__legal { display: flex; flex-wrap: wrap; justify-content: center; gap: var(--space-4); font: 400 14px/20px var(--font-text); }
.profile__legal a { color: var(--ink-muted); }
</style>
