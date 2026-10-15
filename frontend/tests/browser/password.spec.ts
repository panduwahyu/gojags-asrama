import type { Page } from '@playwright/test'
import { expect, test } from '../support/fixtures'
import { MESSAGES } from '../support/messages'

async function changePassword(page: Page, path: string) {
  await page.route('**/api/password', async (route) => {
    expect(route.request().method()).toBe('PUT')
    expect(route.request().postDataJSON()).toEqual({
      current_password: 'lama12345',
      password: 'baru12345',
      password_confirmation: 'baru12345',
    })
    await route.fulfill({ json: { message: MESSAGES.PASSWORD_CHANGED } })
  })

  await page.goto(path)
  const section = page.getByRole('region', { name: 'Ubah kata sandi' })
  await section.getByLabel('Kata sandi saat ini').fill('lama12345')
  await section.getByLabel('Kata sandi baru', { exact: true }).fill('baru12345')
  await section.getByLabel('Ulangi kata sandi baru').fill('baru12345')
  await section.getByRole('button', { name: 'Simpan kata sandi' }).click()

  await expect(section.getByRole('status').filter({ hasText: MESSAGES.PASSWORD_CHANGED })).toBeVisible()
  await expect(section.getByLabel('Kata sandi saat ini')).toHaveValue('')
  await expect(section.getByLabel('Kata sandi baru', { exact: true })).toHaveValue('')
}

test('penghuni mengubah kata sandi sendiri', async ({ asResident: page }) => {
  await changePassword(page, '/resident/account')
})

test('admin mengubah kata sandi sendiri', async ({ asAdmin: page }) => {
  await changePassword(page, '/admin/account')
})

test('kata sandi saat ini yang salah ditolak dengan pesan dari server', async ({ asResident: page }) => {
  await page.route('**/api/password', route =>
    route.fulfill({
      status: 422,
      json: {
        message: MESSAGES.CURRENT_PASSWORD_WRONG,
        errors: { current_password: [MESSAGES.CURRENT_PASSWORD_WRONG] },
      },
    }),
  )

  await page.goto('/resident/account')
  const section = page.getByRole('region', { name: 'Ubah kata sandi' })
  await section.getByLabel('Kata sandi saat ini').fill('salah12345')
  await section.getByLabel('Kata sandi baru', { exact: true }).fill('baru12345')
  await section.getByLabel('Ulangi kata sandi baru').fill('baru12345')
  await section.getByRole('button', { name: 'Simpan kata sandi' }).click()

  await expect(section.getByRole('alert')).toContainText(MESSAGES.CURRENT_PASSWORD_WRONG)
})
