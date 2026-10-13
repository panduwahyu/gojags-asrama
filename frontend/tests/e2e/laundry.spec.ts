import { expect, test } from '@playwright/test'
import { STATE } from './accounts'

test('penghuni mengajukan laundry lalu admin mengisi biaya dan menyelesaikan', async ({ browser }) => {
  const residentContext = await browser.newContext({ storageState: STATE.resident })
  const adminContext = await browser.newContext({ storageState: STATE.admin })
  const residentPage = await residentContext.newPage()
  const adminPage = await adminContext.newPage()

  const uniqueItems = `Pakaian E2E ${Date.now()}`

  await residentPage.goto('/resident/laundry')
  await residentPage.getByLabel('Pakaian / barang yang dilaundry').fill(uniqueItems)
  await residentPage.getByLabel('Jumlah stel').fill('3')
  await residentPage.getByRole('button', { name: 'Ajukan laundry' }).click()

  const residentSection = residentPage.getByRole('region', { name: 'Pengajuan laundry' })
  await expect(residentSection.getByRole('row', { name: new RegExp(uniqueItems) })).toBeVisible()

  await adminPage.goto('/admin/laundry')
  const adminSection = adminPage.getByRole('region', { name: 'Pengajuan laundry' })
  const adminRow = adminSection.getByRole('row', { name: new RegExp(uniqueItems) })
  await expect(adminRow).toBeVisible()
  await adminRow.getByLabel(/Status/).selectOption('done')
  await adminRow.getByLabel(/Biaya laundry/).fill('30000')
  await adminRow.getByRole('button', { name: 'Simpan' }).click()
  await expect(adminRow).toContainText('30.000')

  await residentPage.reload()
  const updatedRow = residentSection.getByRole('row', { name: new RegExp(uniqueItems) })
  await expect(updatedRow).toContainText('Selesai')
  await expect(updatedRow).toContainText('30.000')

  await residentContext.close()
  await adminContext.close()
})
