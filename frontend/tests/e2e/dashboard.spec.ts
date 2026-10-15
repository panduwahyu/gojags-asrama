import { expect, test } from '@playwright/test'
import { STATE } from './accounts'
import { captureScreenshot } from './screenshots'

test('dashboard admin menampilkan metrik dan tautan navigasi ke tiap layanan', async ({ browser }) => {
  const adminContext = await browser.newContext({ storageState: STATE.admin })
  const page = await adminContext.newPage()

  await page.goto('/admin')
  const summary = page.getByRole('region', { name: 'Ringkasan layanan' })
  await expect(summary).toBeVisible()

  // Tangkap screenshot dashboard admin mode terang
  await captureScreenshot(page, 'panduan/admin/03-dashboard-admin-overview.png')

  // Tangkap screenshot dashboard admin mode gelap
  await page.locator('[data-theme-toggle]').first().click()
  await captureScreenshot(page, 'panduan/admin/04-dashboard-admin-dark.png')
  await page.locator('[data-theme-toggle]').first().click()

  // Verifikasi 3 kartu metrik utama
  await expect(summary.getByText('Total kamar')).toBeVisible()
  await expect(summary.getByText('Pesanan laundry')).toBeVisible()
  await expect(summary.getByText('Jadwal catering')).toBeVisible()

  // Navigasi melalui kartu metrik ke kamar
  await summary.getByRole('link', { name: /Total kamar/ }).click()
  await expect(page).toHaveURL('/admin/rooms')

  await adminContext.close()
})

test('dashboard penghuni menampilkan salam personal dan kartu layanan', async ({ browser }) => {
  const residentContext = await browser.newContext({ storageState: STATE.resident })
  const page = await residentContext.newPage()

  await page.goto('/resident')
  await expect(page.getByText('Selamat datang kembali')).toBeVisible()
  await expect(page.getByText('Pandu Wahyu Aji')).toBeVisible()

  // Tangkap screenshot dashboard penghuni mode terang
  await captureScreenshot(page, 'panduan/resident/03-dashboard-penghuni-overview.png')

  // Tangkap screenshot dashboard penghuni mode gelap
  await page.locator('[data-theme-toggle]').first().click()
  await captureScreenshot(page, 'panduan/resident/04-dashboard-penghuni-dark.png')
  await page.locator('[data-theme-toggle]').first().click()

  // Verifikasi kartu layanan penghuni
  await expect(page.getByRole('heading', { name: 'Kamar & Check-in' })).toBeVisible()
  await expect(page.getByRole('heading', { name: 'Cuci Pakaian' })).toBeVisible()
  await expect(page.getByRole('heading', { name: 'Menu & Rating' })).toBeVisible()

  // Navigasi ke cuci pakaian
  await page.getByRole('link', { name: /Cuci Pakaian/ }).click()
  await expect(page).toHaveURL('/resident/laundry')

  await residentContext.close()
})

test('tombol tema berganti antara mode terang dan gelap serta bertahan setelah reload', async ({ browser }) => {
  const residentContext = await browser.newContext({ storageState: STATE.resident })
  const page = await residentContext.newPage()

  await page.goto('/resident')
  const html = page.locator('html')
  const toggle = page.locator('[data-theme-toggle]').first()

  // Pastikan tema awal (terang)
  await expect(html).not.toHaveClass(/\bdark\b/)

  // Klik untuk beralih ke mode gelap
  await toggle.click()
  await expect(html).toHaveClass(/\bdark\b/)

  // Muat ulang halaman dan pastikan kelas dark tetap bertahan
  await page.reload()
  await expect(html).toHaveClass(/\bdark\b/)

  // Kembalikan ke mode terang
  await toggle.click()
  await expect(html).not.toHaveClass(/\bdark\b/)

  await residentContext.close()
})
