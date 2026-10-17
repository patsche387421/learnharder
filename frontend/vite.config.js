import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vite'

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue()],
  // Vitest: Komponenten-Tests im simulierten Browser (jsdom).
  // vmThreads baut jsdom einmal je Worker statt je Testdatei, jede Datei läuft trotzdem für sich (ca. 4× schneller).
  test: {
    environment: 'jsdom',
    pool: 'vmThreads',
  },
})
