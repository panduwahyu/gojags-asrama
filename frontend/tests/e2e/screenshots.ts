import { mkdirSync } from 'node:fs'
import { dirname, resolve } from 'node:path'
import type { Page } from '@playwright/test'

const SCREENSHOT_BASE = resolve(process.cwd(), '../docs/screenshots')

export async function captureScreenshot(page: Page, relativePath: string) {
  const targetPath = resolve(SCREENSHOT_BASE, relativePath)
  mkdirSync(dirname(targetPath), { recursive: true })
  await page.screenshot({
    path: targetPath,
    fullPage: true,
  })
}
