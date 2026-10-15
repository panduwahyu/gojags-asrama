import { chromium } from '@playwright/test';
import fs from 'fs';
import path from 'path';

const targets = [
  {
    name: 'classroom',
    title: 'GOJAGS Classroom',
    url: 'https://gojags-classroom.bps.go.id/'
  },
  {
    name: 'training',
    title: 'GOJAGS Training',
    url: 'https://gojags-training.bps.go.id/'
  }
];

async function scrapeTarget(context, target, projectRoot, docsDir) {
  console.log(`\n=== Scraping ${target.title} (${target.url}) ===`);
  const page = await context.newPage();

  try {
    console.log(`Navigating to ${target.url}...`);
    await page.goto(target.url, { waitUntil: 'load', timeout: 45000 });
    
    // Wait for network to settle slightly
    try {
      await page.waitForLoadState('networkidle', { timeout: 15000 });
    } catch {
      console.log('Network idle timed out, proceeding with current state...');
    }

    // Scroll down to trigger lazy loading / animations
    console.log('Scrolling down page...');
    await page.evaluate(async () => {
      await new Promise((resolve) => {
        let totalHeight = 0;
        const distance = 400;
        const timer = setInterval(() => {
          const scrollHeight = document.body.scrollHeight;
          window.scrollBy(0, distance);
          totalHeight += distance;
          if (totalHeight >= scrollHeight) {
            clearInterval(timer);
            resolve();
          }
        }, 100);
      });
    });

    await page.waitForTimeout(1000);

    // Scroll back to top for viewport screenshot
    await page.evaluate(() => window.scrollTo(0, 0));
    await page.waitForTimeout(500);

    const viewportFile = `gojags_${target.name}_screenshot.png`;
    const fullpageFile = `gojags_${target.name}_fullpage.png`;
    const jsonFile = `scraped_${target.name}_data.json`;

    // Save to docsDir
    const docsViewportPath = path.join(docsDir, viewportFile);
    const docsFullpagePath = path.join(docsDir, fullpageFile);
    const docsJsonPath = path.join(docsDir, jsonFile);

    // Save to projectRoot
    const rootViewportPath = path.join(projectRoot, viewportFile);
    const rootFullpagePath = path.join(projectRoot, fullpageFile);
    const rootJsonPath = path.join(projectRoot, jsonFile);

    console.log(`Taking viewport screenshot for ${target.name}...`);
    await page.screenshot({ path: docsViewportPath });
    fs.copyFileSync(docsViewportPath, rootViewportPath);

    console.log(`Taking full page screenshot for ${target.name}...`);
    await page.screenshot({ path: docsFullpagePath, fullPage: true });
    fs.copyFileSync(docsFullpagePath, rootFullpagePath);

    // Extract structure, colors, fonts, links, and text
    console.log(`Extracting design elements & content for ${target.name}...`);
    const designData = await page.evaluate(() => {
      const pageTitle = document.title;
      const metaDesc = document.querySelector('meta[name="description"]')?.getAttribute('content') || '';
      
      const headings = Array.from(document.querySelectorAll('h1, h2, h3')).map(h => ({
        tag: h.tagName.toLowerCase(),
        text: h.innerText?.trim() || ''
      })).filter(h => h.text.length > 0);

      const navLinks = Array.from(document.querySelectorAll('nav a, header a')).map(a => ({
        text: a.innerText?.trim() || a.getAttribute('aria-label') || '',
        href: a.href
      })).filter(a => a.text.length > 0);

      const buttons = Array.from(document.querySelectorAll('button, a.btn, a[class*="button"], a[class*="btn"]')).map(b => ({
        text: b.innerText?.trim() || b.getAttribute('aria-label') || '',
        classes: b.className
      })).filter(b => b.text.length > 0).slice(0, 20);

      // Extract some computed styles for design reference (primary colors, font family)
      const bodyStyles = window.getComputedStyle(document.body);
      const primaryFont = bodyStyles.fontFamily;
      const bgColor = bodyStyles.backgroundColor;
      const textColor = bodyStyles.color;

      return {
        pageTitle,
        metaDesc,
        typography: { primaryFont, bgColor, textColor },
        headings: headings.slice(0, 25),
        navLinks: navLinks.slice(0, 20),
        buttons
      };
    });

    fs.writeFileSync(docsJsonPath, JSON.stringify(designData, null, 2));
    fs.copyFileSync(docsJsonPath, rootJsonPath);

    console.log(`Successfully scraped ${target.title}!`);
    console.log(`- Viewport: ${rootViewportPath}`);
    console.log(`- Fullpage: ${rootFullpagePath}`);
    console.log(`- JSON: ${rootJsonPath}`);

  } catch (err) {
    console.error(`Error scraping ${target.name}:`, err.message);
  } finally {
    await page.close();
  }
}

async function main() {
  const projectRoot = path.resolve(import.meta.dirname, '../../');
  const docsDir = path.resolve(projectRoot, 'docs/scraping/external-apps');
  if (!fs.existsSync(docsDir)) {
    fs.mkdirSync(docsDir, { recursive: true });
  }

  console.log('Launching browser (msedge)...');
  const browser = await chromium.launch({
    headless: true,
    channel: 'msedge'
  });

  const context = await browser.newContext({
    viewport: { width: 1440, height: 900 },
    ignoreHTTPSErrors: true,
    userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
  });

  for (const target of targets) {
    await scrapeTarget(context, target, projectRoot, docsDir);
  }

  await browser.close();
  console.log('\nAll scraping and screenshot tasks completed!');
}

main().catch(err => {
  console.error('Fatal error:', err);
  process.exit(1);
});
