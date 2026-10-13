import { expect, test } from '@playwright/test'
import { STATE } from './accounts'

test('penghuni mengajukan izin keluar lalu admin menyetujuinya', async ({ browser }) => {
  const residentContext = await browser.newContext({ storageState: STATE.resident })
  const adminContext = await browser.newContext({ storageState: STATE.admin })
  const residentPage = await residentContext.newPage()
  const adminPage = await adminContext.newPage()

  const uniqueReason = `Izin E2E ${Date.now()}`

  await residentPage.goto('/resident/room')
  const residentSection = residentPage.getByRole('region', { name: 'Izin keluar' })
  await residentSection.getByLabel('Waktu keluar (WIB)').fill('2026-10-15T08:00')
  await residentSection.getByLabel('Waktu kembali (WIB)').fill('2026-10-15T17:00')
  await residentSection.getByLabel('Alasan').fill(uniqueReason)
  await residentSection.getByRole('button', { name: 'Ajukan izin' }).click()
  await expect(residentSection.getByRole('row', { name: new RegExp(uniqueReason) })).toBeVisible()

  await adminPage.goto('/admin/rooms')
  const adminSection = adminPage.getByRole('region', { name: 'Izin keluar' })
  const adminRow = adminSection.getByRole('row', { name: new RegExp(uniqueReason) })
  await expect(adminRow).toBeVisible()
  await adminRow.getByRole('button', { name: 'Setujui' }).click()
  await expect(adminRow).toContainText('Disetujui')

  await residentPage.reload()
  const updatedRow = residentSection.getByRole('row', { name: new RegExp(uniqueReason) })
  await expect(updatedRow).toContainText('Disetujui')
  await expect(updatedRow.getByRole('button', { name: 'Edit' })).toHaveCount(0)

  await residentContext.close()
  await adminContext.close()
})
