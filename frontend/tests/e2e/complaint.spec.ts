import { expect, test } from '@playwright/test'
import { STATE } from './accounts'
import { captureScreenshot } from './screenshots'

test('penghuni mengirim keluhan lalu admin menandainya Resolved', async ({ browser }) => {
  const residentContext = await browser.newContext({ storageState: STATE.resident })
  const adminContext = await browser.newContext({ storageState: STATE.admin })
  const residentPage = await residentContext.newPage()
  const adminPage = await adminContext.newPage()

  const uniqueTitle = `Keluhan E2E ${Date.now()}`

  await residentPage.goto('/resident/room')
  const residentSection = residentPage.getByRole('region', { name: 'Keluhan asrama' })
  await expect(residentSection).toBeVisible()

  await residentSection.getByLabel('Judul keluhan').fill(uniqueTitle)
  await residentSection.getByLabel('Isi keluhan').fill('Deskripsi keluhan pengujian end-to-end.')
  await residentSection.getByRole('button', { name: 'Kirim keluhan' }).click()
  await expect(residentSection.getByRole('row', { name: new RegExp(uniqueTitle) })).toBeVisible()
  await captureScreenshot(residentPage, 'panduan/resident/16-keluhan-formulir-pengajuan.png')

  await adminPage.goto('/admin/rooms')
  const adminSection = adminPage.getByRole('region', { name: 'Keluhan asrama' })
  await expect(adminSection).toBeVisible()
  const adminRow = adminSection.getByRole('row', { name: new RegExp(uniqueTitle) })
  await expect(adminRow).toBeVisible()
  await adminRow.getByRole('button', { name: 'Tandai Resolved' }).click()
  await expect(adminRow).toContainText('Sudah ditangani')
  await captureScreenshot(adminPage, 'panduan/admin/15-keluhan-penanganan-selesai.png')

  await residentPage.reload()
  const updatedRow = residentSection.getByRole('row', { name: new RegExp(uniqueTitle) })
  await expect(updatedRow).toContainText('Resolved')
  await expect(updatedRow).not.toContainText('Belum diselesaikan')
  await captureScreenshot(residentPage, 'panduan/resident/17-keluhan-status-resolved.png')

  await residentContext.close()
  await adminContext.close()
})
