import { expect, test } from '../support/fixtures'

test('beranda admin menampilkan ringkasan hitungan dari endpoint dashboard', async ({ asAdmin: page }) => {
  await page.route('**/api/dashboard', route =>
    route.fulfill({
      json: { data: { rooms: 78, laundry_orders: 14, catering_menus: 9 } },
    }),
  )

  await page.goto('/admin')
  const summary = page.getByRole('region', { name: 'Ringkasan layanan' })
  await expect(summary.getByText('78')).toBeVisible()
  await expect(summary.getByText('14')).toBeVisible()
  await expect(summary.getByText('9')).toBeVisible()
})

test('beranda penghuni menampilkan nama dari sesi tanpa memanggil endpoint dashboard', async ({ asResident: page }) => {
  let dashboardCalled = false
  await page.route('**/api/dashboard', (route) => {
    dashboardCalled = true
    return route.fulfill({ status: 403, json: { message: 'Forbidden' } })
  })

  await page.goto('/resident')
  await expect(page.getByText('User uji')).toBeVisible()
  expect(dashboardCalled).toBe(false)
})
