import { expect, test } from '@playwright/test'
import { STATE } from './accounts'

test('admin menambah menu catering lalu penghuni memberi rating', async ({ browser }) => {
  const adminContext = await browser.newContext({ storageState: STATE.admin })
  const residentContext = await browser.newContext({ storageState: STATE.resident })
  const adminPage = await adminContext.newPage()
  const residentPage = await residentContext.newPage()

  const uniqueProvider = `Penyedia E2E ${Date.now()}`

  await adminPage.goto('/admin/catering')
  await adminPage.getByLabel('Nomor menu').fill('88')
  await adminPage.getByLabel('Penyedia').fill(uniqueProvider)
  await adminPage.getByLabel('Waktu catering (WIB)').fill('2026-10-16T12:00')
  await adminPage.getByRole('button', { name: 'Tambah menu' }).click()

  const adminSection = adminPage.getByRole('region', { name: 'Menu catering' })
  await expect(adminSection.getByRole('row', { name: new RegExp(uniqueProvider) })).toBeVisible()

  await residentPage.goto('/resident/catering')
  const residentSection = residentPage.getByRole('region', { name: 'Menu catering' })
  const residentRow = residentSection.getByRole('row', { name: new RegExp(uniqueProvider) })
  await expect(residentRow).toBeVisible()
  await residentRow.getByLabel(/Rating untuk Menu/).selectOption('5')
  await residentRow.getByRole('button', { name: 'Simpan rating' }).click()
  await expect(residentRow).toContainText('5 / 5 ★')

  await adminPage.reload()
  const updatedAdminRow = adminSection.getByRole('row', { name: new RegExp(uniqueProvider) })
  await expect(updatedAdminRow).toContainText('5 / 5 ★')

  await adminContext.close()
  await residentContext.close()
})
