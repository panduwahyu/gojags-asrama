import type { Page } from '@playwright/test'
import { expect, test } from '../support/fixtures'
import { MESSAGES, importSummary } from '../support/messages'
import { sampleResident, sampleRoom } from '../support/samples'

type Resident = ReturnType<typeof sampleResident>

async function mockPage(page: Page, residents: () => Resident[], rooms = [sampleRoom({ id: 9, number: '501' })]) {
  await page.route('**/api/residents', route => route.fulfill({ json: { data: residents() } }))
  await page.route('**/api/rooms?*', route =>
    route.fulfill({
      json: {
        data: rooms,
        meta: { current_page: 1, last_page: 1, total: rooms.length },
        monitoring: { empty: rooms.length, assigned: 0, occupied: 0, full: 0, inactive: 0 },
      },
    }),
  )
}

test('admin membuat akun penghuni dan sandi awal hanya tampil sekali', async ({ asAdmin: page }) => {
  const residents: Resident[] = []
  await mockPage(page, () => residents)
  await page.route('**/api/residents', async (route) => {
    if (route.request().method() !== 'POST') {
      await route.fallback()
      return
    }
    expect(route.request().postDataJSON()).toEqual({
      name: 'Budi Santoso',
      email: 'budi@example.id',
      cohort: '64',
      gender: 'male',
      religion: 'islam',
    })
    residents.push(sampleResident({ id: 5, name: 'Budi Santoso', email: 'budi@example.id' }))
    await route.fulfill({
      status: 201,
      json: {
        message: MESSAGES.RESIDENT_CREATED,
        data: { id: 5, name: 'Budi Santoso', email: 'budi@example.id', password: 'Rahasia1234' },
      },
    })
  })

  await page.goto('/admin/residents')
  const form = page.getByRole('region', { name: 'Tambah akun penghuni' })
  await form.getByLabel('Nama lengkap').fill('Budi Santoso')
  await form.getByLabel('Email').fill('budi@example.id')
  await form.getByLabel('Angkatan').fill('64')
  await form.getByLabel('Jenis kelamin').selectOption('male')
  await form.getByLabel('Agama').selectOption('islam')
  await form.getByRole('button', { name: 'Buat akun penghuni' }).click()

  await expect(page.getByRole('status').filter({ hasText: MESSAGES.RESIDENT_CREATED })).toBeVisible()
  const credentials = page.getByRole('region', { name: 'Sandi awal akun penghuni' })
  await expect(credentials).toContainText('Rahasia1234')
  await expect(page.getByRole('region', { name: 'Daftar penghuni' })).toContainText('Budi Santoso')

  await credentials.getByRole('button', { name: 'Sembunyikan' }).click()
  await expect(page.getByText('Rahasia1234')).toHaveCount(0)
})

test('admin mengimpor akun dari berkas CSV dan menerima sandi awal tiap akun', async ({ asAdmin: page }) => {
  await mockPage(page, () => [])
  await page.route('**/api/residents/import', async (route) => {
    const request = route.request()
    expect(request.method()).toBe('POST')
    expect(request.headers()['content-type']).toContain('multipart/form-data')
    expect(request.postData()).toContain('penghuni.csv')
    expect(request.postData()).toContain('Andi,andi@example.id')
    await route.fulfill({
      status: 201,
      json: {
        message: importSummary(2),
        data: [
          { name: 'Andi', email: 'andi@example.id', password: 'Sandi11111' },
          { name: 'Sari', email: 'sari@example.id', password: 'Sandi22222' },
        ],
      },
    })
  })

  await page.goto('/admin/residents')
  const section = page.getByRole('region', { name: 'Impor akun penghuni' })
  await section.getByLabel('Berkas penghuni').setInputFiles({
    name: 'penghuni.csv',
    mimeType: 'text/csv',
    buffer: Buffer.from('Nama,Email,Angkatan,Jenis Kelamin,Agama\nAndi,andi@example.id,64,L,Islam\nSari,sari@example.id,64,P,Kristen\n'),
  })
  await section.getByRole('button', { name: 'Impor penghuni' }).click()

  await expect(section.getByRole('status').filter({ hasText: importSummary(2) })).toBeVisible()
  const credentials = page.getByRole('region', { name: 'Sandi awal akun penghuni' })
  await expect(credentials).toContainText('Sandi11111')
  await expect(credentials).toContainText('Sandi22222')
})

test('impor yang ditolak menampilkan galat per baris dan tidak menampilkan sandi', async ({ asAdmin: page }) => {
  await mockPage(page, () => [])
  await page.route('**/api/residents/import', route =>
    route.fulfill({
      status: 422,
      json: {
        message: 'Baris 3: Email sudah terdaftar.',
        errors: { file: ['Baris 3: Email sudah terdaftar.', 'Baris 4: Jenis kelamin harus L/P (atau Laki-laki/Perempuan).'] },
      },
    }),
  )

  await page.goto('/admin/residents')
  const section = page.getByRole('region', { name: 'Impor akun penghuni' })
  await section.getByLabel('Berkas penghuni').setInputFiles({
    name: 'penghuni.csv',
    mimeType: 'text/csv',
    buffer: Buffer.from('Nama,Email,Angkatan,Jenis Kelamin,Agama\nA,a@example.id,64,X,Islam\n'),
  })
  await section.getByRole('button', { name: 'Impor penghuni' }).click()

  const alert = section.getByRole('alert')
  await expect(alert).toContainText('Impor dibatalkan')
  await expect(alert.getByRole('listitem')).toHaveCount(2)
  await expect(alert).toContainText('Baris 3: Email sudah terdaftar.')
  await expect(page.getByRole('region', { name: 'Sandi awal akun penghuni' })).toHaveCount(0)
})

test('penempatan manual: peringatan perlu konfirmasi lalu dikirim ulang dengan confirm', async ({ asAdmin: page }) => {
  const residents = [sampleResident({ id: 1, name: 'Andi Pratama' })]
  await mockPage(page, () => residents, [sampleRoom({ id: 9, number: '501', assigned_count: 1 })])
  const bodies: Record<string, unknown>[] = []
  await page.route('**/api/rooms/9/assign', async (route) => {
    const body = route.request().postDataJSON() as Record<string, unknown>
    bodies.push(body)
    if (!body.confirm) {
      await route.fulfill({
        status: 409,
        json: {
          message: MESSAGES.PLACEMENT_NEEDS_CONFIRMATION,
          warnings: ['Beda agama dengan teman sekamar.', 'Beda angkatan dengan teman sekamar.'],
        },
      })
      return
    }
    residents[0] = sampleResident({ id: 1, name: 'Andi Pratama', room_id: 9, room_number: '501' })
    await route.fulfill({ json: { message: 'Andi Pratama ditempatkan di kamar 501.', warnings: ['Beda agama dengan teman sekamar.'] } })
  })

  await page.goto('/admin/residents')
  const section = page.getByRole('region', { name: 'Penempatan manual' })
  await section.getByLabel('Penghuni').selectOption({ index: 1 })
  await section.getByLabel('Kamar tujuan').selectOption({ index: 1 })
  await section.getByRole('button', { name: 'Tempatkan penghuni' }).click()

  const warning = section.getByRole('alert')
  await expect(warning).toContainText('Beda agama dengan teman sekamar.')
  await expect(warning).toContainText('Beda angkatan dengan teman sekamar.')
  expect(bodies).toEqual([{ user_id: 1, confirm: false }])

  await warning.getByRole('button', { name: 'Tetap lanjutkan' }).click()

  await expect(section.getByRole('status')).toContainText('Andi Pratama ditempatkan di kamar 501.')
  expect(bodies).toEqual([{ user_id: 1, confirm: false }, { user_id: 1, confirm: true }])
  await expect(page.getByRole('region', { name: 'Daftar penghuni' }).getByRole('row', { name: /Andi Pratama/ }))
    .toContainText('Kamar 501')
})

test('penempatan manual: membatalkan peringatan tidak mengirim konfirmasi', async ({ asAdmin: page }) => {
  await mockPage(page, () => [sampleResident({ id: 1, name: 'Andi Pratama' })])
  let calls = 0
  await page.route('**/api/rooms/9/assign', async (route) => {
    calls += 1
    await route.fulfill({
      status: 409,
      json: { message: MESSAGES.PLACEMENT_NEEDS_CONFIRMATION, warnings: ['Beda agama dengan teman sekamar.'] },
    })
  })

  await page.goto('/admin/residents')
  const section = page.getByRole('region', { name: 'Penempatan manual' })
  await section.getByLabel('Penghuni').selectOption({ index: 1 })
  await section.getByLabel('Kamar tujuan').selectOption({ index: 1 })
  await section.getByRole('button', { name: 'Tempatkan penghuni' }).click()
  await section.getByRole('alert').getByRole('button', { name: 'Batal' }).click()

  await expect(section.getByRole('alert')).toHaveCount(0)
  expect(calls).toBe(1)
})

test('penempatan manual: penghuni berkamar dipindahkan lewat endpoint move', async ({ asAdmin: page }) => {
  await mockPage(page, () => [sampleResident({ id: 2, name: 'Budi', room_id: 8, room_number: '502' })])
  let moved: unknown = null
  await page.route('**/api/rooms/9/move', async (route) => {
    moved = route.request().postDataJSON()
    await route.fulfill({ json: { message: 'Budi dipindahkan ke kamar 501.', warnings: [] } })
  })

  await page.goto('/admin/residents')
  const section = page.getByRole('region', { name: 'Penempatan manual' })
  await section.getByLabel('Penghuni').selectOption({ index: 1 })
  await section.getByLabel('Kamar tujuan').selectOption({ index: 1 })
  await expect(section.getByRole('button', { name: 'Pindahkan penghuni' })).toBeVisible()
  await section.getByRole('button', { name: 'Pindahkan penghuni' }).click()

  await expect(section.getByRole('status')).toContainText('Budi dipindahkan ke kamar 501.')
  expect(moved).toEqual({ user_id: 2, confirm: false })
})

test('penghuni yang sudah check-in tidak bisa dipilih untuk dipindahkan', async ({ asAdmin: page }) => {
  await mockPage(page, () => [
    sampleResident({ id: 3, name: 'Citra', room_id: 8, room_number: '502', checked_in_at: '2026-10-13T02:00:00.000000Z' }),
  ])

  await page.goto('/admin/residents')
  const section = page.getByRole('region', { name: 'Penempatan manual' })

  await expect(section.getByLabel('Penghuni').locator('option', { hasText: 'Citra' })).toBeDisabled()
})

test('mencabut alokasi dan check-out atas nama penghuni meminta konfirmasi', async ({ asAdmin: page }) => {
  const residents = [
    sampleResident({ id: 11, name: 'Dewi', room_id: 8, room_number: '502' }),
    sampleResident({ id: 12, name: 'Eko', room_id: 7, room_number: '503', checked_in_at: '2026-10-13T02:00:00.000000Z' }),
  ]
  await mockPage(page, () => residents)
  const calls: string[] = []
  await page.route('**/api/residents/11/room', async (route) => {
    calls.push(`${route.request().method()} 11/room`)
    residents[0] = sampleResident({ id: 11, name: 'Dewi' })
    await route.fulfill({ json: { message: MESSAGES.UNASSIGNED } })
  })
  await page.route('**/api/residents/12/check-out', async (route) => {
    calls.push(`${route.request().method()} 12/check-out`)
    residents[1] = sampleResident({ id: 12, name: 'Eko', can_cancel_check_out: true })
    await route.fulfill({ json: { message: MESSAGES.CHECK_OUT_SUCCESS } })
  })

  await page.goto('/admin/residents')
  const table = page.getByRole('region', { name: 'Daftar penghuni' })

  await table.getByRole('button', { name: 'Cabut alokasi Dewi' }).click()
  await table.getByRole('button', { name: 'Batal' }).click()
  expect(calls).toEqual([])

  await table.getByRole('button', { name: 'Cabut alokasi Dewi' }).click()
  await table.getByRole('button', { name: 'Ya, cabut' }).click()
  await expect(page.getByRole('status').filter({ hasText: MESSAGES.UNASSIGNED })).toBeVisible()

  await table.getByRole('button', { name: 'Check-out Eko' }).click()
  await table.getByRole('button', { name: 'Ya, check-out' }).click()
  await expect(page.getByRole('status').filter({ hasText: MESSAGES.CHECK_OUT_SUCCESS })).toBeVisible()
  expect(calls).toEqual(['DELETE 11/room', 'POST 12/check-out'])
  await expect(table.getByRole('row', { name: /Eko/ }).getByRole('button', { name: 'Batalkan check-out Eko' })).toBeVisible()
})

test('admin membatalkan check-out yang keliru', async ({ asAdmin: page }) => {
  const residents = [sampleResident({ id: 21, name: 'Fajar', can_cancel_check_out: true })]
  await mockPage(page, () => residents)
  await page.route('**/api/residents/21/cancel-check-out', async (route) => {
    expect(route.request().method()).toBe('POST')
    residents[0] = sampleResident({ id: 21, name: 'Fajar', room_id: 8, room_number: '502', checked_in_at: '2026-10-13T02:00:00.000000Z' })
    await route.fulfill({ json: { message: MESSAGES.CHECK_OUT_CANCELLED } })
  })

  await page.goto('/admin/residents')
  const table = page.getByRole('region', { name: 'Daftar penghuni' })
  await table.getByRole('button', { name: 'Batalkan check-out Fajar' }).click()
  await table.getByRole('button', { name: 'Ya, batalkan' }).click()

  await expect(page.getByRole('status').filter({ hasText: MESSAGES.CHECK_OUT_CANCELLED })).toBeVisible()
  await expect(table.getByRole('row', { name: /Fajar/ })).toContainText('Kamar 502')
})

test('reset sandi menampilkan sandi baru satu kali', async ({ asAdmin: page }) => {
  await mockPage(page, () => [sampleResident({ id: 31, name: 'Gita', email: 'gita@example.id' })])
  await page.route('**/api/residents/31/reset-password', async (route) => {
    expect(route.request().method()).toBe('POST')
    await route.fulfill({
      json: {
        message: MESSAGES.PASSWORD_RESET,
        data: { id: 31, name: 'Gita', email: 'gita@example.id', password: 'Baru123456' },
      },
    })
  })

  await page.goto('/admin/residents')
  const table = page.getByRole('region', { name: 'Daftar penghuni' })
  await table.getByRole('button', { name: 'Reset sandi Gita' }).click()
  await table.getByRole('button', { name: 'Ya, reset' }).click()

  await expect(page.getByRole('status').filter({ hasText: MESSAGES.PASSWORD_RESET })).toBeVisible()
  await expect(page.getByRole('region', { name: 'Sandi awal akun penghuni' })).toContainText('Baru123456')
})

test('daftar penghuni dapat dicari tanpa memuat ulang', async ({ asAdmin: page }) => {
  await mockPage(page, () => [
    sampleResident({ id: 41, name: 'Hadi', email: 'hadi@example.id' }),
    sampleResident({ id: 42, name: 'Indah', email: 'indah@example.id' }),
  ])

  await page.goto('/admin/residents')
  const table = page.getByRole('region', { name: 'Daftar penghuni' })
  await table.getByRole('searchbox', { name: 'Cari penghuni' }).fill('indah')

  await expect(table.getByRole('row', { name: /Indah/ })).toBeVisible()
  await expect(table.getByRole('row', { name: /Hadi/ })).toHaveCount(0)
})
