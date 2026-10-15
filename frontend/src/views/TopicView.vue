<script setup>
// Thema starten (LearnFlow, Moment 3): was man lernt, wie viele Fragen, wie viele EP.
// Los geht’s ist der Hauptweg, Theorie der Nebenweg. Gesperrt oder ohne Fragen bleibt nur die Theorie.
import { computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import LoadError from '../components/LoadError.vue'
import LhButton from '../components/ui/LhButton.vue'
import LhIcon from '../components/ui/LhIcon.vue'
import { getTopic } from '../api/learn.js'
import { useLoad } from '../composables/useLoad.js'

const route = useRoute()
const { data: topic, error, loading, load } = useLoad(() => getTopic(route.params.subject, route.params.topic))
watch(() => [route.params.subject, route.params.topic], load, { immediate: true })

const base = computed(() => `/learn/${route.params.subject}/${route.params.topic}`)
const canStart = computed(() => topic.value && !topic.value.locked && topic.value.questions_count > 0)
</script>

<template>
  <div class="topic">
    <LhButton class="topic__back" variant="ghost" :to="`/learn/${route.params.subject}`"><LhIcon name="ui-back" />Alle Themen</LhButton>
    <p v-if="loading && !topic" class="topic__loading" aria-live="polite">Lädt …</p>
    <LoadError v-else-if="error" :error="error" @retry="load" />
    <template v-else-if="topic">
      <h1 class="topic__title">{{ topic.title }}</h1>
      <p v-if="topic.description" class="topic__text">{{ topic.description }}</p>
      <div v-if="topic.questions_count" class="lh-stats topic__stats">
        <div class="lh-stat"><b>{{ topic.questions_count }}</b><span>Fragen</span></div>
        <div class="lh-stat"><b>+{{ topic.xp_total }}</b><span>EP höchstens</span></div>
        <div class="lh-stat"><b>{{ topic.theory.length }}</b><span>Theorie-Teile</span></div>
      </div>
      <p v-if="topic.locked" class="topic__hint">
        <LhIcon class="lh-icon--sm" name="ui-locked" />Fragen ab Level {{ topic.level_required }}. Die Theorie kannst du schon lesen.
      </p>
      <p v-else-if="!topic.questions_count" class="topic__hint">Zu diesem Thema gibt es noch keine Fragen.</p>
      <div class="topic__actions">
        <LhButton v-if="canStart" block :to="`${base}/lesson`">Los geht’s</LhButton>
        <LhButton v-if="topic.theory.length" block :variant="canStart ? 'secondary' : 'primary'" :to="`${base}/theory`">
          Nur Theorie lesen
        </LhButton>
      </div>
    </template>
  </div>
</template>

<style scoped>
.topic { display: grid; gap: var(--space-4); }
.topic__back { justify-self: start; }
.topic__loading { margin: 0; color: var(--ink-muted); }
.topic__title { margin: 0; font: 700 26px/32px var(--font-text); color: var(--ink); }
.topic__text { margin: 0; font: 400 16px/24px var(--font-text); color: var(--ink-muted); }
.topic__hint { margin: 0; display: flex; align-items: center; gap: var(--space-2); font: 400 14px/20px var(--font-text); color: var(--ink-muted); }
.topic__actions { display: grid; gap: var(--space-3); }
</style>
