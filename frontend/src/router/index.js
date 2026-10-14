import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  { path: '/', component: () => import('../views/HomeView.vue') },
]

// Styleguide nur in der Entwicklung. Im Produktions-Build fällt die Route samt Seite weg.
if (import.meta.env.DEV) {
  routes.push({ path: '/styleguide', component: () => import('../views/StyleguideView.vue') })
}

export default createRouter({
  history: createWebHistory(),
  routes,
})
