<script setup>
// Spielwert kompakt: illustriertes Icon plus Zahl in Space Mono (Brainpower, Trophäen, Streak, EP).
// Ab 10.000 wird abgekürzt (12,4k). Der Screenreader bekommt immer die volle Zahl.
// Mit to/href antippbar (nur wenn es dazu eine Seite gibt, z. B. Brainpower → Shop), dann mit 48px-Tap-Fläche.
// boost („2×“): statische Marke, solange ein EP-Booster läuft (E-037), auch im aria-label.
import { computed } from 'vue'
import { linkProps, useLink } from './link.js'
import LhArt from './LhArt.vue'

const props = defineProps({
  icon: { type: String, required: true },
  value: { type: Number, required: true },
  label: { type: String, required: true },
  large: Boolean,
  boost: String,
  ...linkProps,
})

const link = useLink(props)
const el = computed(() => link.value ?? { tag: 'span', attrs: { role: 'img' } })
const short = new Intl.NumberFormat('de', { maximumFractionDigits: 1 })
const display = computed(() => (props.value < 10000 ? String(props.value) : `${short.format(props.value / 1000)}k`))
</script>

<template>
  <component
    :is="el.tag"
    v-bind="el.attrs"
    class="lh-chip"
    :class="{ 'lh-chip--lg': large, 'lh-chip--link': link }"
    :aria-label="`${value} ${label}${boost ? `, ${boost} EP läuft` : ''}`"
  >
    <LhArt :name="icon" :size="large ? 32 : 24" />{{ display }}<span v-if="boost" class="lh-chip__boost" aria-hidden="true">{{ boost }}</span>
  </component>
</template>
