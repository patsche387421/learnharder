<script setup>
// Karte. Ohne to/href: reiner Inhalt (Slot). Mit to/href: als Ganzes antippbar, mit Icon-Slot, Titel, Text und Pfeil.
// featured: 2px-Rahmen für das eine wichtigste Element am Screen.
import { linkProps, useLink } from './link.js'
import LhIcon from './LhIcon.vue'

const props = defineProps({
  title: String,
  text: String,
  eyebrow: String,
  featured: Boolean,
  ...linkProps,
})

const link = useLink(props)
</script>

<template>
  <component
    :is="link.tag"
    v-if="link"
    v-bind="link.attrs"
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
