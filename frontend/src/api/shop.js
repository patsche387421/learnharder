// Shop (E-037): Brainpower, laufender Booster und Angebote. Preise und Faktoren kommen nur vom Server.
import { api } from './client.js'

export async function getShop() {
  return (await api('/shop')).data
}

/** Startet einen EP-Booster. Liefert die neue Brainpower und den laufenden Booster. */
export async function startBooster(kind) {
  return (await api('/boosters', { method: 'POST', body: { kind } })).data
}
