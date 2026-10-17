// Erfolge für das Raster im Profil (E-042). Der Server prüft vorher nach und schickt geheime ohne Inhalt.
import { api } from './client.js'

export async function getAchievements() {
  return (await api('/achievements')).data
}
