<script setup>
// Button: primary (die eine Hauptaktion pro Screen), secondary, ghost. Mit to/href wird er zum Link.
import { computed } from 'vue'
import { linkProps, useLink } from './link.js'

const props = defineProps({
  variant: { type: String, default: 'primary', validator: (v) => ['primary', 'secondary', 'ghost'].includes(v) },
  block: Boolean,
  disabled: Boolean,
  type: { type: String, default: 'button' },
  ...linkProps,
})

const link = useLink(props)
// Deaktiviert immer als <button disabled>: Links lassen sich nicht sauber deaktivieren.
const el = computed(() =>
  link.value && !props.disabled ? link.value : { tag: 'button', attrs: { type: props.type, disabled: props.disabled } },
)
</script>

<template>
  <component
    :is="el.tag"
    v-bind="el.attrs"
    class="lh-btn"
    :class="[`lh-btn--${variant}`, { 'lh-btn--block': block }]"
  >
    <slot />
  </component>
</template>
