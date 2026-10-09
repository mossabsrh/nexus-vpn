# Ground truth — the real NexusVPN project (verified)

> **Step 1 of the pre-chapter verification** (2026-10-09). Everything here was verified directly against the **source code**, the **database**, the **route list**, the **installed packages** and the **running website** (live Playwright browse). It is the reference for auditing the existing docs (Step 2) and the mémoire. File paths are relative to the repo root `E:\PFE\nexus-vpn` (backend = `nexus-vpn/`, frontend = repo root).
>
> **Mobile app is POSTPONED (D-16).** Where mobile content will later go, this file says so with a clear *[MOBILE placeholder]* marker. The Sanctum token API for mobile already exists in the backend (see §4.7) and stays.

## 1. How this was verified (method)

- Read the backend (all `nexus-vpn/app/**`, routes, config, `AppServiceProvider`) and frontend (`lib/`, `components/`, `app/`, `proxy.ts`, `next.config.mjs`).
- Database: `php artisan migrate:status`, `php artisan db:table <t>` for every domain table, row counts via `tinker`.
- Routes: `php artisan route:list --except-vendor` (94 routes).
- Packages: `composer.json`, `package.json`, `php artisan about`, `pnpm install` output (installed versions).
- Live browse: started Laravel (`:8000`) + Next prod (`:3000`), verified `/up` = 200 and `/api/pricing`, then Playwright as **visitor** (home), **demo user** (dashboard), **admin** (console + all 8 tabs) — all render real data. Servers stopped afterwards. Dev DB backed up first (`database/database.sqlite.bak-groundtruth-*`, git-ignored).

## 2. Environment & stack (actually installed)

| Layer | Fact |
|---|---|
| Backend framework | **Laravel 12.60.2** (`php artisan about`) |
| PHP | **8.2.12 local** · **8.3 in Docker** (`Dockerfile`) · **8.4 in CI** (`.github/workflows/*`) — version spread to note |
| Backend packages (`composer.json`) | `laravel/framework ^12`, `laravel/sanctum ^4.3` (v4.3.3 installed), `laravel/tinker`, `inertiajs/inertia-laravel ^2` + `tightenco/ziggy ^2` (**starter-kit leftovers, see §6**). Dev: `phpunit ^11.5`, `pint`, `pail`, `sail`, `mockery`, `collision`, `faker`, `laravel/boost` |
| Frontend framework | **Next.js 16.2.6**, **React 19**, **TypeScript 5.7.3** |
| Frontend packages (`package.json`) | Radix UI (shadcn/ui), **Tailwind CSS 4.2**, `framer-motion`, `lucide-react`, `recharts`, `sonner`, `react-hook-form` + `zod`, `date-fns`, `next-themes`, `@vercel/analytics`; dev: `playwright 1.63`, `eslint 9.39.5 + eslint-config-next 16.3.4` |
| Package manager | pnpm (v10.34.6) frontend · Composer 2.8.12 backend |
| Node | v22 |
| DB (local) | **SQLite** (`database/database.sqlite`) |
| DB (production) | **PostgreSQL** (`render.yaml` → `DB_CONNECTION=pgsql`) |
| Drivers (local, `php artisan about`) | cache=**database**, queue=**database**, session=**database**, mail=**smtp**, broadcast=log |
| Fonts | Geist + Geist Mono (`app/layout.tsx`), default theme **dark** |

## 3. Architecture (verified)

```
Browser ──fetch('/backend-api/…', credentials:include)──▶ Next.js (frontend, Vercel)
   next.config.mjs rewrite: /backend-api/:path*  →  {NEXT_PUBLIC_API_URL}/api/:path*
   proxy.ts (Next middleware): server-side gate on /admin and /dashboard (checks /api/me)
Laravel API (Render, Docker PHP/Apache) ── SecurityHeaders → web(session+CSRF)/throttle/auth/admin → controller → models → PostgreSQL
   ◀── JSON (or text/plain file for /vpn/config) relayed to the browser same-origin
External: Chargily Pay v2 (checkout + HMAC webhook) · SMTP e-mail · WireGuard gateway (peers exported by vpn:peers)
```

- **Single origin** for the browser (the frontend); Laravel session cookie + `XSRF-TOKEN` are first-party. `lib/api.ts` adds `X-XSRF-TOKEN` and retries once on 419.
- Two API surfaces: **web** (session+CSRF, `routes/api.php` inside the `web` group) and **mobile** (`/api/mobile/*`, Sanctum bearer token, outside `web`, **D-16 postponed app**).

## 4. Backend (`nexus-vpn/`)

### 4.1 Routes — 94 total (`php artisan route:list`)
- **Public API:** `GET /api/pricing`, `GET /api/testimonials`, `POST /api/webhooks/chargily` (HMAC, no session/CSRF).
- **Web-session API (the real product)** — `routes/api.php` under `['web','throttle:api']` + `csrf-cookie`: auth (`register`,`login`,`logout`,`forgot-password`,`reset-password`,`email/verify/{id}/{hash}`), `me`, `email/verification-notification`, `account/{email,password}` + `DELETE account`, `account/subscriptions|payments|payments/{id}`, `vpn/credentials|config`, `testimonials` (POST), `checkout`, `payments/{id}/verify`, `checkout/{id}/verify`, `subscriptions/trial|cancel`, `team` (show/update/members…), and `admin/*` (users, plans, testimonials, audit-logs, contact-requests, stats, payments, subscriptions) under `['auth','admin']`.
- **Mobile token API** — `/api/mobile/*` under `auth:sanctum` (no CSRF). *[MOBILE placeholder — built, app postponed.]*
- **Starter-kit web (Inertia) + legacy** — `routes/web.php`/`auth.php`/`settings.php`: `/` (Inertia `welcome`), `dashboard` (Inertia), `login/register/forgot-password/reset-password/verify-email/confirm-password/settings/*`. **These are Laravel React starter-kit leftovers; the real UI is the Next.js app.** `payment/success|failed` redirect to the frontend result page (legacy Chargily return URLs).

### 4.2 Controllers (`app/Http/Controllers/Api/`)
| Controller | What it does |
|---|---|
| `AuthController` | register (+7-day trial), login (per-account lockout, see §7), logout, `me`, forgot/reset password, `verifyEmail` (signed), resendVerification, updateEmail/updatePassword, deleteAccount. `userPayload()` (id,name,email,role,emailVerified,planId,subscriptionStatus,subscription,hasVpnAccess,team) reused widely. `updatePassword`/`deleteAccount` are token-safe (hasSession guard + token revocation). |
| `MobileAuthController` | token login/register/logout (Sanctum). *[MOBILE — postponed app.]* |
| `AccountController` | subscriptions (paginated), payments (paginated), one payment (owner-only, 404 otherwise), `vpnStatus`, `vpnConfig` (issues a WireGuard peer, returns `nexusvpn.conf` as `text/plain` attachment; 403 if no access/unverified, 503 if server not configured). |
| `PaymentController` | `createCheckout` (verified-email required, self-serve+amount>0, creates pending `Payment` then calls Chargily, returns `checkout_url`), `verifyPayment`/`verify` (sync with Chargily), `startTrial` (one per account), `cancelSubscription`, `webhook` (HMAC, handles paid/failed/canceled/expired). |
| `PricingController` | public plan list, cached 10 min, flushed on admin plan change; fields incl. `selfServe`. |
| `AdminController` | users (search/filter/paginate), updateUser (can't drop own admin), destroyUser (can't delete last admin / self / team owner → `closeAccount`), plans CRUD (delete blocked if subscriptions → deactivate), testimonials list/moderate/delete (+pendingCount), auditLogs. |
| `AdminBillingController` | stats (users, subscriptions by plan/status, revenue by month, trial conversion, payments by status), payments list, refund (records a dashboard refund; Chargily v2 has no refund API), subscriptions list, createTeamSubscription (sales-assisted), cancelSubscription (immediate). |
| `ContactController` | Team-plan sales inquiries (store public, index/update admin). |
| `TeamController` | team owner manages seats: show, update name, addMember (checks seat limit, member must have an account), removeMember (revokes their VPN if no other access). |
| `TestimonialController` | approved testimonials list; submit (1 pending per user + hourly throttle, waits for moderation). |

### 4.3 Domain models & business rules (`app/Models/`)
- **User** — roles `user`/`admin`; SoftDeletes; `HasApiTokens` (Sanctum); relations subscriptions/payments/vpnCredentials/teams/ownedTeams; `currentSubscription()`, `accessSubscription()` (own or team seat), `closeAccount()` (anonymise + soft-delete, keep billing history, revoke VPN, leave teams, purge sessions/resets).
- **Plan** — `isSelfServe()` = `monthly_price > 0` (Team has null price → sales-assisted); SoftDeletes; `features` JSON.
- **Subscription** — one **current** (`trialing`/`active`) per user (DB unique `subscriptions_one_current_per_user`); `grantingAccess` scope = current OR canceled with `current_period_end` in the future (paid period honoured after cancel); `cancel()` (trial ends now, paid keeps period); `revokeAccessIfEnded()`.
- **Payment** — `markSucceeded()` (locked, idempotent pending→succeeded), `markFailed()`, `recordRefund()`, `applyToSubscription()` (renewal extends, trial converts, plan-change switches, resubscribe starts after paid days left); `afterActivation()` issues VPN + sends receipt. `provider_response` hidden.
- **VpnCredential** — WireGuard **Curve25519** keypair (`sodium_crypto_scalarmult_base`), `private_key` **encrypted** cast; address from pool `10.66.0.0/16` (never reused; `.1` = server); `toWireGuardConfig()` ([Interface]/[Peer]); `serverIsConfigured()` needs `VPN_SERVER_PUBLIC_KEY`+`VPN_SERVER_ENDPOINT`.
- **Team** — owner + members (pivot role), `seatLimit()` from subscription seats.
- **Testimonial** — `approved` scope; status pending/approved/rejected.
- **ContactRequest** — new/handled; name/email/company/seats/message/ip.
- **AuditLog** — append-only (`UPDATED_AT=null`); `record(action, subject?, metadata?, actor?)`; never store secrets; 85 rows now.

### 4.4 Service — `app/Services/ChargilyGateway.php`
Chargily Pay v2 client. Base URL `https://pay.chargily.net/test/api/v2` (test) / `.../api/v2` (live). `createCheckout`, `retrieveCheckout`, `expireCheckout`; `verifySignature` = HMAC-SHA256(raw body, secret) `hash_equals`; `applyCheckoutStatus` (activate only if amount **and** currency match, else `amount_mismatch`/`currency_mismatch`; `paid_after_failure` alert).

### 4.5 Middleware
- `SecurityHeaders` (global): `X-Content-Type-Options:nosniff`, `X-Frame-Options:DENY`, `Referrer-Policy`, `Permissions-Policy`; CSP `default-src 'none'; frame-ancestors 'none'` for `api/*` and `/up`; HSTS when HTTPS.
- `EnsureUserIsAdmin` (alias `admin`): `abort_unless(isAdmin, 403)`.
- `trustProxies` from `TRUSTED_PROXIES` (`*` on Render) so per-IP limits see the real client IP (`bootstrap/app.php`).

### 4.6 Rate limiting (`AppServiceProvider::configureRateLimiting`)
`api` 120/min · `login` 10/min per IP · `register` 3/min + 20/day · `password-reset` 3/min + 10/h · `account` 6/min · `verification` 6/min · `checkout` 5/min + 30/day · `trial` 3/min · `testimonials` 3/h · `vpn` 10/min · `contact` 2/min + 10/day · `webhooks` 120/min. (Plus the per-account **5-failure login lockout** in the controller.)

### 4.7 Scheduler & commands (`routes/console.php`, `app/Console/Commands/`)
- `subscriptions:expire` (`ExpireSubscriptions`) — every 15 min.
- `payments:reconcile` (`ReconcilePayments`) — every 15 min (syncs stale pending, expires abandoned checkouts).
- `subscriptions:remind` (`SendSubscriptionReminders`) — daily 09:00.
- `vpn:peers` (`ExportVpnPeers`) — export active peers to the gateway.
- `admin:grant {email}` — grant admin.

### 4.8 Config
- `config/services.php`: `chargily` (mode/secret_key/webhook_url); `vpn` (server_public_key/endpoint/dns=1.1.1.1/allowed_ips=`0.0.0.0/0, ::/0`/address_pool=10.66.0.0/16/client_prefix=32); `frontend_url` default `https://nexus-vpn-upgraded.vercel.app` **(old project name — see §11)**. Also `resend`, `postmark`, `ses`, `slack` stubs (so **Resend/Postmark HTTP mailers are one env change away** — relevant to the Render-SMTP decision Q-22/0.14).
- `config/sanctum.php`: token `expiration` = `SANCTUM_TOKEN_EXPIRATION` (default 30 days); guard `['web']`.
- `config/auth.php`: only the `web` (session) guard defined; Sanctum injects its `sanctum` guard at runtime.
- `AppServiceProvider` also: `Schema::defaultStringLength(191)`, refuses `APP_DEBUG=true` in production and warns on dev defaults, and builds reset/verify e-mail links to the frontend.

## 5. Database (authoritative, from `db:table`)

19 tables; 18 migrations (all run). Framework tables: `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `sessions`, `migrations`, `password_resets`. **Domain tables** (columns summarised; full types verified via `db:table`):

- **users** (10 cols): id, name, email (unique), password, email_verified_at?, remember_token?, role default `'user'`, deleted_at?, timestamps.
- **plans**: id, slug (unique), name, description?, monthly_price?, yearly_price?, yearly_total?, currency default `'DZD'`, features (JSON text)?, popular `tinyint` default 0, cta?, is_active default 1, deleted_at?, timestamps.
- **subscriptions**: id, user_id→users(restrict), plan_id→plans(restrict), seats default 1, billing_period default `'monthly'`, status default `'active'`, started_at?, trial_ends_at?, current_period_end?, canceled_at?, trial_reminder_sent_at?, renewal_reminder_sent_at?, deleted_at?. **Unique** `subscriptions_one_current_per_user(user_id)`; index `(status,current_period_end)`.
- **payments**: id, user_id→users(restrict), subscription_id?→subscriptions(set null), amount (int), currency `'DZD'`, status default `'pending'`, provider?, provider_response (JSON)?, provider_reference? **(unique)**, metadata (JSON)?, paid_at?, failure_reason?, error_code?, refunded_at?, refund_reason?, deleted_at?. Index `(status,paid_at)`.
- **teams**: id, name, owner_id→users(cascade), subscription_id?→subscriptions(set null).
- **team_user**: team_id→teams(cascade), user_id→users(cascade), role default `'member'`, created_at; PK (team_id,user_id).
- **testimonials**: id, user_id→users(cascade), content, status default `'pending'`, moderated_by?→users(set null), moderated_at?. Index `(status,created_at)`.
- **contact_requests**: id, user_id?→users(set null), name, email, company?, seats?, message, status default `'new'`, handled_by?→users(set null), handled_at?, ip_address?. Index `(status,created_at)`.
- **vpn_credentials**: id, user_id→users(restrict), subscription_id?→subscriptions(set null), public_key **(unique)**, private_key (text, **encrypted**), address **(unique)**, status default `'active'`, revoked_at?. Index `(user_id,status)`.
- **audit_logs**: id, actor_id?→users(set null), action, subject_type?, subject_id?, metadata (JSON)?, ip_address?, created_at (no updated_at). Indexes on action, created_at, (subject_type,subject_id).
- **personal_access_tokens** (Sanctum): tokenable morph, name, token (unique), abilities, last_used_at?, expires_at? *(MOBILE — token store; 0 rows)*.

**Real data now (dev DB):** users 13 (admins 1), plans 4 (3 active), subscriptions 8, payments 8, teams 1, testimonials 4, contact_requests 3, audit_logs 85, vpn_credentials 9, tokens 0. (Admin = `mohamed.belkacemi2004@gmail.com`; demo users `@demo.nexusvpn.dz` from `DemoDataSeeder`.)

## 6. Frontend (repo root)

- **Next.js 16 App Router**, shadcn/ui (Radix) in `components/ui/` (~55 primitives), Tailwind 4, Geist font, `next-themes` (dark default). `components.json` = shadcn config.
- **API client** `lib/api.ts`: `apiFetch` to `/backend-api`, adds `Accept`, `X-XSRF-TOKEN`; fetches `csrf-cookie` before unsafe methods; retries once on **419**. `apiErrorMessage` extracts first validation error / message / error.
- **Gate** `proxy.ts` (Next 16 "proxy" = renamed middleware), matcher `['/admin/:path*','/dashboard/:path*']`: calls `/api/me`; no cookie / 401 / 419 → redirect `/`; `/admin` requires `role==='admin'`, fails **closed** on backend error; dashboard fails open.
- **`next.config.mjs`**: CSP (`default-src 'self'`, `script-src 'self' 'unsafe-inline'`(+`unsafe-eval` dev), `connect-src 'self'`, `frame-ancestors 'none'`…) + security headers + the `/backend-api` rewrite; `images.unoptimized`; throws on Vercel if `NEXT_PUBLIC_API_URL` unset.
- **Pages (`app/`):**
  - `/` — landing: Navbar, Hero, Stats, Features, Map, Testimonials, Pricing (Monthly/Yearly toggle), **Contact** (Team inquiry form), Footer. (Sections lazy-loaded via `next/dynamic`.)
  - `/dashboard` — subscription card, VPN access card (download `nexusvpn.conf`), team card (owner manages members), payment history (+ receipt dialog), subscription history, verify-email banner.
  - `/admin` — sidebar console, 8 sections: **Overview, Accounts, Offers, Subscriptions, Payments, Analytics, Comments, Audit log** (`components/admin/{stats,accounts,offers,billing,moderation}.tsx`).
  - `/payment/result` — polls `/payments/{id}/verify` up to 6× every 3 s; succeeded/pending/failed/refunded states.
  - `/verify-email` — calls the signed verify route; verified/invalid (links valid 60 min); resend.
  - `/reset-password` — set a new password from the e-mailed token.
- **Auth/checkout components:** `auth-provider.tsx` (session via `/me`, login/signup/startTrial/cancel/updateEmail/updatePassword/deleteAccount/resend/logout), `auth-dialog.tsx` (sign-in/sign-up/forgot modal with plan picker, 7-day trial), `payment-dialog.tsx` (POST `/checkout` → redirect to Chargily, EDAHABIA/CIB, "no auto-renew"), `account-menu.tsx` (settings + delete-account danger zone), `use-pricing.ts` (live `/pricing`, cached, no hard-coded fallback).

## 7. Security (verified)

- **Web auth:** session cookie + CSRF (`web` group); `login` uses `Auth::attempt` + session regenerate; per-account **lockout after 5 failures** (email+IP, 60 s) on top of the `login` route limit (10/min). Passwords bcrypt (`BCRYPT_ROUNDS=12`).
- **Mobile auth:** Sanctum bearer tokens, 30-day expiry, revoked on logout/password-change/delete. *[MOBILE — app postponed.]*
- **CSRF** on every state-changing web route; **419** auto-refresh+retry client side.
- **Headers/CSP** on both API (`SecurityHeaders`) and frontend (`next.config.mjs`).
- **WireGuard private keys encrypted** at rest; **Chargily webhook HMAC-verified**; payment activation only on amount+currency match; **idempotent** payment application.
- **Admin** double-gated (proxy.ts + `admin` middleware + client check).
- **Production guards** (`AppServiceProvider`): refuse `APP_DEBUG=true`; warn on sqlite / non-live Chargily / placeholder mail / unencrypted session / debug logs.
- **No MFA** for any account (admin included) — a real limitation (matches risk R-07).

## 8. Key flows (verified, with live screenshots)

1. **Register → verify → trial:** register starts a 7-day trial of the chosen self-serve plan + sends a verification e-mail; verify link (signed, 60 min) marks the e-mail verified.
2. **Checkout (Chargily):** `/checkout` (verified e-mail required) → `checkout_url` → Chargily hosted page (EDAHABIA/CIB) → return to `/payment/result` which polls verify; webhook (HMAC) activates idempotently; receipt e-mail + VPN peer issued.
3. **VPN config:** dashboard "Download WireGuard config" → `/vpn/config` issues a peer and returns `nexusvpn.conf`. **Locally the dashboard shows "VPN servers are being set up"** because `VPN_SERVER_*` is unset (verified live).
4. **Cancel / refund:** user cancel keeps paid period; admin refund records a dashboard refund and can end the subscription + revoke VPN.
5. **Account deletion:** self-serve (`DELETE /api/account`, password) and admin — anonymise + soft-delete, keep billing history, revoke VPN, block last admin / team owner.
6. **Team:** admin provisions a team subscription (custom seats); owner adds/removes members (each gets their own config).

## 9. Deployment & CI (verified from the repo)

- **`render.yaml`** (repo root): web service `nexus-vpn-api`, **Docker**, `rootDir: nexus-vpn`, **region frankfurt, plan free**, health `/up`. Env: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` from `RENDER_EXTERNAL_URL`, `TRUSTED_PROXIES=*`, **`DB_CONNECTION=pgsql`** via `DB_URL` from a **free Postgres** `nexus-vpn-db`, cache/session/queue=database, `SESSION_SECURE_COOKIE`/`SESSION_ENCRYPT=true`, `SESSION_SAME_SITE=lax`, `FRONTEND_URL=https://nexus-vpn-upgraded.vercel.app` (old name), Chargily **test** (secret `sync:false`), **MAIL smtp gmail:587** (username/password/from `sync:false`), VPN keys `sync:false`.
- **`Dockerfile`** (`nexus-vpn/`): `php:8.3-apache-bookworm`, installs `bcmath mbstring pdo_pgsql zip`, `a2enmod rewrite headers`, `composer install --no-dev`, Apache vhost, `EXPOSE 10000`, `docker/entrypoint.sh`.
- **`docker/`**: `entrypoint.sh`, `laravel-vhost.conf`, `predeploy.sh`.
- **Frontend:** Vercel (Next.js) with `NEXT_PUBLIC_API_URL` → the Render URL (the `/backend-api` rewrite target).
- **⚠️ Render Free blocks outbound SMTP (25/465/587)** → the Gmail SMTP config above will **not** send mail on Render Free. Decision pending (Q-22/0.14). Note `config/services.php` already wires **Resend** and **Postmark** (HTTP mailers) — an easy switch.
- **⚠️ CI workflows exist but are effectively inactive:** `nexus-vpn/.github/workflows/{tests,lint}.yml` run PHP 8.4 + phpunit (tests) and Pint + `npm run format`/`lint` (lint) on push/PR to develop/main. **But** (a) they live in `nexus-vpn/.github/` (the backend subfolder), not the **repo root** `.github/`, so GitHub would not trigger them for this monorepo; (b) `lint.yml` calls `npm run format`, which is **not** a script in `package.json`. So the plan's G-11 "no active CI" is practically right, but the leftover files must be moved/fixed (or rewritten) — not created from scratch.

## 10. Live verification result (2026-10-09)

- Backend live: `/up` = **200**; `/api/pricing` = 200 returning Essential 1119 / Standard 1679 (self-serve) + Team (custom). 
- Home page renders (hero, testimonials, Monthly/Yearly pricing, Team contact form, footer).
- Demo dashboard (Yacine Benali): Essential · Active · until 10/20/2026 · 1,119 DZD Paid; VPN card = "servers are being set up" (no `VPN_SERVER_*` locally).
- Admin console (Mohamed Belkacemi): Overview shows 13 users, 3 paying subs, 2 active trials, 1,679 DZD this month + revenue chart; all 8 tabs load. 
- Conclusion: **the web app runs correctly and matches the code after the additive Sanctum change** (web routes/UI unchanged; 130/130 tests).

## 11. Environment / tooling notes (not app bugs)

- **`intl` PHP extension not loaded** locally → `php artisan db:show --counts` fails (`Number::format`). The **app never uses `Number::`/intl**, so this affects only that diagnostic command.
- **Frontend `node_modules` was inconsistent** (missing `@swc/helpers`, so `next start` failed). Repaired with `pnpm install` (purge+reinstall). A fresh install (Vercel/CI) is unaffected. The committed `.next` build is valid once deps are present.
- `config/services.php` + `render.yaml` still default `FRONTEND_URL`/old Vercel name `nexus-vpn-upgraded` — harmless default, but set the real URL at deploy time.

## 12. Discrepancies found vs the existing docs (hand-off to Step 2)

1. **Test count**: assets `README.md` + `tests/summary.md` + `tests/test-output.txt` say **121 tests / 470 assertions**; reality is now **130 / 502** (Sanctum added 9). Update.
2. **CI**: docs/plan G-11 say "no CI". Reality: leftover `tests.yml`/`lint.yml` exist but are mislocated + partly broken (see §9). Reword G-11 and the CI task.
3. **Screenshots inventory**: prefixes skip `03–07` and `21` (renumbered); `README` figure numbers (11–27) don't line up 1:1 with file prefixes. Verify the mapping.
4. **PHP version** stated anywhere should note the spread (local 8.2 / Docker 8.3 / CI 8.4).
5. **Mailer**: docs emphasise Gmail SMTP; note Resend/Postmark are already wired and that Render Free blocks SMTP (Q-22).
6. Confirm `07-api.md`'s "48/49 endpoints" against the current **94 routes** (incl. the new mobile group) and the web-only subset.

*Full doc-by-doc audit is Step 2 (next).*
