import { expect, test } from '@playwright/test'
import { ACCOUNTS } from './accounts'

test('login dan logout tiap peran berfungsi serta melindungi halaman setelah logout', async ({ page }) => {
  await page.goto('/login')
  await page.getByLabel('Email').fill(ACCOUNTS.admin.email)
  await page.getByLabel('Kata sandi').fill(ACCOUNTS.admin.password)
  await page.getByRole('button', { name: 'Masuk' }).click()
  await expect(page).toHaveURL('/admin')

  await page.getByRole('button', { name: 'Logout' }).click()
  await expect(page).toHaveURL('/login')

  await page.goto('/admin/rooms')
  await expect(page).toHaveURL('/login')
})
