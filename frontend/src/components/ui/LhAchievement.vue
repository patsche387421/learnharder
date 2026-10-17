<script setup>
// Kachel im Raster der Erfolge (Design `Achievement`): erreicht (farbig, Datum), noch nicht (grau, Bedingung bleibt lesbar)
// oder geheim („?“, kein Text). Nur Anzeige, nicht antippbar. Stufe als Zahl an der Ecke, nur über 1.
// icon: Spiel-Icon ohne Präfix (xp, streak …). Mit subject zeigt die Kachel das Fach-Icon (Fach-Level in einem Fach).
import { computed } from 'vue'
import LhArt from './LhArt.vue'

const props = defineProps({
  title: String,
  description: String,
  icon: { type: String, default: 'achievement' },
  subject: String,
  level: Number,
  secret: Boolean,
  unlockedAt: String,
})

const unlocked = computed(() => !!props.unlockedAt)
const hidden = computed(() => props.secret && !unlocked.value)
const art = computed(() => (props.subject ? `subject-${props.subject}` : `icon-${props.icon}`))

// „12. Okt“ auf der Kachel, „12. Oktober“ für den Screenreader (Sprache.md: Datum ohne Jahr)
const date = (month) => new Intl.DateTimeFormat('de', { day: 'numeric', month, timeZone: 'Europe/Vienna' })
const shortDate = computed(() => unlocked.value && date('short').format(new Date(props.unlockedAt)).replace(/\.$/, ''))
const longDate = computed(() => unlocked.value && date('long').format(new Date(props.unlockedAt)))

const label = computed(() => {
  if (hidden.value) return 'Geheimer Erfolg'
  return unlocked.value ? `${props.title}, erreicht am ${longDate.value}` : `${props.title}, noch nicht erreicht: ${props.description}`
})
</script>

<template>
  <li
    class="lh-achievement"
    :class="{ 'lh-achievement--locked': !unlocked && !hidden, 'lh-achievement--secret': hidden }"
    :aria-label="label"
  >
    <span class="lh-achievement__art" aria-hidden="true">
      <span v-if="hidden" class="lh-achievement__secret">?</span>
      <template v-else>
        <LhArt :name="art" />
        <span v-if="level > 1" class="lh-achievement__level">{{ level }}</span>
      </template>
    </span>
    <span class="lh-achievement__title" aria-hidden="true">{{ hidden ? 'Geheimer Erfolg' : title }}</span>
    <span v-if="unlocked" class="lh-achievement__date" aria-hidden="true">{{ shortDate }}</span>
    <span v-else-if="!hidden" class="lh-achievement__text" aria-hidden="true">{{ description }}</span>
  </li>
</template>
