# Mobile app — facts from the source code

> Read-only analysis of `E:\PFE\nexus-vpn-feature-android-app` (Mossab's "android app" branch), done 2026-10-07. Nothing in that folder was changed.
> This answers the mémoire's mobile questions (decisions.md Q-04) and plan task 4.2. **It is analysis notes, not a chapter.**
>
> ⚠️ **Headline finding:** this folder is **not a native Android app**. It is a **Next.js web project** (the same kind as our main frontend). There is no Android/Gradle project, no `android/` folder, no APK, no Kotlin/Java, not even a PWA manifest. The folder name `nexus-vpn-feature-android-app` is just the GitHub zip name of a branch called `feature/android-app`. See Q-23 in decisions.md — the student must decide what the "mobile app" really is before we write that part.

## 1. Framework, language, versions, libraries, structure, build

| Item | Finding |
|---|---|
| Type | Web app (Next.js App Router), **not** a mobile/native app |
| Framework | Next.js **16.2.6**, React **19** |
| Language | TypeScript **5.7.3** (`next.config.mjs` sets `typescript.ignoreBuildErrors: true` → type errors are hidden at build) |
| Styling | Tailwind CSS **4.2**, Radix UI primitives, shadcn-style components, `framer-motion`, `lucide-react` |
| Forms/validation | `react-hook-form` + `zod` (present as deps; the auth/payment dialogs use plain `useState`) |
| Package manager | pnpm (has `pnpm-lock.yaml`, `pnpm-workspace.yaml`) |
| Origin | Partly built with **v0** (Vercel): `.gitignore` lists `__v0_*` sandbox files and `.v0-trash/` |
| Structure | `app/` (routes), `components/` (sections + `ui/`), `lib/` (`api.ts`, `pricing.ts`, `utils.ts`), `hooks/`, `db/schema.sql`, `public/`, and a bundled copy of the Laravel backend in `nexus-vpn/` |
| Size | 1.6 MB (no `node_modules`, no `.git`) |
| **How to build an APK** | **Not possible from this folder** — there is no Android project. It builds as a website: `pnpm install` then `pnpm dev` (local) or `pnpm build` (production). To make it a real APK you would have to add something like Capacitor/Bubblewrap — that is new work and would change the folder. |

## 2. Own backend, or only the app? Differences from our main project

It carries **three** backend-ish things:

1. **A full copy of our Laravel backend** in `nexus-vpn/` (has `artisan`, `composer.json`, `Dockerfile`, `render.yaml`-style config). Same backend as the main project, bundled into this branch.
2. **Its own Next.js API routes**: `app/api/pricing/route.ts` (a thin proxy to the Laravel `/api/pricing`) and `app/api/checkout/route.ts` (**a fake/mock payment** — see §3).
3. **A standalone `db/schema.sql`** written for **MySQL** (`CREATE DATABASE nexus_vpn`, InnoDB, utf8mb4) with tables `users, password_resets, plans, subscriptions, payments, teams, team_user`. This is **not** used by Laravel (Laravel uses its own migrations and SQLite/PostgreSQL) — it looks like a parallel design that is not wired to anything.

**Differences vs. our main frontend:**

| | Main project (`nexus-vpn`) | This "android app" branch |
|---|---|---|
| Pages | `/` , `/dashboard`, `/payment`, `/payment/result`, `/verify-email`, `/reset-password`, `/admin` | `/` (landing) + `/admin` only |
| Checkout | Real **Chargily** payment + webhook | **Mock** `/api/checkout` returning a random fake receipt |
| Pricing source | Backend `/api/pricing` | Hard-coded in `lib/pricing.ts` **and** proxied — two sources of truth |
| DB | Laravel migrations (SQLite / PostgreSQL) | Extra unused MySQL `db/schema.sql` |
| Feature claims | WireGuard portal | Marketing text says "87 server locations", "WireGuard & OpenVPN" (OpenVPN/87 locations are likely **not** true for our product) |

So this branch is a **smaller, alternative landing-page frontend**, not a superset of the main app and not a mobile app.

## 3. Which API it calls + how it logs in (vs. our Laravel API)

- **Base URL:** `lib/api.ts` calls `/backend-api/...`. `next.config.mjs` rewrites `/backend-api/:path*` → `${NEXT_PUBLIC_API_URL}/api/:path*` (default `http://127.0.0.1:8000`). On Vercel it refuses to build unless `NEXT_PUBLIC_API_URL` is set.
- **Auth model:** session cookies — `credentials: 'include'`, plus CSRF handling (`/backend-api/csrf-cookie` and an `X-XSRF-TOKEN` header read from the cookie). **This is the same style as our Laravel API** (browser session cookie + CSRF).
- **But** the three auth calls pass `skipCsrf: true`, so in practice they send the session cookie and skip the CSRF token. And it calls `/backend-api/csrf-cookie` → `/api/csrf-cookie`, whereas Laravel Sanctum's default is usually `/sanctum/csrf-cookie`. So the CSRF wiring is inconsistent and may not match our backend as-is.
- **Endpoints this app expects from Laravel** (`/backend-api/*` → `<backend>/api/*`):
  - `GET /api/me` (who am I)
  - `POST /api/auth/login`, `POST /api/auth/register`, `POST /api/auth/logout`
  - `GET /api/csrf-cookie`
  - `GET /api/pricing`
  - `GET /api/admin/users`, `PATCH /api/admin/users/{id}`
  - `GET /api/admin/plans`, `POST /api/admin/plans`, `PATCH /api/admin/plans/{id}`
- **Does it work with our current backend?** Not confirmed, and probably **not out of the box**. It needs: (a) those exact routes to exist on our Laravel API — to be checked with `php artisan route:list`; our real portal uses Chargily checkout, email verification, VPN config download, dashboard/subscription/receipt endpoints that this app never calls, and the route **names** may differ; (b) CORS + Sanctum stateful-domains + session-cookie settings to include this app's origin; (c) `NEXT_PUBLIC_API_URL` set. **No backend change has been made** (read-only).
- **Payment does NOT use the backend:** the payment dialog posts raw card number / expiry / CVC to the local mock `/api/checkout`, which returns `"Payment successful... Receipt: pay_xxxx"`. It never contacts Chargily. ⚠️ For a thesis about *secure* e-commerce payment, presenting a form that collects raw card numbers and fakes success is a problem — flag it clearly; our real portal uses Chargily and never touches card data.

## 4. Every screen and what it does

**Public landing page `/`** (one scrolling page = `app/page.tsx` composing sections):
- **Navbar** — logo, nav links, theme toggle, Sign-in button / account menu.
- **Hero** — headline + call to action.
- **Stats** — numbers (users, servers, etc.).
- **Features** — feature grid.
- **Map** — "server locations" map section.
- **Testimonials** — customer quotes.
- **Pricing** — the 3 plans (Essential 1,119 DZD/mo, Standard 1,679 DZD/mo, Team custom) with a "Pay" button that opens the payment dialog.
- **Footer**.
- **Modals/overlays:** Auth dialog (Sign-in / Sign-up tabs; sign-up also picks a plan), Payment dialog (fake card form), Account menu (when signed in), light/dark theme.

**Admin dashboard `/admin`** (`app/admin/page.tsx`, gated to `role === "admin"`, else redirects to `/`): a sidebar with 7 sections — **Overview, Accounts, Offers, Subscriptions, Analytics, Logs, Settings**. It loads `/admin/users` and `/admin/plans`, can edit a user's name/role, and create/edit pricing offers.

That is the whole app: **one public page (with dialogs) + one admin page.** There are no separate "mobile screens".

## 5. Is it complete / working, or missing / broken?

It **renders as a demo website**, but it is **not a finished, integrated product and not a mobile app**:
- ✅ Builds and runs as a Next.js site; responsive; light/dark theme; clean landing + admin UI.
- ⚠️ **Fake checkout** (no Chargily; collects raw card data).
- ⚠️ **Pricing duplicated** (hard-coded + proxied) and marketing claims that don't match the real product (OpenVPN, 87 locations).
- ⚠️ **Unused MySQL schema** that doesn't match the real backend.
- ⚠️ **`ignoreBuildErrors: true`** hides TypeScript errors.
- ⚠️ **Backend integration unverified** — endpoint names/CSRF likely need alignment; none of the real portal's core flows (Chargily payment, email verification, WireGuard config download, dashboard, receipts) are implemented here.
- ⚠️ **No native layer** (no Android project, no PWA manifest/service worker).

## 6. Git / secrets safety

- The folder is a **sibling** of the repo (`E:\PFE\nexus-vpn-feature-android-app`, next to `E:\PFE\nexus-vpn`), has **no `.git`**, and is **not tracked** by the main repo, so the main repo cannot push it.
- Scanned for secrets: only `nexus-vpn/.env.example` (a safe template). **No** real `.env`, `.sqlite`/`.db`, `.apk`, `.keystore`, `.pem`, or `.mcp.json` files.
- Its own `.gitignore` already ignores `.env*` (except `.env.example`), `*.sqlite`, `*.db`, `*.log`, `nexus-vpn/.mcp.json` — so if the student ever puts this folder in its own GitHub repo, secrets are covered.
- Separate risk (not this folder): our main repo's `docs/memoire-ref/` (the other student's 19/20 mémoire + the methodology guide) is **not** a native app issue but **was not git-ignored** — fixed on 2026-10-07 by adding it to the main `.gitignore` (P-05).

## What the mémoire needs about the mobile app — depends on Q-23

Until the student decides (Q-23), three realistic routes:
- **(a) Treat this as a responsive *web* client** (same portal, mobile-friendly) and show it at phone width — honest, no "native app" claim, no emulator needed.
- **(b) A real native app exists elsewhere** (Mossab has/makes an APK) → then we do a true mobile chapter (framework, screens, APK, install).
- **(c) Drop the mobile app from scope** → web-only, list mobile as future work.
