import { expect, test } from '@playwright/test'
import { STATE } from './accounts'

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

  // 1. Admin menambah menu catering baru
  await adminPage.goto('/admin/catering')
  await adminPage.getByLabel('Nomor menu').fill(String(uniqueId))
  await adminPage.getByLabel('Nama makanan').fill(uniqueDish)
  await adminPage.getByLabel('Waktu makan').selectOption('lunch')
  await adminPage.getByLabel('Penyedia').fill(uniqueProvider)
  await adminPage.getByLabel('Waktu catering (WIB)').fill('2026-10-15T12:00')
  await adminPage.getByRole('button', { name: 'Tambah menu' }).click()

  const adminSection = adminPage.getByRole('region', { name: 'Menu catering' })
  await expect(adminSection.getByRole('row', { name: new RegExp(uniqueDish) })).toBeVisible()

  // 2. Penghuni memberi rating pada menu
  await residentPage.goto('/resident/catering')
  const residentSection = residentPage.getByRole('region', { name: 'Menu catering' })
  const residentRow = residentSection.getByRole('row', { name: new RegExp(uniqueDish) })
  await expect(residentRow).toBeVisible()
  await residentRow.getByLabel(new RegExp(`Rating untuk Menu ${uniqueId}`)).selectOption('5')
  await residentRow.getByRole('button', { name: 'Simpan rating' }).click()
  await expect(residentRow).toContainText('5 / 5 ★')

  // 3. Admin memeriksa hasil rating rata-rata
  await adminPage.reload()
  const ratedAdminRow = adminSection.getByRole('row', { name: new RegExp(uniqueDish) })
  await expect(ratedAdminRow).toContainText('5 / 5 ★')

  // 4. Admin mengedit nama menu catering
  await ratedAdminRow.getByRole('button', { name: 'Edit' }).click()
  const updatedDish = `Ayam Madu Spesial ${uniqueId}`
  await adminPage.getByLabel('Nama makanan').fill(updatedDish)
  await adminPage.getByRole('button', { name: 'Simpan perubahan' }).click()

  await expect(adminSection.getByRole('row', { name: new RegExp(updatedDish) })).toBeVisible()

  // 5. Admin menghapus menu catering dengan konfirmasi dua langkah
  const rowToDelete = adminSection.getByRole('row', { name: new RegExp(updatedDish) })
  await rowToDelete.getByRole('button', { name: 'Hapus' }).click()
  await rowToDelete.getByRole('button', { name: 'Ya, hapus' }).click()

  await expect(adminSection.getByRole('row', { name: new RegExp(updatedDish) })).toHaveCount(0)

  await adminContext.close()
  await residentContext.close()
})
