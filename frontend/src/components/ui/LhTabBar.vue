<script setup>
// Hauptnavigation, Reihenfolge fix: Lernen · Team · Hub · Liga · Profil. Der Hub zeigt das eigene Level-Badge.
// Aktiver Tab: aria-current="page" und gefülltes Icon. Ab 768px Rail links mit Hub oben (macht bundle.css).
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import LhIcon from './LhIcon.vue'
import LhLevelBadge from './LhLevelBadge.vue'

const props = defineProps({
  level: { type: Number, required: true },
  rank: { type: String, default: 'bronze' },
  prestige: { type: Number, default: 0 },
})

const TABS = [
  { to: '/learn', label: 'Lernen', icon: 'ui-learn' },
  { to: '/team', label: 'Team', icon: 'ui-team' },
  { to: '/', hub: true },
  { to: '/league', label: 'Liga', icon: 'ui-league' },
  { to: '/profile', label: 'Profil', icon: 'ui-profile' },
]

const hubLabel = computed(() =>
  ['Hub', `Level ${props.level}`, props.prestige > 0 && `Prestige ${props.prestige}`].filter(Boolean).join(', '),
)
</script>

<template>
  <nav class="lh-tabbar" aria-label="Hauptnavigation">
    <RouterLink v-for="tab in TABS" :key="tab.to" v-slot="{ href, navigate, isActive }" :to="tab.to" custom>
      <a
        :href="href"
        class="lh-tab"
        :class="{ 'lh-tab--hub': tab.hub }"
        :aria-current="isActive ? 'page' : undefined"
        :aria-label="tab.hub ? hubLabel : undefined"
        @click="navigate"
      >
        <LhLevelBadge v-if="tab.hub" :level="level" :rank="rank" :prestige="prestige" aria-hidden="true" />
        <template v-else>
          <LhIcon :name="isActive ? `${tab.icon}-active` : tab.icon" />{{ tab.label }}
        </template>
      </a>
    </RouterLink>
  </nav>
</template>
