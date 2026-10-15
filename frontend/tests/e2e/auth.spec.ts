import { expect, test } from '@playwright/test'
import { ACCOUNTS } from './accounts'

test('admin dan penghuni dapat login, logout, dan dicegah mengakses rute tanpa izin', async ({ page }) => {
  // 1. Admin login dan logout
  await page.goto('/login')
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
  await page.getByLabel('Email').fill('salah@example.id')
  await page.getByLabel('Kata sandi').fill('katasandikeliru')
  await page.getByRole('button', { name: 'Masuk' }).click()

  await expect(page.getByRole('alert')).toContainText('Email atau kata sandi salah.')
  await expect(page).toHaveURL('/login')
})
