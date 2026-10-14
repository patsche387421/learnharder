<script setup>
// Leerer Zustand statt „–“ oder „Keine Daten“: was fehlt und was als Nächstes kommt. Höchstens ein Button im Slot.
import LhArt from './LhArt.vue'

defineProps({
  title: { type: String, required: true },
  text: String,
  art: String,
})
</script>

<template>
  <div class="lh-empty">
    <LhArt v-if="art" :name="art" :size="56" />
    <p class="lh-empty__title">{{ title }}</p>
    <p v-if="text" class="lh-empty__text">{{ text }}</p>
    <slot />
  </div>
</template>
