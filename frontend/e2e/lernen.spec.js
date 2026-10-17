import { expect, test } from '@playwright/test'
import { loginAs } from './personas.js'

// Eine Lerneinheit wie ein Mensch: Die richtigen Antworten kennt der Test vorher nicht. Er tippt zuerst die letzte Option
// (im importierten Inhalt steht die richtige immer vorne, so kommen sicher Fehler und Wiederholungen vor),
// merkt sich nach einem Fehler die als „Richtig“ markierten Optionen und nimmt sie, wenn die Frage am Ende wiederkommt.
// Ändert Streak und EP von Mittendrin, der nächste Lauf setzt sie zurück.

test('Mittendrin lernt eine Einheit, danach steht der Streak auf 7', async ({ page }) => {
  await loginAs(page, 'Mittendrin')

  // Hub vorher: Streak 6 seit gestern, heute noch nichts gelernt
  await expect(page).toHaveURL('/')
  await expect(page.getByText('Noch nichts gelernt heute. 5 Min. reichen.')).toBeVisible()
  await expect(page.getByLabel('6 Tage Streak')).toBeVisible()

  await page.getByRole('link', { name: /Weiterlernen/ }).click()
  await expect(page).toHaveURL(/\/learn\/[^/]+\/[^/]+$/)
  const topic = (await page.getByRole('heading', { level: 1 }).textContent()).trim()
  await page.getByRole('link', { name: 'Los geht’s' }).click()

  const steps = page.getByRole('progressbar', { name: 'Fortschritt in der Lerneinheit' })
  await expect(steps).toBeVisible()
  const total = Number(await steps.getAttribute('aria-valuemax'))
  expect(total).toBeGreaterThan(0)

  const remembered = new Map() // Fragetext → Texte der richtigen Optionen
  let firstTryCorrect = 0
  let repeats = 0

  for (let round = 0; round < total * 2; round++) {
    const question = (await page.locator('.lesson__question').textContent()).trim()
    const options = page.locator('.lh-answer')
    const isRepeat = await page.getByText('Noch einmal', { exact: true }).isVisible()

    if (isRepeat) {
      expect(remembered.has(question)).toBe(true)
      for (const text of remembered.get(question)) await options.filter({ hasText: text }).click()
    } else {
      await options.last().click()
    }
    await page.getByRole('button', { name: 'Prüfen' }).click()

    const feedback = page.locator('.lh-feedback')
    await expect(feedback).toBeVisible()
    const correct = await feedback.getByText('Richtig!').isVisible()
    if (isRepeat) {
      expect(correct, `Wiederholung von „${question}“ mit den gemerkten Antworten`).toBe(true)
    } else if (correct) {
      firstTryCorrect++
    } else {
      // Was richtig gewesen wäre, steht markiert in den Optionen. Die Frage kommt am Ende noch einmal.
      await expect(feedback).toContainText(/Nicht ganz\. Richtig ist [A-F](, [A-F])*\./)
      await expect(feedback).toContainText('Kommt am Ende noch einmal.')
      remembered.set(question, (await page.locator('.lh-answer--correct .lh-answer__text').allTextContents()).map((t) => t.trim()))
      repeats++
      await expect(steps).toHaveAttribute('aria-valuemax', String(total + repeats))
    }

    const last = page.getByRole('button', { name: 'Ergebnis ansehen' })
    if (await last.isVisible()) {
      await last.click()
      break
    }
    await page.getByRole('button', { name: 'Weiter' }).click()
  }

  // Ergebnis: zählt nur den ersten Versuch je Frage, Wiederholungen als geübte Fehler
  await expect(page.getByText(`${topic} geschafft · ${firstTryCorrect} von ${total} richtig`)).toBeVisible()
  await expect(page.locator('.lh-result__gain')).toHaveText(/^\+\d+ EP$/)
  await expect(page.locator('.lh-stat').filter({ hasText: 'Fehler geübt' })).toContainText(String(repeats))

  // Hub danach: heute gelernt, Streak 7. Das Ergebnis ist Fokus-Modus ohne Tab-Leiste, also über das Fach zurück
  await page.getByRole('link', { name: 'Zum Fach' }).click()
  await page.getByRole('navigation', { name: 'Hauptnavigation' }).locator('.lh-tab--hub').click()
  await expect(page).toHaveURL('/')
  await expect(page.getByLabel('7 Tage Streak')).toBeVisible()
  await expect(page.getByText(/^Heute schon gelernt\. Noch eine Runde .+\?$/)).toBeVisible()
  await expect(page.getByText('Noch nichts gelernt heute.')).toHaveCount(0)
})
