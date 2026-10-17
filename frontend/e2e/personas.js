// Anmelden als Testperson (backend/database/seeders/TestPersonasSeeder.php), wie ein Mensch über das Formular.
export const PASSWORD = 'learnharder123'

export async function loginAs(page, tag) {
  await page.goto('/login')
  await page.getByLabel('E-Mail').fill(`${tag.toLowerCase()}@test.learnharder.app`)
  await page.getByLabel('Passwort', { exact: true }).fill(PASSWORD)
  await page.getByRole('button', { name: 'Einloggen' }).click()
}
