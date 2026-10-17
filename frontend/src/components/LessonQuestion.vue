<script setup>
// Eine Frage der Lerneinheit (LearnFlow, Momente 4 und 5): Antworten wählen → Prüfen → Rückmeldung mit Begründung → Weiter.
// Für Lerneinheit und Herausforderung. Zwei Wurzeln: der Körper des Screens und darunter Fuß (Prüfen) oder Feedback-Sheet.
// Die Auswahl hält die Komponente selbst, für die nächste Frage neu mit :key. Richtig/falsch, Lösung und EP kommen vom
// Server über feedback. Was neben „Richtig!“ steht (xp, boost) und der Hinweis bei falscher Antwort (hint) gibt die Seite vor.
import { computed, ref } from 'vue'
import LhAnswerOption from './ui/LhAnswerOption.vue'
import LhButton from './ui/LhButton.vue'
import LhIcon from './ui/LhIcon.vue'

const LETTERS = 'ABCDEF'

const props = defineProps({
  question: { type: Object, required: true },
  feedback: { type: Object, default: null },
  busy: Boolean,
  error: { type: String, default: '' },
  xp: { type: String, default: '' },
  boost: { type: String, default: '' },
  hint: { type: String, default: '' },
  nextLabel: { type: String, default: 'Weiter' },
})

const emit = defineEmits(['check', 'next'])

const selected = ref([])
const isMultiple = computed(() => props.question.type === 'multiple_choice')

function toggle(optionId) {
  if (props.feedback) return
  if (!isMultiple.value) {
    selected.value = [optionId]
  } else {
    selected.value = selected.value.includes(optionId)
      ? selected.value.filter((id) => id !== optionId)
      : [...selected.value, optionId]
  }
}

function optionResult(optionId) {
  if (!props.feedback) return null
  if (props.feedback.correct_option_ids.includes(optionId)) return 'correct'
  return selected.value.includes(optionId) ? 'wrong' : null
}

const correctLetters = computed(() =>
  props.question.options
    .map((option, i) => (props.feedback?.correct_option_ids.includes(option.id) ? LETTERS[i] : null))
    .filter(Boolean)
    .join(' und '),
)
</script>

<template>
  <div class="lh-screen__body">
    <slot name="before" />
    <p class="question lesson__question lesson__pre">{{ question.text }}</p>
    <p v-if="isMultiple" class="lesson__muted">Mehrere richtig</p>
    <div class="lesson__options">
      <LhAnswerOption
        v-for="(option, i) in question.options"
        :key="option.id"
        :letter="LETTERS[i]"
        :selected="selected.includes(option.id)"
        :result="optionResult(option.id)"
        :disabled="!!feedback || busy"
        @click="toggle(option.id)"
      ><span class="lesson__pre">{{ option.text }}</span></LhAnswerOption>
    </div>
  </div>

  <div v-if="feedback" class="lh-feedback" :class="feedback.correct ? 'lh-feedback--correct' : 'lh-feedback--wrong'" aria-live="polite">
    <p class="lh-feedback__title">
      <LhIcon :name="feedback.correct ? 'ui-correct' : 'ui-wrong'" />
      <template v-if="feedback.correct">Richtig!<span v-if="xp" class="lesson__xp">{{ xp }}</span><span v-if="boost" class="lh-tag lh-tag--boost lesson__boost">{{ boost }}</span></template>
      <template v-else>Nicht ganz. Richtig ist {{ correctLetters }}.</template>
    </p>
    <p v-if="feedback.explanation" class="lh-feedback__text lesson__pre">{{ feedback.explanation }}</p>
    <p v-if="!feedback.correct && hint" class="lh-feedback__text lesson__muted">{{ hint }}</p>
    <p v-if="error" class="lesson__error" role="alert">{{ error }}</p>
    <LhButton block :disabled="busy" @click="emit('next')">{{ nextLabel }}</LhButton>
  </div>

  <div v-else class="lh-screen__foot">
    <p v-if="error" class="lesson__error" role="alert">{{ error }}</p>
    <LhButton block :disabled="!selected.length || busy" @click="emit('check', selected)">{{ error ? 'Nochmal versuchen' : 'Prüfen' }}</LhButton>
  </div>
</template>

<style scoped>
.lesson__question { margin: 0; font: 400 18px/27px var(--font-text); color: var(--ink); }
.lesson__options { display: grid; gap: var(--space-3); }
/* Fragen mit Code kommen mehrzeilig aus dem CMS, ohne Markdown: Zeilenumbrüche behalten */
.lesson__pre { white-space: pre-line; }
.lesson__muted { margin: 0; font: 400 14px/20px var(--font-text); color: var(--ink-muted); }
.lesson__xp { margin-left: auto; font: 700 16px/24px var(--font-mono); }
.lesson__boost { margin-left: var(--space-2); }
.lesson__error { margin: 0 0 var(--space-3); font: 400 14px/20px var(--font-text); color: var(--wrong); }
</style>
