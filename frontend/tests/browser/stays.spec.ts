import { test, expect, type Page } from '@playwright/test'

async function login(page: Page, email: string, password: string) {
  await page.goto('/login')
  await page.getByLabel('Email', { exact: true }).fill(email)
  await page.getByLabel('Kata sandi', { exact: true }).fill(password)
  await page.getByRole('button', { name: 'Login', exact: true }).click()
  await expect(page.getByRole('dialog')).toBeVisible()
  await page.getByRole('dialog').getByRole('button', { name: 'OK', exact: true }).click()
}

test('user menus and check-in/out flow show WIB times and retain history', async ({ page }, testInfo) => {
  await login(page, 'user1@example.id', 'user123')
  await expect(page).toHaveURL(/\/user$/)
  let checkedIn = false
  let checkedOut = false
  await page.route('**/backend/api/asrama/user/kamar', async route => {
    await route.fulfill({ json: {
      room: checkedOut ? null : { name: '501', lantai: 5, checked_in_at: checkedIn ? '2026-10-09T04:00:00Z' : null },
      history: checkedIn ? [{ id: 999999, kamar: '501', lantai: 5, checked_in_at: '2026-10-09T04:00:00Z', checked_out_at: checkedOut ? '2026-10-09T05:00:00Z' : null }] : [],
    } })
  })
  // Exercise the UI without checking real residents into or out of their rooms.
  await page.route('**/backend/api/asrama/user/kamar/check-*', async route => {
    expect(route.request().method()).toBe('POST')
    expect(route.request().headers()['x-csrf-token']).toBeTruthy()
    checkedIn = true
    checkedOut = route.request().url().endsWith('check-out')
    await route.fulfill({ json: { message: checkedOut ? 'Check-out berhasil. Slot kamar telah dilepaskan.' : 'Check-in berhasil.' } })
  })
  const sidebar = page.locator('aside')
  for (const label of ['Kamar', 'Laundry', 'Catering']) await expect(sidebar.getByRole('link', { name: label, exact: false })).toBeVisible()
  await sidebar.getByRole('link', { name: 'Kamar', exact: false }).click()
  await expect(page).toHaveURL(/\/user\/kamar$/)
  await expect(page.getByRole('button', { name: 'Check-out', exact: true })).toBeDisabled()
  await page.getByRole('button', { name: 'Check-in', exact: true }).click()
  await expect(page.getByRole('dialog')).toBeVisible()
  await page.getByRole('dialog').getByRole('button', { name: 'OK', exact: true }).click()
  await expect(page.getByRole('button', { name: 'Check-in', exact: true })).toBeDisabled()
  await expect(page.getByRole('button', { name: 'Check-out', exact: true })).toBeEnabled()
  await expect(page.locator('time').first()).toContainText('11.00.00 WIB')
  await page.getByRole('button', { name: 'Check-out', exact: true }).click()
  await expect(page.getByRole('dialog')).toBeVisible()
  await page.getByRole('dialog').getByRole('button', { name: 'OK', exact: true }).click()
  await expect(page.getByRole('heading', { name: 'Belum ada alokasi kamar', exact: true })).toBeVisible()
  await expect(page.locator('time').last()).toContainText('12.00.00 WIB')
  await page.reload()
  await expect(page.locator('time').last()).toContainText('12.00.00 WIB')
  await page.screenshot({ path: testInfo.outputPath('user-stay-history.png'), fullPage: true })
  await sidebar.getByRole('link', { name: 'Laundry', exact: false }).click()
  await expect(page.getByRole('heading', { name: 'Ajukan laundry', exact: true })).toBeVisible()
  for (const label of ['Catering']) {
    await sidebar.getByRole('link', { name: label, exact: false }).click()
    await expect(page.getByRole('heading', { name: 'Menu dan rating catering', exact: true })).toBeVisible()
  }
  await page.goto('/kamar')
  await expect(page).toHaveURL(/\/user$/)
  await page.unrouteAll({ behavior: 'wait' })
})

test('admin monitoring uses real totals and displays four status colors', async ({ page }, testInfo) => {
  await login(page, 'admin@example.id', 'admin123')
  await expect(page).toHaveURL(/\/admin$/)
  await page.goto('/kamar')
  const response = await page.request.get('/backend/api/asrama/kamar')
  expect(response.status()).toBe(200)
  const data = await response.json()
  expect(Object.values(data.monitoring).reduce((sum: number, value) => sum + Number(value), 0)).toBe(78)
  const monitoring = page.getByRole('region', { name: 'Monitoring kamar', exact: true })
  const colors: string[] = []
  for (const status of ['Kosong', 'Assigned', 'Terisi', 'Penuh']) {
    const badge = monitoring.getByText(status, { exact: true })
    await expect(badge).toBeVisible()
    await expect(badge.locator('..')).toContainText(String(data.monitoring[status]))
    colors.push(await badge.evaluate(element => getComputedStyle(element).backgroundColor))
  }
  expect(new Set(colors).size).toBe(4)
  expect((await page.request.get('/backend/api/asrama/user/kamar')).status()).toBe(403)
  await page.screenshot({ path: testInfo.outputPath('admin-room-monitoring.png'), fullPage: true })
})
