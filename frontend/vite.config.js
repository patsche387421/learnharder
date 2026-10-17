import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vite'
import { configDefaults } from 'vitest/config'

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue()],
  // Vitest: Komponenten-Tests im simulierten Browser (jsdom).
  // vmThreads baut jsdom einmal je Worker statt je Testdatei, jede Datei läuft trotzdem für sich (ca. 4× schneller).
  test: {
    environment: 'jsdom',
    pool: 'vmThreads',
    // e2e/ sind Playwright-Abläufe im echten Browser (npm run e2e), nicht Vitest
    exclude: [...configDefaults.exclude, 'e2e/**'],
  },
})
