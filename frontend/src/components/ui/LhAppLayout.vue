<script setup>
// Seitenrahmen jeder Seite: Inhalt als schmale, mittige Spalte plus Hauptnavigation.
// focus: Lerneinheit, Quiz und Theorie, ohne Navigation und höchstens lesson-max breit.
// rank setzt --accent auf die Rangfarbe, damit alle Fortschrittselemente darin mitziehen.
import { computed } from 'vue'
import LhTabBar from './LhTabBar.vue'

const props = defineProps({
  level: { type: Number, required: true },
  rank: { type: String, default: 'bronze', validator: (v) => ['bronze', 'silver', 'gold', 'platin'].includes(v) },
  prestige: { type: Number, default: 0 },
  focus: Boolean,
})

const style = computed(() => ({ '--accent': `var(--rank-${props.rank})` }))
</script>

<template>
  <div class="lh-app" :class="{ 'lh-app--focus': focus }" :style="style">
    <main class="lh-app__main" :class="{ 'lh-app__main--lesson': focus }">
      <slot />
    </main>
    <LhTabBar v-if="!focus" :level="level" :rank="rank" :prestige="prestige" />
  </div>
</template>
