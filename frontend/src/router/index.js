import { createRouter, createWebHistory } from 'vue-router'

// meta.title: Seitentitel. meta.focus: ohne Navigation (Lerneinheit, Quiz, Theorie). meta.layout: false = ohne App-Rahmen.
const placeholder = () => import('../views/PlaceholderView.vue')

const routes = [
  { path: '/', component: placeholder, meta: { title: 'Hub' } },
  { path: '/learn', component: placeholder, meta: { title: 'Lernen' } },
  { path: '/team', component: placeholder, meta: { title: 'Team' } },
  { path: '/league', component: placeholder, meta: { title: 'Liga' } },
  { path: '/profile', component: placeholder, meta: { title: 'Profil' } },
]

// Styleguide nur in der Entwicklung. Im Produktions-Build fällt die Route samt Seite weg.
if (import.meta.env.DEV) {
  routes.push({ path: '/styleguide', component: () => import('../views/StyleguideView.vue'), meta: { layout: false } })
}

export default createRouter({
  history: createWebHistory(),
  routes,
})
