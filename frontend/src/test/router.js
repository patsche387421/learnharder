// Router für Komponenten-Tests mit `to`: Speicher-History und eine Platzhalter-Route für jeden Pfad.
import { h } from 'vue'
import { createMemoryHistory, createRouter, RouterView } from 'vue-router'

export function testRouter() {
  return createRouter({
    history: createMemoryHistory(),
    routes: [{ path: '/:path(.*)*', component: { render: () => null } }],
  })
}

// Router für Seiten-Tests: die Seite unter ihrem echten Pfadmuster (mit :params), alles andere als Platzhalter.
export async function mountRoute(mount, component, pattern, path) {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: pattern, component },
      { path: '/:path(.*)*', component: { render: () => null } },
    ],
  })
  router.push(path)
  await router.isReady()
  const wrapper = mount({ render: () => h(RouterView) }, { global: { plugins: [router] } })
  return { wrapper, router }
}
