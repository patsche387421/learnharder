import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth.js'

// meta.title: Seitentitel. meta.focus: ohne Navigation (Lerneinheit, Quiz, Theorie). meta.layout: false = ohne App-Rahmen.
// meta.public: ohne Login erreichbar. meta.guest: nur ohne Login (sonst weiter zu Lernen). Alles andere braucht eine Session.
const placeholder = () => import('../views/PlaceholderView.vue')
const legal = () => import('../views/LegalView.vue')

const routes = [
  { path: '/login', component: () => import('../views/LoginView.vue'), meta: { title: 'Einloggen', layout: false, public: true, guest: true } },
  { path: '/registrieren', component: () => import('../views/RegisterView.vue'), meta: { title: 'Registrieren', layout: false, public: true, guest: true } },
  // Rechtstexte (E-023): mit und ohne Login lesbar, der Server liefert Text und Fassung
  { path: '/datenschutz', component: legal, props: { slug: 'datenschutz' }, meta: { title: 'Datenschutz', layout: false, public: true } },
  { path: '/impressum', component: legal, props: { slug: 'impressum' }, meta: { title: 'Impressum', layout: false, public: true } },
  { path: '/nutzungsregeln', component: legal, props: { slug: 'nutzungsregeln' }, meta: { title: 'Nutzungsregeln', layout: false, public: true } },
  { path: '/einwilligung-eltern', component: legal, props: { slug: 'einwilligung-eltern' }, meta: { title: 'Einwilligung der Eltern', layout: false, public: true } },
  { path: '/', component: placeholder, meta: { title: 'Hub' } },
  // Verschachtelt, damit der Tab „Lernen“ auch auf Fach, Thema und in der Lerneinheit aktiv bleibt
  {
    path: '/learn',
    meta: { title: 'Lernen' },
    children: [
      { path: '', component: () => import('../views/LearnView.vue') },
      { path: ':subject', component: () => import('../views/SubjectView.vue') },
      { path: ':subject/:topic', component: () => import('../views/TopicView.vue') },
      { path: ':subject/:topic/theory', component: () => import('../views/TheoryView.vue'), meta: { title: 'Theorie', focus: true } },
      { path: ':subject/:topic/lesson', component: () => import('../views/LessonView.vue'), meta: { title: 'Lerneinheit', focus: true } },
    ],
  },
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

  if (to.meta.public) return to.meta.guest && auth.me ? '/learn' : true
  if (!auth.me) return { path: '/login', query: to.fullPath === '/' ? {} : { redirect: to.fullPath } }
  return true
}

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(authGuard)

export default router
