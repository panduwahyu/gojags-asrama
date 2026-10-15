import { expect, test } from '../support/fixtures'
import { MESSAGES } from '../support/messages'
import { CSRF_TOKEN, mockEmptyTables } from '../support/mock-api'

test('login admin berhasil dan mengalihkan ke beranda admin', async ({ page }) => {
  let currentUser: { id: number; name: string; email: string; role: 'admin' } | null = null
  await page.route('**/api/session', route =>
    route.fulfill({ json: { csrfToken: CSRF_TOKEN, user: currentUser } }),
  )
  await mockEmptyTables(page)
  await page.route('**/api/login', async (route) => {
    const body = route.request().postDataJSON()
    expect(body).toEqual({ email: 'admin@example.id', password: 'admin123' })
    currentUser = { id: 1, name: 'Admin Asrama', email: 'admin@example.id', role: 'admin' }
    await route.fulfill({
      json: {
        csrfToken: CSRF_TOKEN,
        user: currentUser,
      },
    })
  })

  await page.goto('/login')
  await page.getByLabel('Email').fill('admin@example.id')
  await page.getByLabel('Kata sandi').fill('admin123')
  await page.getByRole('button', { name: 'Masuk' }).click()

  await expect(page).toHaveURL('/admin')
})

test('login gagal menampilkan pesan error dari server', async ({ page }) => {
  await page.route('**/api/session', route =>
    route.fulfill({ json: { csrfToken: CSRF_TOKEN, user: null } }),
  )
  await page.route('**/api/login', route =>
    route.fulfill({
      status: 422,
      json: {
        message: MESSAGES.INVALID_CREDENTIALS,
        errors: { email: [MESSAGES.INVALID_CREDENTIALS] },
      },
    }),
  )

  await page.goto('/login')
  await page.getByLabel('Email').fill('salah@example.id')
  await page.getByLabel('Kata sandi').fill('salah')
  await page.getByRole('button', { name: 'Masuk' }).click()

  await expect(page.getByRole('alert')).toContainText(MESSAGES.INVALID_CREDENTIALS)
})
