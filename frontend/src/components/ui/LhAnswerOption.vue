<script setup>
// Antwort im Quiz. selected = angetippt (vor dem Prüfen). result = Ergebnis nach dem Prüfen, dann mit Wort und Icon,
// nie nur über die Farbe. Nach dem Prüfen setzt die Elternseite alle Optionen auf disabled.
import LhIcon from './LhIcon.vue'

defineProps({
  letter: { type: String, required: true },
  selected: Boolean,
  result: { type: String, default: null, validator: (v) => ['correct', 'wrong'].includes(v) },
  disabled: Boolean,
})
</script>

<template>
  <button
    type="button"
    class="lh-answer"
    :class="result && `lh-answer--${result}`"
    :aria-pressed="!result && selected ? 'true' : 'false'"
    :disabled="disabled"
  >
    <span class="lh-answer__key">{{ letter }}</span>
    <span class="lh-answer__text"><slot /></span>
    <template v-if="result">
      <span class="lh-answer__result">{{ result === 'correct' ? 'Richtig' : 'Falsch' }}</span>
      <LhIcon :name="result === 'correct' ? 'ui-correct' : 'ui-wrong'" />
    </template>
  </button>
</template>
