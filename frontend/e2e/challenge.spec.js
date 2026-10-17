import { expect, test } from '@playwright/test'
import { loginAs } from './personas.js'

// Herausforderung wie ein Mensch (E-045): Die richtigen Antworten kennt der Test nicht, er tippt immer die letzte Option.
// Was am Ende herauskommt, rechnet er aus den Rückmeldungen nach: 5 EP je richtiger Antwort, Bonus ab 8 und bei 10.
// Ändert Brainpower, EP, Trophäen und Erfolge von Herausforderer, der nächste Lauf setzt sie zurück.

test('Herausforderer spielt eine Herausforderung und sieht den Erfolg im Profil', async ({ page }) => {
  await loginAs(page, 'Herausforderer')

  // Hub vorher: 100 Brainpower, zwei Herausforderungen heute
  await expect(page).toHaveURL('/')
  await expect(page.getByLabel('100 Brainpower')).toBeVisible()
  const row = page.getByRole('link', { name: /Herausforderung/ })
  await expect(row).toContainText('10 Fragen aus deinen Themen · noch 2 heute')
  await row.click()

  // Start: Kosten und Regeln vor dem Tipp
  await expect(page).toHaveURL('/challenge')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Herausforderung')
  await expect(page.locator('.lh-note')).toContainText('bis Mitternacht weitermachen')
  await page.getByRole('button', { name: 'Starten · 25' }).click()

  const steps = page.getByRole('progressbar', { name: 'Fortschritt in der Herausforderung' })
  await expect(steps).toHaveAttribute('aria-valuemax', '10')

  let correct = 0
  for (let i = 1; i <= 10; i++) {
    const eyebrow = page.locator('.challenge__eyebrow')
    await expect(eyebrow).toContainText(`Frage ${i} / 10`)
    await expect(eyebrow.locator('.lh-tag')).toHaveText(/^(POS|DBI)$/)

    await page.locator('.lh-answer').last().click()
    await page.getByRole('button', { name: 'Prüfen' }).click()

    const feedback = page.locator('.lh-feedback')
    await expect(feedback).toBeVisible()
    if (await feedback.getByText('Richtig!').isVisible()) {
      correct++
      await expect(feedback.locator('.lesson__xp')).toHaveText('+5 EP')
    } else {
      // Jede Frage kommt nur einmal
      await expect(feedback).not.toContainText('noch einmal')
    }
    await page.getByRole('button', { name: i === 10 ? 'Ergebnis ansehen' : 'Weiter' }).click()
  }

  // Ergebnis: EP mit Bonus, der Erfolg der ersten Herausforderung
  const bonus = correct === 10 ? 50 : correct >= 8 ? 20 : 0
  await expect(page.getByRole('heading', { level: 1 })).toHaveText(`${correct} von 10 richtig`)
  await expect(page.locator('.lh-result__gain')).toHaveText(`+${correct * 5 + bonus} EP`)
  await expect(page.locator('.new-achievements')).toContainText('Herausforderung angenommen')

  // Hub danach: 25 Brainpower weniger, noch eine heute
  await page.getByRole('link', { name: 'Zum Hub' }).click()
  await expect(page).toHaveURL('/')
  await expect(page.getByLabel('75 Brainpower')).toBeVisible()
  await expect(page.getByRole('link', { name: /Herausforderung/ })).toContainText('noch 1 heute')

  // Profil: der neue Erfolg ist erreicht, „Dranbleiben“ fehlt noch eine Lerneinheit
  await page.getByRole('navigation', { name: 'Hauptnavigation' }).getByRole('link', { name: 'Profil' }).click()
  const achievements = page.getByRole('region', { name: /^Erfolge/ })
  await expect(achievements.getByLabel(/^Herausforderung angenommen, erreicht am /)).toBeVisible()
  await expect(achievements.getByLabel('Dranbleiben, noch nicht erreicht: 25 Lerneinheiten abschließen')).toBeVisible()
  await expect(achievements.getByLabel('Geheimer Erfolg')).toHaveCount(2)
})

test('Neuling hat noch nichts gelernt: Die Herausforderung ist gesperrt und kein Link', async ({ page }) => {
  await loginAs(page, 'Neuling')

  await expect(page).toHaveURL('/')
  await expect(page.getByText('Schließe zuerst ein paar Themen ab.')).toBeVisible()
  await expect(page.getByRole('link', { name: /Herausforderung/ })).toHaveCount(0)
})
