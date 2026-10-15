<script setup>
// Ein Fach: Themen in Reihenfolge. Erledigt = Lerneinheit abgeschlossen. Das erste offene Thema ist „jetzt dran“.
import { computed, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import LoadError from '../components/LoadError.vue'
import LhButton from '../components/ui/LhButton.vue'
import LhIcon from '../components/ui/LhIcon.vue'
import { getSubject } from '../api/learn.js'
import { useLoad } from '../composables/useLoad.js'

const route = useRoute()
const { data: subject, error, loading, load } = useLoad(() => getSubject(route.params.subject))
watch(() => route.params.subject, load, { immediate: true })

const nextSlug = computed(() => subject.value?.topics.find((t) => !t.done && !t.locked)?.slug)

function meta(topic) {
  if (topic.locked) return `Ab Level ${topic.level_required}`
  const questions = topic.questions_count ? `${topic.questions_count} Fragen` : 'Nur Theorie'
  return topic.done ? `Erledigt · ${questions}` : questions
}
</script>

<template>
  <div class="subject">
    <LhButton class="subject__back" variant="ghost" to="/learn"><LhIcon name="ui-back" />Alle Fächer</LhButton>
    <p v-if="loading && !subject" class="subject__loading" aria-live="polite">Lädt …</p>
    <LoadError v-else-if="error" :error="error" @retry="load" />
    <template v-else-if="subject">
      <h1 class="subject__title">{{ subject.name }}</h1>
      <ol class="subject__topics">
        <li v-for="(topic, i) in subject.topics" :key="topic.slug">
          <RouterLink
            class="lh-topic"
            :class="{ 'lh-topic--done': topic.done, 'lh-topic--now': topic.slug === nextSlug, 'lh-topic--locked': topic.locked }"
            :to="`/learn/${subject.code}/${topic.slug}`"
          >
            <span class="lh-topic__no">
              <LhIcon v-if="topic.done" class="lh-icon--sm" name="ui-correct" />
              <LhIcon v-else-if="topic.locked" class="lh-icon--sm" name="ui-locked" />
              <template v-else>{{ i + 1 }}</template>
            </span>
            <span class="lh-topic__text">
              <span class="subject__topic">{{ topic.title }}</span>
              <span class="lh-topic__meta">{{ meta(topic) }}</span>
            </span>
            <LhIcon name="ui-next" />
          </RouterLink>
        </li>
      </ol>
    </template>
  </div>
</template>

<style scoped>
.subject { display: grid; gap: var(--space-4); }
.subject__back { justify-self: start; }
.subject__loading { margin: 0; color: var(--ink-muted); }
.subject__title { margin: 0; font: 700 26px/32px var(--font-text); color: var(--ink); }
.subject__topics { margin: 0; padding: 0; list-style: none; display: grid; gap: var(--space-3); }
.subject__topic { display: block; }
</style>
