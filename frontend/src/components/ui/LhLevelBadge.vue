<script setup>
// Level-Sechseck in der Rangfarbe, ab Prestige 1 mit Patina-Ring und Prestige-Zahl. Werte liefert die API.
import { computed } from 'vue'

const RANKS = { bronze: 'Bronze', silver: 'Silber', gold: 'Gold', platin: 'Platin' }

const props = defineProps({
  level: { type: Number, required: true },
  rank: { type: String, default: 'bronze', validator: (v) => ['bronze', 'silver', 'gold', 'platin'].includes(v) },
  prestige: { type: Number, default: 0 },
  large: Boolean,
})

const label = computed(() =>
  [`Level ${props.level}`, RANKS[props.rank], props.prestige > 0 && `Prestige ${props.prestige}`].filter(Boolean).join(', '),
)
</script>

<template>
  <span
    class="lh-badge"
    :class="{ 'lh-badge--prestige': prestige > 0, 'lh-badge--lg': large }"
    :data-rank="rank"
    role="img"
    :aria-label="label"
  >
    <span class="lh-badge__hex" aria-hidden="true">{{ level }}</span>
    <span v-if="prestige > 0" class="lh-badge__prestige" aria-hidden="true">{{ prestige }}</span>
  </span>
</template>
