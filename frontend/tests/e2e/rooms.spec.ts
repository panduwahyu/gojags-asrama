import { expect, test } from '@playwright/test'
import { STATE } from './accounts'
import { captureScreenshot } from './screenshots'

test('siklus alokasi kamar: admin melengkapi profil & alokasi, user check-in, check-out, & cek riwayat', async ({
  browser,
}) => {
  const adminContext = await browser.newContext({ storageState: STATE.admin })
  const user1Context = await browser.newContext({ storageState: STATE.user1 })
  const adminPage = await adminContext.newPage()
  const user1Page = await user1Context.newPage()

  // 1. Admin memeriksa tabel kamar dan melengkapi profil User 1
  await adminPage.goto('/admin/rooms')
  const roomsSection = adminPage.getByRole('region', { name: 'Daftar kamar' })
  await expect(roomsSection).toBeVisible()
  await captureScreenshot(adminPage, 'panduan/admin/05-manajemen-kamar-tabel.png')

  await adminPage.getByText(/Profil user untuk alokasi/).click()
  await adminPage.getByLabel('Agama User 1', { exact: true }).fill('Islam')
  await captureScreenshot(adminPage, 'panduan/admin/06-profil-penghuni-edit.png')
  await adminPage.getByRole('button', { name: 'Simpan profil User 1', exact: true }).click()
  await expect(adminPage.getByRole('status')).toContainText('Profil User 1 berhasil disimpan.')

  // 2. Admin menjalankan alokasi otomatis
  const allocSection = adminPage.getByRole('region', { name: 'Alokasi kamar otomatis' })
  await allocSection.getByRole('button', { name: 'Alokasikan kamar otomatis' }).click()
  await expect(allocSection.getByRole('status')).toContainText('dialokasikan')
  await captureScreenshot(adminPage, 'panduan/admin/07-alokasi-kamar-berhasil.png')

  // 3. User 1 melihat penempatan kamar dan melakukan check-in
  await user1Page.goto('/resident/room')
  const myRoom = user1Page.getByRole('region', { name: 'Kamar saya' })
  await expect(myRoom.getByRole('button', { name: 'Check-in' })).toBeEnabled()
  await captureScreenshot(user1Page, 'panduan/resident/05-kamar-menunggu-checkin.png')

  await myRoom.getByRole('button', { name: 'Check-in' }).click()
  await expect(myRoom.getByText('Sudah check-in')).toBeVisible()
  await captureScreenshot(user1Page, 'panduan/resident/06-kamar-berhasil-checkin.png')

  // 4. User 1 melakukan check-out
  await myRoom.getByRole('button', { name: 'Check-out' }).click()
  await expect(myRoom.getByRole('heading', { name: 'Belum ada alokasi kamar' })).toBeVisible()

  // 5. Riwayat stay User 1 mencatat status 'Selesai'
  const historySection = user1Page.getByRole('region', { name: 'Riwayat check-in dan check-out' })
  await expect(historySection.getByText('Selesai')).toBeVisible()
  await captureScreenshot(user1Page, 'panduan/resident/07-kamar-riwayat-checkout.png')

  await adminContext.close()
  await user1Context.close()
})
