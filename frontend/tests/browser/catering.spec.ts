import { test, expect } from '@playwright/test'

test('admin creates catering and user ratings appear on both tables', async ({ page }, testInfo) => {
  let role = 'admin'
  let ownRating: number | null = null
  const rows: { id: number; menu: number; name: string; detail: string; penyedia: string; waktu_catering: string; rating_average: number | null; rating_count: number; my_rating: number | null }[] = []
  await page.route('**/backend/api/asrama/session', route => route.fulfill({ json: { user: { id: 99999, name: 'Akun uji', email: 'test@example.id', role }, csrfToken: 'test-token' } }))
  await page.route(/\/backend\/api\/asrama\/(user\/)?catering(?:\/\d+\/rating)?(?:\?.*)?$/, async route => {
    const request = route.request()
    if (request.method() === 'POST') {
      expect(request.headers()['x-csrf-token']).toBe('test-token')
      const body = request.postDataJSON()
      if (request.url().endsWith('/rating')) {
        expect(role).toBe('user')
        ownRating = Number(body.rating)
        rows[0]!.rating_average = ownRating
        rows[0]!.rating_count = 1
        return route.fulfill({ json: { message: 'Rating catering berhasil disimpan.' } })
      }
      expect(Number(body.menu)).toBe(2)
      expect(body.penyedia).toBe('Dapur Gojags')
      expect(body.waktu_catering).toBe('2026-10-10T12:30')
      rows.push({ id: 1, menu: 2, name: 'Menu 2', detail: body.penyedia, penyedia: body.penyedia, waktu_catering: '2026-10-10T05:30:00Z', rating_average: null, rating_count: 0, my_rating: null })
      return route.fulfill({ json: { message: 'Menu catering berhasil ditambahkan.' }, status: 201 })
    }
    const params = new URL(request.url()).searchParams
    const data = rows.filter(row => row.penyedia.includes(params.get('q') || '')).map(row => ({ ...row, my_rating: role === 'user' ? ownRating : null }))
    return route.fulfill({ json: { records: { data, current_page: 1, last_page: 1 } } })
  })
  await page.goto('/catering')
  await page.getByLabel('Nomor menu', { exact: true }).fill('2')
  await page.getByLabel('Penyedia', { exact: true }).fill('Dapur Gojags')
  await page.getByLabel('Waktu catering (WIB)', { exact: true }).fill('2026-10-10T12:30')
  await page.getByRole('button', { name: 'Tambah menu', exact: true }).click()
  await expect(page.getByRole('dialog')).toBeVisible()
  await page.getByRole('dialog').getByRole('button', { name: 'OK', exact: true }).click()
  await expect(page.locator('tbody')).toContainText('Menu 2')
  await expect(page.locator('tbody')).toContainText('12.30 WIB')
  await expect(page.locator('tbody')).toContainText('Belum ada rating')
  role = 'user'
  await page.goto('/user/catering')
  await expect(page.getByLabel('Nomor menu', { exact: true })).toHaveCount(0)
  const select = page.getByRole('combobox', { name: 'Rating untuk Menu 2', exact: true })
  await expect(page.getByRole('button', { name: 'Simpan rating', exact: true })).toBeDisabled()
  await select.selectOption('4')
  await page.getByRole('button', { name: 'Simpan rating', exact: true }).click()
  await expect(page.getByRole('dialog')).toBeVisible()
  await page.getByRole('dialog').getByRole('button', { name: 'OK', exact: true }).click()
  await expect(page.locator('tbody tr td').nth(3)).toContainText('4 / 5')
  await expect(page.locator('tbody tr td').nth(5)).toContainText('4 / 5')
  await select.selectOption('2')
  await page.getByRole('button', { name: 'Simpan rating', exact: true }).click()
  await expect(page.getByRole('dialog')).toBeVisible()
  await page.getByRole('dialog').getByRole('button', { name: 'OK', exact: true }).click()
  await expect(page.locator('tbody tr td').nth(3)).toContainText('2 / 5')
  await expect(page.locator('tbody tr td').nth(4)).toHaveText('1')
  await page.screenshot({ path: testInfo.outputPath('user-catering.png'), fullPage: true })
  role = 'admin'
  await page.goto('/catering')
  await expect(page.locator('tbody tr td').nth(3)).toContainText('2 / 5')
  await expect(page.locator('tbody tr td').nth(4)).toHaveText('1')
  await expect(page.getByRole('button', { name: 'Simpan rating', exact: true })).toHaveCount(0)
  await page.evaluate(() => { document.documentElement.dataset.cateringDocument = 'same' })
  await page.getByLabel('Cari catering', { exact: true }).fill('Dapur')
  await page.getByLabel('Baris per halaman', { exact: true }).selectOption('50')
  await page.getByRole('columnheader', { name: 'Rata-rata rating', exact: false }).getByRole('button').click()
  await expect(page.getByRole('columnheader', { name: 'Rata-rata rating', exact: false })).toHaveAttribute('aria-sort', 'ascending')
  await expect(page.locator('html')).toHaveAttribute('data-catering-document', 'same')
  await page.screenshot({ path: testInfo.outputPath('admin-catering.png'), fullPage: true })
  await page.unrouteAll({ behavior: 'wait' })
})
