import { expect, test } from '../support/fixtures'
import { MESSAGES } from '../support/messages'
import { mockTable } from '../support/mock-api'
import { sampleComplaint } from '../support/samples'

test('penghuni mengirim keluhan baru dan tabel diperbarui', async ({ asResident: page }) => {
  const complaints = [sampleComplaint({ status: 'resolved', status_label: 'Resolved' })]
  await mockTable(page, 'complaints', () => complaints)
  await page.route('**/api/complaints', async (route) => {
    expect(route.request().method()).toBe('POST')
    expect(route.request().postDataJSON()).toEqual({
      title: 'Kran bocor',
      body: 'Kran kamar mandi menetes terus.',
    })
    complaints.unshift(sampleComplaint({ title: 'Kran bocor', body: 'Kran kamar mandi menetes terus.' }))
    await route.fulfill({ status: 201, json: { message: MESSAGES.COMPLAINT_SENT } })
  })

  await page.goto('/resident/room')
  const section = page.getByRole('region', { name: 'Keluhan asrama' })
  await section.getByLabel('Judul keluhan').fill('Kran bocor')
  await section.getByLabel('Isi keluhan').fill('Kran kamar mandi menetes terus.')
  await section.getByRole('button', { name: 'Kirim keluhan' }).click()

  await expect(section.getByRole('status')).toContainText('Keluhan berhasil dikirim.')
  await expect(section.getByRole('row', { name: /Kran bocor/ })).toBeVisible()
  await expect(section.getByLabel('Judul keluhan')).toHaveValue('')
})

test('admin menandai keluhan Resolved', async ({ asAdmin: page }) => {
  const complaint = sampleComplaint({ id: 77, user_name: 'Siti Aminah', title: 'AC kurang dingin' })
  await mockTable(page, 'complaints', () => [complaint])
  await page.route('**/api/complaints/77/resolve', async (route) => {
    expect(route.request().method()).toBe('PATCH')
    complaint.status = 'resolved'
    complaint.status_label = 'Resolved'
    complaint.resolved_at = '2026-10-13T06:00:00.000000Z'
    await route.fulfill({ json: { message: MESSAGES.COMPLAINT_RESOLVED } })
  })

  await page.goto('/admin/rooms')
  const section = page.getByRole('region', { name: 'Keluhan asrama' })
  await expect(section.getByRole('row', { name: /Siti Aminah/ })).toBeVisible()
  await section.getByRole('button', { name: 'Tandai Resolved' }).click()

  await expect(section.getByRole('status')).toContainText('berhasil ditandai selesai')
  await expect(section.getByRole('row', { name: /Sudah ditangani/ })).toBeVisible()
})

test('menampilkan pesan error server saat pengiriman keluhan gagal', async ({ asResident: page }) => {
  await page.route('**/api/complaints', route =>
    route.fulfill({
      status: 422,
      json: { errors: { title: ['Judul keluhan wajib diisi.'] } },
    }),
  )

  await page.goto('/resident/room')
  const section = page.getByRole('region', { name: 'Keluhan asrama' })
  await section.getByLabel('Judul keluhan').fill('A')
  await section.getByLabel('Isi keluhan').fill('B')
  await section.getByRole('button', { name: 'Kirim keluhan' }).click()

  await expect(section.getByRole('alert')).toContainText('Judul keluhan wajib diisi.')
})
