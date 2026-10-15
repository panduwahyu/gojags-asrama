import { expect, test } from '../support/fixtures'
import { MESSAGES } from '../support/messages'
import { sampleLaundryOrder, sampleLeavePermit } from '../support/samples'

test('memuat ulang otomatis setiap 15 detik selama tab terlihat', async ({ asResident: page }) => {
  await page.clock.install()
  let fetchCount = 0
  await page.route('**/api/leave-permits?*', async (route) => {
    fetchCount += 1
    await route.fulfill({
      json: {
        data: [sampleLeavePermit()],
        meta: { current_page: 1, last_page: 1, total: 1 },
      },
    })
  })

  await page.goto('/resident/room')
  await expect.poll(() => fetchCount).toBe(1)

  await page.clock.fastForward(15_000)
  await expect.poll(() => fetchCount).toBe(2)

  await page.evaluate(() => {
    Object.defineProperty(document, 'hidden', { configurable: true, get: () => true })
  })
  await page.clock.fastForward(15_000)
  expect(fetchCount).toBe(2)
})

test('polling berhenti selama aksi tulis berjalan dan lanjut setelah selesai', async ({ asResident: page }) => {
  await page.clock.install()
  let listFetches = 0
  await page.route('**/api/leave-permits?*', async (route) => {
    listFetches += 1
    await route.fulfill({
      json: { data: [sampleLeavePermit()], meta: { current_page: 1, last_page: 1, total: 1 } },
    })
  })
  let finishWrite: () => void = () => {}
  const writeHeld = new Promise<void>((resolve) => {
    finishWrite = resolve
  })
  await page.route('**/api/leave-permits', async (route) => {
    await writeHeld
    await route.fulfill({ status: 201, json: { message: MESSAGES.PERMIT_SENT } })
  })

  await page.goto('/resident/room')
  await expect.poll(() => listFetches).toBe(1)

  const section = page.getByRole('region', { name: 'Izin keluar' })
  await section.getByLabel('Waktu keluar (WIB)').fill('2026-10-20T08:00')
  await section.getByLabel('Waktu kembali (WIB)').fill('2026-10-20T18:00')
  await section.getByLabel('Alasan').fill('Kontrol kesehatan')
  await section.getByRole('button', { name: 'Ajukan izin' }).click()

  await page.clock.fastForward(15_000)
  expect(listFetches).toBe(1)

  finishWrite()
  await expect(section.getByRole('status').filter({ hasText: MESSAGES.PERMIT_SENT })).toBeVisible()
  const afterWrite = listFetches
  await page.clock.fastForward(15_000)
  await expect.poll(() => listFetches).toBeGreaterThan(afterWrite)
})

test('halaman laundry admin tidak melakukan polling agar formulir status tidak ter-reset', async ({ asAdmin: page }) => {
  await page.clock.install()
  let listFetches = 0
  await page.route('**/api/laundry-orders?*', async (route) => {
    listFetches += 1
    await route.fulfill({
      json: { data: [sampleLaundryOrder()], meta: { current_page: 1, last_page: 1, total: 1 } },
    })
  })

  await page.goto('/admin/laundry')
  await expect.poll(() => listFetches).toBe(1)

  await page.clock.fastForward(45_000)
  expect(listFetches).toBe(1)
})
