import { test, expect } from '@playwright/test'

test('theme switch persists across reload and all Nuxt pages', async ({ page }, testInfo) => {
  await page.goto('/')
  const toggle = page.locator('[data-theme-toggle]')
  await expect(toggle).toHaveAttribute('aria-pressed', 'false')
  const lightBackground = await page.locator('body').evaluate(el => getComputedStyle(el).backgroundColor)
  await toggle.click()
  await expect(toggle).toHaveAttribute('aria-label', 'Aktifkan mode terang')
  await expect(page.locator('html')).toHaveClass(/dark/)
  expect(await page.locator('body').evaluate(el => getComputedStyle(el).backgroundColor)).not.toBe(lightBackground)
  await page.reload()
  await expect(toggle).toHaveAttribute('aria-pressed', 'true')
  for (const path of ['/kamar', '/laundry', '/catering', '/login']) {
    await page.goto(path)
    await expect(toggle).toHaveAttribute('aria-pressed', 'true')
    await expect(page.locator('html')).toHaveCSS('color-scheme', 'dark')
  }
  await page.goto('/')
  await expect(page.getByRole('heading', { level: 1 })).toContainText('Selamat datang')
  await expect(page.getByRole('heading', { level: 1 })).toBeVisible()
  await page.screenshot({ path: testInfo.outputPath('night-desktop.png'), fullPage: true })
  await toggle.click()
  await page.reload()
  await expect(toggle).toHaveAttribute('aria-pressed', 'false')
})

test('device theme follows changes until the user chooses a theme', async ({ page }) => {
  for (const url of ['/login', process.env.LARAVEL_URL || 'http://127.0.0.1:8000/login']) {
    await page.emulateMedia({ colorScheme: 'dark' })
    await page.goto(url)
    await expect(page.locator('[data-theme-toggle]')).toHaveAttribute('aria-pressed', 'true')
    await expect(page.locator('[data-theme-toggle]')).toHaveAttribute('aria-label', 'Aktifkan mode terang')
    expect(await page.evaluate(() => localStorage.getItem('gojags-asrama-theme'))).toBeNull()
    await page.emulateMedia({ colorScheme: 'light' })
    await expect(page.locator('[data-theme-toggle]')).toHaveAttribute('aria-pressed', 'false')
    await expect(page.locator('[data-theme-toggle]')).toHaveAttribute('aria-label', 'Aktifkan mode gelap')
    await page.reload()
    await expect(page.locator('[data-theme-toggle]')).toHaveAttribute('aria-pressed', 'false')
    expect(await page.evaluate(() => localStorage.getItem('gojags-asrama-theme'))).toBeNull()
    await page.locator('[data-theme-toggle]').click()
    await page.emulateMedia({ colorScheme: 'dark' })
    await page.emulateMedia({ colorScheme: 'light' })
    await expect(page.locator('[data-theme-toggle]')).toHaveAttribute('aria-pressed', 'true')
    await page.reload()
    await expect(page.locator('[data-theme-toggle]')).toHaveAttribute('aria-pressed', 'true')
  }
})

test('theme stays usable when browser storage is unavailable', async ({ page }) => {
  await page.addInitScript(() => {
    Storage.prototype.getItem = () => { throw new Error('Storage unavailable') }
    Storage.prototype.setItem = () => { throw new Error('Storage unavailable') }
  })
  await page.goto('/login')
  await page.locator('[data-theme-toggle]').click()
  await expect(page.locator('[data-theme-toggle]')).toHaveAttribute('aria-pressed', 'true')
})

test('theme is synchronized between tabs', async ({ page, context }) => {
  await page.goto('/')
  const other = await context.newPage()
  await other.goto('/login')
  await page.locator('[data-theme-toggle]').click()
  await expect(other.locator('[data-theme-toggle]')).toHaveAttribute('aria-pressed', 'true')
  await other.locator('[data-theme-toggle]').click()
  await expect(page.locator('[data-theme-toggle]')).toHaveAttribute('aria-pressed', 'false')
})

test('mobile theme switch is accessible without opening the sidebar', async ({ page }, testInfo) => {
  await page.setViewportSize({ width: 390, height: 844 })
  await page.goto('/')
  await page.locator('[data-theme-toggle]').click()
  await expect(page.locator('[data-theme-toggle]')).toHaveAttribute('aria-pressed', 'true')
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true)
  await page.screenshot({ path: testInfo.outputPath('night-mobile.png'), fullPage: true })
})

test('Laravel Blade has the same working theme switch', async ({ page }) => {
  await page.goto(process.env.LARAVEL_URL || 'http://127.0.0.1:8000')
  await page.locator('[data-theme-toggle]').click()
  await expect(page.locator('html')).toHaveCSS('color-scheme', 'dark')
  await expect(page.locator('body')).toHaveCSS('background-color', 'rgb(2, 6, 24)')
  await page.reload()
  await expect(page.locator('[data-theme-toggle]')).toHaveAttribute('aria-pressed', 'true')
})
