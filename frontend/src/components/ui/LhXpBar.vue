<script setup>
// Fortschrittsleiste in der Rangfarbe (accent): EP bis zum nächsten Level, auch Fach-Fortschritt.
// Text steht in den Slots #top und #bottom (je ein oder zwei <span>), nie auf der Leiste.
import { computed } from 'vue'

const props = defineProps({
  value: { type: Number, required: true },
  max: { type: Number, required: true },
  label: { type: String, default: 'Erfahrungspunkte' },
})

const percent = computed(() => (props.max > 0 ? Math.min(100, Math.max(0, (props.value / props.max) * 100)) : 0))
</script>

<template>
  <div
    class="lh-xp"
    role="progressbar"
    :aria-label="label"
    aria-valuemin="0"
    :aria-valuemax="max"
    :aria-valuenow="value"
  >
    <div v-if="$slots.top" class="lh-xp__meta"><slot name="top" /></div>
    <div class="lh-xp__track"><div class="lh-xp__fill" :style="{ '--value': `${percent}%` }" /></div>
    <div v-if="$slots.bottom" class="lh-xp__meta"><slot name="bottom" /></div>
  </div>
</template>
