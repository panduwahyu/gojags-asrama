import { expect, test } from '../support/fixtures'
import { CSRF_TOKEN } from '../support/mock-api'

test('tamu yang belum login dialihkan ke halaman login', async ({ page }) => {
  await page.route('**/api/session', route =>
    route.fulfill({ json: { csrfToken: CSRF_TOKEN, user: null } }),
  )
  await page.goto('/admin/rooms')
  await expect(page).toHaveURL('/login')
})

test('admin yang membuka halaman penghuni dialihkan ke beranda admin', async ({ asAdmin: page }) => {
  await page.goto('/resident/room')
  await expect(page).toHaveURL('/admin')
})

test('penghuni yang membuka halaman admin dialihkan ke beranda penghuni', async ({ asResident: page }) => {
  await page.goto('/admin/rooms')
  await expect(page).toHaveURL('/resident')
})
