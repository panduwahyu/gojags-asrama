import { test, expect, type Page } from '@playwright/test'

async function login(page: Page, email: string, password: string) {
  await page.goto('/login')
  await page.getByLabel('Email', { exact: true }).fill(email)
  await page.getByLabel('Kata sandi', { exact: true }).fill(password)
  await page.getByRole('button', { name: 'Login', exact: true }).click()
}

test('landing page is login without sidebar or footer and protected pages redirect guests', async ({ page }) => {
  await page.goto('/')
  await expect(page).toHaveURL(/\/login$/)
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Selamat datang kembali')
  await expect(page.locator('aside')).toHaveCount(0)
  await expect(page.locator('footer')).toHaveCount(0)
  for (const path of ['/admin', '/user', '/kamar']) {
    await page.goto(path)
    await expect(page).toHaveURL(/\/login$/)
    await expect(page.locator('aside')).toHaveCount(0)
  }
})

test('admin has management access, cannot open user dashboard, and can logout', async ({ page }, testInfo) => {
  await login(page, 'admin@example.id', 'admin123')
  await expect(page).toHaveURL(/\/admin$/)
  await expect(page.getByText('Total kamar', { exact: true })).toBeVisible()
  await expect(page.locator('aside').getByRole('button', { name: 'Logout', exact: true })).toBeVisible()
  await expect(page.locator('aside')).not.toContainText('Selamat datang')
  expect((await page.request.get('/backend/api/asrama/welcome')).status()).toBe(403)
  await page.goto('/user')
  await expect(page).toHaveURL(/\/admin$/)
  await page.goto('/kamar')
  await expect(page.getByRole('button', { name: 'Alokasikan kamar otomatis', exact: true })).toBeVisible()
  await page.screenshot({ path: testInfo.outputPath('admin-kamar.png'), fullPage: true })
  await page.getByRole('button', { name: 'Logout', exact: true }).click()
  await expect(page).toHaveURL(/\/login$/)
  expect((await page.request.get('/backend/api/asrama/dashboard')).status()).toBe(401)
  await page.goto('/admin')
  await expect(page).toHaveURL(/\/login$/)
})

test('user only sees a personalized welcome and cannot access admin pages or data', async ({ page }, testInfo) => {
  await login(page, 'user@example.id', 'user123')
  await expect(page).toHaveURL(/\/user$/)
  await expect(page.getByRole('heading', { level: 1 })).toContainText('Selamat datang di')
  await expect(page.getByText('Pandu Wahyu Aji', { exact: true })).toBeVisible()
  await expect(page.getByText('Total kamar', { exact: true })).toHaveCount(0)
  await expect(page.locator('aside')).toContainText('Kamar')
  await expect(page.locator('aside')).not.toContainText('Selamat datang')
  for (const path of ['dashboard', 'kamar', 'laundry', 'catering']) {
    expect((await page.request.get('/backend/api/asrama/' + path)).status()).toBe(403)
  }
  for (const path of ['/admin', '/kamar', '/laundry', '/catering']) {
    await page.goto(path)
    await expect(page).toHaveURL(/\/user$/)
    await expect(page.getByText('Pandu Wahyu Aji', { exact: true })).toBeVisible()
  }
  await page.reload()
  await expect(page.getByText('Pandu Wahyu Aji', { exact: true })).toBeVisible()
  await page.screenshot({ path: testInfo.outputPath('user-welcome.png'), fullPage: true })
  await page.getByRole('button', { name: 'Logout', exact: true }).click()
  await expect(page).toHaveURL(/\/login$/)
  await expect(page.locator('footer')).toHaveCount(0)
})
