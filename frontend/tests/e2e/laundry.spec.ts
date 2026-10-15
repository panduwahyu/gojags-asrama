import { expect, test } from '@playwright/test'
import { STATE } from './accounts'
import { captureScreenshot } from './screenshots'

test('penghuni mengajukan laundry lalu admin mengisi biaya dan menyelesaikan', async ({ browser }) => {
  const residentContext = await browser.newContext({ storageState: STATE.resident })
  const adminContext = await browser.newContext({ storageState: STATE.admin })
  const residentPage = await residentContext.newPage()
  const adminPage = await adminContext.newPage()

  const uniqueItems = `Pakaian E2E ${Date.now()}`

  await residentPage.goto('/resident/laundry')
  const residentSection = residentPage.getByRole('region', { name: 'Pengajuan laundry' })
  await expect(residentSection).toBeVisible()

  await residentPage.getByLabel('Pakaian / barang yang dilaundry').fill(uniqueItems)
  await residentPage.getByLabel('Jumlah stel').fill('3')
  await residentPage.getByRole('button', { name: 'Ajukan laundry' }).click()

  await expect(residentSection.getByRole('row', { name: new RegExp(uniqueItems) })).toBeVisible()
  await captureScreenshot(residentPage, 'panduan/resident/08-laundry-formulir-pengajuan.png')

  await adminPage.goto('/admin/laundry')
  const adminSection = adminPage.getByRole('region', { name: 'Pengajuan laundry' })
  await expect(adminSection).toBeVisible()
  const adminRow = adminSection.getByRole('row', { name: new RegExp(uniqueItems) })
  await expect(adminRow).toBeVisible()
  await captureScreenshot(adminPage, 'panduan/admin/08-laundry-antrean-masuk.png')

  await adminRow.getByLabel(/Status/).selectOption('done')
  await adminRow.getByLabel(/Biaya laundry/).fill('30000')
  await adminRow.getByRole('button', { name: 'Simpan' }).click()
  await expect(adminRow).toContainText('30.000')
  await captureScreenshot(adminPage, 'panduan/admin/09-laundry-selesai-dengan-biaya.png')

  await residentPage.reload()
  const updatedRow = residentSection.getByRole('row', { name: new RegExp(uniqueItems) })
  await expect(updatedRow).toContainText('Selesai')
  await expect(updatedRow).toContainText('30.000')
  await captureScreenshot(residentPage, 'panduan/resident/09-laundry-status-selesai.png')

  await residentContext.close()
  await adminContext.close()
})
