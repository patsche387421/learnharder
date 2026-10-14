// Router für Komponenten-Tests mit `to`: Speicher-History und eine Platzhalter-Route für jeden Pfad.
import { createMemoryHistory, createRouter } from 'vue-router'

export function testRouter() {
  return createRouter({
    history: createMemoryHistory(),
    routes: [{ path: '/:path(.*)*', component: { render: () => null } }],
  })
}
