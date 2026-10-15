import { expect, test } from '../support/fixtures'
import { sampleLeavePermit } from '../support/samples'

test('pencarian di-debounce 300 ms dan tombol Reset mengosongkan pencarian', async ({ asResident: page }) => {
  await page.clock.install()
  const queries: string[] = []
  await page.route('**/api/leave-permits?*', async (route) => {
    const url = new URL(route.request().url())
    queries.push(url.searchParams.get('q') ?? '')
    await route.fulfill({
      json: {
        data: [sampleLeavePermit({ reason: 'Kontrol kesehatan' })],
        meta: { current_page: 1, last_page: 1, total: 1 },
      },
    })
  })

  await page.goto('/resident/room')
  const section = page.getByRole('region', { name: 'Izin keluar' })
  const searchInput = section.getByRole('searchbox', { name: 'Cari izin keluar' })

  await searchInput.fill('kesehatan')
  expect(queries).toEqual([''])

  await page.clock.fastForward(350)
  await expect.poll(() => queries.at(-1)).toBe('kesehatan')

  await section.getByRole('button', { name: 'Reset' }).click()
  await expect.poll(() => queries.at(-1)).toBe('')
})

test('klik header mengurutkan kolom, membalik arah, dan memperbarui aria-sort', async ({ asResident: page }) => {
  const sorts: string[] = []
  await page.route('**/api/leave-permits?*', async (route) => {
    const url = new URL(route.request().url())
    sorts.push(`${url.searchParams.get('sort')}:${url.searchParams.get('direction')}`)
    await route.fulfill({
      json: {
        data: [sampleLeavePermit()],
        meta: { current_page: 1, last_page: 1, total: 1 },
      },
    })
  })

  await page.goto('/resident/room')
  const section = page.getByRole('region', { name: 'Izin keluar' })
  const reasonHeader = section.getByRole('columnheader', { name: /Alasan/ })

  await expect(reasonHeader).toHaveAttribute('aria-sort', 'none')
  await reasonHeader.getByRole('button').click()
  await expect(reasonHeader).toHaveAttribute('aria-sort', 'ascending')
  expect(sorts.at(-1)).toBe('reason:asc')

  await reasonHeader.getByRole('button').click()
  await expect(reasonHeader).toHaveAttribute('aria-sort', 'descending')
  expect(sorts.at(-1)).toBe('reason:desc')
})

test('navigasi halaman dan penggantian baris per halaman mengembalikan ke halaman 1', async ({ asResident: page }) => {
  const paramsLog: { page: string; perPage: string }[] = []
  await page.route('**/api/leave-permits?*', async (route) => {
    const url = new URL(route.request().url())
    const currentPage = Number(url.searchParams.get('page') ?? '1')
    const perPage = url.searchParams.get('per_page') ?? '10'
    paramsLog.push({ page: String(currentPage), perPage })
    await route.fulfill({
      json: {
        data: [sampleLeavePermit()],
        meta: { current_page: currentPage, last_page: 3, total: 25 },
      },
    })
  })

  await page.goto('/resident/room')
  const section = page.getByRole('region', { name: 'Izin keluar' })

  await section.getByRole('button', { name: 'Berikutnya' }).click()
  await expect.poll(() => paramsLog.at(-1)?.page).toBe('2')

  await section.getByLabel('Baris per halaman').selectOption('50')
  await expect.poll(() => paramsLog.at(-1)).toEqual({ page: '1', perPage: '50' })
})

test('menampilkan keadaan kosong dan tombol Coba lagi saat gagal memuat', async ({ asResident: page }) => {
  let shouldFail = true
  await page.route('**/api/leave-permits?*', async (route) => {
    if (shouldFail) {
      await route.fulfill({ status: 500, json: { message: 'Server error' } })
      return
    }
    await route.fulfill({
      json: {
        data: [],
        meta: { current_page: 1, last_page: 1, total: 0 },
      },
    })
  })

  await page.goto('/resident/room')
  const section = page.getByRole('region', { name: 'Izin keluar' })
  await expect(section.getByRole('alert')).toContainText('Gagal memuat data.')

  shouldFail = false
  await section.getByRole('button', { name: 'Coba lagi' }).click()
  await expect(section.getByText('Belum ada pengajuan izin keluar.')).toBeVisible()
})

test('jawaban lambat tidak menimpa hasil yang lebih baru', async ({ asResident: page }) => {
  await page.clock.install()
  await page.route('**/api/leave-permits?*', async (route) => {
    const url = new URL(route.request().url())
    const q = url.searchParams.get('q') ?? ''
    if (q === 'lama') {
      setTimeout(async () => {
        await route.fulfill({
          json: {
            data: [sampleLeavePermit({ reason: 'Hasil pencarian lama' })],
            meta: { current_page: 1, last_page: 1, total: 1 },
          },
        })
      }, 600)
      return
    }
    if (q === 'baru') {
      await route.fulfill({
        json: {
          data: [sampleLeavePermit({ reason: 'Hasil pencarian baru' })],
          meta: { current_page: 1, last_page: 1, total: 1 },
        },
      })
      return
    }
    await route.fulfill({
      json: {
        data: [sampleLeavePermit({ reason: 'Default reason' })],
        meta: { current_page: 1, last_page: 1, total: 1 },
      },
    })
  })

  await page.goto('/resident/room')
  const section = page.getByRole('region', { name: 'Izin keluar' })
  const searchInput = section.getByRole('searchbox', { name: 'Cari izin keluar' })

  await searchInput.fill('lama')
  await page.clock.fastForward(350)

  await searchInput.fill('baru')
  await page.clock.fastForward(350)

  await page.clock.fastForward(650)

  await expect(section.getByText('Hasil pencarian baru')).toBeVisible()
  await expect(section.getByText('Hasil pencarian lama')).not.toBeVisible()
})
