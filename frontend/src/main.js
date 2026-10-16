import { createApp } from 'vue'
import { createPinia } from 'pinia'
// Design-System: Reihenfolge ist Pflicht (fonts → tokens → bundle → style), siehe styles/CLAUDE.md
import './styles/fonts.css'
import './styles/tokens.css'
import './styles/bundle.css'
import './styles/style.css'
import App from './App.vue'
import { onUnauthorized } from './api/client.js'
import router from './router'
import { useAuthStore } from './stores/auth.js'

const app = createApp(App)
app.use(createPinia())
app.use(router)

// Session abgelaufen oder Konto gesperrt: Store leeren und zum Login. Beim ersten Prüfen erledigt das der Router-Guard selbst.
onUnauthorized((reason) => {
  const auth = useAuthStore()
  if (!auth.loaded || router.currentRoute.value.meta.public) return
  auth.clear()
  const query = reason === 'account_deactivated' ? { gesperrt: '1' } : { redirect: router.currentRoute.value.fullPath }
  router.replace({ path: '/login', query })
})
// Erst nach der ersten Navigation mounten, damit route.meta (Rahmen ja/nein) von Anfang an stimmt.
router.isReady().then(() => app.mount('#app'))
