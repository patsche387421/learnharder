<script setup>
// Neue Erfolge im Ergebnis einer Lerneinheit oder Herausforderung (Design `Achievement`): je eine Karte mit Icon, Titel
// und den Trophäen. Ohne eigene Animation. achievements kommt so aus POST attempts/{id}/finish.
import LhArt from './ui/LhArt.vue'
import LhCurrencyChip from './ui/LhCurrencyChip.vue'

defineProps({
  achievements: { type: Array, required: true },
})
</script>

<template>
  <ul class="new-achievements" aria-label="Neue Erfolge">
    <li v-for="achievement in achievements" :key="achievement.slug" class="lh-card lh-card--row">
      <LhArt :name="`icon-${achievement.icon_name}`" :size="40" />
      <span class="lh-card__body">
        <span class="lh-card__eyebrow">Neuer Erfolg</span>
        <span class="lh-card__title">{{ achievement.title }}</span>
      </span>
      <LhCurrencyChip v-if="achievement.trophies > 0" icon="icon-trophy" :value="achievement.trophies" :label="achievement.trophies === 1 ? 'Trophäe' : 'Trophäen'" />
    </li>
  </ul>
</template>

<style scoped>
.new-achievements { display: grid; gap: var(--space-2); width: 100%; margin: 0; padding: 0; list-style: none; text-align: left; }
</style>
