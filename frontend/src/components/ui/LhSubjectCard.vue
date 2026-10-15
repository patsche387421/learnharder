<script setup>
// Fach in der Liste unter „Lernen“: Fach-Icon, Kürzel, Name und Fortschritt. Gesperrt: kein Link, Satz mit Grund.
import { computed } from 'vue'
import { linkProps, useLink } from './link.js'
import LhArt from './LhArt.vue'
import LhIcon from './LhIcon.vue'
import LhXpBar from './LhXpBar.vue'

const props = defineProps({
  code: { type: String, required: true },
  name: { type: String, required: true },
  level: Number,
  done: { type: Number, default: 0 },
  total: { type: Number, default: 0 },
  locked: Boolean,
  lockedText: { type: String, default: 'Noch nicht freigeschaltet' },
  ...linkProps,
})

const link = useLink(props)
const el = computed(() => (link.value && !props.locked ? link.value : { tag: 'div', attrs: {} }))
const art = computed(() => `subject-${props.code.toLowerCase()}`)
</script>

<template>
  <component
    :is="el.tag"
    v-bind="el.attrs"
    class="lh-subject"
    :class="{ 'lh-subject--locked': locked }"
    :aria-disabled="locked ? 'true' : undefined"
  >
    <LhArt class="lh-subject__art" :name="art" />
    <span class="lh-subject__title">{{ code }}</span>
    <span class="lh-subject__name">{{ name }}</span>
    <span v-if="locked" class="lh-subject__lock"><LhIcon class="lh-icon--sm" name="ui-locked" />{{ lockedText }}</span>
    <LhXpBar v-else class="lh-subject__progress" :value="done" :max="total" :label="`Fortschritt ${code}`">
      <template #bottom>
        <span v-if="done > 0 && level"><b>Fach-Level {{ level }}</b></span>
        <span v-else-if="done === 0">Noch nicht begonnen</span>
        <span>{{ done }} / {{ total }} Themen</span>
      </template>
    </LhXpBar>
  </component>
</template>
