// Lädt Daten für eine Seite und merkt sich Zustand und Fehler. load() lädt neu, z. B. nach „Nochmal versuchen“.
import { ref } from 'vue'

export function useLoad(loader) {
  const data = ref(null)
  const error = ref(null)
  const loading = ref(false)

  async function load() {
    loading.value = true
    error.value = null
    try {
      data.value = await loader()
    } catch (e) {
      error.value = e
    } finally {
      loading.value = false
    }
  }

  return { data, error, loading, load }
}
