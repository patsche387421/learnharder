<script setup>
// Styleguide (nur in der Entwicklung): alle Tokens, Icons und lh-Bausteine auf einer Seite, in Hell und Dunkel.
// Farben und Text-Stile kommen direkt aus tokens.json, damit hier nichts doppelt gepflegt wird.
import { ref, watch } from 'vue'
import tokens from '../styles/tokens.json'
import { icons } from '../components/ui/icons.js'
import LhArt from '../components/ui/LhArt.vue'
import LhButton from '../components/ui/LhButton.vue'
import LhCard from '../components/ui/LhCard.vue'
import LhEmptyState from '../components/ui/LhEmptyState.vue'
import LhIcon from '../components/ui/LhIcon.vue'
import LhTextField from '../components/ui/LhTextField.vue'

const theme = ref('system')
watch(theme, (t) => {
  if (t === 'system') document.documentElement.removeAttribute('data-theme')
  else document.documentElement.setAttribute('data-theme', t)
}, { immediate: true })

const iconGroups = ['ui', 'subject', 'icon'].map((prefix) => ({
  prefix,
  names: Object.keys(icons).filter((n) => n.startsWith(`${prefix}-`)).sort(),
}))

const email = ref('')
const emailError = ref('')
const tag = ref('patsche')
const schoolClass = ref('3AHIF')
</script>

<template>
  <main class="sg">
    <header class="sg__head">
      <h1 class="h1">Styleguide</h1>
      <div class="sg__row" role="group" aria-label="Theme">
        <LhButton
          v-for="t in ['system', 'light', 'dark']"
          :key="t"
          :variant="theme === t ? 'primary' : 'secondary'"
          :aria-pressed="theme === t"
          @click="theme = t"
        >
          {{ { system: 'System', light: 'Hell', dark: 'Dunkel' }[t] }}
        </LhButton>
      </div>
    </header>

    <section class="sg__section">
      <h2 class="h2">Farben</h2>
      <div class="sg__swatches">
        <div v-for="c in tokens.color.tokens" :key="c.name" class="sg__swatch">
          <span class="sg__chip" :style="{ background: `var(--${c.name})` }" />
          <span class="stat">--{{ c.name }}</span>
          <span class="small">{{ c.usage }}</span>
        </div>
      </div>
    </section>

    <section class="sg__section">
      <h2 class="h2">Text-Stile</h2>
      <template v-for="g in tokens.type.groups" :key="g.name">
        <p v-for="s in g.styles" :key="s.name" :class="s.name" class="sg__type">
          .{{ s.name }} · {{ s.fontSize }}/{{ s.lineHeight }} · Was gibt <code>i++</code> aus? 12 / 19 Themen
        </p>
      </template>
    </section>

    <section class="sg__section">
      <h2 class="h2">Icons</h2>
      <div v-for="g in iconGroups" :key="g.prefix" class="sg__icons">
        <figure v-for="n in g.names" :key="n" class="sg__icon">
          <LhIcon v-if="g.prefix === 'ui'" :name="n" />
          <LhArt v-else :name="n" :size="n.endsWith('-label') ? 64 : 48" />
          <figcaption class="meta">{{ n }}</figcaption>
        </figure>
      </div>
    </section>

    <section class="sg__section">
      <h2 class="h2">LhButton</h2>
      <div class="sg__row">
        <LhButton>Weiterlernen</LhButton>
        <LhButton variant="secondary">Nur Theorie lesen</LhButton>
        <LhButton variant="ghost">Überspringen</LhButton>
        <LhButton disabled>Prüfen</LhButton>
        <LhButton variant="secondary" to="/">Zum Hub</LhButton>
      </div>
      <LhButton block>Los geht’s</LhButton>
    </section>

    <section class="sg__section">
      <h2 class="h2">LhCard</h2>
      <LhCard eyebrow="Fr, 17. Okt">
        <p class="body">Inhalt ohne Link. Eine Karte, ein Thema.</p>
      </LhCard>
      <LhCard to="/" title="Herausforderung" text="Zufällige Fragen aus allen Fächern">
        <template #icon><LhArt name="icon-challenge" :size="40" /></template>
      </LhCard>
      <LhCard to="/" featured eyebrow="in 3 Tagen" title="DBI-Test" text="2 Themen sind noch schwach.">
        <template #icon><LhArt name="subject-dbi" :size="40" /></template>
      </LhCard>
    </section>

    <section class="sg__section">
      <h2 class="h2">LhEmptyState</h2>
      <LhEmptyState art="subject-pos" title="Noch keine Themen gelernt" text="Fang mit POS an.">
        <LhButton>Weiterlernen</LhButton>
      </LhEmptyState>
      <LhEmptyState art="icon-league" title="Die Liga startet bald" text="Die Liga startet am 1. November." />
    </section>

    <section class="sg__section">
      <h2 class="h2">LhTextField</h2>
      <LhTextField
        v-model="email"
        label="E-Mail"
        type="email"
        autocomplete="email"
        placeholder="lena@htl.at"
        :error="emailError"
        @blur="emailError = email.includes('@') ? '' : 'Da fehlt noch etwas, z. B. lena@htl.at'"
      />
      <LhTextField label="Gamer-Tag" v-model="tag" hint="Den sehen alle in der Liga." />
      <LhTextField label="Klasse" v-model="schoolClass" hint="Legt die Lehrkraft fest." disabled />
    </section>
  </main>
</template>

<style scoped>
.sg { max-width: var(--content-max); margin-inline: auto; padding: var(--space-4); display: grid; gap: var(--space-8); }
.sg__head { display: grid; gap: var(--space-3); }
.sg__section { display: grid; gap: var(--space-3); }
.sg__section h2, .sg__head h1, .sg__type { margin: 0; }
.sg__row { display: flex; flex-wrap: wrap; gap: var(--space-2); }
.sg__swatches { display: grid; gap: var(--space-3); }
.sg__swatch { display: grid; grid-template-columns: 48px 1fr; gap: 0 var(--space-3); align-items: center; }
.sg__chip { grid-row: span 2; width: 48px; height: 48px; border-radius: var(--radius-md); box-shadow: inset 0 0 0 1px var(--line); }
.sg__icons { display: grid; grid-template-columns: repeat(auto-fill, minmax(96px, 1fr)); gap: var(--space-3); }
.sg__icon { margin: 0; display: grid; justify-items: center; gap: var(--space-1); text-align: center; color: var(--ink); }
.sg__icon figcaption { color: var(--ink-muted); overflow-wrap: anywhere; }
</style>
