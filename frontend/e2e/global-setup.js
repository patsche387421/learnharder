// Vor jedem Lauf: prüfen, ob App und API laufen, dann die Testpersonen zurücksetzen (TestPersonasSeeder).
import { execFileSync } from 'node:child_process'
import { fileURLToPath } from 'node:url'

const BACKEND = fileURLToPath(new URL('../../backend', import.meta.url))

async function reachable(url) {
  try {
    return (await fetch(url)).ok
  } catch {
    return false
  }
}

export default async function globalSetup(config) {
  const app = config.projects[0].use.baseURL
  const api = process.env.E2E_API_URL ?? 'http://localhost'
  if (!(await reachable(`${api}/up`))) throw new Error(`API unter ${api} nicht erreichbar. Zuerst Sail starten (Testzentrale „Start“ oder scripts/dev.py start).`)
  if (!(await reachable(app))) throw new Error(`App unter ${app} nicht erreichbar. Zuerst Vite starten (npm --prefix frontend run dev).`)

  execFileSync('./vendor/bin/sail', ['artisan', 'db:seed', '--class=TestPersonasSeeder', '--no-interaction'], { cwd: BACKEND, stdio: 'pipe' })
}
