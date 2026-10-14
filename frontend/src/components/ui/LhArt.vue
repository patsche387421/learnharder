<script setup>
// Illustriertes Icon (subject-*, icon-*) als <img>. Ohne alt rein dekorativ.
import { computed } from 'vue'
import { iconUrl } from './icons.js'

const props = defineProps({
  name: { type: String, required: true },
  size: { type: Number, default: 48 },
  alt: { type: String, default: '' },
})

const src = computed(() => iconUrl(props.name))
</script>

<template>
  <img class="lh-art" :src="src" :width="size" :height="size" :alt="alt" />
</template>
