// Capture the print-usable "memoire/" section frames (public home page) with Playwright.
//
// These are viewport frames (NOT full-page): 1440x900 desktop (and 390x844 mobile) at
// deviceScaleFactor 2 -> 2880x1800 (and 780x1688) PNGs, light theme, each scrolled so the target
// section sits under the fixed navigation bar. Matches docs/.../screenshots/README.md.
//
// Prerequisites (from the repo root):
//   1) backend:  (in nexus-vpn/)  php artisan serve --port=8000
//   2) frontend: (prod)           node node_modules/next/dist/bin/next build && next start   # :3000
//   3) run:      NODE_PATH=./node_modules node docs/pfe/memoire-assets/screenshots/capture-memoire.mjs
//
// Env: BASE_URL (default http://localhost:3000). ONLY the public home-page frames are produced here;
// the authenticated dashboard/admin/dialog captures of the set are done separately.
import { chromium } from 'playwright'
import { fileURLToPath } from 'node:url'
import { dirname, join } from 'node:path'

const OUT = join(dirname(fileURLToPath(import.meta.url)), 'memoire')
const BASE = process.env.BASE_URL ?? 'http://localhost:3000'

// Desktop section frames: filename -> CSS selector to bring under the navbar (null = page top).
const DESKTOP = [
  { file: '01-home-navbar-hero.png', selector: null },
  { file: '02-features.png', selector: '#features' },
  { file: '03-map-stats.png', selector: '#map' },
  { file: '05-pricing-cards.png', selector: '#pricing' },
  { file: '07-footer.png', selector: 'footer' },
]
const MOBILE = [{ file: '90-mobile-home.png', selector: null }]

async function settle(page) {
  // Scroll through once so the dynamic-import sections mount and their in-view animations fire.
  await page.evaluate(async () => {
    await new Promise((resolve) => {
      let y = 0
      const step = () => {
        window.scrollBy(0, Math.max(400, window.innerHeight * 0.85))
        y += window.innerHeight * 0.85
        if (y >= document.body.scrollHeight) resolve()
        else setTimeout(step, 150)
      }
      step()
    })
  }).catch(() => {})
  await page.waitForTimeout(600)
}

async function frame(page, selector, file) {
  await page.evaluate((sel) => {
    const y = sel ? (document.querySelector(sel)?.getBoundingClientRect().top ?? 0) + window.scrollY : 0
    window.scrollTo({ top: Math.max(0, y), behavior: 'instant' })
  }, selector)
  await page.waitForTimeout(700)
  await page.screenshot({ path: join(OUT, file) }) // viewport frame (fullPage: false)
  console.log('OK', file)
}

async function run() {
  const browser = await chromium.launch()
  for (const [vp, targets] of [
    [{ width: 1440, height: 900 }, DESKTOP],
    [{ width: 390, height: 844 }, MOBILE],
  ]) {
    const ctx = await browser.newContext({ viewport: vp, deviceScaleFactor: 2, colorScheme: 'light' })
    await ctx.addInitScript(() => { try { localStorage.setItem('theme', 'light') } catch {} })
    const page = await ctx.newPage()
    await page.goto(BASE, { waitUntil: 'networkidle' }).catch(() => {})
    await settle(page)
    for (const t of targets) await frame(page, t.selector, t.file)
    await ctx.close()
  }
  await browser.close()
  console.log('done')
}

run().catch((e) => { console.error(e); process.exit(1) })
