<script setup>
import { useRoute } from 'vue-router'
import LhAppLayout from './components/ui/LhAppLayout.vue'

const route = useRoute()

// Platzhalter, bis ein User-Store Level, Rang und Prestige von der API liefert.
const player = { level: 1, rank: 'bronze', prestige: 0 }
</script>

<template>
  <div class="lh-root">
    <RouterView v-if="route.meta.layout === false" />
    <LhAppLayout v-else v-bind="player" :focus="!!route.meta.focus">
      <RouterView />
    </LhAppLayout>
  </div>
</template>
