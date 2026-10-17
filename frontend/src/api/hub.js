// Fakten für den Hub (Konzept Spielwelt §4). Welches Thema als Nächstes dran ist, entscheidet der Server.
import { api } from './client.js'

export async function getHub() {
  return (await api('/hub')).data
}
