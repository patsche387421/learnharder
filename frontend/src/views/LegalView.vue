<script setup>
// Rechtstexte (E-023): Datenschutz, Impressum, Nutzungsregeln, Elternformular. Mit und ohne Login lesbar, auch druckbar.
// Das HTML rendert der Server aus Markdown und entschärft es (wie bei der Theorie), deshalb v-html.
import { watch } from 'vue'
import { useRouter } from 'vue-router'
import LoadError from '../components/LoadError.vue'
import LhButton from '../components/ui/LhButton.vue'
import { getLegal } from '../api/account.js'
import { useLoad } from '../composables/useLoad.js'

const props = defineProps({ slug: { type: String, required: true } })
const router = useRouter()

const { data: text, error, loading, load } = useLoad(() => getLegal(props.slug))
watch(() => props.slug, load, { immediate: true })

// Zurück, woher man kam (Registrierung, Profil). Direkt geöffnet gibt es kein Zurück, dann zum Login.
function back() {
  if (window.history.state?.back) router.back()
  else router.push('/login')
}
</script>

<template>
  <main class="legal">
    <LhButton class="legal__back" variant="ghost" @click="back">Zurück</LhButton>
    <p v-if="loading && !text" class="legal__muted" aria-live="polite">Lädt …</p>
    <LoadError v-else-if="error" :error="error" @retry="load" />
    <article v-else-if="text">
      <h1 class="legal__title">{{ text.title }}</h1>
      <div class="legal__body" v-html="text.html" />
    </article>
  </main>
</template>

<style scoped>
.legal { max-width: var(--lesson-max); margin-inline: auto; padding: var(--space-6) var(--space-4) var(--space-8); display: grid; gap: var(--space-4); justify-items: start; }
.legal > article { width: 100%; }
.legal__title { margin: 0 0 var(--space-4); font: 700 26px/32px var(--font-text); color: var(--ink); }
.legal__muted { margin: 0; font: 400 14px/20px var(--font-mono); color: var(--ink-muted); }

/* Lese-Stile für das Server-HTML */
.legal__body { font: 400 16px/26px var(--font-text); color: var(--ink); overflow-wrap: anywhere; }
.legal__body :deep(p), .legal__body :deep(ul) { margin: 0 0 var(--space-3); }
.legal__body :deep(ul) { padding-left: var(--space-6); }
.legal__body :deep(h2) { margin: var(--space-6) 0 var(--space-2); font: 700 21px/28px var(--font-text); }
.legal__body :deep(blockquote) { margin: 0 0 var(--space-4); padding: var(--space-3) var(--space-4); border-radius: var(--radius-md); background: var(--surface-sunken); }
.legal__body :deep(blockquote p) { margin: 0; }
.legal__body :deep(code) { font: 400 15px/22px var(--font-mono); }
.legal__body :deep(a) { color: var(--ink); }
.legal__body :deep(table) { width: 100%; border-collapse: collapse; margin: 0 0 var(--space-4); }
.legal__body :deep(th), .legal__body :deep(td) { padding: var(--space-2); box-shadow: inset 0 0 0 1px var(--line); text-align: left; vertical-align: top; }
/* Leere Zellen im Elternformular: Platz zum Ausfüllen */
.legal__body :deep(td:empty) { min-width: 40%; height: 40px; }

@media print {
  .legal__back { display: none; }
}
</style>
