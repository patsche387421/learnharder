<script setup>
// Profil, vorerst nur Gamer-Tag und Abmelden. Klarnamen und E-Mail zeigt die App nie.
import { useRouter } from 'vue-router'
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
    <LhButton variant="secondary" block @click="logout">Abmelden</LhButton>
  </div>
</template>

<style scoped>
.profile { display: grid; gap: var(--space-4); }
.profile__tag { margin: 0; font: 700 18px/24px var(--font-text); color: var(--ink); }
</style>
