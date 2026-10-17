import { expect, test } from '@playwright/test'
import { loginAs } from './personas.js'

// Anmelden mit den Testpersonen: Jede landet dort, wo ihr Zustand sie hinführt. Ändert keine Spielwerte.

test('Neuling landet im Hub, ohne Streak-Chip', async ({ page }) => {
  await loginAs(page, 'Neuling')

  await expect(page).toHaveURL('/')
  const progress = page.getByRole('region', { name: 'Fortschritt' })
  await expect(progress).toContainText('Level 1')
  await expect(progress.getByLabel('100 Brainpower')).toBeVisible()
  await expect(progress.getByLabel(/Streak/)).toHaveCount(0)
})

test('Boosterfan sieht den laufenden Booster auf Weiterlernen und am Brainpower-Chip', async ({ page }) => {
  await loginAs(page, 'Boosterfan')

  await expect(page).toHaveURL('/')
  await expect(page.getByLabel('3 Tage Streak')).toBeVisible()
  await expect(page.getByLabel('60 Brainpower, 2× EP läuft')).toBeVisible()
  await expect(page.locator('.lh-tag--boost')).toHaveText(/^2× EP · 2[45] Min\.$/)
})

test('OhneKlasse erfährt, dass noch keine Klasse da ist', async ({ page }) => {
  await loginAs(page, 'OhneKlasse')

  await expect(page).toHaveURL('/')
  await expect(page.getByText('Noch keiner Klasse zugeordnet.')).toBeVisible()
  await expect(page.getByText('Weiterlernen')).toHaveCount(0)
})

test('Gesperrt kommt nicht hinein und liest, warum', async ({ page }) => {
  await loginAs(page, 'Gesperrt')

  await expect(page).toHaveURL(/\/login/)
  await expect(page.getByText('Dein Konto ist gesperrt. Wende dich an deine Lehrkraft.')).toBeVisible()
  await page.goto('/')
  await expect(page).toHaveURL(/\/login/)
})
