import { expect, test } from '../support/fixtures'
import { MESSAGES } from '../support/messages'
import { mockTable } from '../support/mock-api'
import { sampleLeavePermit } from '../support/samples'

test('penghuni membatalkan izin Diajukan lewat konfirmasi dua langkah', async ({ asResident: page }) => {
  const permit = sampleLeavePermit({ id: 50, reason: 'Periksa gigi' })
  await mockTable(page, 'leave-permits', () => [permit])
  let cancelRequests = 0
  await page.route('**/api/leave-permits/50/cancel', async (route) => {
    cancelRequests += 1
    expect(route.request().method()).toBe('PATCH')
    permit.status = 'cancelled'
    permit.status_label = 'Dibatalkan'
    await route.fulfill({ json: { message: MESSAGES.PERMIT_CANCELLED } })
  })

  await page.goto('/resident/room')
  const section = page.getByRole('region', { name: 'Izin keluar' })
  const row = section.getByRole('row', { name: /Periksa gigi/ })

  // Batal pada langkah konfirmasi tidak mengirim apa pun.
  await row.getByRole('button', { name: /Batalkan izin keluar/ }).click()
  await row.getByRole('button', { name: 'Batal', exact: true }).click()
  expect(cancelRequests).toBe(0)

  await row.getByRole('button', { name: /Batalkan izin keluar/ }).click()
  await row.getByRole('button', { name: 'Ya, batalkan' }).click()

  await expect(section.getByRole('status').filter({ hasText: MESSAGES.PERMIT_CANCELLED })).toBeVisible()
  await expect(section.getByRole('row', { name: /Periksa gigi/ })).toContainText('Dibatalkan penghuni')
  expect(cancelRequests).toBe(1)
})

test('admin menolak izin hanya dengan alasan tertulis', async ({ asAdmin: page }) => {
  const permit = sampleLeavePermit({ id: 51, user_name: 'Budi Santoso', reason: 'Acara keluarga' })
  await mockTable(page, 'leave-permits', () => [permit])
  await page.route('**/api/leave-permits/51/review', async (route) => {
    expect(route.request().postDataJSON()).toEqual({ status: 'rejected', rejection_reason: 'Bentrok jadwal kelas' })
    permit.status = 'rejected'
    permit.status_label = 'Ditolak'
    permit.rejection_reason = 'Bentrok jadwal kelas'
    await route.fulfill({ json: { message: MESSAGES.PERMIT_REJECTED } })
  })

  await page.goto('/admin/rooms')
  const section = page.getByRole('region', { name: 'Izin keluar' })
  const row = section.getByRole('row', { name: /Budi Santoso/ })

  await row.getByRole('button', { name: 'Tolak' }).click()
  await expect(row.getByLabel('Alasan penolakan')).toBeVisible()
  await row.getByLabel('Alasan penolakan').fill('Bentrok jadwal kelas')
  await row.getByRole('button', { name: 'Kirim penolakan' }).click()

  await expect(section.getByRole('status').filter({ hasText: MESSAGES.PERMIT_REJECTED })).toBeVisible()
  await expect(section.getByRole('row', { name: /Budi Santoso/ })).toContainText('Alasan penolakan: Bentrok jadwal kelas')
})

test('admin dapat mengubah keputusan: izin ditolak bisa disetujui kembali', async ({ asAdmin: page }) => {
  const permit = sampleLeavePermit({
    id: 52,
    user_name: 'Sari',
    status: 'rejected',
    rejection_reason: 'Salah jadwal',
  })
  await mockTable(page, 'leave-permits', () => [permit])
  await page.route('**/api/leave-permits/52/review', async (route) => {
    expect(route.request().postDataJSON()).toEqual({ status: 'approved' })
    permit.status = 'approved'
    permit.status_label = 'Disetujui'
    permit.rejection_reason = null
    await route.fulfill({ json: { message: MESSAGES.PERMIT_APPROVED } })
  })

  await page.goto('/admin/rooms')
  const section = page.getByRole('region', { name: 'Izin keluar' })
  const row = section.getByRole('row', { name: /Sari/ })

  await expect(row.getByRole('button', { name: 'Tolak' })).toHaveCount(0)
  await row.getByRole('button', { name: 'Setujui' }).click()

  await expect(section.getByRole('status').filter({ hasText: MESSAGES.PERMIT_APPROVED })).toBeVisible()
  await expect(section.getByRole('row', { name: /Sari/ })).not.toContainText('Alasan penolakan')
})

test('izin yang dibatalkan penghuni tidak bisa ditinjau admin', async ({ asAdmin: page }) => {
  await mockTable(page, 'leave-permits', () => [
    sampleLeavePermit({ id: 53, user_name: 'Dewi', status: 'cancelled' }),
  ])

  await page.goto('/admin/rooms')
  const row = page.getByRole('region', { name: 'Izin keluar' }).getByRole('row', { name: /Dewi/ })

  await expect(row).toContainText('Dibatalkan penghuni')
  await expect(row.getByRole('button', { name: 'Setujui' })).toHaveCount(0)
  await expect(row.getByRole('button', { name: 'Tolak' })).toHaveCount(0)
})

test('penghuni yang belum check-in melihat alasan penolakan dari server', async ({ asResident: page }) => {
  await page.route('**/api/leave-permits', route =>
    route.fulfill({
      status: 422,
      json: { message: MESSAGES.NOT_STAYING, errors: { room: [MESSAGES.NOT_STAYING] } },
    }),
  )

  await page.goto('/resident/room')
  const section = page.getByRole('region', { name: 'Izin keluar' })
  await section.getByLabel('Waktu keluar (WIB)').fill('2026-10-20T08:00')
  await section.getByLabel('Waktu kembali (WIB)').fill('2026-10-20T18:00')
  await section.getByLabel('Alasan').fill('Kontrol kesehatan')
  await section.getByRole('button', { name: 'Ajukan izin' }).click()

  await expect(section.getByRole('alert')).toContainText(MESSAGES.NOT_STAYING)
})

test('izin yang bertabrakan dengan izin lain ditolak dengan pesan dari server', async ({ asResident: page }) => {
  await page.route('**/api/leave-permits', route =>
    route.fulfill({
      status: 422,
      json: { message: MESSAGES.PERMIT_OVERLAP, errors: { leaves_at: [MESSAGES.PERMIT_OVERLAP] } },
    }),
  )

  await page.goto('/resident/room')
  const section = page.getByRole('region', { name: 'Izin keluar' })
  await section.getByLabel('Waktu keluar (WIB)').fill('2026-10-20T08:00')
  await section.getByLabel('Waktu kembali (WIB)').fill('2026-10-20T18:00')
  await section.getByLabel('Alasan').fill('Acara keluarga')
  await section.getByRole('button', { name: 'Ajukan izin' }).click()

  await expect(section.getByRole('alert')).toContainText(MESSAGES.PERMIT_OVERLAP)
})
