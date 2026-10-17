<script setup>
// Eine Wahl aus 2 bis 4 kurzen Optionen, die sofort gilt (z. B. Darstellung). Echte Radio-Buttons:
// Pfeiltasten wechseln, der Screenreader sagt die Gruppe und „1 von 3“.
import { useId } from 'vue'

const model = defineModel({ type: String })
defineProps({
  legend: { type: String, required: true },
  // [{ value, label }], Label höchstens zwei Wörter
  options: { type: Array, required: true },
  hint: String,
})

const name = useId()
const hintId = useId()
</script>

<template>
  <fieldset class="lh-segmented" :aria-describedby="hint ? hintId : undefined">
    <legend class="lh-segmented__legend">{{ legend }}</legend>
    <div class="lh-segmented__options">
      <label v-for="option in options" :key="option.value" class="lh-segmented__option">
        <input v-model="model" class="lh-segmented__input" type="radio" :name="name" :value="option.value" />
        {{ option.label }}
      </label>
    </div>
    <p v-if="hint" :id="hintId" class="lh-segmented__hint">{{ hint }}</p>
  </fieldset>
</template>
