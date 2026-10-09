import { test, expect, type Page } from '@playwright/test'

async function login(page: Page, blade = false) {
  await page.goto(blade ? 'http://127.0.0.1:8000/login' : '/login')
  await page.getByLabel('Email', { exact: true }).fill('admin@example.id')
  await page.getByLabel('Kata sandi', { exact: true }).fill('admin123')
  await page.getByRole('button', { name: 'Login', exact: true }).click()
  await expect(page).toHaveURL(/\/admin$/)
}

async function mark(page: Page) {
  await page.evaluate(() => { document.documentElement.dataset.tableTest = 'same-document' })
}

async function noReload(page: Page) {
  await expect(page.locator('html')).toHaveAttribute('data-table-test', 'same-document')
}

test('Nuxt rooms search, pagination, and reset keep the document and unsaved profile', async ({ page }) => {
  await login(page)
  await page.route('**/backend/api/asrama/kamar?*', async route => {
    const response = await route.fetch()
    const data = await response.json()
    // Use an editable profile fixture without changing existing room assignments.
    data.allocation_users = [{ id: 999999, name: 'Pandu Wahyu Aji', kamar_id: null, angkatan: null, gender: null, agama: null }]
    await route.fulfill({ response, json: data })
  })
  await page.goto('/kamar')
  await page.getByText('Profil user untuk alokasi', { exact: false }).click()
  await page.getByLabel('Angkatan Pandu Wahyu Aji', { exact: true }).fill('Belum disimpan')
  await mark(page)
  await page.getByRole('button', { name: 'Berikutnya', exact: false }).click()
  await expect(page.getByText('2/8', { exact: true })).toBeVisible()
  await expect(page.getByLabel('Angkatan Pandu Wahyu Aji', { exact: true })).toHaveValue('Belum disimpan')
  await page.getByRole('button', { name: 'Sebelumnya', exact: false }).click()
  await expect(page.getByText('1/8', { exact: true })).toBeVisible()
  await page.getByRole('button', { name: 'Akhir', exact: true }).click()
  await expect(page.getByText('8/8', { exact: true })).toBeVisible()
  await page.getByRole('button', { name: 'Awal', exact: true }).click()
  await page.getByLabel('Baris per halaman', { exact: true }).selectOption('50')
  await expect(page.locator('tbody tr')).toHaveCount(50)
  await expect(page.getByText('1/2', { exact: true })).toBeVisible()
  await page.getByLabel('Baris per halaman', { exact: true }).selectOption('100')
  await expect(page.locator('tbody tr')).toHaveCount(78)
  await expect(page.getByText('1/1', { exact: true })).toBeVisible()
  await expect(page.getByRole('button', { name: 'Akhir', exact: true })).toBeDisabled()
  await page.getByLabel('Baris per halaman', { exact: true }).selectOption('10')
  await page.getByLabel('Cari kamar', { exact: true }).fill('501')
  await expect(page.locator('tbody tr')).toHaveCount(1)
  await expect(page.locator('tbody')).toContainText('501')
  await expect(page.getByText('1/1', { exact: true })).toBeVisible()
  await page.getByRole('button', { name: 'Reset', exact: true }).click()
  await expect(page.locator('tbody tr')).toHaveCount(10)
  await noReload(page)
})

for (const category of ['laundry', 'catering']) {
  test(`Nuxt ${category} filters and paginates without navigation`, async ({ page }) => {
    await login(page)
    // Deterministic sample responses; no service records are written to the database.
    await page.route(`**/backend/api/asrama/${category}*`, async route => {
      const url = new URL(route.request().url())
      const q = url.searchParams.get('q') || ''
      const current = Number(url.searchParams.get('page') || 1)
      const size = Number(url.searchParams.get('per_page') || 10)
      const rows = Array.from({ length: 18 }, (_, index) => ({ id: index + 1, name: `${category === 'catering' ? 'Menu' : 'Pesanan'} ${index + 1}`, detail: 'Keterangan', date: '2026-10-09', jumlah_stel: 2, biaya: null, menu: index + 1, penyedia: 'Dapur Gojags', waktu_catering: '2026-10-09T05:00:00Z', rating_average: null, rating_count: 0, my_rating: null, status: category === 'laundry' ? 'Diajukan' : 'Terjadwal' }))
        .filter(row => row.name.includes(q))
      await route.fulfill({ json: {
        counts: { kamar: 78, laundry: 18, catering: 18 }, statuses: [category === 'laundry' ? 'Menunggu' : 'Terjadwal'],
        records: { data: rows.slice((current - 1) * size, current * size), current_page: current, last_page: Math.max(1, Math.ceil(rows.length / size)) },
      } })
    })
    await page.goto(`/${category}`)
    await expect(page.locator('tbody tr')).toHaveCount(10)
    await mark(page)
    await page.getByRole('button', { name: 'Berikutnya', exact: false }).click()
    await expect(page.getByText('2/2', { exact: true })).toBeVisible()
    await page.getByRole('button', { name: 'Sebelumnya', exact: false }).click()
    await expect(page.getByText('1/2', { exact: true })).toBeVisible()
    const target = category === 'catering' ? 'Menu 18' : 'Pesanan 18'
    await page.getByLabel(`Cari ${category}`, { exact: true }).fill(target)
    await expect(page.locator('tbody tr')).toHaveCount(1)
    await expect(page.locator('tbody')).toContainText(target)
    await page.getByRole('button', { name: 'Cari', exact: true }).click()
    await page.getByRole('button', { name: 'Reset', exact: true }).click()
    await expect(page.locator('tbody tr')).toHaveCount(10)
    await noReload(page)
  })
}

test('Blade room results update without reload, preserve forms, and support browser Back', async ({ page }) => {
  await login(page, true)
  await page.goto('http://127.0.0.1:8000/kamar')
  await page.getByText('Profil user untuk alokasi', { exact: false }).click()
  // Enable only the browser fixture; no profile is submitted or changed in the database.
  await page.locator('fieldset').first().evaluate(element => element.removeAttribute('disabled'))
  await page.locator('input[name="angkatan"]').first().fill('Belum disimpan')
  await mark(page)
  const section = page.locator('[data-async-table]')
  await section.getByRole('link', { name: 'Berikutnya', exact: false }).click()
  await expect(section.locator('.pagination')).toContainText('2/8')
  await page.goBack()
  await expect(section.locator('.pagination')).toContainText('1/8')
  await section.getByRole('link', { name: 'Berikutnya', exact: false }).click()
  await section.getByRole('link', { name: 'Sebelumnya', exact: false }).click()
  await expect(section.locator('.pagination')).toContainText('1/8')
  await section.getByRole('link', { name: 'Akhir', exact: true }).click()
  await expect(section.locator('[data-page-indicator]')).toHaveText('8/8')
  await section.getByRole('link', { name: 'Awal', exact: true }).click()
  await section.getByLabel('Baris per halaman', { exact: true }).selectOption('50')
  await expect(section.locator('tbody tr')).toHaveCount(50)
  await section.getByLabel('Baris per halaman', { exact: true }).selectOption('100')
  await expect(section.locator('tbody tr')).toHaveCount(78)
  await expect(section.locator('[data-page-indicator]')).toHaveText('1/1')
  await section.getByLabel('Baris per halaman', { exact: true }).selectOption('10')
  await page.getByLabel('Cari kamar', { exact: true }).fill('501')
  await expect(section.locator('tbody tr')).toHaveCount(1)
  await expect(section.locator('tbody')).toContainText('501')
  await section.getByRole('link', { name: 'Reset', exact: true }).click()
  await expect(section.locator('tbody tr')).toHaveCount(10)
  await expect(page.locator('input[name="angkatan"]').first()).toHaveValue('Belum disimpan')
  await noReload(page)
})

test('legacy laundry and complaint tables search locally with working pagination', async ({ page }) => {
  await login(page, true)
  await page.goto('http://127.0.0.1:8000/pengumuman')
  await page.evaluate(() => { document.querySelector('#kamar')?.classList.remove('hidden') })
  await mark(page)
  await page.locator('#kamar tbody').evaluate(body => {
    for (let index = 0; index < 15; index++) {
      const row = body.querySelector('tr')!.cloneNode(true) as HTMLTableRowElement
      row.cells[0]!.textContent = `Sample ${index}`
      body.append(row)
    }
  })
  const panel = page.locator('#kamar')
  await expect(panel.locator('tbody tr:visible')).toHaveCount(10)
  await panel.getByRole('button', { name: 'Berikutnya', exact: true }).click()
  await expect(panel.locator('tbody tr:visible')).toHaveCount(10)
  await panel.getByRole('button', { name: 'Akhir', exact: true }).click()
  await expect(panel.locator('tbody tr:visible')).toHaveCount(5)
  await panel.getByRole('button', { name: 'Awal', exact: true }).click()
  await panel.getByLabel('Baris per halaman', { exact: true }).selectOption('50')
  await expect(panel.locator('tbody tr:visible')).toHaveCount(25)
  await panel.getByLabel('Baris per halaman', { exact: true }).selectOption('100')
  await expect(panel.locator('tbody tr:visible')).toHaveCount(25)
  await panel.getByLabel('Baris per halaman', { exact: true }).selectOption('10')
  await panel.getByRole('button', { name: 'Berikutnya', exact: true }).click()
  await panel.getByRole('button', { name: 'Sebelumnya', exact: true }).click()
  await panel.getByRole('searchbox').fill('10/01/2026')
  await expect(panel.locator('tbody tr:visible')).toHaveCount(1)
  await panel.getByRole('button', { name: 'Reset', exact: true }).click()
  await expect(panel.locator('tbody tr:visible')).toHaveCount(10)
  await page.evaluate(() => { document.querySelector('#keluhan')?.classList.remove('hidden') })
  await page.locator('#keluhan').getByRole('searchbox').fill('Lampu')
  await expect(page.locator('#keluhan tbody tr:visible')).toHaveCount(1)
  await noReload(page)
})
