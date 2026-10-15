import fs from 'node:fs'
import path from 'node:path'
import https from 'node:https'

const targetDir = path.resolve('docs/scraping/gojags-training-assets')
fs.mkdirSync(targetDir, { recursive: true })

async function fetchUrl(url) {
  return new Promise((resolve, reject) => {
    https.get(url, { rejectUnauthorized: false, timeout: 10000 }, (res) => {
      if (res.statusCode >= 300 && res.statusCode < 400 && res.headers.location) {
        return resolve(fetchUrl(new URL(res.headers.location, url).href))
      }
      if (res.statusCode !== 200) {
        return reject(new Error(`Status ${res.statusCode} for ${url}`))
      }
      const chunks = []
      res.on('data', d => chunks.push(d))
      res.on('end', () => resolve(Buffer.concat(chunks)))
    }).on('error', reject)
  })
}

async function run() {
  console.log('Fetching main portal HTML...')
  const htmlBuf = await fetchUrl('https://gojags.web.bps.go.id/')
  const html = htmlBuf.toString('utf8')
  fs.writeFileSync(path.join(targetDir, 'index.html'), html)
  console.log('Saved index.html, size:', html.length)

  // Find all assets in HTML
  const regex = /(?:src|href|content)\s*=\s*["']([^"']+)["']/gi
  const urls = new Set()
  let match
  while ((match = regex.exec(html)) !== null) {
    const raw = match[1]
    if (/\.(png|jpg|jpeg|svg|webp|ico|gif|woff2?|css|js)/i.test(raw)) {
      urls.add(raw)
    }
  }

  // Find all scripts and stylesheets
  const scriptRegex = /<script[^>]+src=["']([^"']+)["']/gi
  let smatch
  while ((smatch = scriptRegex.exec(html)) !== null) {
    urls.add(smatch[1])
  }

  // Parse CSS for fonts, backgrounds
  const cssRegex = /url\(([^)]+)\)/g
  const cssContent = fs.existsSync(path.join(targetDir, 'index.BK5vOUIn.css'))
    ? fs.readFileSync(path.join(targetDir, 'index.BK5vOUIn.css'), 'utf8')
    : ''
  let cMatch
  while ((cMatch = cssRegex.exec(cssContent)) !== null) {
    const clean = cMatch[1].replace(/['"]/g, '').trim()
    if (!clean.startsWith('data:')) {
      urls.add(clean)
    }
  }

  console.log('Total asset URLs found:', Array.from(urls))

  for (const assetUrl of urls) {
    try {
      const fullUrl = assetUrl.startsWith('http') ? assetUrl : new URL(assetUrl, 'https://gojags.web.bps.go.id/').href
      console.log('Downloading:', fullUrl)
      const data = await fetchUrl(fullUrl)
      const basename = path.basename(new URL(fullUrl).pathname)
      const dest = path.join(targetDir, basename)
      fs.writeFileSync(dest, data)
      console.log(`Saved: ${basename} (${data.length} bytes)`)

      if (/\.(png|svg|ico|webp)$/i.test(basename)) {
        fs.copyFileSync(dest, path.resolve('frontend/public/images', basename))
      }
    } catch (e) {
      console.error('Failed to download asset:', assetUrl, e.message)
    }
  }
}

run().catch(console.error)
