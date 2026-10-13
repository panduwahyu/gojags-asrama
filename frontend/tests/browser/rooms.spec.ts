import { expect, test } from '../support/fixtures'
import { sampleResident, sampleRoom } from '../support/samples'

test('admin menyimpan satu profil penghuni tanpa menghapus draf penghuni lain', async ({ asAdmin: page }) => {
  const residents = [
    sampleResident({ id: 1, name: 'Andi Pratama', cohort: null, gender: null, religion: null }),
    sampleResident({ id: 2, name: 'Budi Raharjo', cohort: null, gender: null, religion: null }),
  ]
  await page.route('**/api/residents', route => route.fulfill({ json: { data: residents } }))
  await page.route('**/api/residents/1', async (route) => {
    expect(route.request().method()).toBe('PATCH')
    expect(route.request().postDataJSON()).toEqual({
      cohort: '2026',
      gender: 'male',
      religion: 'Islam',
    })
    await route.fulfill({ json: { message: 'Profil berhasil disimpan.' } })
  })

  await page.goto('/admin/rooms')
  await page.getByText(/Profil user untuk alokasi/).click()

  await page.getByLabel('Angkatan Budi Raharjo').fill('2025')
  await page.getByLabel('Angkatan Andi Pratama').fill('2026')
  await page.getByLabel('Gender Andi Pratama').selectOption('male')
  await page.getByLabel('Agama Andi Pratama').fill('Islam')
  await page.getByRole('button', { name: 'Simpan profil Andi Pratama' }).click()

  await expect(page.getByRole('status')).toContainText('Profil Andi Pratama berhasil disimpan.')
  await expect(page.getByLabel('Angkatan Budi Raharjo')).toHaveValue('2025')
})

test('alokasi otomatis menampilkan ringkasan dan memuat ulang tabel kamar', async ({ asAdmin: page }) => {
  let rooms = [sampleRoom({ id: 1, number: '501', status: 'empty', status_label: 'Kosong' })]
  await page.route('**/api/rooms?*', route =>
    route.fulfill({
      json: {
        data: rooms,
        meta: { current_page: 1, last_page: 1, total: rooms.length },
        monitoring: { empty: 0, assigned: 1, occupied: 0, full: 0 },
      },
    }),
  )
  await page.route('**/api/rooms/allocate', async (route) => {
    expect(route.request().method()).toBe('POST')
    rooms = [
      sampleRoom({
        id: 1,
        number: '501',
        status: 'assigned',
        status_label: 'Assigned',
        assigned_count: 1,
        residents: [{ id: 1, name: 'Andi Pratama', checked_in_at: null }],
      }),
    ]
    await route.fulfill({
      json: {
        message: 'Alokasi selesai: 1 ditempatkan, 0 menunggu kamar sesuai, 0 profil belum lengkap.',
        allocated: [{ id: 1, name: 'Andi Pratama', room: '501', floor: 5 }],
        waiting: [],
        incomplete: [],
      },
    })
  })

  await page.goto('/admin/rooms')
  const allocSection = page.getByRole('region', { name: 'Alokasi kamar otomatis' })
  await allocSection.getByRole('button', { name: 'Alokasikan kamar otomatis' }).click()

  await expect(allocSection.getByRole('status')).toContainText('Alokasi selesai: 1 ditempatkan')
  const tableSection = page.getByRole('region', { name: 'Daftar kamar' })
  await expect(tableSection.getByRole('row', { name: /Andi Pratama/ })).toBeVisible()
})

test('menampilkan pesan error server saat penyimpanan profil gagal', async ({ asAdmin: page }) => {
  await page.route('**/api/residents', route =>
    route.fulfill({ json: { data: [sampleResident({ id: 1, name: 'Andi Pratama' })] } }),
  )
  await page.route('**/api/residents/1', route =>
    route.fulfill({
      status: 422,
      json: { message: 'Profil penghuni yang sudah dialokasikan ke kamar tidak dapat diubah.' },
    }),
  )

  await page.goto('/admin/rooms')
  await page.getByText(/Profil user untuk alokasi/).click()
  await page.getByRole('button', { name: 'Simpan profil Andi Pratama' }).click()

  await expect(page.getByRole('alert')).toContainText(
    'Profil penghuni yang sudah dialokasikan ke kamar tidak dapat diubah.',
  )
})
