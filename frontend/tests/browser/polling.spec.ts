import { expect, test } from '../support/fixtures'
import { sampleLeavePermit } from '../support/samples'

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
