import { expect, test } from '../support/fixtures'
import { MESSAGES } from '../support/messages'
import { mockTable } from '../support/mock-api'
import { sampleCateringMenu } from '../support/samples'

test('admin menambah menu catering: permintaan memuat dish_name dan meal_time', async ({ asAdmin: page }) => {
  const menus = [sampleCateringMenu({ menu_number: 1, provider: 'Katering Lama' })]
  await mockTable(page, 'catering-menus', () => menus)
  await page.route('**/api/catering-menus', async (route) => {
    expect(route.request().method()).toBe('POST')
    expect(route.request().postDataJSON()).toEqual({
      menu_number: 7,
      dish_name: 'Ayam Bakar Madu',
      meal_time: 'lunch',
      provider: 'Sari Rasa',
      served_at: '2026-10-13T05:00:00.000Z',
    })
    menus.unshift(sampleCateringMenu({
      menu_number: 7,
      dish_name: 'Ayam Bakar Madu',
      meal_time: 'lunch',
      meal_time_label: 'Siang',
      provider: 'Sari Rasa',
    }))
    await route.fulfill({ status: 201, json: { message: MESSAGES.CATERING_ADDED } })
  })

  await page.goto('/admin/catering')
  await page.getByLabel('Nomor menu').fill('7')
  await page.getByLabel('Nama makanan').fill('Ayam Bakar Madu')
  await page.getByLabel('Waktu makan').selectOption('lunch')
  await page.getByLabel('Penyedia').fill('Sari Rasa')
  await page.getByLabel('Waktu catering (WIB)').fill('2026-10-13T12:00')
  await page.getByRole('button', { name: 'Tambah menu' }).click()

  await expect(page.getByRole('status')).toContainText(MESSAGES.CATERING_ADDED)
  const tableSection = page.getByRole('region', { name: 'Menu catering' })
  const newRow = tableSection.getByRole('row', { name: /Ayam Bakar Madu/ })
  await expect(newRow).toBeVisible()
  await expect(newRow).toContainText('Siang')
})

test('admin menekan Edit pada satu menu: formulir terisi dan permintaan PATCH terkirim', async ({ asAdmin: page }) => {
  const menu = sampleCateringMenu({
    id: 15,
    menu_number: 2,
    dish_name: 'Soto Daging',
    meal_time: 'breakfast',
    meal_time_label: 'Pagi',
    provider: 'Dapur Enak',
  })
  const menus = [menu]
  await mockTable(page, 'catering-menus', () => menus)

  let patchPayload: unknown = null
  await page.route('**/api/catering-menus/15', async (route) => {
    expect(route.request().method()).toBe('PATCH')
    patchPayload = route.request().postDataJSON()
    menu.dish_name = 'Soto Ayam Spesial'
    await route.fulfill({ json: { message: MESSAGES.CATERING_UPDATED } })
  })

  await page.goto('/admin/catering')
  const tableSection = page.getByRole('region', { name: 'Menu catering' })
  await tableSection.getByRole('button', { name: 'Edit' }).click()

  await expect(page.getByLabel('Nama makanan')).toHaveValue('Soto Daging')
  await page.getByLabel('Nama makanan').fill('Soto Ayam Spesial')
  await page.getByRole('button', { name: 'Simpan perubahan' }).click()

  await expect(page.getByRole('status')).toContainText(MESSAGES.CATERING_UPDATED)
  expect(patchPayload).toMatchObject({ dish_name: 'Soto Ayam Spesial' })
})

test('admin menekan Hapus: konfirmasi dua langkah dan permintaan DELETE terkirim', async ({ asAdmin: page }) => {
  const menu = sampleCateringMenu({ id: 22, menu_number: 5, dish_name: 'Gulai Ikan' })
  let menus = [menu]
  await mockTable(page, 'catering-menus', () => menus)

  let deleteCalled = false
  await page.route('**/api/catering-menus/22', async (route) => {
    if (route.request().method() === 'DELETE') {
      deleteCalled = true
      menus = []
      await route.fulfill({ json: { message: MESSAGES.CATERING_DELETED } })
      return
    }
    await route.fallback()
  })

  await page.goto('/admin/catering')
  const tableSection = page.getByRole('region', { name: 'Menu catering' })

  // Klik Hapus -> muncul Ya, hapus dan Batal
  await tableSection.getByRole('button', { name: 'Hapus' }).click()
  await expect(tableSection.getByRole('button', { name: 'Ya, hapus' })).toBeVisible()

  // Klik Batal -> batal hapus, tidak kirim DELETE
  await tableSection.getByRole('button', { name: 'Batal' }).click()
  expect(deleteCalled).toBe(false)
  await expect(tableSection.getByRole('button', { name: 'Hapus' })).toBeVisible()

  // Klik Hapus lagi -> Ya, hapus -> kirim DELETE
  await tableSection.getByRole('button', { name: 'Hapus' }).click()
  await tableSection.getByRole('button', { name: 'Ya, hapus' }).click()

  await expect(page.getByRole('status')).toContainText(MESSAGES.CATERING_DELETED)
  expect(deleteCalled).toBe(true)
})

test('penghuni: menu ber-can_rate false menampilkan label sesuai waktu, can_rate true menampilkan form', async ({
  asResident: page,
}) => {
  const futureMenu = sampleCateringMenu({
    id: 1,
    menu_number: 1,
    dish_name: 'Menu Besok',
    served_at: new Date(Date.now() + 86400000).toISOString(),
    can_rate: false,
  })
  const pastMenu = sampleCateringMenu({
    id: 2,
    menu_number: 2,
    dish_name: 'Menu Kemarin',
    served_at: new Date(Date.now() - 86400000).toISOString(),
    can_rate: false,
  })
  const activeMenu = sampleCateringMenu({
    id: 3,
    menu_number: 3,
    dish_name: 'Menu Hari Ini',
    served_at: new Date().toISOString(),
    can_rate: true,
  })

  await mockTable(page, 'catering-menus', () => [futureMenu, pastMenu, activeMenu])

  await page.goto('/resident/catering')
  const tableSection = page.getByRole('region', { name: 'Menu catering' })

  const rowFuture = tableSection.getByRole('row', { name: /Menu Besok/ })
  await expect(rowFuture).toContainText('Belum dihidangkan')
  await expect(rowFuture.getByRole('button', { name: 'Simpan rating' })).toHaveCount(0)

  const rowPast = tableSection.getByRole('row', { name: /Menu Kemarin/ })
  await expect(rowPast).toContainText('Penilaian ditutup')
  await expect(rowPast.getByRole('button', { name: 'Simpan rating' })).toHaveCount(0)

  const rowActive = tableSection.getByRole('row', { name: /Menu Hari Ini/ })
  await expect(rowActive.getByRole('button', { name: 'Simpan rating' })).toBeVisible()
})

test('penghuni memberi rating menu catering', async ({ asResident: page }) => {
  const menu = sampleCateringMenu({ id: 9, menu_number: 3, provider: 'Dapur Nusantara', can_rate: true })
  await mockTable(page, 'catering-menus', () => [menu])
  await page.route('**/api/catering-menus/9/rating', async (route) => {
    expect(route.request().method()).toBe('PUT')
    expect(route.request().postDataJSON()).toEqual({ rating: 5 })
    menu.my_rating = 5
    menu.rating_average = 5
    menu.rating_count = 1
    await route.fulfill({ json: { message: MESSAGES.CATERING_RATED } })
  })

  await page.goto('/resident/catering')
  const tableSection = page.getByRole('region', { name: 'Menu catering' })
  await tableSection.getByLabel('Rating untuk Menu 3').selectOption('5')
  await tableSection.getByRole('button', { name: 'Simpan rating' }).click()

  await expect(page.getByRole('status')).toContainText(MESSAGES.CATERING_RATED)
  await expect(tableSection.getByRole('row', { name: /5 \/ 5 ★/ })).toBeVisible()
})

test('pesan error server saat rating ditolak tampil di layar', async ({ asResident: page }) => {
  const menu = sampleCateringMenu({ id: 9, menu_number: 3, can_rate: true })
  await mockTable(page, 'catering-menus', () => [menu])
  await page.route('**/api/catering-menus/9/rating', async (route) => {
    await route.fulfill({
      status: 422,
      json: {
        message: MESSAGES.CATERING_RATING_CLOSED,
        errors: { rating: ['Masa penilaian menu ini sudah berakhir.'] },
      },
    })
  })

  await page.goto('/resident/catering')
  const tableSection = page.getByRole('region', { name: 'Menu catering' })
  await tableSection.getByLabel('Rating untuk Menu 3').selectOption('5')
  await tableSection.getByRole('button', { name: 'Simpan rating' }).click()

  await expect(page.getByRole('alert')).toContainText(MESSAGES.CATERING_RATING_CLOSED)
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
  await page.getByLabel('Nama makanan').fill('Nasi Kuning')
  await page.getByLabel('Waktu makan').selectOption('lunch')
  await page.getByLabel('Penyedia').fill('A')
  await page.getByLabel('Waktu catering (WIB)').fill('2026-10-13T12:00')
  await page.getByRole('button', { name: 'Tambah menu' }).click()

  await expect(page.getByRole('alert')).toContainText('Penyedia wajib diisi.')
})
