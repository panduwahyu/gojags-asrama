import { test, expect, type Page } from '@playwright/test'

type Permission = { id: number; user_name: string; waktu_keluar: string; waktu_kembali: string; alasan: string; status: string }
const sample = (id: number, status = 'Diajukan'): Permission => ({ id, user_name: 'User 1', waktu_keluar: '2026-10-10T01:00:00Z', waktu_kembali: '2026-10-10T11:00:00Z', alasan: `Keperluan keluarga ${id}`, status })
async function mockSession(page: Page, role: 'admin' | 'user') {
  await page.route(/\/backend\/api\/asrama\/(user\/)?keluhan(?:\?.*)?$/, route => route.fulfill({ json: { records: { data: [], current_page: 1, last_page: 1 } } }))
  await page.route('**/backend/api/asrama/session', route => route.fulfill({ json: { user: { id: 9999, name: 'User uji', email: 'test@example.id', role }, csrfToken: 'test-token' } }))
  await page.route('**/backend/api/asrama/user/kamar', route => route.fulfill({ json: { room: null, history: [] } }))
  await page.route('**/backend/api/asrama/kamar*', route => route.fulfill({ json: { counts: { kamar: 78 }, monitoring: { Kosong: 78, Assigned: 0, Terisi: 0, Penuh: 0 }, allocation_users: [], records: { data: [], current_page: 1, last_page: 1 } } }))
}

test('user submits and edits pending leave, sees locked decisions, and searches without navigation', async ({ page }, testInfo) => {
  await mockSession(page, 'user')
  let rows = [sample(1, 'Disetujui'), sample(2, 'Ditolak')]
  await page.route('**/backend/api/asrama/user/izin-keluar**', async route => {
    const request = route.request()
    if (request.method() !== 'GET') {
      expect(request.headers()['x-csrf-token']).toBe('test-token')
      const body = request.postDataJSON()
      expect(body.waktu_keluar).toBe('2026-10-10T08:00')
      expect(body.waktu_kembali).toBe('2026-10-10T18:00')
      if (request.method() === 'POST') rows.unshift({ ...sample(3), alasan: body.alasan })
      else { expect(request.method()).toBe('PATCH'); rows[0]!.alasan = body.alasan }
      return route.fulfill({ json: { message: request.method() === 'POST' ? 'Izin keluar berhasil diajukan.' : 'Pengajuan izin berhasil diperbarui.' } })
    }
    const params = new URL(request.url()).searchParams
    const filtered = rows.filter(row => row.alasan.includes(params.get('q') || ''))
    return route.fulfill({ json: { records: { data: filtered, current_page: 1, last_page: 1 } } })
  })
  await page.goto('/user/kamar')
  const section = page.getByRole('region', { name: 'Izin keluar', exact: true })
  await expect(section.getByRole('button', { name: 'Edit', exact: true })).toHaveCount(0)
  await section.getByLabel('Waktu keluar (WIB)', { exact: true }).fill('2026-10-10T08:00')
  await section.getByLabel('Waktu kembali (WIB)', { exact: true }).fill('2026-10-10T18:00')
  await section.getByLabel('Alasan', { exact: true }).fill('Mengunjungi keluarga')
  await section.getByRole('button', { name: 'Ajukan izin', exact: true }).click()
  await expect(page.getByRole('dialog')).toBeVisible()
  await page.getByRole('dialog').getByRole('button', { name: 'OK', exact: true }).click()
  await expect(section.getByRole('button', { name: 'Edit', exact: true })).toHaveCount(1)
  await section.getByRole('button', { name: 'Edit', exact: true }).click()
  await expect(section.getByLabel('Waktu keluar (WIB)', { exact: true })).toHaveValue('2026-10-10T08:00')
  await section.getByLabel('Alasan', { exact: true }).fill('Keluarga diperbarui')
  await section.getByRole('button', { name: 'Simpan perubahan izin', exact: true }).click()
  await expect(page.getByRole('dialog')).toBeVisible()
  await page.getByRole('dialog').getByRole('button', { name: 'OK', exact: true }).click()
  await expect(section.locator('tbody')).toContainText('Keluarga diperbarui')
  await expect(section.locator('tbody')).toContainText('08.00 WIB')
  await section.getByRole('combobox', { name: 'Baris per halaman', exact: true }).selectOption('50')
  let documentLoads = 0
  page.on('request', request => { if (request.isNavigationRequest()) documentLoads++ })
  await section.getByLabel('Cari izin keluar', { exact: true }).fill('diperbarui')
  await expect(section.locator('tbody tr')).toHaveCount(1)
  await section.getByRole('button', { name: 'Status', exact: false }).click()
  await expect(section.locator('th[aria-sort="ascending"]')).toContainText('Status')
  expect(documentLoads).toBe(0)
  await page.screenshot({ path: testInfo.outputPath('user-leave.png'), fullPage: true })
  await page.unrouteAll({ behavior: 'wait' })
})

test('admin approves and rejects leave and cannot edit form fields', async ({ page }, testInfo) => {
  await mockSession(page, 'admin')
  const rows = [sample(1), sample(2)]
  await page.route('**/backend/api/asrama/izin-keluar**', async route => {
    const request = route.request()
    if (request.method() === 'PATCH') {
      expect(request.headers()['x-csrf-token']).toBe('test-token')
      const id = Number(new URL(request.url()).pathname.split('/').pop())
      const body = request.postDataJSON()
      expect(Object.keys(body)).toEqual(['status'])
      rows.find(row => row.id === id)!.status = body.status
      return route.fulfill({ json: { message: `Izin keluar ${body.status.toLowerCase()}.` } })
    }
    return route.fulfill({ json: { records: { data: rows, current_page: 1, last_page: 1 } } })
  })
  await page.goto('/kamar')
  const section = page.getByRole('region', { name: 'Izin keluar', exact: true })
  await expect(section.getByLabel('Alasan', { exact: true })).toHaveCount(0)
  await section.getByRole('button', { name: 'Setujui', exact: true }).first().click()
  await expect(page.getByRole('dialog')).toBeVisible()
  await page.getByRole('dialog').getByRole('button', { name: 'OK', exact: true }).click()
  await expect(section.locator('tbody tr').first()).toContainText('Disetujui')
  await expect(section.getByRole('button', { name: 'Setujui', exact: true })).toHaveCount(1)
  await section.getByRole('button', { name: 'Tolak', exact: true }).click()
  await expect(page.getByRole('dialog')).toBeVisible()
  await page.getByRole('dialog').getByRole('button', { name: 'OK', exact: true }).click()
  await expect(section.locator('tbody tr').last()).toContainText('Ditolak')
  await expect(section.getByRole('button', { name: 'Tolak', exact: true })).toHaveCount(0)
  await page.screenshot({ path: testInfo.outputPath('admin-leave.png'), fullPage: true })
  await page.unrouteAll({ behavior: 'wait' })
})
