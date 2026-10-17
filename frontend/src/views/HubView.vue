<script setup>
// Hub (Startseite, Konzept Spielwelt §4): Begrüßung nach Tageszeit, Level und Brainpower aus /me, „Weiterlernen“ aus /hub.
// Platz für später: Avatar neben der Begrüßung (G4), Spielhalle und die anderen Spiel-Elemente im Raster „Bald“.
import { computed, onMounted } from 'vue'
import LoadError from '../components/LoadError.vue'
import LhArt from '../components/ui/LhArt.vue'
import LhCard from '../components/ui/LhCard.vue'
import LhCurrencyChip from '../components/ui/LhCurrencyChip.vue'
import LhEmptyState from '../components/ui/LhEmptyState.vue'
import LhLevelBadge from '../components/ui/LhLevelBadge.vue'
import LhXpBar from '../components/ui/LhXpBar.vue'
import { getHub } from '../api/hub.js'
import { useLoad } from '../composables/useLoad.js'
import { useAuthStore } from '../stores/auth.js'

const RANKS = { bronze: 'Bronze', silver: 'Silber', gold: 'Gold', platin: 'Platin' }

// Kommt nach und nach mit G2–G6. Icons vorläufig, bis es eigene für Spielhalle und Shop gibt.
const SOON = [
  { icon: 'icon-streak', name: 'Streak' },
  { icon: 'icon-challenge', name: 'Herausforderung' },
  { icon: 'icon-campaign', name: 'Spielhalle' },
  { icon: 'icon-league', name: 'Liga-Platz' },
  { icon: 'icon-trophy', name: 'Shop' },
  { icon: 'icon-achievement', name: 'Erfolge' },
]

const auth = useAuthStore()
const { data: hub, error, loading, load } = useLoad(getHub)

onMounted(() => {
  if (!auth.waitingForClass) load()
})

const greeting = computed(() => {
  const tag = auth.me?.gamer_tag
  const hour = new Date().getHours()
  if (hour >= 5 && hour < 11) return `Guten Morgen, ${tag}!`
  if (hour >= 11 && hour < 18) return `Hey ${tag}!`
  if (hour >= 18 && hour < 22) return `Guten Abend, ${tag}!`
  return `Noch wach, ${tag}?`
})

const next = computed(() => hub.value?.next_topic ?? null)

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
        <p v-if="next" class="hub__sub">Weiter mit {{ next.title }}? 5 Min. reichen.</p>
      </div>
      <!-- Platzhalter für den Avatar (G4) -->
      <div class="hub__avatar" role="img" aria-label="Avatar, kommt bald"><span aria-hidden="true">Bald</span></div>
    </header>

    <section class="hub__progress lh-card" aria-label="Fortschritt">
      <div class="hub__level">
        <LhLevelBadge v-bind="auth.player" large />
        <span class="hub__levelname">Level {{ auth.player.level }} · {{ RANKS[auth.player.rank] }}</span>
        <LhCurrencyChip v-if="auth.me?.brainpower" icon="icon-brainpower" :value="auth.me.brainpower.current" label="Brainpower" />
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
      eyebrow="Weiterlernen"
      :title="next.title"
      :text="next.subject.name"
      :to="`/learn/${next.subject.code}/${next.slug}`"
    >
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

    <section class="hub__soon" aria-labelledby="hub-soon">
      <h2 id="hub-soon" class="hub__soontitle">Bald</h2>
      <ul class="hub__soonlist">
        <li v-for="item in SOON" :key="item.name" class="hub__soonitem">
          <LhArt :name="item.icon" :size="32" />
          <span>{{ item.name }}</span>
        </li>
      </ul>
    </section>
  </div>
</template>

<style scoped>
.hub { display: grid; gap: var(--space-4); }
.hub__head { display: flex; align-items: center; justify-content: space-between; gap: var(--space-3); }
.hub__hello { display: grid; gap: var(--space-1); }
.hub__title { margin: 0; font: 700 26px/32px var(--font-text); color: var(--ink); }
.hub__sub { margin: 0; color: var(--ink-muted); }
.hub__avatar {
  flex: none; display: grid; place-items: center; width: 72px; height: 72px; border-radius: var(--radius-full);
  border: 2px dashed var(--line-strong); font: 400 13px/16px var(--font-mono); color: var(--ink-muted);
}
.hub__progress { display: grid; gap: var(--space-3); }
.hub__level { display: flex; align-items: center; gap: var(--space-3); }
.hub__levelname { flex: 1; font: 700 16px/24px var(--font-text); color: var(--ink); }
.hub__loading { margin: 0; color: var(--ink-muted); }
.hub__soon { display: grid; gap: var(--space-2); }
.hub__soontitle { margin: 0; font: 400 13px/16px var(--font-mono); color: var(--ink-muted); }
.hub__soonlist { margin: 0; padding: 0; list-style: none; display: grid; grid-template-columns: repeat(2, 1fr); gap: var(--space-2); }
.hub__soonitem {
  display: flex; align-items: center; gap: var(--space-2); padding: var(--space-3); border-radius: var(--radius-md);
  box-shadow: inset 0 0 0 1px var(--line); font: 400 14px/20px var(--font-text); color: var(--ink-muted);
}
@media (min-width: 768px) {
  .hub__soonlist { grid-template-columns: repeat(3, 1fr); }
}
</style>
