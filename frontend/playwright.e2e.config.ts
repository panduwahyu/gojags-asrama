import { defineConfig } from '@playwright/test'
import { BASE_URL, LARAVEL_PORT, NUXT_PORT, sharedConfig } from './tests/support/shared'

const LARAVEL_URL = `http://127.0.0.1:${LARAVEL_PORT}`

export default defineConfig({
  ...sharedConfig,
  testDir: './tests/e2e',
  fullyParallel: false,
  projects: [
    { name: 'setup', testMatch: 'global.setup.ts' },
    { name: 'e2e', testMatch: '*.spec.ts', dependencies: ['setup'] },
  ],
  webServer: [
    {
      command: `php artisan serve --env=e2e --port=${LARAVEL_PORT}`,
      cwd: '..',
      url: `${LARAVEL_URL}/up`,
      reuseExistingServer: false,
    },
    {
      command: `npm run dev -- --port ${NUXT_PORT}`,
      url: BASE_URL,
      env: {
        NUXT_LARAVEL_BASE_URL: LARAVEL_URL,
        NUXT_IGNORE_LOCK: '1',
      },
      reuseExistingServer: false,
    },
  ],
})
