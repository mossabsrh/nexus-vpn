// Capture des captures d'écran du mémoire avec Playwright.
//
// Prérequis :
//   1) Démarrer le backend :  (dans nexus-vpn/)  php artisan serve --port=8000
//   2) Démarrer le frontend : (à la racine)      npx pnpm@10 dev           # http://localhost:3000
//   3) Installer Playwright : (à la racine)       npm i -D playwright && npx playwright install chromium
//   4) Lancer :                                   node docs/pfe/memoire-assets/screenshots/capture.mjs
//
// Variables d'environnement :
//   BASE_URL (défaut http://localhost:3000)
//   ADMIN_EMAIL / ADMIN_PASSWORD  (compte admin pour /dashboard et /admin)
//
// Sortie : fichiers PNG plein-page dans ce dossier, nommés NN-page-viewport-theme[-etat].png

import { chromium } from 'playwright'
import { fileURLToPath } from 'node:url'
import { dirname, join } from 'node:path'

const OUT = dirname(fileURLToPath(import.meta.url))
const BASE = process.env.BASE_URL ?? 'http://localhost:3000'
const API = `${BASE}/backend-api`
const ADMIN_EMAIL = process.env.ADMIN_EMAIL ?? 'mohamed.belkacemi2004@gmail.com'
const ADMIN_PASSWORD = process.env.ADMIN_PASSWORD ?? '12345678'

const VIEWPORTS = [
  { name: 'desktop', width: 1440, height: 900 },
  { name: 'mobile', width: 390, height: 844 },
]
const THEMES = ['light', 'dark']

// Pages publiques / sans authentification (le numéro sert d'ordre de figure).
const PUBLIC_PAGES = [
  { id: '01-home', path: '/' },
  { id: '08-reset-password', path: '/reset-password?token=demo-token&email=demo%40example.com' },
  { id: '09-verify-email', path: '/verify-email?id=1&hash=demo&expires=1&signature=demo' },
  { id: '10-payment-result', path: '/payment/result?payment=1' },
]

// Pages privées (nécessitent une session authentifiée admin).
const PRIVATE_PAGES = [
  { id: '11-dashboard', path: '/dashboard' },
  { id: '12-admin', path: '/admin' },
]

// Onglets de l'admin (boutons cliquables par leur libellé).
const ADMIN_TABS = ['Accounts', 'Billing', 'Offers', 'Moderation', 'Stats']

async function setTheme(context, theme) {
  await context.addInitScript((t) => {
    try { localStorage.setItem('theme', t) } catch {}
  }, theme)
}

async function authenticate(context) {
  // Ouvre une session via l'API (le cookie est partagé avec le contexte du navigateur).
  const req = context.request
  await req.get(`${API}/csrf-cookie`)
  const cookies = await context.cookies()
  const xsrf = decodeURIComponent(cookies.find((c) => c.name === 'XSRF-TOKEN')?.value ?? '')
  const res = await req.post(`${API}/auth/login`, {
    headers: { 'X-XSRF-TOKEN': xsrf, Accept: 'application/json' },
    data: { email: ADMIN_EMAIL, password: ADMIN_PASSWORD },
  })
  if (!res.ok()) throw new Error(`Login failed: HTTP ${res.status()}`)
}

async function shoot(page, file) {
  await page.waitForTimeout(600)
  await page.screenshot({ path: join(OUT, file), fullPage: true })
  console.log('✓', file)
}

async function run() {
  const browser = await chromium.launch()
  for (const vp of VIEWPORTS) {
    for (const theme of THEMES) {
      const context = await browser.newContext({ viewport: { width: vp.width, height: vp.height }, colorScheme: theme })
      await setTheme(context, theme)
      const page = await context.newPage()

      // 1) Pages publiques
      for (const p of PUBLIC_PAGES) {
        await page.goto(`${BASE}${p.path}`, { waitUntil: 'networkidle' }).catch(() => {})
        await shoot(page, `${p.id}-${vp.name}-${theme}.png`)
      }

      // 2) États du formulaire d'authentification (modale sur la page d'accueil) — best-effort
      await page.goto(BASE, { waitUntil: 'networkidle' }).catch(() => {})
      for (const label of ['Sign in', 'Log in', 'Get started', 'Se connecter']) {
        const btn = page.getByRole('button', { name: new RegExp(label, 'i') }).first()
        if (await btn.count()) { await btn.click().catch(() => {}); break }
      }
      await page.waitForTimeout(500)
      await shoot(page, `02-auth-dialog-${vp.name}-${theme}.png`)

      // 3) Pages privées (admin connecté)
      await authenticate(context).catch((e) => console.warn('auth:', e.message))
      for (const p of PRIVATE_PAGES) {
        await page.goto(`${BASE}${p.path}`, { waitUntil: 'networkidle' }).catch(() => {})
        await shoot(page, `${p.id}-${vp.name}-${theme}.png`)
      }

      // 4) Onglets de l'admin
      await page.goto(`${BASE}/admin`, { waitUntil: 'networkidle' }).catch(() => {})
      let i = 13
      for (const tab of ADMIN_TABS) {
        const t = page.getByRole('button', { name: new RegExp(`^${tab}`, 'i') }).first()
        if (await t.count()) {
          await t.click().catch(() => {})
          await shoot(page, `${String(i++).padStart(2, '0')}-admin-${tab.toLowerCase()}-${vp.name}-${theme}.png`)
        }
      }

      await context.close()
    }
  }
  await browser.close()
  console.log('Terminé.')
}

run().catch((e) => { console.error(e); process.exit(1) })
