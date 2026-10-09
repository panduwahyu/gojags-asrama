import { test, expect } from '@playwright/test'

test('user submits laundry, admin enters cost and completes it, user sees result', async ({ page }, testInfo) => {
  let role = 'user'
  const rows: { id: number; name: string; detail: string; jumlah_stel: number; date: string; status: string; biaya: string | null }[] = []
  await page.route('**/backend/api/asrama/session', route => route.fulfill({ json: { user: { id: 99999, name: 'User uji', email: 'test@example.id', role }, csrfToken: 'test-token' } }))
  await page.route(/\/backend\/api\/asrama\/(user\/)?laundry(?:\?.*)?$/, async route => {
    const request = route.request()
    if (request.method() === 'POST') {
      expect(request.headers()['x-csrf-token']).toBe('test-token')
      const body = request.postDataJSON()
      expect(body.detail).toBe('Kemeja dan celana')
      expect(Number(body.jumlah_stel)).toBe(3)
      rows.push({ id: 1, name: 'User uji', detail: body.detail, jumlah_stel: Number(body.jumlah_stel), date: '2026-10-09', status: 'Diajukan', biaya: null })
      return route.fulfill({ json: { message: 'Pengajuan laundry berhasil dikirim.' }, status: 201 })
    }
    const params = new URL(request.url()).searchParams
    const filtered = rows.filter(row => row.detail.includes(params.get('q') || ''))
    return route.fulfill({ json: { records: { data: filtered, current_page: 1, last_page: 1 } } })
  })
  await page.route('**/backend/api/asrama/laundry/1', async route => {
    expect(route.request().method()).toBe('PATCH')
    expect(route.request().headers()['x-csrf-token']).toBe('test-token')
    const body = route.request().postDataJSON()
    expect(body.status).toBe('Selesai')
    expect(Number(body.biaya)).toBe(25000)
    rows[0]!.status = body.status
    rows[0]!.biaya = '25000.00'
    return route.fulfill({ json: { message: 'Status berhasil diperbarui.' } })
  })
  await page.goto('/user/laundry')
  await page.getByLabel('Pakaian / barang yang dilaundry', { exact: true }).fill('Kemeja dan celana')
  await page.getByLabel('Jumlah stel', { exact: true }).fill('3')
  await page.getByRole('button', { name: 'Ajukan laundry', exact: true }).click()
  await expect(page.locator('tbody')).toContainText('Kemeja dan celana')
  await expect(page.locator('tbody')).toContainText('Diajukan')
  await expect(page.locator('tbody')).toContainText('Belum ditentukan')
  await expect(page.getByRole('button', { name: 'Simpan', exact: true })).toHaveCount(0)
  role = 'admin'
  await page.goto('/laundry')
  await page.getByRole('combobox', { name: 'Status User uji', exact: true }).selectOption('Selesai')
  await page.getByRole('button', { name: 'Simpan', exact: true }).click()
  expect(rows[0]!.status).toBe('Diajukan') // Browser blocks completion until cost is entered.
  await page.getByLabel('Biaya laundry User uji', { exact: true }).fill('25000')
  await page.getByRole('button', { name: 'Simpan', exact: true }).click()
  await expect(page.locator('tbody')).toContainText('Selesai')
  await expect(page.locator('tbody')).toContainText('25.000')
  await expect(page.getByRole('status').filter({ hasText: 'berhasil diperbarui' })).toBeVisible()
  await page.screenshot({ path: testInfo.outputPath('admin-laundry.png'), fullPage: true })
  role = 'user'
  await page.goto('/user/laundry')
  await expect(page.locator('tbody')).toContainText('Selesai')
  await expect(page.locator('tbody')).toContainText('25.000')
  await page.evaluate(() => { document.documentElement.dataset.laundryDocument = 'same' })
  await page.getByLabel('Cari laundry', { exact: true }).fill('Kemeja')
  await page.getByRole('combobox', { name: 'Baris per halaman', exact: true }).selectOption('50')
  await page.getByRole('columnheader', { name: 'Jumlah stel', exact: false }).getByRole('button').click()
  await expect(page.getByRole('columnheader', { name: 'Jumlah stel', exact: false })).toHaveAttribute('aria-sort', 'ascending')
  await expect(page.locator('html')).toHaveAttribute('data-laundry-document', 'same')
  await page.screenshot({ path: testInfo.outputPath('user-laundry.png'), fullPage: true })
  await page.unrouteAll({ behavior: 'wait' })
})
