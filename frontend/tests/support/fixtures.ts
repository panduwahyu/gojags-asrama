import { test as base, type Page } from '@playwright/test'
import { mockEmptyTables, mockSession } from './mock-api'

export const test = base.extend<{ asAdmin: Page; asResident: Page }>({
  asAdmin: async ({ page }, use) => {
    await mockSession(page, 'admin')
    await mockEmptyTables(page)
    await use(page)
  },
  asResident: async ({ page }, use) => {
    await mockSession(page, 'resident')
    await mockEmptyTables(page)
    await use(page)
  },
})

export { expect } from '@playwright/test'
