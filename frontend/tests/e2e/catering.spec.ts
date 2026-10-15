import { expect, test } from '@playwright/test'
import { STATE } from './accounts'
import { captureScreenshot } from './screenshots'

test('siklus lengkap catering: admin menambah, penghuni menilai, admin menyunting dan menghapus', async ({
  browser,
}) => {
  const adminContext = await browser.newContext({ storageState: STATE.admin })
  const residentContext = await browser.newContext({ storageState: STATE.resident })
  const adminPage = await adminContext.newPage()
  const residentPage = await residentContext.newPage()

  const uniqueId = Date.now() % 9000 + 1000
  const uniqueDish = `Ayam Bakar E2E ${uniqueId}`
  const uniqueProvider = `Penyedia E2E ${uniqueId}`

  const now = new Date()
  const year = now.getFullYear()
  const month = String(now.getMonth() + 1).padStart(2, '0')
  const date = String(now.getDate()).padStart(2, '0')
  const todayServedAt = `${year}-${month}-${date}T00:01`

  // 1. Admin menambah menu catering baru
  await adminPage.goto('/admin/catering')
  const adminSection = adminPage.getByRole('region', { name: 'Menu catering' })
  await expect(adminSection).toBeVisible()

  await adminPage.getByLabel('Nomor menu').fill(String(uniqueId))
  await adminPage.getByLabel('Nama makanan').fill(uniqueDish)
  await adminPage.getByLabel('Waktu makan').selectOption('lunch')
  await adminPage.getByLabel('Penyedia').fill(uniqueProvider)
  await adminPage.getByLabel('Waktu catering (WIB)').fill(todayServedAt)
  await captureScreenshot(adminPage, 'panduan/admin/10-catering-tambah-menu.png')
  await adminPage.getByRole('button', { name: 'Tambah menu' }).click()

  await expect(adminSection.getByRole('row', { name: new RegExp(uniqueDish) })).toBeVisible()

  // 2. Penghuni memberi rating pada menu
  await residentPage.goto('/resident/catering')
  const residentSection = residentPage.getByRole('region', { name: 'Menu catering' })
  await expect(residentSection).toBeVisible()
  const residentRow = residentSection.getByRole('row', { name: new RegExp(uniqueDish) })
  await expect(residentRow).toBeVisible()
  await captureScreenshot(residentPage, 'panduan/resident/10-catering-daftar-dan-form-rating.png')

  await residentRow.getByLabel(new RegExp(`Rating untuk Menu ${uniqueId}`)).selectOption('5')
  await residentRow.getByRole('button', { name: 'Simpan rating' }).click()
  await expect(residentRow).toContainText('5 / 5 ★')
  await captureScreenshot(residentPage, 'panduan/resident/11-catering-rating-tersimpan.png')

  // 3. Admin memeriksa hasil rating rata-rata
  await adminPage.reload()
  const ratedAdminRow = adminSection.getByRole('row', { name: new RegExp(uniqueDish) })
  await expect(ratedAdminRow).toContainText('5 / 5 ★')
  await captureScreenshot(adminPage, 'panduan/admin/11-catering-lihat-rating.png')

  // 4. Admin mengedit nama menu catering
  await ratedAdminRow.getByRole('button', { name: 'Edit' }).click()
  const updatedDish = `Ayam Madu Spesial ${uniqueId}`
  await adminPage.getByLabel('Nama makanan').fill(updatedDish)
  await adminPage.getByRole('button', { name: 'Simpan perubahan' }).click()

  await expect(adminSection.getByRole('row', { name: new RegExp(updatedDish) })).toBeVisible()
  await captureScreenshot(adminPage, 'panduan/admin/12-catering-edit-menu.png')

  // 5. Admin menghapus menu catering dengan konfirmasi dua langkah
  const rowToDelete = adminSection.getByRole('row', { name: new RegExp(updatedDish) })
  await rowToDelete.getByRole('button', { name: 'Hapus' }).click()
  await rowToDelete.getByRole('button', { name: 'Ya, hapus' }).click()

  await expect(adminSection.getByRole('row', { name: new RegExp(updatedDish) })).toHaveCount(0)

  await adminContext.close()
  await residentContext.close()
})
