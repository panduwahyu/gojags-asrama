import { chromium } from '@playwright/test';
import fs from 'fs';
import path from 'path';

async function main() {
  console.log('Launching browser (msedge)...');
  const browser = await chromium.launch({ headless: true, channel: 'msedge' });
  const context = await browser.newContext({
    viewport: { width: 1440, height: 900 },
    userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
  });
  const page = await context.newPage();

  console.log('Navigating to https://gojags.web.bps.go.id/...');
  await page.goto('https://gojags.web.bps.go.id/', { waitUntil: 'networkidle', timeout: 60000 });

  // Scroll down smoothly to trigger IntersectionObserver animations
  console.log('Scrolling page to trigger animations and visible cards...');
  await page.evaluate(async () => {
    await new Promise((resolve) => {
      let totalHeight = 0;
      const distance = 300;
      const timer = setInterval(() => {
        const scrollHeight = document.body.scrollHeight;
        window.scrollBy(0, distance);
        totalHeight += distance;

        if (totalHeight >= scrollHeight) {
          clearInterval(timer);
          window.scrollTo(0, 0); // scroll back to top for viewport shot
          resolve();
        }
      }, 100);
    });
  });

  // Also force all .card-enter elements to be visible
  await page.evaluate(() => {
    document.querySelectorAll('.card-enter').forEach(el => el.classList.add('is-visible'));
  });

  // Short pause to allow animations/transitions to settle
  await page.waitForTimeout(1000);

  const outputDir = path.resolve(import.meta.dirname, '../../docs/scraping/gojags-portal');
  if (!fs.existsSync(outputDir)) {
    fs.mkdirSync(outputDir, { recursive: true });
  }

  const screenshotPath = path.join(outputDir, 'gojags_web_bps_screenshot.png');
  const fullScreenshotPath = path.join(outputDir, 'gojags_web_bps_fullpage.png');

  console.log('Taking viewport screenshot...');
  await page.screenshot({ path: screenshotPath });

  console.log('Taking full page screenshot...');
  await page.screenshot({ path: fullScreenshotPath, fullPage: true });

  console.log('Extracting application cards and links...');
  const data = await page.evaluate(() => {
    // Collect all application cards
    const apps = Array.from(document.querySelectorAll('article')).map(el => {
      const title = el.querySelector('h3')?.innerText?.trim() || '';
      const desc = el.querySelector('p')?.innerText?.trim() || '';
      const tag = el.querySelector('.border-t span')?.innerText?.trim() || '';
      const statusBadge = el.querySelector('h3 + span')?.innerText?.trim() || '';
      const link = el.querySelector('a[href^="http"]')?.href || '';
      return { title, desc, tag, status: statusBadge || 'Active', link };
    });

    // Stats bar
    const stats = Array.from(document.querySelectorAll('.stats-bar-item')).map(el => {
      const value = el.querySelector('.stats-bar-value')?.innerText?.trim() || '';
      const label = el.querySelector('.stats-bar-label')?.innerText?.trim() || '';
      return { label, value };
    });

    // External links
    const externalLinks = Array.from(document.querySelectorAll('a[href^="http"]')).map(a => ({
      text: a.innerText?.trim() || a.getAttribute('aria-label') || '',
      href: a.href
    }));

    return { apps, stats, externalLinks };
  });

  const dataJsonPath = path.join(outputDir, 'scraped_data.json');
  fs.writeFileSync(dataJsonPath, JSON.stringify(data, null, 2));

  console.log('SUCCESS!');
  console.log('Viewport screenshot:', screenshotPath);
  console.log('Fullpage screenshot:', fullScreenshotPath);
  console.log('Scraped data JSON:', dataJsonPath);

  await browser.close();
}

main().catch(err => {
  console.error('Error running script:', err);
  process.exit(1);
});
