<script setup>
// „Lernen“: alle Fächer mit Fortschritt (erledigte Themen). Sichtbar ist, was der Server liefert (E-017).
import { onMounted } from 'vue'
import LoadError from '../components/LoadError.vue'
import LhEmptyState from '../components/ui/LhEmptyState.vue'
import LhSubjectCard from '../components/ui/LhSubjectCard.vue'
import { getSubjects } from '../api/learn.js'
import { useLoad } from '../composables/useLoad.js'

const { data: subjects, error, loading, load } = useLoad(getSubjects)
onMounted(load)
</script>

<template>
  <div class="learn">
    <h1 class="learn__title">Lernen</h1>
    <p v-if="loading && !subjects" class="learn__loading" aria-live="polite">Lädt …</p>
    <LoadError v-else-if="error" :error="error" @retry="load" />
    <LhEmptyState
      v-else-if="subjects && !subjects.length"
      title="Noch keine Fächer."
      text="Sobald es Themen gibt, findest du sie hier."
    />
    <div v-else-if="subjects" class="learn__list">
      <LhSubjectCard
        v-for="subject in subjects"
        :key="subject.code"
        :code="subject.code.toUpperCase()"
        :name="subject.name"
        :done="subject.topics_done"
        :total="subject.topics_count"
        :to="`/learn/${subject.code}`"
      />
    </div>
  </div>
</template>

<style scoped>
.learn { display: grid; gap: var(--space-4); }
.learn__title { margin: 0; font: 700 26px/32px var(--font-text); color: var(--ink); }
.learn__loading { margin: 0; color: var(--ink-muted); }
.learn__list { display: grid; gap: var(--space-3); }
/* Abstand zwischen „Noch nicht begonnen“ und „0 / 21 Themen“, wenn die Karte schmal ist.
   Gehört eigentlich in .lh-xp__meta im Design-System-Artefakt (E-006), bis dahin hier. */
.learn__list :deep(.lh-xp__meta) { gap: var(--space-2); }
@media (min-width: 768px) {
  .learn__list { grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); }
}
</style>
