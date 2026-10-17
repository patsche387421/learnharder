// Laufender EP-Booster (E-037) aus { multiplier, ends_at } vom Server: Faktor als Text und Restzeit in Minuten.
// Einmal pro Minute neu gerechnet, kein Countdown in Sekunden. Ist er abgelaufen, ist das Ergebnis null.
import { computed, onMounted, onUnmounted, ref, toValue } from 'vue'

const factor = new Intl.NumberFormat('de', { maximumFractionDigits: 1 })

export function formatFactor(multiplier) {
  return `${factor.format(multiplier)}×`
}

export function useBooster(source) {
  const now = ref(Date.now())
  let timer
  onMounted(() => {
    timer = setInterval(() => (now.value = Date.now()), 60_000)
  })
  onUnmounted(() => clearInterval(timer))

  return computed(() => {
    const booster = toValue(source)
    if (!booster) return null
    const minutes = Math.ceil((Date.parse(booster.ends_at) - now.value) / 60_000)
    return minutes > 0 ? { factor: formatFactor(booster.multiplier), minutes } : null
  })
}
