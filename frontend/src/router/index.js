import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth.js'

// meta.title: Seitentitel. meta.focus: ohne Navigation (Lerneinheit, Quiz, Theorie). meta.layout: false = ohne App-Rahmen.
// meta.public: ohne Login erreichbar. Alles andere braucht eine Session.
const placeholder = () => import('../views/PlaceholderView.vue')

const routes = [
  { path: '/login', component: () => import('../views/LoginView.vue'), meta: { title: 'Einloggen', layout: false, public: true } },
  { path: '/', component: placeholder, meta: { title: 'Hub' } },
  { path: '/learn', component: placeholder, meta: { title: 'Lernen' } },
  { path: '/team', component: placeholder, meta: { title: 'Team' } },
  { path: '/league', component: placeholder, meta: { title: 'Liga' } },
  { path: '/profile', component: () => import('../views/ProfileView.vue'), meta: { title: 'Profil' } },
]

// Styleguide nur in der Entwicklung. Im Produktions-Build fällt die Route samt Seite weg.
if (import.meta.env.DEV) {
  routes.push({ path: '/styleguide', component: () => import('../views/StyleguideView.vue'), meta: { layout: false, public: true } })
}

/** Beim ersten Aufruf einmal /me fragen. Ohne Session geht es zum Login, mit Session nicht mehr dorthin. */
export async function authGuard(to) {
  const auth = useAuthStore()
  // Ist der Server nicht erreichbar, geht es zum Login. Dort erscheint dann der Verbindungsfehler.
  if (!auth.loaded) await auth.fetchMe().catch(() => {})

  if (to.meta.public) return to.path === '/login' && auth.me ? '/learn' : true
  if (!auth.me) return { path: '/login', query: to.fullPath === '/' ? {} : { redirect: to.fullPath } }
  return true
}

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(authGuard)

export default router
