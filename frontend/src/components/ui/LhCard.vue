<script setup>
// Karte. Ohne to/href/button: reiner Inhalt (Slot). Mit to/href: als Ganzes antippbarer Link, mit Icon-Slot, Titel,
// Text und Pfeil. Mit button: gleich aufgebaut, aber ein Button, der etwas auslöst (@click), statt zu einer Seite zu führen.
// featured: 2px-Rahmen für das eine wichtigste Element am Screen.
import { computed } from 'vue'
import { linkProps, useLink } from './link.js'
import LhIcon from './LhIcon.vue'

const props = defineProps({
  title: String,
  text: String,
  eyebrow: String,
  featured: Boolean,
  button: Boolean,
  ...linkProps,
})

const link = useLink(props)
const tappable = computed(() => link.value ?? (props.button ? { tag: 'button', attrs: { type: 'button' } } : null))
</script>

<template>
  <component
    :is="tappable.tag"
    v-if="tappable"
    v-bind="tappable.attrs"
    class="lh-card lh-card--link"
    :class="{ 'lh-card--featured': featured }"
  >
    <slot name="icon" />
    <span class="lh-card__body">
      <span v-if="eyebrow" class="lh-card__eyebrow">{{ eyebrow }}</span>
      <span class="lh-card__title">{{ title }}</span>
      <span v-if="text" class="lh-card__text">{{ text }}</span>
    </span>
    <LhIcon name="ui-next" />
  </component>
  <div v-else class="lh-card" :class="{ 'lh-card--featured': featured }">
    <div v-if="eyebrow" class="lh-card__eyebrow">{{ eyebrow }}</div>
    <slot />
  </div>
</template>
