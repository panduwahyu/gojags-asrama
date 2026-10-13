import { expect, test } from '@playwright/test'
import { STATE } from './accounts'

test('penghuni mengirim keluhan lalu admin menandainya Resolved', async ({ browser }) => {
  const residentContext = await browser.newContext({ storageState: STATE.resident })
  const adminContext = await browser.newContext({ storageState: STATE.admin })
  const residentPage = await residentContext.newPage()
  const adminPage = await adminContext.newPage()

  const uniqueTitle = `Keluhan E2E ${Date.now()}`

  await residentPage.goto('/resident/room')
  const residentSection = residentPage.getByRole('region', { name: 'Keluhan asrama' })
  await residentSection.getByLabel('Judul keluhan').fill(uniqueTitle)
  await residentSection.getByLabel('Isi keluhan').fill('Deskripsi keluhan pengujian end-to-end.')
  await residentSection.getByRole('button', { name: 'Kirim keluhan' }).click()
  await expect(residentSection.getByRole('row', { name: new RegExp(uniqueTitle) })).toBeVisible()

  await adminPage.goto('/admin/rooms')
  const adminSection = adminPage.getByRole('region', { name: 'Keluhan asrama' })
  const adminRow = adminSection.getByRole('row', { name: new RegExp(uniqueTitle) })
  await expect(adminRow).toBeVisible()
  await adminRow.getByRole('button', { name: 'Tandai Resolved' }).click()
  await expect(adminRow).toContainText('Sudah ditangani')

  await residentPage.reload()
  const updatedRow = residentSection.getByRole('row', { name: new RegExp(uniqueTitle) })
  await expect(updatedRow).toContainText('Resolved')
  await expect(updatedRow).not.toContainText('Belum diselesaikan')

  await residentContext.close()
  await adminContext.close()
})
