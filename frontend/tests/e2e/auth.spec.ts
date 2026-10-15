import { expect, test } from '@playwright/test'
import { ACCOUNTS } from './accounts'
import { captureScreenshot } from './screenshots'

test('admin dan penghuni dapat login, logout, dan dicegah mengakses rute tanpa izin', async ({ page }) => {
  // 1. Tampilan login mode terang dan gelap
  await page.goto('/login')
  await expect(page.getByRole('button', { name: 'Masuk' })).toBeVisible()
  await captureScreenshot(page, 'panduan/admin/01-login-admin-light.png')
  await captureScreenshot(page, 'panduan/resident/01-login-penghuni-light.png')

  await page.evaluate(() => document.documentElement.classList.add('dark'))
  await captureScreenshot(page, 'panduan/admin/02-login-admin-dark.png')
  await page.evaluate(() => document.documentElement.classList.remove('dark'))

  // Admin login dan logout
  await page.getByLabel('Email').fill(ACCOUNTS.admin.email)
  await page.getByLabel('Kata sandi').fill(ACCOUNTS.admin.password)
  await page.getByRole('button', { name: 'Masuk' }).click()
  await expect(page).toHaveURL('/admin')

  await page.getByRole('button', { name: 'Logout' }).click()
  await expect(page).toHaveURL('/login')

  // Proteksi rute setelah logout
  await page.goto('/admin/rooms')
  await expect(page).toHaveURL('/login')

  // 2. Penghuni login dan verifikasi halaman beranda
  await page.getByLabel('Email').fill(ACCOUNTS.resident.email)
  await page.getByLabel('Kata sandi').fill(ACCOUNTS.resident.password)
  await page.getByRole('button', { name: 'Masuk' }).click()
  await expect(page).toHaveURL('/resident')
  await expect(page.getByText('Selamat datang kembali')).toBeVisible()

  // 3. Penghuni dicegah mengakses halaman admin
  await page.goto('/admin')
  await expect(page).toHaveURL('/resident')

  // 4. Logout penghuni
  await page.getByRole('button', { name: 'Logout' }).click()
  await expect(page).toHaveURL('/login')
})

test('menampilkan pesan validasi error saat kredensial salah', async ({ page }) => {
  await page.goto('/login')
  await expect(page.getByRole('button', { name: 'Masuk' })).toBeVisible()
  await page.getByLabel('Email').fill('salah@example.id')
  await page.getByLabel('Kata sandi').fill('katasandikeliru')
  await page.getByRole('button', { name: 'Masuk' }).click()

  await expect(page.getByRole('alert')).toContainText('tidak sesuai')
  await captureScreenshot(page, 'panduan/resident/02-login-validasi-error.png')
  await expect(page).toHaveURL('/login')
})
