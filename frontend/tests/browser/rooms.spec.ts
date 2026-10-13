import { test, expect } from '@playwright/test'

test('admin room inventory, profile form, and allocation report work without adding rooms', async ({ page }) => {
  await page.goto('/login')
  await page.getByLabel('Email', { exact: true }).fill('admin@example.id')
  await page.getByLabel('Kata sandi', { exact: true }).fill('admin123')
  await page.getByRole('button', { name: 'Login', exact: true }).click()
  await expect(page.getByRole('dialog')).toBeVisible()
  await page.getByRole('dialog').getByRole('button', { name: 'OK', exact: true }).click()
  await expect(page).toHaveURL(/\/admin$/)
  await page.goto('/kamar')
  await expect(page.getByText('78 kamar di lantai 3, 4, dan 5.', { exact: false })).toBeVisible()
  await expect(page.getByText('＋ Tambah kamar', { exact: true })).toHaveCount(0)
  await expect(page.getByRole('columnheader', { name: 'Lantai', exact: true })).toBeVisible()
  await expect(page.getByRole('columnheader', { name: 'Penghuni', exact: true })).toBeVisible()
  await expect(page.getByRole('columnheader', { name: 'Keterangan', exact: true })).toHaveCount(0)
  await page.getByText('Profil user untuk alokasi', { exact: false }).click()
  await expect(page.getByLabel('Angkatan Pandu Wahyu Aji', { exact: true })).toBeVisible()
  // Verify the form sends the correct endpoint and body without editing live profiles.
  await page.route('**/backend/api/asrama/allocation-users/*', async route => {
    expect(route.request().method()).toBe('PATCH')
    expect(route.request().postDataJSON()).toEqual({ angkatan: '2026', gender: 'Laki-laki', agama: 'Islam' })
    await route.fulfill({ json: { message: 'Profil alokasi berhasil disimpan.' } })
  })
  await page.getByLabel('Angkatan Pandu Wahyu Aji', { exact: true }).fill('2026')
  await page.getByLabel('Gender Pandu Wahyu Aji', { exact: true }).selectOption('Laki-laki')
  await page.getByLabel('Agama Pandu Wahyu Aji', { exact: true }).fill('Islam')
  await page.getByRole('button', { name: 'Simpan profil Pandu Wahyu Aji', exact: true }).click()
  await expect(page.getByRole('dialog')).toBeVisible()
  await page.getByRole('dialog').getByRole('button', { name: 'OK', exact: true }).click()
  await page.route('**/backend/api/asrama/kamar/allocate', async route => {
    expect(route.request().method()).toBe('POST')
    await route.fulfill({ json: {
      message: '0 user dialokasikan. 1 profil belum lengkap. 0 user menunggu kamar yang sesuai.',
      allocated: [], waiting: [], incomplete: [{ id: 2, name: 'Pandu Wahyu Aji' }],
    } })
  })
  await page.getByRole('button', { name: 'Alokasikan kamar otomatis', exact: true }).click()
  await expect(page.getByRole('dialog')).toBeVisible()
  await page.getByRole('dialog').getByRole('button', { name: 'OK', exact: true }).click()
  await expect(page.getByText('Profil belum lengkap: Pandu Wahyu Aji', { exact: true })).toBeVisible()
  await page.getByRole('button', { name: 'Logout', exact: true }).click()
  await expect(page.getByRole('dialog')).toBeVisible()
  await page.getByRole('dialog').getByRole('button', { name: 'OK', exact: true }).click()
  await expect(page).toHaveURL(/\/login$/)
})
