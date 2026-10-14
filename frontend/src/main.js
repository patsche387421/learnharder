import { createApp } from 'vue'
import { createPinia } from 'pinia'
// Design-System: Reihenfolge ist Pflicht (fonts → tokens → bundle), siehe styles/CLAUDE.md
import './styles/fonts.css'
import './styles/tokens.css'
import './styles/bundle.css'
import App from './App.vue'
import router from './router'

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.mount('#app')
