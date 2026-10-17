<script setup>
// Shop (E-037, docs/design/components/Shop.md): Brainpower einsetzen, zuerst Abschnitt „EP-Booster“.
// Preise, Faktoren und Dauer kommen vom Server. Höchstens ein Booster läuft, die Restzeit zählt in Minuten.
import { computed, onMounted, ref } from 'vue'
import LoadError from '../components/LoadError.vue'
import LhArt from '../components/ui/LhArt.vue'
import LhButton from '../components/ui/LhButton.vue'
import LhCurrencyChip from '../components/ui/LhCurrencyChip.vue'
import { getShop, startBooster } from '../api/shop.js'
import { formatFactor, useBooster } from '../composables/useBooster.js'
import { useLoad } from '../composables/useLoad.js'
import { useAuthStore } from '../stores/auth.js'

const auth = useAuthStore()
const { data: shop, error, loading, load } = useLoad(getShop)
const buying = ref(null)
const buyError = ref('')

onMounted(load)

const running = useBooster(() => shop.value?.booster)

const brainpower = computed(() => shop.value?.brainpower.current ?? 0)

function why(offer) {
  if (running.value) return 'Läuft schon einer'
  const missing = offer.cost - brainpower.value
  return missing > 0 ? `Noch ${missing} Brainpower` : ''
}

async function buy(offer) {
  buying.value = offer.kind
  buyError.value = ''
  try {
    const result = await startBooster(offer.kind)
    shop.value = { ...shop.value, ...result }
    auth.applyProgress(result)
  } catch (e) {
    buyError.value = e.message
  } finally {
    buying.value = null
  }
}
</script>

<template>
  <div class="lh-shop">
    <div class="lh-shop__head">
      <h1 class="h1 shop__title">Shop</h1>
      <LhCurrencyChip v-if="shop" icon="icon-brainpower" :value="brainpower" label="Brainpower" />
    </div>

    <p v-if="loading && !shop" class="shop__muted" aria-live="polite">Lädt …</p>
    <LoadError v-else-if="error" :error="error" @retry="load" />

    <section v-else-if="shop" class="lh-shop__section" aria-labelledby="shop-boost">
      <h2 id="shop-boost" class="lh-shop__title">EP-Booster</h2>
      <p class="lh-shop__intro">Mehr EP für richtige Antworten. Gilt für Fragen, die dir heute noch EP bringen. Einer zur Zeit.</p>

      <div v-if="running" class="lh-shop__running" role="status">
        <LhArt name="icon-booster" :size="24" />{{ running.factor }} EP läuft · noch {{ running.minutes }} Min.
      </div>
      <p v-if="buyError" class="shop__error" role="alert">{{ buyError }}</p>

      <div v-for="offer in shop.boosters" :key="offer.kind" class="lh-offer">
        <LhArt name="icon-booster" :size="40" />
        <span class="lh-offer__body">
          <span class="lh-offer__name">{{ formatFactor(offer.multiplier) }} EP</span>
          <span class="lh-offer__meta">{{ offer.minutes }} Min.</span>
        </span>
        <span class="lh-offer__buy">
          <LhButton variant="secondary" :disabled="!!why(offer) || !!buying" @click="buy(offer)">
            {{ buying === offer.kind ? 'Startet …' : `Starten · ${offer.cost}` }}
          </LhButton>
          <span v-if="why(offer)" class="lh-offer__why">{{ why(offer) }}</span>
        </span>
      </div>
    </section>
  </div>
</template>

<style scoped>
.shop__title { margin: 0; color: var(--ink); }
.shop__muted { margin: 0; color: var(--ink-muted); }
.shop__error { margin: 0; font: 400 14px/20px var(--font-text); color: var(--wrong); }
</style>
