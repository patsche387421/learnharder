<script setup>
// Shop (E-037, docs/design/components/Shop.md): Brainpower einsetzen, zuerst Abschnitt „EP-Booster“.
// Preise, Faktoren und Dauer kommen vom Server. Höchstens ein Booster läuft, die Restzeit zählt in Minuten.
// Läuft einer, sagt das der Hinweis oben einmal, die Buttons verweisen darauf (aria-describedby).
// Bringt heute keine Frage mehr EP (xp_left_today vom Server), sagt das ein Satz über den Angeboten. Kaufen bleibt möglich.
// Im Kopf der eigene Roboter klein, Akku aus der Brainpower. Nach einem Kauf jubelt er einmal (neu eingehängt per key).
import { computed, onMounted, ref } from 'vue'
import LoadError from '../components/LoadError.vue'
import LhArt from '../components/ui/LhArt.vue'
import LhButton from '../components/ui/LhButton.vue'
import LhCurrencyChip from '../components/ui/LhCurrencyChip.vue'
import { getShop, startBooster } from '../api/shop.js'
import RobotAvatar from '../avatar/RobotAvatar.vue'
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
const battery = computed(() => {
  const bp = shop.value?.brainpower
  return bp?.max ? bp.current / bp.max : null
})
// Zählt die Käufe auf dieser Seite: neuer key, damit der Jubel auch beim zweiten Kauf noch einmal läuft
const cheers = ref(0)

// Grund unter dem Button, nur wenn die Brainpower nicht reicht. Läuft einer, steht der Grund oben im Hinweis
function why(offer) {
  if (running.value) return ''
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
    cheers.value++
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
      <div v-if="shop" class="shop__side">
        <div class="shop__robot">
          <RobotAvatar :key="cheers" :action="cheers ? 'celebrate' : null" :battery="battery" label="Dein Roboter" />
        </div>
        <LhCurrencyChip icon="icon-brainpower" :value="brainpower" label="Brainpower" />
      </div>
    </div>

    <p v-if="loading && !shop" class="shop__muted" aria-live="polite">Lädt …</p>
    <LoadError v-else-if="error" :error="error" @retry="load" />

    <section v-else-if="shop" class="lh-shop__section" aria-labelledby="shop-boost">
      <h2 id="shop-boost" class="lh-shop__title">EP-Booster</h2>
      <p class="lh-shop__intro">Mehr EP für richtige Antworten. Gilt für Fragen, die dir heute noch EP bringen. Einer zur Zeit.</p>
      <p v-if="shop.xp_left_today === false" class="lh-shop__intro shop__no-xp">
        Heute bringen deine Themen keine EP mehr. Morgen lohnt sich ein Booster wieder.
      </p>

      <div v-if="running" id="shop-running" class="lh-shop__running" role="status">
        <LhArt name="icon-booster" :size="24" />
        <span>{{ running.factor }} EP läuft · noch {{ running.minutes }} Min.<span class="lh-shop__running-next">Danach kannst du den nächsten starten.</span></span>
      </div>
      <p v-if="buyError" class="shop__error" role="alert">{{ buyError }}</p>

      <div v-for="offer in shop.boosters" :key="offer.kind" class="lh-offer">
        <LhArt name="icon-booster" :size="40" />
        <span class="lh-offer__body">
          <span class="lh-offer__name">{{ formatFactor(offer.multiplier) }} EP</span>
          <span class="lh-offer__meta">{{ offer.minutes }} Min.</span>
        </span>
        <span class="lh-offer__buy">
          <LhButton
            variant="secondary"
            :disabled="!!running || !!why(offer) || !!buying"
            :aria-describedby="running ? 'shop-running' : undefined"
            @click="buy(offer)"
          >
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
.shop__side { display: flex; align-items: center; gap: var(--space-2); }
.shop__robot { flex: none; width: 48px; height: 58px; }
.shop__muted { margin: 0; color: var(--ink-muted); }
.shop__error { margin: 0; font: 400 14px/20px var(--font-text); color: var(--wrong); }
</style>
