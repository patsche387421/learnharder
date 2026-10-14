<script setup>
// Einfarbiges UI-Icon (ui-*), 24px, übernimmt die Textfarbe. Ohne label rein dekorativ.
import { computed } from 'vue'
import { iconUrl } from './icons.js'

const props = defineProps({
  name: { type: String, required: true },
  label: String,
})

const style = computed(() => ({ '--icon': `url("${iconUrl(props.name)}")` }))
</script>

<template>
  <span
    class="lh-icon"
    :style="style"
    :role="label ? 'img' : undefined"
    :aria-label="label"
    :aria-hidden="label ? undefined : 'true'"
  />
</template>
