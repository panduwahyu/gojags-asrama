import { execFileSync } from 'node:child_process'
import { mkdirSync } from 'node:fs'
import { expect, test as setup } from '@playwright/test'
import { ACCOUNTS, STATE } from './accounts'

setup('reset basis data', () => {
  mkdirSync('tests/e2e/.auth', { recursive: true })
  execFileSync('node', ['scripts/reset-e2e-db.mjs'], { stdio: 'inherit' })
})

for (const [name, account] of Object.entries(ACCOUNTS)) {
  setup(`login ${name}`, async ({ page }) => {
    await page.goto('/login')
    await page.getByLabel('Email').fill(account.email)
    await page.getByLabel('Kata sandi').fill(account.password)
    await page.getByRole('button', { name: 'Masuk' }).click()
    await expect(page).toHaveURL(account.home)
    await page.context().storageState({ path: STATE[name as keyof typeof STATE] })
  })
}
