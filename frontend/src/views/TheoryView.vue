<script setup>
// Theorie-Modus (LearnFlow, Moment 8): das Thema als kurzer Artikel, ohne Tab-Leiste. Am Ende geht es direkt ins Üben.
// Das HTML rendert der Server aus Markdown und entschärft es (HTML maskiert, keine javascript:-Links), deshalb v-html.
import { computed, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
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

// Grobe Lesezeit: etwa 150 Wörter pro Minute, mindestens 1 Minute
const minutes = computed(() => {
  const text = (topic.value?.theory ?? []).map((s) => s.html.replace(/<[^>]+>/g, ' ')).join(' ')
  const words = text.split(/\s+/).filter(Boolean).length
  return Math.max(1, Math.round(words / 150))
})
</script>

<template>
  <div class="theory">
    <div class="lh-lessonbar">
      <RouterLink class="lh-iconbtn" :to="base" aria-label="Schließen"><LhIcon name="ui-close" /></RouterLink>
    </div>
    <p v-if="loading && !topic" class="theory__muted" aria-live="polite">Lädt …</p>
    <LoadError v-else-if="error" :error="error" @retry="load" />
    <article v-else-if="topic" class="theory__article">
      <h1 class="theory__title">{{ topic.title }}</h1>
      <p v-if="topic.theory.length" class="theory__muted">{{ minutes }} Min. Lesezeit</p>
      <section v-for="section in topic.theory" :key="section.title" class="theory__section">
        <h2 class="theory__heading">{{ section.title }}</h2>
        <div class="theory__body" v-html="section.html" />
      </section>
      <p v-if="!topic.theory.length" class="theory__muted">Zu diesem Thema gibt es noch keine Theorie.</p>
      <div class="theory__actions">
        <LhButton v-if="canStart" block :to="`${base}/lesson`">Jetzt üben</LhButton>
        <LhButton v-else block variant="secondary" :to="base">Zurück zum Thema</LhButton>
      </div>
    </article>
  </div>
</template>

<style scoped>
.theory { display: grid; gap: var(--space-4); }
.theory__article { display: grid; gap: var(--space-4); }
.theory__title { margin: 0; font: 700 26px/32px var(--font-text); color: var(--ink); }
.theory__heading { margin: 0 0 var(--space-2); font: 700 21px/28px var(--font-text); color: var(--ink); }
.theory__muted { margin: 0; font: 400 14px/20px var(--font-mono); color: var(--ink-muted); }
.theory__actions { padding-top: var(--space-4); }

/* Lese-Stile für das Server-HTML (Absätze, Listen, Code) */
.theory__body { font: 400 16px/26px var(--font-text); color: var(--ink); overflow-wrap: anywhere; }
.theory__body :deep(p), .theory__body :deep(ul), .theory__body :deep(ol) { margin: 0 0 var(--space-3); }
.theory__body :deep(ul), .theory__body :deep(ol) { padding-left: var(--space-6); }
.theory__body :deep(h1), .theory__body :deep(h2), .theory__body :deep(h3) { margin: var(--space-4) 0 var(--space-2); font: 600 18px/24px var(--font-text); }
.theory__body :deep(code) { font: 400 15px/22px var(--font-mono); padding: 1px 6px; border-radius: var(--radius-sm); background: var(--surface-sunken); }
.theory__body :deep(pre) { margin: 0 0 var(--space-3); padding: var(--space-3) var(--space-4); border-radius: var(--radius-md);
  background: var(--surface-sunken); overflow-x: auto; }
.theory__body :deep(pre code) { padding: 0; background: none; white-space: pre; }
.theory__body :deep(a) { color: var(--ink); }
.theory__body :deep(table) { border-collapse: collapse; display: block; overflow-x: auto; margin: 0 0 var(--space-3); }
.theory__body :deep(th), .theory__body :deep(td) { padding: var(--space-1) var(--space-2); box-shadow: inset 0 0 0 1px var(--line); text-align: left; }
</style>
