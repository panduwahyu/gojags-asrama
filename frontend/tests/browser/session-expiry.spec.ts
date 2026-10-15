import type { Page } from '@playwright/test'
import { expect, test } from '../support/fixtures'
import { MESSAGES } from '../support/messages'
import { CSRF_TOKEN, mockEmptyTables } from '../support/mock-api'
import { sampleLeavePermit } from '../support/samples'

/** Sesi aktif sampai `expire()` dipanggil; sesudah itu peladen menjawab bahwa pengguna sudah keluar. */
async function mockExpiringSession(page: Page) {
  let expired = false
  const user = { id: 9999, name: 'User uji', email: 'uji@example.id', role: 'resident' }
  await page.route('**/api/session', route =>
    route.fulfill({ json: { csrfToken: CSRF_TOKEN, user: expired ? null : user } }),
  )
  await mockEmptyTables(page)
  return { expire: () => { expired = true } }
}

test('daftar tabel yang dijawab 401 membawa pengguna ke halaman login', async ({ page }) => {
  const session = await mockExpiringSession(page)
  await page.route('**/api/leave-permits?*', async (route) => {
    session.expire()
    await route.fulfill({ status: 401, json: { message: 'Unauthenticated.' } })
  })

  await page.goto('/resident/room')

  await expect(page).toHaveURL('/login')
})

test('aksi tulis yang dijawab 401 membawa pengguna ke halaman login', async ({ page }) => {
  const session = await mockExpiringSession(page)
  await page.route('**/api/leave-permits?*', route =>
    route.fulfill({ json: { data: [sampleLeavePermit()], meta: { current_page: 1, last_page: 1, total: 1 } } }),
  )
  await page.route('**/api/complaints', async (route) => {
    session.expire()
    await route.fulfill({ status: 401, json: { message: 'Unauthenticated.' } })
  })

  await page.goto('/resident/room')
  const section = page.getByRole('region', { name: 'Keluhan asrama' })
  await section.getByLabel('Judul keluhan').fill('Lampu mati')
  await section.getByLabel('Isi keluhan').fill('Lampu kamar mati sejak semalam.')
  await section.getByRole('button', { name: 'Kirim keluhan' }).click()

  await expect(page).toHaveURL('/login')
})

test('logout yang dijawab 401 tetap berakhir di halaman login', async ({ page }) => {
  const session = await mockExpiringSession(page)
  await page.route('**/api/logout', async (route) => {
    session.expire()
    await route.fulfill({ status: 401, json: { message: 'Unauthenticated.' } })
  })

  await page.goto('/resident')
  await page.getByRole('button', { name: 'Logout' }).click()

  await expect(page).toHaveURL('/login')
})

test('login yang salah tidak dianggap sesi berakhir', async ({ page }) => {
  await page.route('**/api/session', route =>
    route.fulfill({ json: { csrfToken: CSRF_TOKEN, user: null } }),
  )
  await page.route('**/api/login', route =>
    route.fulfill({
      status: 422,
      json: { message: MESSAGES.INVALID_CREDENTIALS, errors: { email: [MESSAGES.INVALID_CREDENTIALS] } },
    }),
  )

  await page.goto('/login')
  await page.getByLabel('Email').fill('salah@example.id')
  await page.getByLabel('Kata sandi').fill('salah')
  await page.getByRole('button', { name: 'Masuk' }).click()

  await expect(page.getByRole('alert')).toContainText(MESSAGES.INVALID_CREDENTIALS)
  await expect(page).toHaveURL('/login')
})
