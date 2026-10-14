<script setup>
// Spielwert kompakt: illustriertes Icon plus Zahl in Space Mono (Brainpower, Trophäen, Streak, EP).
// Ab 10.000 wird abgekürzt (12,4k). Der Screenreader bekommt immer die volle Zahl.
import { computed } from 'vue'
import LhArt from './LhArt.vue'

const props = defineProps({
  icon: { type: String, required: true },
  value: { type: Number, required: true },
  label: { type: String, required: true },
  large: Boolean,
})

const short = new Intl.NumberFormat('de', { maximumFractionDigits: 1 })
const display = computed(() => (props.value < 10000 ? String(props.value) : `${short.format(props.value / 1000)}k`))
</script>

<template>
  <span class="lh-chip" :class="{ 'lh-chip--lg': large }" role="img" :aria-label="`${value} ${label}`">
    <LhArt :name="icon" :size="large ? 32 : 24" />{{ display }}
  </span>
</template>
