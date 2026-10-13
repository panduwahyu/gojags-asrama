import { expect, test } from '../support/fixtures'

test('tombol tema menukar kelas dark dan bertahan setelah muat ulang', async ({ asResident: page }) => {
  await page.goto('/resident')
  const html = page.locator('html')
  await expect(html).not.toHaveClass(/\bdark\b/)

  await page.getByRole('button', { name: /mode malam/i }).first().click()
  await expect(html).toHaveClass(/\bdark\b/)

  await page.reload()
  await expect(html).toHaveClass(/\bdark\b/)
})
