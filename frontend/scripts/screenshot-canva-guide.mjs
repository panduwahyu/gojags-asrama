import { chromium } from '@playwright/test';
import path from 'path';

async function main() {
  const browser = await chromium.launch({ headless: true, channel: 'msedge' });
  const context = await browser.newContext({
    viewport: { width: 1440, height: 900 }
  });
  const page = await context.newPage();

  console.log('Navigating to Canva Guide...');
  await page.goto('https://panduangojags.my.canva.site/home', { waitUntil: 'networkidle', timeout: 60000 });
  await page.waitForTimeout(2000);

  // Scroll through all sections
  await page.evaluate(async () => {
    await new Promise((resolve) => {
      let total = 0;
      const step = 400;
      const timer = setInterval(() => {
        window.scrollBy(0, step);
        total += step;
        if (total >= document.body.scrollHeight + 1000) {
          clearInterval(timer);
          resolve();
        }
      }, 150);
    });
  });
  await page.waitForTimeout(2000);

  const rootDir = path.resolve(import.meta.dirname, '../../');
  const screenshotPath = path.join(rootDir, 'gojags_guidelines_canva_screenshot.png');
  const fullpagePath = path.join(rootDir, 'gojags_guidelines_canva_fullpage.png');

  console.log('Capturing screenshots of Canva guidelines...');
  await page.screenshot({ path: screenshotPath });
  await page.screenshot({ path: fullpagePath, fullPage: true });

  console.log('Saved to root:', screenshotPath, fullpagePath);
  await browser.close();
}

main().catch(err => {
  console.error('Error:', err);
  process.exit(1);
});
