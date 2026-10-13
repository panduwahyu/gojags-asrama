import type { PlaywrightTestConfig } from '@playwright/test'

export const NUXT_PORT = 3100
export const LARAVEL_PORT = 8100
export const BASE_URL = `http://127.0.0.1:${NUXT_PORT}`

export const sharedConfig: PlaywrightTestConfig = {
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  workers: 1,
  reporter: [['list'], ['html', { open: 'never' }]],
  use: {
    baseURL: BASE_URL,
    browserName: 'chromium',
    channel: process.env.PLAYWRIGHT_CHANNEL || (process.platform === 'win32' ? 'msedge' : undefined),
    locale: 'id-ID',
    timezoneId: 'Asia/Jakarta',
    colorScheme: 'light',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
  },
}
