import { defineConfig, devices } from '@playwright/test'

// Abläufe im echten Browser (E-039) gegen den laufenden lokalen Stack: App (Vite) und API (Sail).
// Daten: Entwicklungs-Datenbank `laravel` mit den Testpersonen, die global-setup.js vor jedem Lauf zurücksetzt.
// Anleitung: docs/handbuch/testen.md
export default defineConfig({
  testDir: 'e2e',
  globalSetup: './e2e/global-setup.js',
  // Alle Tests teilen sich dieselben Testpersonen, deshalb nacheinander
  workers: 1,
  reporter: [['list'], ['html', { open: 'never' }]],
  use: {
    baseURL: process.env.E2E_APP_URL ?? 'http://localhost:5173',
    locale: 'de-AT',
    timezoneId: 'Europe/Vienna',
    trace: 'retain-on-failure',
  },
  // Mobile first: ein Android-Handy reicht für den Anfang
  projects: [{ name: 'handy', use: { ...devices['Pixel 7'] } }],
})
