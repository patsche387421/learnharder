<script setup>
// Profil: Gamer-Tag, Klasse, Roboter, Abmelden, Rechtstexte und „Konto löschen“ (nur Schüler:innen, E-027).
// Klarnamen und E-Mail zeigt die App nie. Pose und Lackierung sind nur Vorschau, gespeichert wird noch nichts.
import { computed, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { BASIC_KIT, PARTS, SLOTS } from '../avatar/catalog.js'
import { pickMood } from '../avatar/mood.js'
import { PAINTS } from '../avatar/paints.js'
import { POSES } from '../avatar/poses.js'
import RobotAvatar from '../avatar/RobotAvatar.vue'
import LhButton from '../components/ui/LhButton.vue'
import LhCard from '../components/ui/LhCard.vue'
import { useAuthStore } from '../stores/auth.js'

const auth = useAuthStore()
const router = useRouter()

const pose = ref('relaxed')
const paint = ref(BASIC_KIT.paint)
const kit = computed(() => ({ ...BASIC_KIT, paint: paint.value }))
const mood = pickMood({ now: new Date() })
const battery = computed(() => {
  const bp = auth.me?.brainpower
  return bp?.max ? bp.current / bp.max : null
})

function equipped(slot) {
  if (slot === 'paint') return PAINTS[kit.value.paint].name
  const key = kit.value[slot]
  return key ? PARTS[slot][key] : slot === 'tool' ? 'Leer, das erste kommt mit Fach-Level 3' : 'Leer'
}

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
    <section class="profile__robot lh-card" aria-labelledby="profile-robot">
      <h2 id="profile-robot" class="lh-card__eyebrow profile__robottitle">Dein Roboter</h2>
      <div class="profile__stage">
        <RobotAvatar :kit="kit" :pose="pose" :mood="mood" :battery="battery" label="Dein Roboter" />
      </div>
      <div class="profile__choices" role="group" aria-label="Pose">
        <button
          v-for="(p, key) in POSES"
          :key="key"
          type="button"
          class="profile__choice"
          :aria-pressed="pose === key"
          @click="pose = key"
        >
          {{ p.name }}
        </button>
      </div>
      <div class="profile__choices" role="group" aria-label="Lackierung">
        <button
          v-for="(p, key) in PAINTS"
          :key="key"
          type="button"
          class="profile__choice"
          :aria-pressed="paint === key"
          @click="paint = key"
        >
          <span class="profile__swatch" :style="{ background: p.main, borderColor: p.accent }" aria-hidden="true" />
          {{ p.name }}
        </button>
      </div>
      <dl class="profile__slots">
        <div v-for="slot in SLOTS" :key="slot.key" class="profile__slot">
          <dt>{{ slot.name }}</dt>
          <dd>{{ equipped(slot.key) }}</dd>
        </div>
      </dl>
      <p class="profile__hint">Nur Vorschau, gespeichert wird später.</p>
    </section>
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
.profile__robot { display: grid; gap: var(--space-3); }
.profile__robottitle { margin: 0; }
.profile__stage { justify-self: center; width: 160px; height: 192px; }
.profile__choices { display: flex; flex-wrap: wrap; gap: var(--space-2); }
.profile__choice {
  display: inline-flex; align-items: center; gap: var(--space-2); min-height: var(--tap-target); padding: 0 var(--space-4);
  border: 0; border-radius: var(--radius-full); background: var(--surface-raised); box-shadow: inset 0 0 0 1px var(--line);
  font: 400 14px/20px var(--font-text); color: var(--ink); cursor: pointer;
}
.profile__choice[aria-pressed='true'] { box-shadow: inset 0 0 0 2px var(--ink); font-weight: 700; }
.profile__choice:focus-visible { outline: 2px solid var(--ink); outline-offset: 2px; }
.profile__swatch { width: 16px; height: 16px; border-radius: var(--radius-full); border: 3px solid; }
.profile__slots { margin: 0; display: grid; gap: var(--space-1); }
.profile__slot { display: flex; justify-content: space-between; gap: var(--space-3); font: 400 14px/20px var(--font-text); }
.profile__slot dt { color: var(--ink-muted); }
.profile__slot dd { margin: 0; color: var(--ink); text-align: right; }
.profile__hint { margin: 0; font: 400 13px/16px var(--font-mono); color: var(--ink-muted); }
.profile__legal { display: flex; flex-wrap: wrap; justify-content: center; gap: var(--space-4); font: 400 14px/20px var(--font-text); }
.profile__legal a { color: var(--ink-muted); }
</style>
