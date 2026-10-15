import { expect, test } from '../support/fixtures'
import { MESSAGES } from '../support/messages'
import { CSRF_TOKEN, mockTable } from '../support/mock-api'
import { sampleLeavePermit } from '../support/samples'

test('penghuni mengajukan izin dan waktu dikirim sebagai UTC', async ({ asResident: page }) => {
  const permits = [sampleLeavePermit({ status: 'approved', status_label: 'Disetujui' })]
  await mockTable(page, 'leave-permits', () => permits)
  await page.route('**/api/leave-permits', async (route) => {
    const request = route.request()
    expect(request.method()).toBe('POST')
    expect(request.headers()['x-csrf-token']).toBe(CSRF_TOKEN)
    expect(request.postDataJSON().leaves_at).toBe('2026-10-13T01:00:00.000Z')
    permits.unshift(sampleLeavePermit({ reason: 'Mengunjungi keluarga' }))
    await route.fulfill({ status: 201, json: { message: MESSAGES.PERMIT_SENT } })
  })

  await page.goto('/resident/room')
  const section = page.getByRole('region', { name: 'Izin keluar' })
  await section.getByLabel('Waktu keluar (WIB)').fill('2026-10-13T08:00')
  await section.getByLabel('Waktu kembali (WIB)').fill('2026-10-13T18:00')
  await section.getByLabel('Alasan').fill('Mengunjungi keluarga')
  await section.getByRole('button', { name: 'Ajukan izin' }).click()

  await expect(section.getByRole('status')).toContainText(MESSAGES.PERMIT_SENT)
  await expect(section.getByRole('row', { name: /Mengunjungi keluarga/ })).toBeVisible()
  await expect(section.getByLabel('Alasan')).toHaveValue('')
})

test('penghuni mengedit izin yang masih berstatus Diajukan', async ({ asResident: page }) => {
  const permit = sampleLeavePermit({ id: 42, reason: 'Alasan lama' })
  await mockTable(page, 'leave-permits', () => [permit])
  await page.route('**/api/leave-permits/42', async (route) => {
    expect(route.request().method()).toBe('PATCH')
    expect(route.request().postDataJSON().reason).toBe('Alasan diperbarui')
    permit.reason = 'Alasan diperbarui'
    await route.fulfill({ json: { message: MESSAGES.PERMIT_UPDATED } })
  })

  await page.goto('/resident/room')
  const section = page.getByRole('region', { name: 'Izin keluar' })
  await section.getByRole('button', { name: 'Edit' }).click()
  await section.getByLabel('Alasan').fill('Alasan diperbarui')
  await section.getByRole('button', { name: 'Simpan perubahan izin' }).click()

  await expect(section.getByRole('status')).toContainText(MESSAGES.PERMIT_UPDATED)
  await expect(section.getByRole('row', { name: /Alasan diperbarui/ })).toBeVisible()
})

test('admin meninjau izin keluar dan memperbarui status baris', async ({ asAdmin: page }) => {
  const permit = sampleLeavePermit({ id: 50, user_name: 'Budi Santoso', reason: 'Periksa gigi' })
  await mockTable(page, 'leave-permits', () => [permit])
  await page.route('**/api/leave-permits/50/review', async (route) => {
    expect(route.request().method()).toBe('PATCH')
    expect(route.request().postDataJSON()).toEqual({ status: 'approved' })
    permit.status = 'approved'
    permit.status_label = 'Disetujui'
    await route.fulfill({ json: { message: MESSAGES.PERMIT_APPROVED } })
  })

  await page.goto('/admin/rooms')
  const section = page.getByRole('region', { name: 'Izin keluar' })
  await expect(section.getByRole('row', { name: /Budi Santoso/ })).toBeVisible()
  await section.getByRole('button', { name: 'Setujui' }).click()

  await expect(section.getByRole('status').filter({ hasText: MESSAGES.PERMIT_APPROVED })).toBeVisible()
  // Keputusan masih dapat diubah: Setujui hilang (sudah disetujui), Tolak tetap tersedia.
  const row = section.getByRole('row', { name: /Budi Santoso/ })
  await expect(row.getByRole('button', { name: 'Setujui' })).toHaveCount(0)
  await expect(row.getByRole('button', { name: 'Tolak' })).toBeVisible()
})

test('menampilkan pesan error server saat pengajuan izin gagal', async ({ asResident: page }) => {
  await page.route('**/api/leave-permits', route =>
    route.fulfill({
      status: 422,
      json: {
        errors: { returns_at: ['Waktu kembali harus setelah waktu keluar.'] },
      },
    }),
  )

  await page.goto('/resident/room')
  const section = page.getByRole('region', { name: 'Izin keluar' })
  await section.getByLabel('Waktu keluar (WIB)').fill('2026-10-13T18:00')
  await section.getByLabel('Waktu kembali (WIB)').fill('2026-10-13T18:00')
  await section.getByLabel('Alasan').fill('Tes validasi')
  await section.getByRole('button', { name: 'Ajukan izin' }).click()

  await expect(section.getByRole('alert')).toContainText('Waktu kembali harus setelah waktu keluar.')
})
