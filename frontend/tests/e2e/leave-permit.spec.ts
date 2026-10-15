import { expect, test } from '@playwright/test'
import { STATE } from './accounts'

test('penghuni mengajukan izin keluar, mengedit alasan, lalu admin menyetujuinya', async ({ browser }) => {
  const residentContext = await browser.newContext({ storageState: STATE.resident })
  const adminContext = await browser.newContext({ storageState: STATE.admin })
  const residentPage = await residentContext.newPage()
  const adminPage = await adminContext.newPage()

  const uniqueReason = `Izin E2E ${Date.now()}`
  const editedReason = `${uniqueReason} (revisi keperluan dinas)`

  // 1. Penghuni mengajukan izin
  await residentPage.goto('/resident/room')
  const residentSection = residentPage.getByRole('region', { name: 'Izin keluar' })
  await residentSection.getByLabel('Waktu keluar (WIB)').fill('2026-10-15T08:00')
  await residentSection.getByLabel('Waktu kembali (WIB)').fill('2026-10-15T17:00')
  await residentSection.getByLabel('Alasan').fill(uniqueReason)
  await residentSection.getByRole('button', { name: 'Ajukan izin' }).click()

  const pendingRow = residentSection.getByRole('row', { name: new RegExp(uniqueReason) })
  await expect(pendingRow).toBeVisible()

  // 2. Penghuni mengedit alasan izin sebelum ditinjau admin
  await pendingRow.getByRole('button', { name: 'Edit' }).click()
  await residentSection.getByLabel('Alasan').fill(editedReason)
  await residentSection.getByRole('button', { name: 'Simpan perubahan izin' }).click()

  const updatedResidentRow = residentSection.getByRole('row', { name: new RegExp(editedReason) })
  await expect(updatedResidentRow).toBeVisible()

  // 3. Admin meninjau dan menyetujui izin
  await adminPage.goto('/admin/rooms')
  const adminSection = adminPage.getByRole('region', { name: 'Izin keluar' })
  const adminRow = adminSection.getByRole('row', { name: new RegExp(editedReason) })
  await expect(adminRow).toBeVisible()
  await adminRow.getByRole('button', { name: 'Setujui' }).click()
  await expect(adminRow).toContainText('Disetujui')

  // 4. Penghuni memeriksa status yang disetujui (tombol edit tidak lagi muncul)
  await residentPage.reload()
  const approvedRow = residentSection.getByRole('row', { name: new RegExp(editedReason) })
  await expect(approvedRow).toContainText('Disetujui')
  await expect(approvedRow.getByRole('button', { name: 'Edit' })).toHaveCount(0)

  await residentContext.close()
  await adminContext.close()
})

test('admin dapat menolak permohonan izin keluar', async ({ browser }) => {
  const residentContext = await browser.newContext({ storageState: STATE.resident })
  const adminContext = await browser.newContext({ storageState: STATE.admin })
  const residentPage = await residentContext.newPage()
  const adminPage = await adminContext.newPage()

  const rejectReason = `Izin Ditolak E2E ${Date.now()}`

  await residentPage.goto('/resident/room')
  const residentSection = residentPage.getByRole('region', { name: 'Izin keluar' })
  await residentSection.getByLabel('Waktu keluar (WIB)').fill('2026-10-16T19:00')
  await residentSection.getByLabel('Waktu kembali (WIB)').fill('2026-10-16T22:00')
  await residentSection.getByLabel('Alasan').fill(rejectReason)
  await residentSection.getByRole('button', { name: 'Ajukan izin' }).click()

  await adminPage.goto('/admin/rooms')
  const adminSection = adminPage.getByRole('region', { name: 'Izin keluar' })
  const adminRow = adminSection.getByRole('row', { name: new RegExp(rejectReason) })
  await expect(adminRow).toBeVisible()
  await adminRow.getByRole('button', { name: 'Tolak' }).click()
  await expect(adminRow).toContainText('Ditolak')

  await residentPage.reload()
  const rejectedResidentRow = residentSection.getByRole('row', { name: new RegExp(rejectReason) })
  await expect(rejectedResidentRow).toContainText('Ditolak')

  await residentContext.close()
  await adminContext.close()
})
