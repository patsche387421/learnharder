<script setup>
// Fehler beim Laden einer Seite: 404 heißt „gibt es nicht (mehr)“, alles andere „nochmal versuchen“.
import LhButton from './ui/LhButton.vue'
import LhEmptyState from './ui/LhEmptyState.vue'

defineProps({
  error: { type: Object, required: true },
})
defineEmits(['retry'])
</script>

<template>
  <LhEmptyState v-if="error.status === 404" title="Gibt’s hier nicht (mehr)." text="Vielleicht wurde es gerade überarbeitet.">
    <LhButton variant="secondary" to="/learn">Zu den Fächern</LhButton>
  </LhEmptyState>
  <LhEmptyState v-else title="Das hat nicht geklappt." :text="error.message">
    <LhButton variant="secondary" @click="$emit('retry')">Nochmal versuchen</LhButton>
  </LhEmptyState>
</template>
