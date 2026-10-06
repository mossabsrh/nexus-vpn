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
// Utilisateur de démonstration avec abonnement actif + VPN (pour le tableau de bord).
const DEMO_EMAIL = process.env.DEMO_EMAIL ?? 'yacine.benali@demo.nexusvpn.dz'
const DEMO_PASSWORD = process.env.DEMO_PASSWORD ?? 'password'

const ALL_VIEWPORTS = [
  { name: 'desktop', width: 1440, height: 900 },
  { name: 'mobile', width: 390, height: 844 },
]
const ALL_THEMES = ['light', 'dark']
// Batch control (low-memory): run one viewport + one theme per invocation.
const VIEWPORTS = process.env.VIEWPORT ? ALL_VIEWPORTS.filter((v) => v.name === process.env.VIEWPORT) : ALL_VIEWPORTS
const THEMES = process.env.THEME ? ALL_THEMES.filter((t) => t === process.env.THEME) : ALL_THEMES

// Pages publiques / sans authentification (le numéro sert d'ordre de figure).
const PUBLIC_PAGES = [
  { id: '01-home', path: '/' },
  { id: '08-reset-password', path: '/reset-password?token=demo-token&email=demo%40example.com' },
  { id: '09-verify-email', path: '/verify-email?id=1&hash=demo&expires=1&signature=demo' },
  { id: '10-payment-result', path: '/payment/result?payment=1' },
]

// Onglets de l'admin (libellés réels de la barre latérale).
const ADMIN_TABS = ['Overview', 'Accounts', 'Offers', 'Subscriptions', 'Payments', 'Analytics', 'Comments', 'Audit log']

async function setTheme(context, theme) {
  await context.addInitScript((t) => {
    try { localStorage.setItem('theme', t) } catch {}
  }, theme)
}

async function authenticate(context, email, password) {
  // Ouvre une session via l'API (le cookie est partagé avec le contexte du navigateur).
  const req = context.request
  await req.get(`${API}/csrf-cookie`)
  const cookies = await context.cookies()
  const xsrf = decodeURIComponent(cookies.find((c) => c.name === 'XSRF-TOKEN')?.value ?? '')
  const res = await req.post(`${API}/auth/login`, {
    headers: { 'X-XSRF-TOKEN': xsrf, Accept: 'application/json' },
    data: { email, password },
  })
  if (!res.ok()) throw new Error(`Login failed (${email}): HTTP ${res.status()}`)
}

async function autoScroll(page) {
  // Fait défiler la page pour déclencher les animations/chargements au scroll, puis revient en haut.
  await page.evaluate(async () => {
    await new Promise((resolve) => {
      let y = 0
      const step = () => {
        window.scrollBy(0, Math.max(300, window.innerHeight * 0.8))
        y += window.innerHeight * 0.8
        if (y >= document.body.scrollHeight) { window.scrollTo(0, 0); resolve() }
        else setTimeout(step, 120)
      }
      step()
    })
  }).catch(() => {})
  await page.waitForTimeout(400)
}

async function shoot(page, file) {
  await autoScroll(page)
  await page.waitForTimeout(600)
  await page.screenshot({ path: join(OUT, file), fullPage: true })
  console.log('✓', file)
}

async function openMenu(page) {
  // Sur mobile, la barre latérale de l'admin est repliée derrière un bouton (SidebarTrigger).
  const trig = page.getByRole('button', { name: /toggle sidebar|menu/i }).first()
  if ((await trig.count()) && (await trig.isVisible().catch(() => false))) {
    await trig.click().catch(() => {})
    await page.waitForTimeout(350)
  }
}

async function clickNav(page, label) {
  for (const loc of [page.getByRole('button', { name: new RegExp(`^${label}`, 'i') }),
                     page.getByRole('link', { name: new RegExp(`^${label}`, 'i') }),
                     page.getByText(new RegExp(`^${label}$`, 'i'))]) {
    const el = loc.first()
    if (await el.count()) { await el.click().catch(() => {}); return true }
  }
  return false
}

async function run() {
  const browser = await chromium.launch()
  for (const vp of VIEWPORTS) {
    for (const theme of THEMES) {
      const suffix = `${vp.name}-${theme}`
      const vpArg = { width: vp.width, height: vp.height }

      // --- Contexte 1 : public + tableau de bord d'un utilisateur de démo ---
      const ctx = await browser.newContext({ viewport: vpArg, colorScheme: theme })
      await setTheme(ctx, theme)
      let page = await ctx.newPage()

      for (const p of PUBLIC_PAGES) {
        await page.goto(`${BASE}${p.path}`, { waitUntil: 'networkidle' }).catch(() => {})
        await shoot(page, `${p.id}-${suffix}.png`)
      }

      // Modale de connexion / inscription (best-effort)
      await page.goto(BASE, { waitUntil: 'networkidle' }).catch(() => {})
      for (const label of ['Sign in', 'Log in', 'Get started', 'Se connecter', 'Login']) {
        if (await clickNav(page, label)) break
      }
      await page.waitForTimeout(600)
      await shoot(page, `02-auth-dialog-${suffix}.png`)

      // Tableau de bord de l'utilisateur de démo (abonnement actif + VPN)
      await authenticate(ctx, DEMO_EMAIL, DEMO_PASSWORD).catch((e) => console.warn('demo auth:', e.message))
      await page.goto(`${BASE}/dashboard`, { waitUntil: 'networkidle' }).catch(() => {})
      await shoot(page, `11-dashboard-${suffix}.png`)
      await ctx.close()

      // --- Contexte 2 : administrateur ---
      const actx = await browser.newContext({ viewport: vpArg, colorScheme: theme })
      await setTheme(actx, theme)
      await authenticate(actx, ADMIN_EMAIL, ADMIN_PASSWORD).catch((e) => console.warn('admin auth:', e.message))
      page = await actx.newPage()
      await page.goto(`${BASE}/admin`, { waitUntil: 'networkidle' }).catch(() => {})
      await shoot(page, `12-admin-${suffix}.png`)

      let i = 13
      for (const tab of ADMIN_TABS) {
        await page.keyboard.press('Escape').catch(() => {}) // ferme la feuille mobile éventuellement ouverte
        await openMenu(page)
        if (await clickNav(page, tab)) {
          await page.keyboard.press('Escape').catch(() => {}) // referme la feuille avant la capture
          await page.waitForTimeout(700)
          const slug = tab.toLowerCase().replace(/\s+/g, '-')
          await shoot(page, `${String(i++).padStart(2, '0')}-admin-${slug}-${suffix}.png`)
        }
      }

      // --- États interactifs (sur l'onglet Accounts) ---
      await page.keyboard.press('Escape').catch(() => {})
      await openMenu(page)
      await clickNav(page, 'Accounts')
      await page.keyboard.press('Escape').catch(() => {})
      await page.getByRole('button', { name: /^Delete$/ }).first().waitFor({ timeout: 8000 }).catch(() => {})
      // a) dialogue de confirmation de suppression (sur la première ligne supprimable)
      const delBtn = page.getByRole('button', { name: /^Delete$/, exact: true }).and(page.locator('button:enabled')).first()
      if (await delBtn.count()) {
        await delBtn.scrollIntoViewIfNeeded().catch(() => {})
        await delBtn.click().catch(() => {}); await page.waitForTimeout(500)
        await shoot(page, `22-state-delete-confirm-${suffix}.png`)
        await clickNav(page, 'Cancel').catch(() => {})
      }
      // b) dialogue de changement de rôle (change le premier menu Rôle)
      const roleSelect = page.locator('select').filter({ hasText: /User|Admin/ }).first()
      if (await roleSelect.count()) {
        await roleSelect.selectOption({ label: 'Admin' }).catch(() => {}); await page.waitForTimeout(400)
        await shoot(page, `23-state-role-change-${suffix}.png`)
      }
      await actx.close()
    }
  }
  await browser.close()
  console.log('Terminé.')
}

run().catch((e) => { console.error(e); process.exit(1) })
