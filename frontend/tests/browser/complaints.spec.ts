import { test, expect } from '@playwright/test'

test('room complaints go from user submission to admin resolution and user status', async ({ page }, testInfo) => {
  let role = 'user'
  const rows: { id: number; user_name: string; judul: string; isi: string; status: string; created_at: string; resolved_at: string | null }[] = []
  await page.route('**/backend/api/asrama/session', route => route.fulfill({ json: { user: { id: 99999, name: 'User uji', email: 'test@example.id', role }, csrfToken: 'test-token' } }))
  await page.route('**/backend/api/asrama/user/kamar', route => route.fulfill({ json: { room: null, history: [] } }))
  await page.route('**/backend/api/asrama/kamar*', route => route.fulfill({ json: { counts: { kamar: 78 }, monitoring: { Kosong: 78, Assigned: 0, Terisi: 0, Penuh: 0 }, allocation_users: [], records: { data: [], current_page: 1, last_page: 8 } } }))
  await page.route(/\/backend\/api\/asrama\/(user\/)?izin-keluar(?:\?.*)?$/, route => route.fulfill({ json: { records: { data: [], current_page: 1, last_page: 1 } } }))
  await page.route(/\/backend\/api\/asrama\/(user\/)?keluhan(?:\/\d+)?(?:\?.*)?$/, async route => {
    const request = route.request()
    if (request.method() !== 'GET') {
      expect(request.headers()['x-csrf-token']).toBe('test-token')
      const body = request.postDataJSON()
      if (request.method() === 'POST') {
        expect(role).toBe('user')
        expect(body).toEqual({ judul: 'Kran bocor', isi: 'Kran kamar 501 bocor.' })
        rows.push({ id: 1, user_name: 'User uji', ...body, status: 'Diajukan', created_at: '2026-10-10T04:00:00Z', resolved_at: null })
        return route.fulfill({ json: { message: 'Keluhan asrama berhasil dikirim.' }, status: 201 })
      }
      expect(request.method()).toBe('PATCH')
      expect(role).toBe('admin')
      expect(body).toEqual({ status: 'Resolved' })
      rows[0]!.status = 'Resolved'; rows[0]!.resolved_at = '2026-10-10T05:00:00Z'
      return route.fulfill({ json: { message: 'Keluhan ditandai Resolved.' } })
    }
    const params = new URL(request.url()).searchParams
    const data = rows.filter(row => row.judul.includes(params.get('q') || ''))
    return route.fulfill({ json: { records: { data, current_page: 1, last_page: 1 } } })
  })
  await page.goto('/user/kamar')
  const section = page.getByRole('region', { name: 'Keluhan asrama', exact: true })
  await section.getByLabel('Judul keluhan', { exact: true }).fill('Kran bocor')
  await section.getByLabel('Isi keluhan', { exact: true }).fill('Kran kamar 501 bocor.')
  await section.getByRole('button', { name: 'Kirim keluhan', exact: true }).click()
  await expect(page.getByRole('dialog')).toBeVisible()
  await page.getByRole('dialog').getByRole('button', { name: 'OK', exact: true }).click()
  await expect(section.locator('tbody')).toContainText('Diajukan')
  await expect(section.locator('tbody')).toContainText('11.00 WIB')
  await expect(section.getByRole('button', { name: 'Tandai Resolved', exact: true })).toHaveCount(0)
  await expect(section.getByLabel('Judul keluhan', { exact: true })).toHaveValue('')
  role = 'admin'
  await page.goto('/kamar')
  await expect(section.locator('tbody')).toContainText('User uji')
  await expect(section.getByLabel('Judul keluhan', { exact: true })).toHaveCount(0)
  await section.getByRole('button', { name: 'Tandai Resolved', exact: true }).click()
  await expect(page.getByRole('dialog')).toBeVisible()
  await page.getByRole('dialog').getByRole('button', { name: 'OK', exact: true }).click()
  await expect(section.locator('tbody')).toContainText('Resolved')
  await expect(section.locator('tbody')).toContainText('12.00 WIB')
  await expect(section.getByRole('button', { name: 'Tandai Resolved', exact: true })).toHaveCount(0)
  await page.evaluate(() => { document.documentElement.dataset.complaintDocument = 'same' })
  await section.getByLabel('Cari keluhan asrama', { exact: true }).fill('Kran')
  await section.getByLabel('Baris per halaman', { exact: true }).selectOption('50')
  await section.getByRole('columnheader', { name: 'Status', exact: false }).getByRole('button').click()
  await expect(section.getByRole('columnheader', { name: 'Status', exact: false })).toHaveAttribute('aria-sort', 'ascending')
  await expect(page.getByLabel('Cari kamar', { exact: true })).toHaveValue('')
  await expect(page.getByRole('region', { name: 'Izin keluar', exact: true }).getByLabel('Baris per halaman', { exact: true })).toHaveValue('10')
  await expect(page.locator('html')).toHaveAttribute('data-complaint-document', 'same')
  await section.screenshot({ path: testInfo.outputPath('admin-complaints.png') })
  role = 'user'
  await page.goto('/user/kamar')
  await expect(section.locator('tbody')).toContainText('Resolved')
  await expect(section.locator('tbody')).toContainText('12.00 WIB')
  await section.screenshot({ path: testInfo.outputPath('user-complaints.png') })
  await page.unrouteAll({ behavior: 'wait' })
})
