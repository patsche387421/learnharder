<script setup>
// Hub (Startseite, Konzept Spielwelt §4): Begrüßung nach Zustand und Tageszeit, Level und Brainpower aus /me, „Weiterlernen“ aus /hub.
// Zustände in dieser Reihenfolge: gerade aufgestiegen, zurück nach 3 oder mehr Tagen, heute schon gelernt, erster Besuch.
// Ob „heute“, rechnet die App nach Gerätezeit aus last_study_at. Den Aufstieg merkt sich die App aus der Lerneinheit.
// Neben der Begrüßung der Avatar (G4): winkt beim Öffnen, Stimmung passend zum Zustand, Akku zeigt die Brainpower.
// Shop (E-037): eigene Zeile, dazu der Brainpower-Chip als Abkürzung. Ein laufender Booster steht auf „Weiterlernen“ und am Chip.
import { computed, onMounted } from 'vue'
import LoadError from '../components/LoadError.vue'
import LhArt from '../components/ui/LhArt.vue'
import LhCard from '../components/ui/LhCard.vue'
import LhCurrencyChip from '../components/ui/LhCurrencyChip.vue'
import LhEmptyState from '../components/ui/LhEmptyState.vue'
import LhLevelBadge from '../components/ui/LhLevelBadge.vue'
import LhXpBar from '../components/ui/LhXpBar.vue'
import { getHub } from '../api/hub.js'
import { pickMood } from '../avatar/mood.js'
import RobotAvatar from '../avatar/RobotAvatar.vue'
import { useBooster } from '../composables/useBooster.js'
import { useLoad } from '../composables/useLoad.js'
import { useAuthStore } from '../stores/auth.js'

const RANKS = { bronze: 'Bronze', silver: 'Silber', gold: 'Gold', platin: 'Platin' }

const auth = useAuthStore()
const { data: hub, error, loading, load } = useLoad(getHub)
const booster = useBooster(() => auth.me?.booster)

onMounted(() => {
  if (!auth.waitingForClass) load()
})

// Einmal beim Öffnen abholen, danach gilt der Aufstieg als gesehen
const levelUp = auth.takeLevelUp()
const now = new Date()

// Kalendertage seit der letzten Lerneinheit nach Gerätezeit, null vor der ersten
const daysAway = computed(() => {
  const last = hub.value?.last_study_at
  if (!last) return null
  const day = (d) => new Date(d.getFullYear(), d.getMonth(), d.getDate())
  return Math.max(0, Math.round((day(now) - day(new Date(last))) / 86_400_000))
})
const firstVisit = computed(() => !!hub.value && daysAway.value === null)
const learnedToday = computed(() => daysAway.value === 0)
const backAfterBreak = computed(() => daysAway.value >= 3)

const greeting = computed(() => {
  const tag = auth.me?.gamer_tag
  if (levelUp) return `Level ${auth.player.level}!`
  if (backAfterBreak.value) return `Schön, dass du da bist, ${tag}!`
  const hour = now.getHours()
  if (hour >= 5 && hour < 11) return `Guten Morgen, ${tag}!`
  if (hour >= 11 && hour < 18) return `Hey ${tag}!`
  if (hour >= 18 && hour < 22) return `Guten Abend, ${tag}!`
  return `Noch wach, ${tag}?`
})

const next = computed(() => hub.value?.next_topic ?? null)

const nudge = computed(() => {
  const title = next.value?.title
  if (!title) return ''
  if (firstVisit.value) return `Fang mit ${title} an. 5 Min. reichen.`
  if (learnedToday.value && !levelUp) return `Heute schon gelernt. Noch eine Runde ${title}?`
  return `Weiter mit ${title}? 5 Min. reichen.`
})

const mood = computed(() =>
  pickMood({ now, unseenMilestone: levelUp, daysAway: daysAway.value ?? 0, learnedToday: learnedToday.value }),
)
const battery = computed(() => {
  const bp = auth.me?.brainpower
  return bp?.max ? bp.current / bp.max : null
})

const levelBar = computed(() => {
  const xp = auth.me?.xp
  return xp ? { value: xp.total - xp.level_start, max: xp.next_level - xp.level_start, left: xp.next_level - xp.total } : null
})

// Nach Level 100 kommt das nächste Prestige, Level beginnt wieder bei 1
const nextStep = computed(() => (auth.player.level < 100 ? `Level ${auth.player.level + 1}` : `Prestige ${auth.player.prestige + 1}`))
</script>

<template>
  <div class="hub">
    <header class="hub__head">
      <div class="hub__hello">
        <h1 class="hub__title">{{ greeting }}</h1>
        <p v-if="nudge" class="hub__sub">{{ nudge }}</p>
      </div>
      <div class="hub__avatar">
        <RobotAvatar :mood="mood" :action="levelUp ? 'celebrate' : 'wave'" :battery="battery" label="Dein Roboter" />
      </div>
    </header>

    <section class="hub__progress lh-card" aria-label="Fortschritt">
      <div class="hub__level">
        <LhLevelBadge v-bind="auth.player" large />
        <span class="hub__levelname">Level {{ auth.player.level }} · {{ RANKS[auth.player.rank] }}</span>
        <LhCurrencyChip
          v-if="auth.me?.brainpower"
          icon="icon-brainpower"
          :value="auth.me.brainpower.current"
          label="Brainpower"
          :boost="booster?.factor"
          to="/shop"
        />
      </div>
      <LhXpBar v-if="levelBar" :value="levelBar.value" :max="levelBar.max">
        <template #bottom>
          <span>{{ levelBar.value }} / {{ levelBar.max }} EP</span>
          <span>{{ levelBar.left }} EP bis {{ nextStep }}</span>
        </template>
      </LhXpBar>
    </section>

    <LhEmptyState
      v-if="auth.waitingForClass"
      title="Noch keiner Klasse zugeordnet."
      text="Melde dich bei Admin oder deiner Lehrkraft, dann siehst du hier, wie es weitergeht."
    />
    <p v-else-if="loading && !hub" class="hub__loading" aria-live="polite">Lädt …</p>
    <LoadError v-else-if="error" :error="error" @retry="load" />
    <LhCard
      v-else-if="next"
      featured
      :title="next.title"
      :text="next.subject.name"
      :to="`/learn/${next.subject.code}/${next.slug}`"
    >
      <template #eyebrow>
        <span class="hub__eyebrow">Weiterlernen<span v-if="booster" class="lh-tag lh-tag--boost">{{ booster.factor }} EP · {{ booster.minutes }} Min.</span></span>
      </template>
      <template #icon><LhArt :name="`subject-${next.subject.code}`" :size="40" /></template>
    </LhCard>
    <LhCard
      v-else-if="hub"
      featured
      eyebrow="Weiterlernen"
      title="Alles erledigt."
      text="Wiederhol ein Thema, dann sitzt es."
      to="/learn"
    />

    <LhCard v-if="!auth.waitingForClass" title="Shop" text="Brainpower einsetzen" to="/shop">
      <template #icon><LhArt name="icon-booster" :size="40" /></template>
    </LhCard>
  </div>
</template>

<style scoped>
.hub { display: grid; grid-template-columns: minmax(0, 1fr); gap: var(--space-4); }
.hub__head { display: flex; align-items: center; justify-content: space-between; gap: var(--space-3); }
.hub__hello { display: grid; gap: var(--space-1); }
.hub__title { margin: 0; font: 700 26px/32px var(--font-text); color: var(--ink); }
.hub__sub { margin: 0; color: var(--ink-muted); }
.hub__avatar { flex: none; width: 96px; height: 116px; }
.hub__progress { display: grid; gap: var(--space-3); }
.hub__level { display: flex; align-items: center; gap: var(--space-3); }
.hub__levelname { flex: 1; min-width: 0; font: 700 16px/24px var(--font-text); color: var(--ink); }
.hub__loading { margin: 0; color: var(--ink-muted); }
.hub__eyebrow { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: var(--space-2); }
</style>
