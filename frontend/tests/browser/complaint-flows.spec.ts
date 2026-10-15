import { expect, test } from '../support/fixtures'
import { MESSAGES } from '../support/messages'
import { mockTable } from '../support/mock-api'
import { sampleComplaint } from '../support/samples'

test('admin menandai keluhan sedang ditangani beserta tanggapan', async ({ asAdmin: page }) => {
  const complaint = sampleComplaint({ id: 60, user_name: 'Siti Aminah', title: 'Kran bocor' })
  await mockTable(page, 'complaints', () => [complaint])
  await page.route('**/api/complaints/60/progress', async (route) => {
    expect(route.request().method()).toBe('PATCH')
    expect(route.request().postDataJSON()).toEqual({ response: 'Teknisi datang besok pagi.' })
    complaint.status = 'in_progress'
    complaint.status_label = 'Sedang ditangani'
    complaint.response = 'Teknisi datang besok pagi.'
    await route.fulfill({ json: { message: MESSAGES.COMPLAINT_IN_PROGRESS } })
  })

  await page.goto('/admin/rooms')
  const section = page.getByRole('region', { name: 'Keluhan asrama' })
  const row = section.getByRole('row', { name: /Siti Aminah/ })

  await row.getByLabel('Tanggapan petugas').fill('Teknisi datang besok pagi.')
  await row.getByRole('button', { name: 'Tandai sedang ditangani' }).click()

  await expect(section.getByRole('status').filter({ hasText: MESSAGES.COMPLAINT_IN_PROGRESS })).toBeVisible()
  const updated = section.getByRole('row', { name: /Siti Aminah/ })
  await expect(updated).toContainText('Sedang ditangani')
  await expect(updated).toContainText('Teknisi datang besok pagi.')
  // Tombol "sedang ditangani" hanya untuk keluhan yang masih Diajukan.
  await expect(updated.getByRole('button', { name: 'Tandai sedang ditangani' })).toHaveCount(0)
})

test('admin menyelesaikan keluhan dengan tanggapan', async ({ asAdmin: page }) => {
  const complaint = sampleComplaint({ id: 61, user_name: 'Rina', status: 'in_progress' })
  await mockTable(page, 'complaints', () => [complaint])
  await page.route('**/api/complaints/61/resolve', async (route) => {
    expect(route.request().postDataJSON()).toEqual({ response: 'AC sudah diganti.' })
    complaint.status = 'resolved'
    complaint.status_label = 'Resolved'
    complaint.response = 'AC sudah diganti.'
    complaint.resolved_at = '2026-10-13T06:00:00.000000Z'
    await route.fulfill({ json: { message: MESSAGES.COMPLAINT_RESOLVED } })
  })

  await page.goto('/admin/rooms')
  const section = page.getByRole('region', { name: 'Keluhan asrama' })
  const row = section.getByRole('row', { name: /Rina/ })

  await row.getByLabel('Tanggapan petugas').fill('AC sudah diganti.')
  await row.getByRole('button', { name: 'Tandai Resolved' }).click()

  await expect(section.getByRole('status').filter({ hasText: MESSAGES.COMPLAINT_RESOLVED })).toBeVisible()
  await expect(section.getByRole('row', { name: /Rina/ })).toContainText('AC sudah diganti.')
})

test('penghuni melihat tanggapan petugas dan dapat membuka kembali keluhan Resolved', async ({ asResident: page }) => {
  const complaint = sampleComplaint({
    id: 62,
    title: 'Lampu redup',
    status: 'resolved',
    response: 'Lampu sudah diganti.',
  })
  await mockTable(page, 'complaints', () => [complaint])
  await page.route('**/api/complaints/62/reopen', async (route) => {
    expect(route.request().method()).toBe('PATCH')
    complaint.status = 'pending'
    complaint.status_label = 'Diajukan'
    complaint.resolved_at = null
    await route.fulfill({ json: { message: MESSAGES.COMPLAINT_REOPENED } })
  })

  await page.goto('/resident/room')
  const section = page.getByRole('region', { name: 'Keluhan asrama' })

  await expect(section.getByRole('row', { name: /Lampu redup/ })).toContainText('Lampu sudah diganti.')
  await section.getByRole('button', { name: 'Buka kembali' }).click()

  await expect(section.getByRole('status').filter({ hasText: MESSAGES.COMPLAINT_REOPENED })).toBeVisible()
  await expect(section.getByRole('row', { name: /Lampu redup/ })).toContainText('Menunggu petugas')
})

test('keluhan tanpa tanggapan menampilkan keterangan kosong', async ({ asResident: page }) => {
  await mockTable(page, 'complaints', () => [sampleComplaint({ id: 63, title: 'Pintu macet' })])

  await page.goto('/resident/room')
  const row = page.getByRole('region', { name: 'Keluhan asrama' }).getByRole('row', { name: /Pintu macet/ })

  await expect(row).toContainText('Belum ada tanggapan')
})
