<!--
DRAFT — Chapter IV (English Markdown, per P-03: converted to Word at the end).
Numbering follows the supervisor's structure (VPN_Portal_Thesis_Structure.txt): CHAPTER IV (IV.1–IV.7),
ordered by the methodology guide, adapted per decisions.md §5 (G-06..G-13), at the 19/20 reference level.
Every version, endpoint and code excerpt is verified against the source code (composer.lock / package.json,
routes/, app/, config/, render.yaml, Dockerfile, the test suite). Facts not provable from the repository
(live-site evidence) are marked as placeholders and tracked in docs/memoire/placeholders.md.
Technology descriptions carry footnotes to the official documentation (access date 11 October 2026).
Mobile elements are marked POSTPONED (decision D-16).
-->

# CHAPTER IV — Implementation and Deployment

## Introduction

The previous chapter translated the requirements into a design — actors, use cases, data model and business rules. This chapter turns that design into a working system and prepares its deployment. It presents, in the order of the supervisor's structure, the technologies and tools chosen for the project (IV.1), the overall software architecture (IV.2), the functional modules that make up the portal (IV.3), a walk-through of the user interface (IV.4), the implementation of the most critical components with verified code excerpts (IV.5), the quality-assurance and testing work (IV.6), and finally the production environment, continuous integration and monitoring (IV.7).

The guiding principle throughout is honesty about the state of the system. The portal is fully implemented and covered by an automated backend test suite; it is prepared for deployment on free hosting tiers but has not yet been operated in production. Wherever a claim would require the live site (security scans, load tests, production screenshots), the chapter states what can be shown from the code and configuration today and marks the live evidence as a clearly identified placeholder.

## IV.1 Technologies and Tools

The portal is built as two applications — a **Next.js** front end and a **Laravel** REST API — supported by a small, deliberately conventional set of tools. This section introduces each technology with its role and the exact version in use, grouped by purpose. The versions were read from `nexus-vpn/composer.lock` and the frontend `package.json` / `pnpm-lock.yaml`.

### IV.1.1 Languages and frameworks

The back end is written in **PHP 8.2**[^php] and built on **Laravel 12**,[^laravel] a mature PHP framework that provides routing, the Eloquent ORM, request validation, sessions and CSRF protection, rate limiting, a task scheduler, an HTTP client (used to call Chargily) and an encryption layer (used to encrypt the stored VPN private keys). The API exposes JSON only; it renders no HTML for the real application.

The front end is a **Next.js 16**[^nextjs] application written in **TypeScript 5.7**[^typescript] on top of **React 19**.[^react] It uses the App Router, server-side rendering and Next.js middleware (`proxy.ts`) to gate the private areas and to rewrite API calls to the Laravel service on the same origin. The user interface is styled with **Tailwind CSS 4**[^tailwind] and composed from accessible **shadcn/ui** components built on the Radix UI primitives.

A native mobile client in **Flutter** is planned but **postponed** (decision D-16): the backend token API and the Flutter authentication scaffolding (in the repository's `mobile/` directory) already exist, but the application itself is future work and appears in this chapter only as marked placeholders.

| Technology | Version | Role |
|---|---|---|
| PHP | 8.2 (local); 8.3 in the production image | Backend language |
| Laravel | 12.60.2 | Backend REST framework (ORM, validation, auth, scheduler) |
| Next.js | 16.2.6 | Frontend framework (App Router, middleware, same-origin rewrite) |
| React | 19.2.4 | User-interface library |
| TypeScript | 5.7.3 | Static typing (`strict` mode) |
| Tailwind CSS | 4.2.0 | Utility-first CSS framework |
| shadcn/ui · Radix UI | — | Accessible UI component library |
| Flutter | *(mobile — postponed, D-16)* | Native mobile client (future work) |

**Table IV.1 — Languages and frameworks.** *Source: author, from `composer.lock` and `package.json`.*

### IV.1.2 Database management systems

Data is stored in a relational database accessed exclusively through Laravel's **Eloquent ORM**, so the same code runs on two engines. Development and the automated tests use **SQLite**[^sqlite] (an in-memory database for the tests, which keeps them fast and isolated), while production uses **PostgreSQL**[^postgresql] hosted on Render. The schema is created and evolved by Laravel migrations, and no raw SQL is written by hand in the application.

### IV.1.3 Security and payment technologies

Several technologies address security and payment specifically:

- **Laravel Sanctum 4.3**[^sanctum] issues personal access tokens for the (postponed) mobile client, on routes kept outside the session/CSRF web group.
- **Chargily Pay v2**[^chargily4] is the payment gateway for the Algerian market: it accepts EDAHABIA and CIB cards in Algerian dinars through a hosted checkout and a signed webhook. No Chargily SDK is used — the integration goes through Laravel's HTTP client against the Chargily REST API.
- The PHP **`sodium`** extension generates the Curve25519 key pairs used for WireGuard provisioning, and Laravel's encrypter protects the private keys at rest.

Multi-factor authentication is **not** implemented and is listed as future work (gap G-06); the account-security measures actually in place — e-mail verification, account lockout after repeated failures, named rate limiters and bcrypt password hashing — are described in IV.5.

### IV.1.4 Development and operations tools

| Tool | Version / detail | Role |
|---|---|---|
| Git · GitHub | — | Version control and repository hosting (CI, IV.7.2) |
| Composer | 2 | PHP dependency manager |
| Node.js · pnpm | Node 22.15 · pnpm 10 | JavaScript runtime and package manager (frontend) |
| Docker | `php:8.3-apache-bookworm` base | Containerisation of the backend for Render |
| Render | — | Backend (Docker) and PostgreSQL hosting |
| Vercel | — | Frontend (Next.js) hosting |
| Brevo | HTTP e-mail API | Production transactional e-mail (Gmail SMTP in development) |
| PHPUnit | 11.5.55 | Backend automated testing (IV.6)[^phpunit] |
| Laravel Pint · ESLint | Pint 1.29 · ESLint 9.39 | Code style and static analysis |
| Playwright | 1.63 | Headless-browser capture of the interface screenshots[^playwright] |

**Table IV.2 — Development and operations tools.** *Source: author.*

### IV.1.5 Modelling tools

The UML and architecture diagrams of Chapter III were produced as **diagram-as-code**, so they live in the repository next to the chapters and are regenerated from text. Use-case diagrams use **PlantUML**[^plantuml] (laid out with the **Graphviz** engine); sequence, class, architecture and entity-relationship diagrams use **Mermaid**.[^mermaid] This choice is the project's equivalent of the dedicated UML tool used by the reference work: it keeps every diagram versioned, reviewable and reproducible.

### IV.1.6 Focus on a key technology: WireGuard provisioning

Following the reference work, which devotes a focused section to one distinctive technology, this project's spotlight is on **WireGuard provisioning** rather than the payment gateway. The choice is deliberate: WireGuard is the product itself (it gives the portal and the company their name), and, unlike the payment flow — which delegates to an external gateway — the provisioning logic is implemented from first principles inside the API, which makes it the most technically interesting component to examine.

WireGuard is a modern VPN protocol designed to be simpler and faster than IPsec or OpenVPN, using a fixed, modern set of cryptographic primitives.[^wgproto4] A WireGuard peer is identified by a **Curve25519** key pair: the client keeps a private key and shares its public key with the server. In this project the server generates the client's key pair, stores the private key **encrypted**, allocates a unique tunnel address from a private pool, and assembles a ready-to-import `nexusvpn.conf` file. The implementation is examined in IV.5.4; the honest limit of the current state (no live gateway is operated by the project; gap G-13) is stated there and in the conclusion.

![Technology stack](../figures/tech-stack.png){width=15cm}

**Figure IV.1 — Main technologies of the portal.** *Source: author; logos are the property of their respective projects.*

## IV.2 System Architecture

### IV.2.1 Architectural pattern

The system follows a **decoupled client–server** pattern: a Next.js front end and a single Laravel REST API, each deployed and scaled independently. The browser never calls the API across origins; instead it calls a same-origin path (`/backend-api/*`) that the Next.js layer rewrites to the Laravel service. This keeps the session cookie first-party and removes cross-origin complexity, while still allowing the two applications to be hosted on different platforms (Vercel and Render). The overall component view was given in Figure III.1.

The back end is a **modular monolith**, not a set of microservices. For a project built by a small team around a single bounded domain (selling and provisioning one VPN service), a monolith is the appropriate choice: it is simpler to develop, test and deploy, it keeps all business rules and the database under one transaction boundary, and it avoids the operational overhead (service discovery, inter-service contracts, distributed transactions) that microservices would add without a matching benefit at this scale. The code is nonetheless organised into clear modules (IV.3) so that responsibilities stay separated inside the single deployable unit.

### IV.2.2 Logical layers

Inside each application the code is organised in three layers:

| Layer | Front end (Next.js) | Back end (Laravel) |
|---|---|---|
| Presentation | React components, pages (App Router), `proxy.ts` route guard | JSON responses; no server-rendered HTML for the real app |
| Business logic | client-side form handling and view state | controllers, services (e.g. `ChargilyGateway`), domain methods on the models |
| Data | API client (`lib/api.ts`) over the same-origin rewrite | Eloquent models, migrations, SQLite / PostgreSQL |

**Table IV.3 — Logical layers of the system.** *Source: author.*

### IV.2.3 Third-party services

The API depends on three external services, each isolated behind a small, replaceable integration point:

- **Chargily Pay v2** — hosted checkout and signed webhook for EDAHABIA/CIB payments (IV.5.3).
- **E-mail** — Gmail SMTP in development and the **Brevo** HTTP API in production (Render's free tier blocks outbound SMTP; decision D-17). Account e-mails are transactional (verification, password reset, welcome, receipt, reminders).
- **WireGuard gateway** — the server that terminates the tunnels. The portal issues peers and configuration files; running the gateway is the network team's responsibility (gap G-13) and is not part of this repository.

## IV.3 Functional Modules

The portal is organised into eight functional modules. Each one groups a set of API endpoints and the business rules that govern them; all were verified against `routes/api.php` and the controllers.

### IV.3.1 Offers and pricing

Publishes the plans shown on the public pricing page. `GET /api/pricing` returns the active plans (cached for ten minutes); administrators manage them through the offers module (IV.3.8 / admin). Prices are stored in Algerian dinars and are never supplied by the client — the server always computes the amount from the plan.

### IV.3.2 Order management

Turns a chosen plan into a pending order. `POST /api/checkout` validates the selection, records a **pending payment first**, then creates a Chargily checkout and returns its hosted-payment URL. Recording the payment before contacting Chargily is what later lets the webhook find and complete the order idempotently (IV.5.3).

### IV.3.3 Billing and payment

Confirms payments and keeps the billing history. It handles the Chargily webhook (`POST /api/webhooks/chargily`) and the active verification fallback (`GET /api/payments/{id}/verify`), exposes the user's payment history and receipts, and lets an administrator record a refund (`POST /api/admin/payments/{payment}/refund`). Because Chargily Pay v2 has no refund API, a refund is **recorded** in the portal and reconciled manually in the Chargily dashboard.

### IV.3.4 Subscription management

Owns the subscription lifecycle: a seven-day trial once per account (`POST /api/subscriptions/trial`), activation and renewal on payment, cancellation (`POST /api/subscriptions/cancel`), and the scheduled expiry of ended periods. Access follows the paid period, not the status: a cancelled **paid** period keeps access until its end, while a cancelled **trial** ends immediately (IV.5).

### IV.3.5 VPN account provisioning (WireGuard keys and configuration files)

Issues and revokes the WireGuard peer that backs a user's access (gap G-07). On the first configuration download (`GET /api/vpn/config`), the server creates a key pair and tunnel address and returns a `nexusvpn.conf` file; the peer is revoked automatically when no subscription grants access any more (cancellation, expiry, refund or removal from a team). The cryptographic details are in IV.5.4.

### IV.3.6 User profile and account

Lets a signed-in user manage their account: change e-mail (which requires re-verification) or password (`PUT /api/account/email`, `PUT /api/account/password`), view their subscriptions and receipts, and permanently delete the account (`DELETE /api/account`). Deletion **anonymises and soft-deletes** the user while keeping the (anonymised) billing history, and frees the e-mail for reuse.

### IV.3.7 Authentication and access control

Provides registration, login, logout, e-mail verification and password reset for the web session, the equivalent token endpoints for the mobile client (`/api/mobile/*`), and role-based access control for the administration area. Its security-critical parts are detailed in IV.5.1–IV.5.2.

### IV.3.8 Support and administration

Covers the back-office console and the merged support activity (gap G-05: a dedicated ticketing system is future work). Administrators manage users, offers, subscriptions and payments, consult the audit log, and handle the two support channels — **contact requests** (`/api/admin/contact-requests`) and **testimonial moderation** (`/api/admin/testimonials`). Every administrative action is written to the audit log.

| Module | Key endpoints |
|---|---|
| Offers and pricing | `GET /api/pricing` |
| Order management | `POST /api/checkout` |
| Billing and payment | `POST /api/webhooks/chargily`, `GET /api/payments/{id}/verify`, `POST /api/admin/payments/{id}/refund` |
| Subscription management | `POST /api/subscriptions/trial`, `POST /api/subscriptions/cancel` |
| VPN provisioning | `GET /api/vpn/config` |
| User profile and account | `PUT /api/account/email`, `PUT /api/account/password`, `DELETE /api/account` |
| Authentication and access control | `POST /api/auth/register`, `POST /api/auth/login`, `/api/mobile/*` |
| Support and administration | `GET /api/admin/*` (users, plans, subscriptions, payments, testimonials, contact-requests, audit-logs) |

**Table IV.4 — Functional modules and their main endpoints.** *Source: author, from `routes/api.php`.*

## IV.4 User Interface Walkthrough

This section walks through the main screens of the running application, captured with Playwright on seeded demonstration data. The client interface comes first, then the administration console and the support views, and finally the responsive (mobile-width) views and the postponed native client.

### IV.4.1 Client interface

The public site is a single scrolling landing page followed by the authenticated dashboard.

![Home — navigation bar and hero](../pfe/memoire-assets/screenshots/memoire/01-home-navbar-hero.png){width=14cm}

**Figure IV.2 — Public home page: navigation bar and hero.** *Source: author.*

![Features section](../pfe/memoire-assets/screenshots/memoire/02-features.png){width=14cm}

**Figure IV.3 — Home page: features section.** *Source: author.*

![Server map and statistics](../pfe/memoire-assets/screenshots/memoire/03-map-stats.png){width=14cm}

**Figure IV.4 — Home page: server-coverage map and statistics (marketing mock-up).** *Source: author.*

![Testimonials](../pfe/memoire-assets/screenshots/memoire/04-testimonials.png){width=14cm}

**Figure IV.5 — Home page: approved customer testimonials.** *Source: author.*

![Pricing cards](../pfe/memoire-assets/screenshots/memoire/05-pricing-cards.png){width=14cm}

**Figure IV.6 — Home page: pricing plans (in Algerian dinars).** *Source: author.*

![Contact form](../pfe/memoire-assets/screenshots/memoire/06-contact-form.png){width=14cm}

**Figure IV.7 — Home page: Team-plan contact form.** *Source: author.*

![Footer](../pfe/memoire-assets/screenshots/memoire/07-footer.png){width=14cm}

**Figure IV.8 — Home page: footer.** *Source: author.*

![Sign-in dialog](../pfe/memoire-assets/screenshots/memoire/08-auth-signin.png){width=10cm}

**Figure IV.9 — Authentication: sign-in dialog.** *Source: author.*

![Sign-up dialog](../pfe/memoire-assets/screenshots/memoire/09-auth-signup.png){width=10cm}

**Figure IV.10 — Authentication: sign-up dialog (with plan selection).** *Source: author.*

![Validation error](../pfe/memoire-assets/screenshots/memoire/10-auth-validation-error.png){width=10cm}

**Figure IV.11 — Authentication: server-side validation error.** *Source: author.*

![Forgot password](../pfe/memoire-assets/screenshots/memoire/11-auth-forgot-password.png){width=10cm}

**Figure IV.12 — Authentication: forgotten-password request.** *Source: author.*

![Success message](../pfe/memoire-assets/screenshots/memoire/27-auth-success-message.png){width=10cm}

**Figure IV.13 — Authentication: confirmation message.** *Source: author.*

![Dashboard — subscription and VPN](../pfe/memoire-assets/screenshots/memoire/12-dashboard-subscription-vpn.png){width=14cm}

**Figure IV.14 — Client dashboard: subscription status and VPN access.** *Source: author.*

![Dashboard — empty state](../pfe/memoire-assets/screenshots/memoire/25-dashboard-empty-state.png){width=14cm}

**Figure IV.15 — Client dashboard: empty state (no active subscription).** *Source: author.*

![Dashboard — failed payment](../pfe/memoire-assets/screenshots/memoire/26-dashboard-failed-payment.png){width=14cm}

**Figure IV.16 — Client dashboard: failed-payment state.** *Source: author.*

![Payment dialog](../pfe/memoire-assets/screenshots/memoire/16-payment-dialog.png){width=10cm}

**Figure IV.17 — Checkout: payment dialog before redirection to Chargily.** *Source: author.*

![Receipt dialog](../pfe/memoire-assets/screenshots/memoire/14-receipt-dialog.png){width=10cm}

**Figure IV.18 — Billing: payment receipt dialog.** *Source: author.*

![Account settings](../pfe/memoire-assets/screenshots/memoire/15-account-settings.png){width=14cm}

**Figure IV.19 — Account settings: e-mail, password and account deletion.** *Source: author.*

![Team management](../pfe/memoire-assets/screenshots/memoire/13-dashboard-team-management.png){width=14cm}

**Figure IV.20 — Client dashboard: team-member management (team owner).** *Source: author.*

### IV.4.2 Administration console

The administrator operates a back-office console with a section for each business area.

![Admin overview](../pfe/memoire-assets/screenshots/memoire/17-admin-overview.png){width=14cm}

**Figure IV.21 — Administration: overview.** *Source: author.*

![Admin accounts](../pfe/memoire-assets/screenshots/memoire/18-admin-accounts.png){width=14cm}

**Figure IV.22 — Administration: user accounts.** *Source: author.*

![Admin offers](../pfe/memoire-assets/screenshots/memoire/19-admin-offers.png){width=14cm}

**Figure IV.23 — Administration: offers (plans) management.** *Source: author.*

![Admin subscriptions](../pfe/memoire-assets/screenshots/memoire/20-admin-subscriptions.png){width=14cm}

**Figure IV.24 — Administration: subscriptions.** *Source: author.*

![Admin payments](../pfe/memoire-assets/screenshots/memoire/21-admin-payments.png){width=14cm}

**Figure IV.25 — Administration: payments and refunds.** *Source: author.*

![Admin analytics](../pfe/memoire-assets/screenshots/memoire/22-admin-analytics.png){width=14cm}

**Figure IV.26 — Administration: analytics (custom-built charts).** *Source: author.*

![Admin audit log](../pfe/memoire-assets/screenshots/memoire/24-admin-audit-log.png){width=14cm}

**Figure IV.27 — Administration: audit log.** *Source: author.*

### IV.4.3 Support views

Support is handled inside the administration console (gap G-05): testimonial moderation and the contact-request inbox.

![Admin testimonial moderation](../pfe/memoire-assets/screenshots/memoire/23-admin-comments.png){width=14cm}

**Figure IV.28 — Support: testimonial moderation.** *Source: author.*

### IV.4.4 Responsive and mobile

The web application is responsive and usable at phone width, as the following captures show. These are the **web** application in a narrow viewport, not a native application.

![Responsive home](../pfe/memoire-assets/screenshots/memoire/90-mobile-home.png){width=6.5cm}

**Figure IV.29 — Responsive web: home page at phone width.** *Source: author.*

![Responsive dashboard](../pfe/memoire-assets/screenshots/memoire/91-mobile-dashboard.png){width=6.5cm}

**Figure IV.30 — Responsive web: dashboard at phone width.** *Source: author.*

> **Native mobile client — placeholder (decision D-16).** The native Flutter application is postponed. Its screens, together with the token-based flows it will use over `/api/mobile/*`, will be documented here when the application is resumed.

## IV.5 Critical Components

This section examines the components that most affect security and correctness, with short excerpts taken verbatim from the source code.

### IV.5.1 Security hardening

**Security headers.** A global middleware adds the standard protective headers to every response, a strict `Content-Security-Policy` to the API and health endpoints, and HSTS over HTTPS. The front end sets an equivalent policy on every page (`next.config.mjs`).

```php
// app/Http/Middleware/SecurityHeaders.php
$headers->set('X-Content-Type-Options', 'nosniff');
$headers->set('X-Frame-Options', 'DENY');
$headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
$headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

if ($request->is('api/*') || $request->is('up')) {
    $headers->set('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'");
}
if ($request->isSecure()) {
    $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
}
```

**Listing IV.1 — Response security headers.** *Source: `app/Http/Middleware/SecurityHeaders.php`.*

**Injection (SQLi / XSS).** All database access goes through Eloquent's parameterised queries, and `LIKE` searches escape the `%` and `_` wildcards; amounts are never taken from the client. On the front end React escapes every rendered value and the application code uses no `dangerouslySetInnerHTML`, so there is no HTML-injection point.

**CSRF.** Session routes are in Laravel's `web` group with CSRF protection; the front end fetches the `XSRF-TOKEN` cookie and echoes it in the `X-XSRF-TOKEN` header, retrying once on a 419. The payment webhook is outside the session group and is authenticated by signature instead (IV.5.3).

**Rate limiting.** Named limiters protect the sensitive endpoints; the keys are the user id when authenticated, otherwise the client IP.

```php
// app/Providers/AppServiceProvider.php — configureRateLimiting()
RateLimiter::for('login', fn (Request $r) => Limit::perMinute(10)->by($r->ip()));
RateLimiter::for('checkout', fn (Request $r) => [
    Limit::perMinute(5)->by($byUserOrIp($r)),
    Limit::perDay(30)->by($byUserOrIp($r)),
]);
RateLimiter::for('contact', fn (Request $r) => [
    Limit::perMinute(2)->by($r->ip()),
    Limit::perDay(10)->by($r->ip()),
]);
```

**Listing IV.2 — Named rate limiters (excerpt).** *Source: `app/Providers/AppServiceProvider.php`.*

**Production guard.** The application refuses to boot in production with debug output enabled, and logs a warning for any development default that slips through:

```php
if (config('app.debug')) {
    throw new RuntimeException('APP_DEBUG must be false in production.');
}
```

**Listing IV.3 — Production configuration guard.** *Source: `app/Providers/AppServiceProvider.php`.*

### IV.5.2 Authentication and token security

The web application authenticates with a server-side **session cookie** (`HttpOnly`, same-origin via the rewrite, encrypted and `SameSite=lax` in production); the session is regenerated on login and invalidated on logout. Passwords are hashed with **bcrypt**. E-mail verification uses a temporary **signed** link (valid 60 minutes), and payment and VPN download both require a verified e-mail. Brute force is slowed by the `login` limiter plus an application lock after five failed attempts within sixty seconds, keyed by e-mail and IP.

The **mobile** client (postponed) authenticates with **Laravel Sanctum** personal access tokens on routes kept outside the `web` group — no session, no CSRF — so the website's cookie flow is untouched and the administration area is never exposed to tokens.

```php
// routes/api.php — token (Sanctum) routes for the mobile client
Route::prefix('mobile')->middleware('throttle:api')->group(function () {
    Route::post('auth/register', [MobileAuthController::class, 'register'])->middleware('throttle:register');
    Route::post('auth/login',    [MobileAuthController::class, 'login'])->middleware('throttle:login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [MobileAuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        // ... account, subscriptions, payments, vpn/config, checkout ...
    });
});
```

**Listing IV.4 — Sanctum token routes for the mobile client.** *Source: `routes/api.php`.*

Tokens are issued with `createToken()` and returned only once as plain text; they are stored **hashed** in `personal_access_tokens` and expire after thirty days (`SANCTUM_TOKEN_EXPIRATION`). A token is revoked on logout (`currentAccessToken()->delete()`), on password change (other tokens) and on account deletion (all tokens). A dedicated test (`MobileAuthTest`) checks that a mobile token cannot open the session-only web routes and vice-versa.

### IV.5.3 Payment gateway integration

Two independent channels confirm a payment — the server-to-server **webhook** and an active **verification** fallback from the result page — and both converge on the same logic. Authenticity and exactly-once application are therefore essential.

The webhook body is authenticated with an **HMAC-SHA256** signature compared in constant time:

```php
// app/Services/ChargilyGateway.php
public function verifySignature(string $rawBody, string $signature): bool
{
    $secretKey = $this->secretKey();
    if ($secretKey === '') {
        Log::critical('Chargily webhook received but CHARGILY_SECRET_KEY is not configured.');
        return false;
    }
    return hash_equals(hash_hmac('sha256', $rawBody, $secretKey), $signature);
}
```

**Listing IV.5 — Webhook signature verification.** *Source: `app/Services/ChargilyGateway.php`.*

Activation is **idempotent**: the `pending → succeeded` transition is a conditional update on a row locked inside a transaction, so a replayed webhook and a simultaneous verification can activate the subscription only once. Activation is also refused if the amount or currency returned by Chargily does not match the recorded payment.

```php
// app/Models/Payment.php — markSucceeded()
$payment = self::query()->whereKey($this->id)->lockForUpdate()->first();
if (! $payment || $payment->status !== 'pending') {
    return false;                       // already claimed by the other channel
}
$claimed = self::query()->whereKey($this->id)->where('status', 'pending')->update([
    'status' => 'succeeded', 'paid_at' => now(),
]);
```

**Listing IV.6 — Exactly-once payment activation.** *Source: `app/Models/Payment.php`.*

### IV.5.4 VPN provisioning, notifications and reporting

**WireGuard key pairs.** A peer's keys are Curve25519: a clamped 32-byte random scalar (the private key) and its base-point multiple (the public key), both Base64-encoded. The clamping bits are set exactly as WireGuard requires.

```php
// app/Models/VpnCredential.php — generateKeyPair()
$secret = random_bytes(32);
$secret[0]  = chr(ord($secret[0])  & 248);
$secret[31] = chr((ord($secret[31]) & 127) | 64);

return [base64_encode($secret), base64_encode(sodium_crypto_scalarmult_base($secret))];
```

**Listing IV.7 — WireGuard Curve25519 key generation.** *Source: `app/Models/VpnCredential.php`.*

The private key is stored with Eloquent's `encrypted` cast and hidden from serialisation; it is decrypted only to build the `nexusvpn.conf` file, which is served with `Cache-Control: no-store`. Tunnel addresses are drawn in order from the `10.66.0.0/16` pool and **never reused**, so a revoked peer's address cannot collide with a new one.

**Notification system (transactional e-mails and scheduled tasks).** Account e-mails (verification, reset, welcome, receipt, reminders) are sent during the request; a queue is future work (gap G-08). Three scheduled commands run periodically — `subscriptions:expire` and `payments:reconcile` every fifteen minutes, and `subscriptions:remind` daily — driven by `schedule:work` inside the container.

**Reporting.** The administration overview computes its figures from the real billing data (revenue, active subscriptions, recent payments); the charts are drawn by hand in the front end rather than with a charting library.

## IV.6 Quality Assurance and Testing

### IV.6.1 Automated tests

The back end is covered by an automated test suite of **131 tests (508 assertions)** that runs in about fourteen seconds on an in-memory SQLite database. The tests are feature tests: each one drives the real HTTP endpoints and asserts on the response and the database state. Table IV.5 groups them by area.

| Area | Tests | What it verifies |
|---|---|---|
| Authentication, account and security | 47 | Registration, login, logout, e-mail verification, password reset, account lockout, security headers, account deletion and anonymisation |
| Payments and webhooks | 41 | Checkout, webhook signature and idempotency, amount/currency checks, reconciliation of stale payments, receipts |
| Subscriptions and VPN provisioning | 11 | Trial, renewal, cancellation, scheduled expiry, WireGuard peer issue and distinct tunnel addresses |
| Mobile token API (Sanctum) | 9 | Token issue, protected routes, guard separation, token revocation |
| Administration and billing | 19 | Admin-only access, statistics, refunds, plan deactivation, moderation, team subscriptions, contact requests |
| Platform and data integrity | 4 | Dashboard access control, seeder idempotency, scaffold test |
| **Total** | **131** | 508 assertions, ~14 s |

**Table IV.5 — Backend automated test suite.** *Source: author, from `php artisan test`.*

Automated **frontend** testing is not yet in place and is listed as future work; Playwright is currently used to capture the interface screenshots rather than to assert on behaviour.

### IV.6.2 Security testing

The security measures of IV.5 were checked manually against the running application during development. Table IV.6 records the checks that can be made without the deployed site.

| Check | Expected result |
|---|---|
| Modifying request without a valid CSRF token | 419, one automatic retry, then success |
| Six consecutive failed logins for one account | 429 (account lockout) |
| Non-admin calling an `/api/admin/*` route | 403 |
| Webhook with an invalid signature | 403, IP logged |
| Requesting another user's payment or checkout | 404 (existence not revealed) |

**Table IV.6 — Manual security checks.** *Source: author.*

> **Live security scan — placeholder (gap G-10).** Online scans of the deployed site (security-header grade, TLS configuration, and a re-check of the 419/429/403 behaviours against the public URL) will be run and reported here once the site is deployed.

### IV.6.3 Performance testing

The test suite runs in about fourteen seconds, and the one bounded N+1 query identified in the audit (payment listing) stays within a handful of queries per page. A full performance assessment, however, requires the live site.

> **Live performance test — placeholder (gap G-09).** A Lighthouse report on the deployed front end and a small load test against a public endpoint will be run and reported here once the site is deployed, together with the cold-start behaviour of the free hosting tier.

## IV.7 Deployment

### IV.7.1 Production environment

The project is a monorepo that deploys as two services. The Next.js front end is hosted on **Vercel**; the Laravel API is containerised with **Docker** (base image `php:8.3-apache-bookworm`) and hosted on **Render** as the `nexus-vpn-api` service, alongside a managed **PostgreSQL** database, both in Render's Frankfurt region on the free tier. Figure IV.31 shows the flow from the repository to production.

![Deployment architecture](../figures/deployment-en.png){width=14cm}

**Figure IV.31 — Deployment architecture.** *Source: author.*

The service is described declaratively in `render.yaml` (region, health check on `/up`, environment variables, a generated application-key secret and the secrets entered by hand in the dashboard). On each container start, the entry point reconstructs the application key, runs `php artisan migrate --force --seed`, and launches the scheduler in the background before serving the application. In production, transactional e-mail is sent through the **Brevo** HTTP API because Render's free tier blocks outbound SMTP (decision D-17).

The free hosting tier has honest limits that are stated rather than hidden: the web service sleeps after fifteen minutes of inactivity and takes about a minute to wake, and the free PostgreSQL database expires thirty days after creation. The portal's access control remains correct during sleep because it is based on the stored period end, but scheduled e-mails and revocations are delayed until the service wakes.

> **Live deployment evidence — placeholder (deployment phase).** The Render and Vercel dashboards, the live health check, the Chargily webhook configuration, and an end-to-end run (payment, verification e-mail, `.conf` import into the WireGuard application) will be captured and added here once the project is deployed. No live VPN tunnel is operated by the project itself (gap G-13): the gateway values are supplied so that a real, importable configuration file is produced.

### IV.7.2 Continuous integration

Continuous integration is provided by **GitHub Actions**[^githubactions] (gap G-11): on every push and pull request, one workflow installs the backend dependencies and runs the full PHPUnit suite on PHP 8.2 and 8.3, and a second workflow installs the frontend dependencies and runs the linter, the TypeScript type-check and the production build. The workflows are defined at the repository root so that GitHub runs them; hosting-side continuous deployment is automatic, as Render and Vercel rebuild each service on a push to the main branch.

> **Live CI/CD evidence — placeholder (deployment phase).** A screenshot of a green Actions run and of the automatic Render/Vercel deployments will be added once the repository is pushed to GitHub.

### IV.7.3 Monitoring and logging

In production the application logs to standard error, which Render and Vercel collect and display; the `/up` health endpoint lets Render detect and restart an unhealthy container; and the in-application **audit log** records every security- and billing-relevant event. An optional free uptime monitor on `/up` can be added to detect outages and to keep the free service awake (gap G-12).

## Conclusion

This chapter presented the implementation and the planned deployment of the portal. It set out the technology stack and justified a decoupled Next.js / Laravel architecture built as a modular monolith; it described the eight functional modules and walked through the client, administration and support interfaces; and it examined the critical components — security headers and rate limiting, session and Sanctum authentication, the HMAC-signed and idempotent payment flow, and the WireGuard provisioning built on Curve25519 — with verified excerpts from the source code. Quality is backed by an automated suite of 131 backend tests, complemented by documented manual security checks.

The work was carried out honestly about its boundaries: the parts that depend on a running deployment — live security scans, performance and load tests, production dashboards and an end-to-end payment-and-tunnel demonstration — are prepared from the code and configuration and left as clearly marked placeholders, as are the native mobile client (postponed) and the live VPN gateway (operated by the network team). These items, gathered in the project's placeholder list, define exactly what remains to be captured once the site is deployed. The general conclusion that follows draws together the results of the whole project and its perspectives.

---

## Notes

[^php]: The PHP Group: *PHP Manual*. https://www.php.net/docs.php (accessed 11 October 2026).
[^laravel]: Laravel: *Laravel 12.x Documentation*. https://laravel.com/docs/12.x (accessed 11 October 2026).
[^nextjs]: Vercel: *Next.js Documentation*. https://nextjs.org/docs (accessed 11 October 2026).
[^react]: Meta Open Source: *React Documentation*. https://react.dev/ (accessed 11 October 2026).
[^typescript]: Microsoft: *TypeScript Documentation*. https://www.typescriptlang.org/docs/ (accessed 11 October 2026).
[^tailwind]: Tailwind Labs: *Tailwind CSS Documentation*. https://tailwindcss.com/docs (accessed 11 October 2026).
[^sqlite]: SQLite: *About SQLite*. https://www.sqlite.org/about.html (accessed 11 October 2026).
[^postgresql]: The PostgreSQL Global Development Group: *PostgreSQL Documentation*. https://www.postgresql.org/docs/ (accessed 11 October 2026).
[^sanctum]: Laravel: *Laravel Sanctum Documentation*. https://laravel.com/docs/12.x/sanctum (accessed 11 October 2026).
[^chargily4]: Chargily: *Chargily Pay — Developer Documentation (v2)*. https://dev.chargily.com/ (accessed 10 October 2026).
[^wgproto4]: WireGuard: *Protocol & Cryptography* (official protocol page). https://www.wireguard.com/protocol/ (accessed 11 October 2026).
[^phpunit]: Bergmann (Sebastian): *PHPUnit Documentation*. https://docs.phpunit.de/ (accessed 11 October 2026).
[^playwright]: Microsoft: *Playwright Documentation*. https://playwright.dev/ (accessed 11 October 2026).
[^plantuml]: PlantUML: *PlantUML Language Reference Guide*. https://plantuml.com/ (accessed 11 October 2026).
[^mermaid]: Mermaid: *Mermaid Documentation*. https://mermaid.js.org/ (accessed 11 October 2026).
[^githubactions]: GitHub: *GitHub Actions Documentation*. https://docs.github.com/actions (accessed 11 October 2026).
