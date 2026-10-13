import { expect, test } from '../support/fixtures'
import { mockTable } from '../support/mock-api'
import { sampleCateringMenu } from '../support/samples'

test('admin menambah menu catering dan waktu dikirim dalam UTC', async ({ asAdmin: page }) => {
  const menus = [sampleCateringMenu({ menu_number: 1, provider: 'Katering Lama' })]
  await mockTable(page, 'catering-menus', () => menus)
  await page.route('**/api/catering-menus', async (route) => {
    expect(route.request().method()).toBe('POST')
    expect(route.request().postDataJSON()).toEqual({
      menu_number: 7,
      provider: 'Sari Rasa',
      served_at: '2026-10-13T05:00:00.000Z',
    })
    menus.unshift(sampleCateringMenu({ menu_number: 7, provider: 'Sari Rasa' }))
    await route.fulfill({ status: 201, json: { message: 'Menu catering berhasil ditambahkan.' } })
  })

  await page.goto('/admin/catering')
  await page.getByLabel('Nomor menu').fill('7')
  await page.getByLabel('Penyedia').fill('Sari Rasa')
  await page.getByLabel('Waktu catering (WIB)').fill('2026-10-13T12:00')
  await page.getByRole('button', { name: 'Tambah menu' }).click()

  await expect(page.getByRole('status')).toContainText('Menu catering berhasil ditambahkan.')
  const tableSection = page.getByRole('region', { name: 'Menu catering' })
  await expect(tableSection.getByRole('row', { name: /Sari Rasa/ })).toBeVisible()
})

test('penghuni memberi rating menu catering', async ({ asResident: page }) => {
  const menu = sampleCateringMenu({ id: 9, menu_number: 3, provider: 'Dapur Nusantara' })
  await mockTable(page, 'catering-menus', () => [menu])
  await page.route('**/api/catering-menus/9/rating', async (route) => {
    expect(route.request().method()).toBe('PUT')
    expect(route.request().postDataJSON()).toEqual({ rating: 5 })
    menu.my_rating = 5
    menu.rating_average = 5
    menu.rating_count = 1
    await route.fulfill({ json: { message: 'Rating catering berhasil disimpan.' } })
  })

  await page.goto('/resident/catering')
  const tableSection = page.getByRole('region', { name: 'Menu catering' })
  await tableSection.getByLabel('Rating untuk Menu 3').selectOption('5')
  await tableSection.getByRole('button', { name: 'Simpan rating' }).click()

  await expect(page.getByRole('status')).toContainText('Rating catering berhasil disimpan.')
  await expect(tableSection.getByRole('row', { name: /5 \/ 5 ★/ })).toBeVisible()
})

test('menampilkan pesan error server saat penambahan menu gagal', async ({ asAdmin: page }) => {
  await page.route('**/api/catering-menus', route =>
    route.fulfill({
      status: 422,
      json: { errors: { provider: ['Penyedia wajib diisi.'] } },
    }),
  )

  await page.goto('/admin/catering')
  await page.getByLabel('Nomor menu').fill('1')
  await page.getByLabel('Penyedia').fill('A')
  await page.getByLabel('Waktu catering (WIB)').fill('2026-10-13T12:00')
  await page.getByRole('button', { name: 'Tambah menu' }).click()

  await expect(page.getByRole('alert')).toContainText('Penyedia wajib diisi.')
})
