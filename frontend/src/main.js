import { createApp } from 'vue'
import { createPinia } from 'pinia'
// Design-System: Reihenfolge ist Pflicht (fonts → tokens → bundle → style), siehe styles/CLAUDE.md
import './styles/fonts.css'
import './styles/tokens.css'
import './styles/bundle.css'
import './styles/style.css'
import App from './App.vue'
import router from './router'

const app = createApp(App)
app.use(createPinia())
app.use(router)
// Erst nach der ersten Navigation mounten, damit route.meta (Rahmen ja/nein) von Anfang an stimmt.
router.isReady().then(() => app.mount('#app'))
