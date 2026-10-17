<script setup>
// Herausforderung (E-045, Design `Challenge`): Start mit Kosten und Regeln → 10 Fragen mit Fach → Ergebnis mit Bonus.
// Fokus-Modus ohne Tab-Leiste. Jede Frage kommt einmal, ohne Wiederholung am Ende. Kosten, Regeln und Stand kommen aus /hub,
// Fragen und Ergebnis vom Server. Eine offene Herausforderung lässt sich bis Mitternacht fortsetzen: Schließen führt zum Hub.
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import LessonQuestion from '../components/LessonQuestion.vue'
import LoadError from '../components/LoadError.vue'
import NewAchievements from '../components/NewAchievements.vue'
import LhArt from '../components/ui/LhArt.vue'
import LhButton from '../components/ui/LhButton.vue'
import LhEmptyState from '../components/ui/LhEmptyState.vue'
import LhIcon from '../components/ui/LhIcon.vue'
import LhLevelBadge from '../components/ui/LhLevelBadge.vue'
import { getCurrentChallenge, startChallenge } from '../api/challenge.js'
import { getHub } from '../api/hub.js'
import { answerQuestion, finishLesson } from '../api/learn.js'
import { useAuthStore } from '../stores/auth.js'

const auth = useAuthStore()

const phase = ref('loading') // loading | start | question | feedback | result
const loadError = ref(null)
const actionError = ref('')
const busy = ref(false)

const status = ref(null) // challenge aus /hub: Kosten, Regeln, heute übrig, offen, gesperrt
const attemptId = ref(null)
const questions = ref([])
const index = ref(0)
const feedback = ref(null)
const result = ref(null)

const current = computed(() => questions.value[index.value])
const isLast = computed(() => index.value === questions.value.length - 1)

async function load() {
  phase.value = 'loading'
  loadError.value = null
  try {
    status.value = (await getHub()).challenge
    if (!status.value.open) {
      phase.value = 'start'
      return
    }
    const open = await getCurrentChallenge()
    if (!open) {
      // Inzwischen abgelaufen (nach Mitternacht): neu starten
      status.value = { ...status.value, open: null }
      phase.value = 'start'
      return
    }
    begin(open)
    // Alle schon beantwortet, nur der Abschluss fehlte
    if (index.value >= questions.value.length) await finish()
  } catch (e) {
    loadError.value = e
  }
}
onMounted(load)

function begin(challenge) {
  attemptId.value = challenge.id
  questions.value = challenge.questions
  index.value = challenge.answered.length
  feedback.value = null
  phase.value = 'question'
}

const missing = computed(() => (status.value ? status.value.cost - (auth.me?.brainpower?.current ?? 0) : 0))
const startBlocked = computed(() => !status.value?.left_today || missing.value > 0)
const bonusText = computed(() => {
  const [low, high] = status.value?.bonus ?? []
  if (!low) return ''
  const top = high ?? low
  const trophy = status.value.perfect_trophies ? ' und eine Trophäe' : ''
  return high
    ? `Ab ${low.from} richtig gibt es +${low.xp} EP Bonus, bei allen ${top.from} +${top.xp} EP${trophy}.`
    : `Ab ${low.from} richtig gibt es +${low.xp} EP Bonus${trophy}.`
})

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

function start() {
  return run(async () => {
    const challenge = await startChallenge()
    auth.applyProgress({ brainpower: challenge.brainpower })
    begin(challenge)
  })
}

function check(optionIds) {
  return run(async () => {
    feedback.value = await answerQuestion(attemptId.value, current.value.id, optionIds)
    phase.value = 'feedback'
  })
}

function next() {
  if (isLast.value) return finish()
  index.value++
  feedback.value = null
  phase.value = 'question'
}

function finish() {
  return run(async () => {
    result.value = await finishLesson(attemptId.value)
    auth.applyProgress({ ...result.value.progress, streak: result.value.streak }, result.value.level_up)
    phase.value = 'result'
  })
}

const feedbackXp = computed(() => (feedback.value?.correct && feedback.value.xp > 0 ? `+${feedback.value.xp} EP` : ''))
</script>

<template>
  <div class="lh-screen challenge">
    <div class="lh-lessonbar">
      <RouterLink class="lh-iconbtn" to="/" aria-label="Schließen"><LhIcon name="ui-close" /></RouterLink>
      <div v-if="questions.length && (phase === 'question' || phase === 'feedback')" class="lh-steps" role="progressbar" :aria-valuenow="index" aria-valuemin="0" :aria-valuemax="questions.length" aria-label="Fortschritt in der Herausforderung">
        <i v-for="(question, i) in questions" :key="question.id" :class="{ 'is-done': i < index, 'is-now': i === index }" />
      </div>
    </div>

    <LessonQuestion
      v-if="phase === 'question' || phase === 'feedback'"
      :key="index"
      :question="current"
      :feedback="feedback"
      :busy="busy"
      :error="actionError"
      :xp="feedbackXp"
      :next-label="isLast ? 'Ergebnis ansehen' : 'Weiter'"
      @check="check"
      @next="next"
    >
      <template #before>
        <p class="lh-card__eyebrow challenge__eyebrow">
          Frage {{ index + 1 }} / {{ questions.length }}
          <span v-if="current.subject" class="lh-tag">{{ current.subject.slug.toUpperCase() }}</span>
        </p>
      </template>
    </LessonQuestion>

    <template v-else>
      <div class="lh-screen__body">
        <p v-if="phase === 'loading' && !loadError" class="challenge__muted" aria-live="polite">Lädt …</p>
        <LoadError v-else-if="loadError" :error="loadError" @retry="load" />

        <LhEmptyState
          v-else-if="phase === 'start' && status.locked"
          art="icon-challenge"
          title="Noch gesperrt."
          text="Schließe zuerst ein paar Themen ab. Die Herausforderung fragt nur, was du schon gelernt hast."
        >
          <LhButton variant="secondary" to="/learn">Zum Lernen</LhButton>
        </LhEmptyState>

        <template v-else-if="phase === 'start'">
          <div class="challenge__head">
            <LhArt name="icon-challenge" :size="64" />
            <h1 class="challenge__title">Herausforderung</h1>
          </div>
          <p class="challenge__text">{{ status.questions }} zufällige Fragen aus Themen, die du schon gelernt hast.</p>
          <div class="lh-stats">
            <div class="lh-stat"><b>{{ status.questions }}</b><span>Fragen</span></div>
            <div class="lh-stat"><b>{{ status.xp_per_correct }}</b><span>EP je Frage</span></div>
            <div class="lh-stat"><b>+{{ status.bonus.at(-1)?.xp ?? 0 }}</b><span>Bonus</span></div>
          </div>
          <div class="lh-note">
            <span class="lh-note__label">So funktioniert’s</span>
            <p class="challenge__note">Jede Frage kommt einmal. {{ bonusText }} Abbrechen geht, du kannst bis Mitternacht weitermachen.</p>
          </div>
        </template>

        <div v-else-if="phase === 'result'" class="lh-result">
          <template v-if="result.level_up">
            <LhLevelBadge class="lh-levelup" large :level="result.progress.level" :rank="result.progress.rank" :prestige="result.progress.prestige" />
            <p class="challenge__levelup">Level {{ result.progress.level }}!</p>
          </template>
          <LhArt v-else name="icon-challenge" :size="64" />
          <h1 class="challenge__title">{{ result.correct_count }} von {{ result.total_questions }} richtig</h1>
          <p class="lh-result__gain">+{{ result.xp_earned }} EP</p>
          <p v-if="result.xp_bonus > 0" class="challenge__muted">davon +{{ result.xp_bonus }} Bonus</p>
          <div class="lh-stats">
            <div class="lh-stat"><b>{{ result.correct_count }}/{{ result.total_questions }}</b><span>richtig</span></div>
            <div class="lh-stat"><b>{{ result.streak?.current ?? 0 }}</b><span>Tage Streak</span></div>
            <div class="lh-stat"><b>{{ result.trophies_earned > 0 ? `+${result.trophies_earned}` : 0 }}</b><span>Trophäen</span></div>
          </div>
          <NewAchievements v-if="result.achievements_unlocked?.length" :achievements="result.achievements_unlocked" />
        </div>
      </div>

      <div v-if="phase === 'start' && !status.locked" class="lh-screen__foot challenge__foot">
        <p v-if="actionError" class="challenge__error" role="alert">{{ actionError }}</p>
        <LhButton block :disabled="startBlocked || busy" @click="start">
          {{ status.left_today ? `Starten · ${status.cost}` : 'Morgen wieder' }}
        </LhButton>
        <p v-if="status.left_today && missing > 0" class="challenge__muted">Noch {{ missing }} Brainpower. Morgen lädt sie sich wieder auf.</p>
        <p v-else-if="status.left_today" class="challenge__muted">Noch {{ status.left_today }} heute · Zeit bis Mitternacht</p>
      </div>

      <div v-else-if="phase === 'result'" class="lh-screen__foot">
        <LhButton block to="/">Zum Hub</LhButton>
      </div>
    </template>
  </div>
</template>

<style scoped>
/* Buttons unten in Daumenreichweite, wie in der Lerneinheit */
.challenge { min-height: calc(100dvh - 2 * var(--space-4)); }
.challenge__head { display: flex; align-items: center; gap: var(--space-3); }
.challenge__title { margin: 0; font: 700 26px/32px var(--font-text); color: var(--ink); }
.challenge__text { margin: 0; color: var(--ink); }
.challenge__note { margin: 0; font: 400 14px/20px var(--font-text); }
.challenge__eyebrow { display: flex; justify-content: space-between; align-items: center; gap: var(--space-2); margin: 0; }
.challenge__muted { margin: 0; font: 400 14px/20px var(--font-text); color: var(--ink-muted); text-align: center; }
.challenge__foot { display: grid; gap: var(--space-2); }
.challenge__error { margin: 0; font: 400 14px/20px var(--font-text); color: var(--wrong); }
.challenge__levelup { margin: 0; font: 800 32px/38px var(--font-text); color: var(--accent); }
</style>
