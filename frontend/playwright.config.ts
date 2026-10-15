import { defineConfig } from '@playwright/test'
import { BASE_URL, NUXT_PORT, sharedConfig } from './tests/support/shared'

export default defineConfig({
  ...sharedConfig,
  testDir: './tests/browser',
  webServer: {
    command: `npm run dev -- --port ${NUXT_PORT}`,
    url: BASE_URL,
    reuseExistingServer: !process.env.CI,
    env: {
      NUXT_IGNORE_LOCK: '1',
    },
  },
})
