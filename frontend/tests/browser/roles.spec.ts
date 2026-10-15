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
  await page.goto('/resident/laundry')
  await expect(page).toHaveURL('/admin')
  await page.goto('/resident/catering')
  await expect(page).toHaveURL('/admin')
  await page.goto('/resident/account')
  await expect(page).toHaveURL('/admin')
})

test('penghuni yang membuka halaman admin dialihkan ke beranda penghuni', async ({ asResident: page }) => {
  await page.goto('/admin/rooms')
  await expect(page).toHaveURL('/resident')
  await page.goto('/admin/laundry')
  await expect(page).toHaveURL('/resident')
  await page.goto('/admin/catering')
  await expect(page).toHaveURL('/resident')
  await page.goto('/admin/residents')
  await expect(page).toHaveURL('/resident')
  await page.goto('/admin/account')
  await expect(page).toHaveURL('/resident')
})

test('menu samping admin memuat Penghuni dan Akun; penghuni hanya memuat Akun', async ({ asAdmin: page }) => {
  await page.goto('/admin')
  const nav = page.getByRole('navigation', { name: 'Navigasi utama' })
  await expect(nav.getByRole('link', { name: 'Penghuni' })).toBeVisible()
  await expect(nav.getByRole('link', { name: 'Akun' })).toBeVisible()
})

test('menu samping penghuni memuat Akun tetapi tidak Penghuni', async ({ asResident: page }) => {
  await page.goto('/resident')
  const nav = page.getByRole('navigation', { name: 'Navigasi utama' })
  await expect(nav.getByRole('link', { name: 'Akun' })).toBeVisible()
  await expect(nav.getByRole('link', { name: 'Penghuni' })).toHaveCount(0)
})
