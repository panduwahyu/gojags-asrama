import { expect, test } from '../support/fixtures'
import { CSRF_TOKEN, mockEmptyTables } from '../support/mock-api'

test('halaman mengirim header anti-clickjacking', async ({ page }) => {
  await page.route('**/api/session', route =>
    route.fulfill({ json: { csrfToken: CSRF_TOKEN, user: null } }),
  )
  await mockEmptyTables(page)

  const response = await page.goto('/login')

  expect(response?.headers()['x-frame-options']).toBe('DENY')
  expect(response?.headers()['x-content-type-options']).toBe('nosniff')
})
