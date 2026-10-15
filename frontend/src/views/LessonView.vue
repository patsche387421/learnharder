<script setup>
// Lerneinheit (LearnFlow, Momente 4–7): Frage → Prüfen → Feedback → … → Ergebnis. Fokus-Modus ohne Tab-Leiste.
// Falsch beantwortete Fragen kommen am Ende einmal wieder. Richtig/falsch und EP liefert nur der Server.
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import LoadError from '../components/LoadError.vue'
import LhAnswerOption from '../components/ui/LhAnswerOption.vue'
import LhButton from '../components/ui/LhButton.vue'
import LhEmptyState from '../components/ui/LhEmptyState.vue'
import LhIcon from '../components/ui/LhIcon.vue'
import LhLevelBadge from '../components/ui/LhLevelBadge.vue'
import LhXpBar from '../components/ui/LhXpBar.vue'
import { answerQuestion, finishLesson, getSubject, getTopic, startLesson } from '../api/learn.js'
import { useAuthStore } from '../stores/auth.js'

const LETTERS = 'ABCDEF'

const route = useRoute()
const auth = useAuthStore()
const base = `/learn/${route.params.subject}/${route.params.topic}`
const subjectPath = `/learn/${route.params.subject}`

const phase = ref('loading') // loading | question | feedback | result
const loadError = ref(null)
const locked = ref(false) // Level reicht noch nicht: kein Fehler, sondern ein Grund
const actionError = ref('')
const busy = ref(false)

const topic = ref(null)
const attemptId = ref(null)
const queue = ref([]) // { question, repeat }
const index = ref(0)
const selected = ref([])
const feedback = ref(null)
const result = ref(null)
const nextTopic = ref(null)

const current = computed(() => queue.value[index.value])
const isMultiple = computed(() => current.value?.question.type === 'multiple_choice')
const isLast = computed(() => index.value === queue.value.length - 1)
const repeated = computed(() => queue.value.filter((item) => item.repeat).length)

async function start() {
  phase.value = 'loading'
  loadError.value = null
  try {
    topic.value = await getTopic(route.params.subject, route.params.topic)
    if (topic.value.locked) {
      locked.value = true
      return
    }
    const lesson = await startLesson(topic.value.id)
    attemptId.value = lesson.id
    queue.value = lesson.questions.map((question) => ({ question, repeat: false }))
    phase.value = 'question'
  } catch (e) {
    // 403 beim Start: Der Server sagt, das Level reicht nicht (etwa direkt über die Adresse geöffnet)
    if (e.status === 403 && topic.value) locked.value = true
    else loadError.value = e
  }
}
onMounted(start)

const lockedText = computed(() => {
  const level = `Fragen gibt es ab Level ${topic.value.level_required}.`
  return topic.value.theory.length ? `${level} Die Theorie kannst du schon lesen.` : level
})

function toggle(optionId) {
  if (phase.value !== 'question') return
  if (!isMultiple.value) {
    selected.value = [optionId]
  } else {
    selected.value = selected.value.includes(optionId)
      ? selected.value.filter((id) => id !== optionId)
      : [...selected.value, optionId]
  }
}

async function run(action) {
  busy.value = true
  actionError.value = ''
  try {
    await action()
  } catch (e) {
    actionError.value = e.message
  } finally {
    busy.value = false
  }
}

function check() {
  return run(async () => {
    feedback.value = await answerQuestion(attemptId.value, current.value.question.id, selected.value)
    // Erste Runde falsch: kommt am Ende noch einmal (nur einmal, keine Schleife)
    if (!feedback.value.correct && !current.value.repeat) {
      queue.value.push({ question: current.value.question, repeat: true })
    }
    phase.value = 'feedback'
  })
}

function next() {
  if (isLast.value) return finish()
  index.value++
  selected.value = []
  feedback.value = null
  phase.value = 'question'
}

function finish() {
  return run(async () => {
    result.value = await finishLesson(attemptId.value)
    auth.applyProgress(result.value.progress)
    phase.value = 'result'
    // Nächstes Thema im Fach für den Primär-Button. Klappt das nicht, bleibt „Zum Fach“.
    const subject = await getSubject(route.params.subject).catch(() => null)
    const topics = subject?.topics ?? []
    const at = topics.findIndex((t) => t.slug === route.params.topic)
    nextTopic.value = topics.slice(at + 1).find((t) => !t.locked) ?? null
  })
}

function optionResult(optionId) {
  if (phase.value !== 'feedback') return null
  if (feedback.value.correct_option_ids.includes(optionId)) return 'correct'
  return selected.value.includes(optionId) ? 'wrong' : null
}

const correctLetters = computed(() =>
  current.value?.question.options
    .map((option, i) => (feedback.value?.correct_option_ids.includes(option.id) ? LETTERS[i] : null))
    .filter(Boolean)
    .join(' und '),
)

const feedbackXp = computed(() => {
  if (!feedback.value?.correct || current.value?.repeat) return ''
  return feedback.value.xp > 0 ? `+${feedback.value.xp} EP` : 'Heute schon gesammelt'
})

const levelBar = computed(() => {
  const xp = result.value?.progress.xp
  return xp ? { value: xp.total - xp.level_start, max: xp.next_level - xp.level_start, left: xp.next_level - xp.total } : null
})
</script>

<template>
  <div class="lh-screen lesson">
    <div class="lh-lessonbar">
      <RouterLink class="lh-iconbtn" :to="base" aria-label="Schließen"><LhIcon name="ui-close" /></RouterLink>
      <div v-if="queue.length && phase !== 'result'" class="lh-steps" role="progressbar" :aria-valuenow="index" aria-valuemin="0" :aria-valuemax="queue.length" aria-label="Fortschritt in der Lerneinheit">
        <i v-for="(item, i) in queue" :key="i" :class="{ 'is-done': i < index, 'is-now': i === index }" />
      </div>
    </div>

    <div class="lh-screen__body">
      <LhEmptyState v-if="locked" title="Noch gesperrt." :text="lockedText">
        <LhButton v-if="topic.theory.length" variant="secondary" :to="`${base}/theory`">Theorie lesen</LhButton>
        <LhButton v-else variant="secondary" :to="base">Zum Thema</LhButton>
      </LhEmptyState>
      <p v-else-if="phase === 'loading' && !loadError" class="lesson__muted" aria-live="polite">Lädt …</p>
      <LoadError v-else-if="loadError" :error="loadError" @retry="start" />

      <template v-else-if="phase === 'question' || phase === 'feedback'">
        <p v-if="current.repeat" class="lesson__muted">Noch einmal</p>
        <p class="question lesson__question lesson__pre">{{ current.question.text }}</p>
        <p v-if="isMultiple" class="lesson__muted">Mehrere richtig</p>
        <div class="lesson__options">
          <LhAnswerOption
            v-for="(option, i) in current.question.options"
            :key="option.id"
            :letter="LETTERS[i]"
            :selected="selected.includes(option.id)"
            :result="optionResult(option.id)"
            :disabled="phase === 'feedback' || busy"
            @click="toggle(option.id)"
          ><span class="lesson__pre">{{ option.text }}</span></LhAnswerOption>
        </div>
      </template>

      <div v-else-if="phase === 'result'" class="lh-result">
        <template v-if="result.level_up">
          <LhLevelBadge class="lh-levelup" large :level="result.progress.level" :rank="result.progress.rank" :prestige="result.progress.prestige" />
          <p class="lesson__levelup">Level {{ result.progress.level }}!</p>
        </template>
        <p class="lesson__done">{{ topic.title }} geschafft · {{ result.correct_count }} von {{ result.total_questions }} richtig</p>
        <p class="lh-result__gain">+{{ result.xp_earned }} EP</p>
        <div class="lh-stats">
          <div class="lh-stat"><b>{{ result.correct_count }}/{{ result.total_questions }}</b><span>richtig</span></div>
          <div class="lh-stat"><b>{{ result.xp_earned }}</b><span>EP</span></div>
          <div class="lh-stat"><b>{{ repeated }}</b><span>Fehler geübt</span></div>
        </div>
        <LhXpBar v-if="levelBar" class="lesson__bar" :value="levelBar.value" :max="levelBar.max">
          <template #bottom>
            <span><b>Level {{ result.progress.level }}</b></span>
            <span>{{ levelBar.left }} EP bis Level {{ result.progress.level + 1 }}</span>
          </template>
        </LhXpBar>
        <p v-if="result.xp_earned === 0" class="lesson__muted">Die EP für diese Fragen hast du heute schon gesammelt. Morgen gibt es wieder welche.</p>
      </div>
    </div>

    <div v-if="phase === 'feedback'" class="lh-feedback" :class="feedback.correct ? 'lh-feedback--correct' : 'lh-feedback--wrong'" aria-live="polite">
      <p class="lh-feedback__title">
        <LhIcon :name="feedback.correct ? 'ui-correct' : 'ui-wrong'" />
        <template v-if="feedback.correct">Richtig!<span v-if="feedbackXp" class="lesson__xp">{{ feedbackXp }}</span></template>
        <template v-else>Nicht ganz. Richtig ist {{ correctLetters }}.</template>
      </p>
      <p v-if="feedback.explanation" class="lh-feedback__text lesson__pre">{{ feedback.explanation }}</p>
      <p v-if="!feedback.correct && !current.repeat" class="lh-feedback__text lesson__muted">Kommt am Ende noch einmal.</p>
      <p v-if="actionError" class="lesson__error" role="alert">{{ actionError }}</p>
      <LhButton block :disabled="busy" @click="next">{{ isLast ? 'Ergebnis ansehen' : 'Weiter' }}</LhButton>
    </div>

    <div v-else-if="phase === 'question'" class="lh-screen__foot">
      <p v-if="actionError" class="lesson__error" role="alert">{{ actionError }}</p>
      <LhButton block :disabled="!selected.length || busy" @click="check">{{ actionError ? 'Nochmal versuchen' : 'Prüfen' }}</LhButton>
    </div>

    <div v-else-if="phase === 'result'" class="lh-screen__foot lesson__actions">
      <LhButton v-if="nextTopic" block :to="`${subjectPath}/${nextTopic.slug}`">Nächstes Thema</LhButton>
      <LhButton block :variant="nextTopic ? 'secondary' : 'primary'" :to="subjectPath">Zum Fach</LhButton>
    </div>
  </div>
</template>

<style scoped>
/* Buttons unten in Daumenreichweite: Bildschirmhöhe abzüglich Innenabstand des App-Rahmens */
.lesson { min-height: calc(100dvh - 2 * var(--space-4)); }
.lesson__question { margin: 0; font: 400 18px/27px var(--font-text); color: var(--ink); }
.lesson__options { display: grid; gap: var(--space-3); }
/* Fragen mit Code kommen mehrzeilig aus dem CMS, ohne Markdown: Zeilenumbrüche behalten */
.lesson__pre { white-space: pre-line; }
.lesson__muted { margin: 0; font: 400 14px/20px var(--font-text); color: var(--ink-muted); }
.lesson__xp { margin-left: auto; font: 700 16px/24px var(--font-mono); }
.lesson__error { margin: 0 0 var(--space-3); font: 400 14px/20px var(--font-text); color: var(--wrong); }
.lesson__done { margin: 0; font: 700 18px/24px var(--font-text); color: var(--ink); }
.lesson__levelup { margin: 0; font: 800 32px/38px var(--font-text); color: var(--accent); }
.lesson__bar { width: 100%; }
.lesson__actions { display: grid; gap: var(--space-3); }
</style>
