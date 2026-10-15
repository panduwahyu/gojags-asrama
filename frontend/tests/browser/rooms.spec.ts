import { expect, test } from '../support/fixtures'
import { MESSAGES, allocationSummary } from '../support/messages'
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
      religion: 'islam',
    })
    await route.fulfill({ json: { message: MESSAGES.PROFILE_SAVED, warnings: [] } })
  })

  await page.goto('/admin/rooms')
  await page.getByText(/Profil user untuk alokasi/).click()

  await page.getByLabel('Angkatan Budi Raharjo').fill('2025')
  await page.getByLabel('Angkatan Andi Pratama').fill('2026')
  await page.getByLabel('Gender Andi Pratama').selectOption('male')
  await page.getByLabel('Agama Andi Pratama').selectOption('islam')
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
        monitoring: { empty: 0, assigned: 1, occupied: 0, full: 0, inactive: 0 },
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
        message: allocationSummary(1, 0, 0),
        allocated: [{ id: 1, name: 'Andi Pratama', room: '501', floor: 5, notes: [] }],
        waiting: [],
        incomplete: [],
      },
    })
  })

  await page.goto('/admin/rooms')
  const allocSection = page.getByRole('region', { name: 'Alokasi kamar otomatis' })
  await allocSection.getByRole('button', { name: 'Alokasikan kamar otomatis' }).click()

  await expect(allocSection.getByRole('status')).toContainText(allocationSummary(1, 0, 0))
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
      json: { message: MESSAGES.COHORT_FORMAT },
    }),
  )

  await page.goto('/admin/rooms')
  await page.getByText(/Profil user untuk alokasi/).click()
  await page.getByRole('button', { name: 'Simpan profil Andi Pratama' }).click()

  await expect(page.getByRole('alert')).toContainText(MESSAGES.COHORT_FORMAT)
})

test('admin menonaktifkan kamar dan kartu monitoring Nonaktif ikut berubah', async ({ asAdmin: page }) => {
  let rooms = [sampleRoom({ id: 1, number: '501', status: 'empty', status_label: 'Kosong' })]
  let inactive = 0
  await page.route('**/api/rooms?*', route =>
    route.fulfill({
      json: {
        data: rooms,
        meta: { current_page: 1, last_page: 1, total: rooms.length },
        monitoring: { empty: 1 - inactive, assigned: 0, occupied: 0, full: 0, inactive },
      },
    }),
  )
  await page.route('**/api/rooms/1', async (route) => {
    expect(route.request().method()).toBe('PATCH')
    expect(route.request().postDataJSON()).toEqual({ is_active: false })
    inactive = 1
    rooms = [sampleRoom({ id: 1, number: '501', status: 'inactive', is_active: false })]
    await route.fulfill({ json: { message: MESSAGES.ROOM_DEACTIVATED } })
  })

  await page.goto('/admin/rooms')
  const monitoring = page.getByRole('region', { name: 'Monitoring kamar' })
  await expect(monitoring).toContainText('Nonaktif')

  const tableSection = page.getByRole('region', { name: 'Daftar kamar' })
  await tableSection.getByRole('button', { name: 'Nonaktifkan kamar 501' }).click()

  await expect(page.getByRole('status').filter({ hasText: MESSAGES.ROOM_DEACTIVATED })).toBeVisible()
  await expect(tableSection.getByRole('row', { name: /501/ })).toContainText('Nonaktif')
  await expect(tableSection.getByRole('button', { name: 'Aktifkan kamar 501' })).toBeVisible()
})

test('kamar berpenghuni tidak bisa dinonaktifkan: pesan server tampil', async ({ asAdmin: page }) => {
  await page.route('**/api/rooms?*', route =>
    route.fulfill({
      json: {
        data: [sampleRoom({ id: 2, number: '502', status: 'assigned', assigned_count: 1 })],
        meta: { current_page: 1, last_page: 1, total: 1 },
        monitoring: { empty: 0, assigned: 1, occupied: 0, full: 0, inactive: 0 },
      },
    }),
  )
  await page.route('**/api/rooms/2', route =>
    route.fulfill({
      status: 422,
      json: { message: MESSAGES.ROOM_HAS_RESIDENTS, errors: { is_active: [MESSAGES.ROOM_HAS_RESIDENTS] } },
    }),
  )

  await page.goto('/admin/rooms')
  await page.getByRole('button', { name: 'Nonaktifkan kamar 502' }).click()

  await expect(page.getByRole('alert').filter({ hasText: MESSAGES.ROOM_HAS_RESIDENTS })).toBeVisible()
})

test('profil boleh diperbaiki setelah dialokasikan dan peringatan teman sekamar tampil', async ({ asAdmin: page }) => {
  const residents = [sampleResident({ id: 1, name: 'Andi Pratama', room_id: 5, room_number: '501' })]
  await page.route('**/api/residents', route => route.fulfill({ json: { data: residents } }))
  await page.route('**/api/residents/1', async (route) => {
    expect(route.request().method()).toBe('PATCH')
    await route.fulfill({
      json: { message: MESSAGES.PROFILE_SAVED, warnings: ['Beda agama dengan teman sekamar.'] },
    })
  })

  await page.goto('/admin/rooms')
  await page.getByText(/Profil user untuk alokasi/).click()

  // Penghuni sudah berkamar tetapi isiannya tidak terkunci.
  await expect(page.getByLabel('Angkatan Andi Pratama')).toBeEnabled()
  await page.getByLabel('Agama Andi Pratama').selectOption('hindu')
  await page.getByRole('button', { name: 'Simpan profil Andi Pratama' }).click()

  const warning = page.getByRole('status').filter({ hasText: 'profil tidak lagi cocok dengan teman sekamar' })
  await expect(warning).toContainText('Beda agama dengan teman sekamar.')
})

test('daftar profil ikut dimuat ulang tiap 15 detik tanpa menghapus isian penghuni lain', async ({ asAdmin: page }) => {
  await page.clock.install()
  let residents = [
    sampleResident({ id: 1, name: 'Andi Pratama', room_id: 5, room_number: '501', checked_in_at: '2026-10-13T02:00:00.000000Z' }),
    sampleResident({ id: 2, name: 'Budi Raharjo' }),
  ]
  await page.route('**/api/residents', route => route.fulfill({ json: { data: residents } }))

  await page.goto('/admin/rooms')
  await page.getByText(/Profil user untuk alokasi/).click()
  await expect(page.getByText('Sudah dialokasikan')).toBeVisible()
  await page.getByLabel('Angkatan Budi Raharjo').fill('2030')

  // Andi check-out: slotnya lepas, sementara ketikan pada formulir Budi belum disimpan.
  residents = [
    sampleResident({ id: 1, name: 'Andi Pratama' }),
    sampleResident({ id: 2, name: 'Budi Raharjo' }),
  ]
  await page.clock.fastForward(15_000)

  await expect(page.getByText('Sudah dialokasikan')).toHaveCount(0)
  await expect(page.getByLabel('Angkatan Budi Raharjo')).toHaveValue('2030')
})
