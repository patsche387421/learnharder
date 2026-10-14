<script setup>
// Styleguide (nur in der Entwicklung): alle Tokens, Icons und lh-Bausteine auf einer Seite, in Hell und Dunkel.
// Farben und Text-Stile kommen direkt aus tokens.json, damit hier nichts doppelt gepflegt wird.
import { ref, watch } from 'vue'
import tokens from '../styles/tokens.json'
import { icons } from '../components/ui/icons.js'
import LhAnswerOption from '../components/ui/LhAnswerOption.vue'
import LhArt from '../components/ui/LhArt.vue'
import LhButton from '../components/ui/LhButton.vue'
import LhCard from '../components/ui/LhCard.vue'
import LhCurrencyChip from '../components/ui/LhCurrencyChip.vue'
import LhEmptyState from '../components/ui/LhEmptyState.vue'
import LhIcon from '../components/ui/LhIcon.vue'
import LhLevelBadge from '../components/ui/LhLevelBadge.vue'
import LhSubjectCard from '../components/ui/LhSubjectCard.vue'
import LhTextField from '../components/ui/LhTextField.vue'
import LhXpBar from '../components/ui/LhXpBar.vue'

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

const xp = ref(154)

// Mini-Quiz für LhAnswerOption: antippen, prüfen, nochmal
const answers = [
  { letter: 'A', text: '9-mal' },
  { letter: 'B', text: '10-mal', ok: true },
  { letter: 'C', text: '11-mal' },
]
const picked = ref(null)
const checked = ref(false)
const resultFor = (a) => (!checked.value ? null : a.ok ? 'correct' : a.letter === picked.value ? 'wrong' : null)
function check() {
  if (checked.value) picked.value = null
  checked.value = !checked.value
}
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

    <section class="sg__section">
      <h2 class="h2">LhLevelBadge</h2>
      <div class="sg__row sg__row--center">
        <LhLevelBadge :level="7" rank="bronze" />
        <LhLevelBadge :level="34" rank="silver" />
        <LhLevelBadge :level="61" rank="gold" />
        <LhLevelBadge :level="93" rank="platin" />
        <LhLevelBadge :level="4" :prestige="2" />
        <LhLevelBadge :level="58" rank="gold" :prestige="1" large />
      </div>
    </section>

    <section class="sg__section">
      <h2 class="h2">LhXpBar</h2>
      <LhXpBar :value="xp" :max="200">
        <template #top><span><b>Level 21</b> · Bronze</span><span>{{ xp }} / 200 EP</span></template>
        <template #bottom><span>{{ xp >= 200 ? 'Level 22!' : `${200 - xp} EP bis Level 22` }}</span></template>
      </LhXpBar>
      <div class="sg__row">
        <LhButton variant="secondary" @click="xp = Math.min(200, xp + 20)">+20 EP</LhButton>
        <LhButton variant="ghost" @click="xp = 154">Zurücksetzen</LhButton>
      </div>
    </section>

    <section class="sg__section">
      <h2 class="h2">LhCurrencyChip</h2>
      <div class="sg__row">
        <LhCurrencyChip icon="icon-brainpower" :value="5" label="Brainpower" />
        <LhCurrencyChip icon="icon-trophy" :value="50" label="Trophäen" />
        <LhCurrencyChip icon="icon-streak" :value="7" label="Tage Streak" />
        <LhCurrencyChip icon="icon-xp" :value="12400" label="EP" />
      </div>
      <div class="sg__row">
        <LhCurrencyChip icon="icon-brainpower" :value="5" label="Brainpower" large />
        <LhCurrencyChip icon="icon-trophy" :value="50" label="Trophäen" large />
      </div>
    </section>

    <section class="sg__section">
      <h2 class="h2">LhSubjectCard</h2>
      <LhSubjectCard to="/" code="POS" name="Programmieren und Software-Engineering" :level="4" :done="12" :total="19" />
      <LhSubjectCard to="/" code="DBI" name="Datenbanken und Informationssysteme" :total="14" />
      <LhSubjectCard code="SYP" name="Systemplanung und Projektentwicklung" locked lockedText="Wird nach „Schleifen“ freigeschaltet." />
    </section>

    <section class="sg__section">
      <h2 class="h2">LhAnswerOption</h2>
      <p class="question sg__type">Wie oft läuft <code>for (int i = 0; i &lt; n; i++)</code> durch, wenn n = 10?</p>
      <LhAnswerOption
        v-for="a in answers"
        :key="a.letter"
        :letter="a.letter"
        :selected="picked === a.letter"
        :result="resultFor(a)"
        :disabled="checked"
        @click="picked = a.letter"
      >
        {{ a.text }}
      </LhAnswerOption>
      <LhButton block :disabled="!picked" @click="check">{{ checked ? 'Nochmal' : 'Prüfen' }}</LhButton>
    </section>
  </main>
</template>

<style scoped>
.sg { max-width: var(--content-max); margin-inline: auto; padding: var(--space-4); display: grid; gap: var(--space-8); }
.sg__head { display: grid; gap: var(--space-3); }
.sg__section { display: grid; gap: var(--space-3); }
.sg__section h2, .sg__head h1, .sg__type { margin: 0; }
.sg__row { display: flex; flex-wrap: wrap; gap: var(--space-2); }
.sg__row--center { align-items: center; gap: var(--space-6); }
.sg__swatches { display: grid; gap: var(--space-3); }
.sg__swatch { display: grid; grid-template-columns: 48px 1fr; gap: 0 var(--space-3); align-items: center; }
.sg__chip { grid-row: span 2; width: 48px; height: 48px; border-radius: var(--radius-md); box-shadow: inset 0 0 0 1px var(--line); }
.sg__icons { display: grid; grid-template-columns: repeat(auto-fill, minmax(96px, 1fr)); gap: var(--space-3); }
.sg__icon { margin: 0; display: grid; justify-items: center; gap: var(--space-1); text-align: center; color: var(--ink); }
.sg__icon figcaption { color: var(--ink-muted); overflow-wrap: anywhere; }
</style>
