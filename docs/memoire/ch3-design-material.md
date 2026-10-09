# Chapter III — design material (English, verified from the real system)

> Built in Step 5 from **ground-truth.md** (real schema, routes, middleware, models). Nothing invented. Feeds Ch III (III.3 role matrix, III.4 use-case tables, III.8 relational model, III.9 data dictionary, III.10 business rules, III.11 cardinalities). Titles adapted per **decisions.md §5** (G-01…G-13). ⏸️ Mobile items are marked placeholders.

## III.3 — Role and permission mapping (G-01 adapt)
The app implements the roles **Visitor, User (client), Team owner, Team member, Admin**. The supervisor's five *business* roles map onto them and onto the admin console sections:

| Business role (supervisor) | Implemented as | Where in the system |
|---|---|---|
| Client (Buyer) | **User** (`role=user`) + Team member | client dashboard, checkout, VPN config |
| System Administrator | **Admin** (`role=admin`) | admin → Accounts, Audit log |
| Sales Manager | Admin (Sales view) | admin → Offers, Subscriptions/Team, Contact requests |
| Contract & Billing Manager | Admin (Billing view) | admin → Payments (refunds), Subscriptions |
| Technical Support | Admin (Support view) | admin → Contact requests, Comments (moderation) |

> Separate OS-level accounts per business role are **future work** (G-01); today one `admin` role covers all admin sections (console-section access = the de-facto permission split).

**Permission matrix** (✓ = allowed; server-enforced by session+role, `EnsureUserIsAdmin`, and `proxy.ts`):

| Capability | Visitor | User | Team owner | Admin |
|---|:--:|:--:|:--:|:--:|
| Browse offers / testimonials, send a contact request | ✓ | ✓ | ✓ | ✓ |
| Register / log in | ✓ | — | — | — |
| Start trial / subscribe / pay / download VPN config | — | ✓ | ✓ | ✓ |
| Cancel own subscription, manage own account, view own billing | — | ✓ | ✓ | ✓ |
| Add / remove team members (within seats) | — | — | ✓ | — |
| Manage users / plans / payments / subscriptions / testimonials, view audit log, create team subscription | — | — | — | ✓ |

## III.4 — Use-case specifications (titles adapted per §5)
Format follows the 19/20 reference (Title, Actor, Description, Pre-condition, Nominal scenario, Alternative scenarios).

**UC-01 — Authenticate** · Actors: Visitor, User · *Register (with a 7-day trial on a chosen self-serve plan), log in, log out, reset password.* Pre: valid e-mail; (login) an existing account. Nominal: submit credentials → server validates, creates/opens the session, returns the user profile. Alt: invalid credentials → 401; 5 failures → **lockout 429**; CSRF expired → 419 (client refreshes once).

**UC-02 — Browse VPN offers** · Actors: Visitor, User · *View active plans + prices (monthly/yearly) and approved testimonials.* Pre: none. Nominal: `GET /pricing` (cached) → plans shown. Alt: load error → retry.

**UC-03 — Choose a plan and get the VPN configuration** *(G-03; "configure" = pick plan + download `.conf`)* · Actor: User · Pre: active trial/subscription **and** verified e-mail **and** gateway configured. Nominal: dashboard → "Download WireGuard config" → server issues a peer (keypair + tunnel address) → `nexusvpn.conf`. Alt: no access → 403; e-mail unverified → 403 `email_unverified`; gateway not configured → 503 ("servers being set up").

**UC-04 — Subscribe and checkout** *(G-02; one active subscription per user, DB-enforced — no cart)* · Actor: User · Pre: signed in, **e-mail verified**, a self-serve plan. Nominal: choose plan + period → `POST /checkout` → Chargily `checkout_url` → redirect. Alt: unverified → 403; too many attempts → 429; provider unreachable → 502.

**UC-05 — Process payment (Chargily)** · Actors: User, Chargily (external) · Pre: a pending checkout. Nominal: user pays on Chargily (EDAHABIA/CIB) → **webhook (HMAC) + active verification** confirm → payment applied **idempotently** → subscription activated, VPN peer issued, receipt e-mailed. Alt: amount/currency mismatch → not activated (`amount_mismatch`); failed/canceled/expired → subscription unchanged; webhook delayed → result page polls verify.

**UC-06 — Manage VPN accounts (provisioning & revocation)** · Actors: User, System (scheduler) · Nominal: a peer is issued on first download; revoked automatically when no subscription grants access (cancel/expire/refund/team removal). Alt: address pool exhausted → error (not reached in practice).

**UC-07 — View billing history** · Actor: User · Nominal: dashboard shows paginated payment and subscription history. 

**UC-08 — View payment receipts** *(G-04; receipt window, no PDF invoice)* · Actor: User · Nominal: "Details" on a payment → receipt dialog (reference, plan, amount, status, dates, Chargily checkout id). PDF invoice → future work.

**UC-09 — Manage subscriptions** · Actor: User · Nominal: cancel → a **trial ends immediately**, a **paid period stays until its end**. Alt: no active subscription → 409; team subscription → managed by sales.

**UC-10 — Contact the company** *(G-05; replaces the ticketing/helpdesk — future work)* · Actors: Visitor/User, Admin · Nominal: Team inquiry form → stored; admin lists/handles it and provisions a team subscription. Alt: validation/rate-limit errors.

> ⏸️ **Mobile use cases: placeholder** — when the Flutter client resumes, the same client use cases (UC-01, UC-03, UC-04, UC-07, UC-09) are reached over the token API `/api/mobile/*`.

## III.8 — Relational database model (text form)
(PK <u>underlined</u>; FK prefixed `#`; `?` = nullable.)

- **USERS**(<u>id</u>, name, email*UK*, password, email_verified_at?, remember_token?, role, deleted_at?, created_at, updated_at)
- **PLANS**(<u>id</u>, slug*UK*, name, description?, monthly_price?, yearly_price?, yearly_total?, currency, features?, popular, cta?, is_active, deleted_at?, created_at, updated_at)
- **SUBSCRIPTIONS**(<u>id</u>, #user_id→USERS, #plan_id→PLANS, seats, billing_period, status, started_at?, trial_ends_at?, current_period_end?, canceled_at?, trial_reminder_sent_at?, renewal_reminder_sent_at?, deleted_at?, …) — *partial UNIQUE(user_id) where status ∈ {active,trialing}*
- **PAYMENTS**(<u>id</u>, #user_id→USERS, #subscription_id?→SUBSCRIPTIONS, amount, currency, status, provider?, provider_reference?*UK*, provider_response?, metadata?, paid_at?, failure_reason?, error_code?, refunded_at?, refund_reason?, deleted_at?, …)
- **TEAMS**(<u>id</u>, name, #owner_id→USERS, #subscription_id?→SUBSCRIPTIONS, …)
- **TEAM_USER**(<u>#team_id→TEAMS</u>, <u>#user_id→USERS</u>, role, created_at)
- **VPN_CREDENTIALS**(<u>id</u>, #user_id→USERS, #subscription_id?→SUBSCRIPTIONS, public_key*UK*, private_key *(encrypted)*, address*UK*, status, revoked_at?, …)
- **TESTIMONIALS**(<u>id</u>, #user_id→USERS, content, status, #moderated_by?→USERS, moderated_at?, …)
- **CONTACT_REQUESTS**(<u>id</u>, #user_id?→USERS, name, email, company?, seats?, message, status, #handled_by?→USERS, handled_at?, ip_address?, …)
- **AUDIT_LOGS**(<u>id</u>, #actor_id?→USERS, action, subject_type?, subject_id?, metadata?, ip_address?, created_at)
- ⏸️ **PERSONAL_ACCESS_TOKENS**(<u>id</u>, tokenable_type, tokenable_id, name, token*UK*, abilities?, last_used_at?, expires_at?, …) *(Sanctum, mobile — postponed)*

## III.9 — Data dictionary (English; types from the real schema)
Legend: A = alphabetic, N = numeric, AN = alphanumeric, D = date/datetime, B = boolean, J = JSON.

**USERS**
| Field | Meaning | Type | Constraint |
|---|---|---|---|
| id | identifier | N (bigint) | PK, auto |
| name | display name | A/AN (≤255) | required |
| email | login e-mail | AN (≤255) | **unique**, lowercased |
| password | bcrypt hash | AN | required, hidden |
| email_verified_at | e-mail verification date | D | nullable |
| role | `user`/`admin` | A | default `user` |
| deleted_at | soft-delete date | D | nullable |

**PLANS** — id (PK); slug (AN, unique); name (AN); description (text, ?); monthly_price/yearly_price/yearly_total (N DZD, ?); currency (A, `DZD`); features (J, ?); popular (B); cta (AN, ?); is_active (B); deleted_at (D,?).

**SUBSCRIPTIONS** — id (PK); user_id/plan_id (N, FK RESTRICT); seats (N, def 1); billing_period (A, monthly/yearly); status (A: trialing/active/canceled/expired/past_due); started_at/trial_ends_at/current_period_end/canceled_at/trial_reminder_sent_at/renewal_reminder_sent_at (D,?); deleted_at (D,?).

**PAYMENTS** — id (PK); user_id (N, FK RESTRICT); subscription_id (N,?, FK SET NULL); amount (N DZD); currency (A); status (A: pending/succeeded/failed/refunded); provider (A,?); provider_reference (AN,?, **unique**); provider_response/metadata (J,?); paid_at/refunded_at (D,?); failure_reason/refund_reason (AN,?); error_code (AN,?); deleted_at (D,?).

**VPN_CREDENTIALS** — id (PK); user_id (N, FK RESTRICT); subscription_id (N,?, FK SET NULL); public_key (AN, **unique**); private_key (AN, **encrypted**); address (AN, **unique**, tunnel IP); status (A: active/revoked); revoked_at (D,?).

**TEAMS** — id (PK); name (AN); owner_id (N, FK CASCADE); subscription_id (N,?, FK SET NULL).
**TEAM_USER** — team_id+user_id (PK, FK CASCADE); role (A, owner/member); created_at (D).
**TESTIMONIALS** — id (PK); user_id (N, FK CASCADE); content (text); status (A: pending/approved/rejected); moderated_by (N,?, FK SET NULL); moderated_at (D,?).
**CONTACT_REQUESTS** — id (PK); user_id (N,?, FK SET NULL); name/email (AN); company (AN,?); seats (N,?); message (text); status (A: new/handled); handled_by (N,?, FK SET NULL); handled_at (D,?); ip_address (AN,?).
**AUDIT_LOGS** — id (PK); actor_id (N,?, FK SET NULL); action (AN); subject_type (A,?)/subject_id (N,?); metadata (J,?); ip_address (AN,?); created_at (D).

## III.11 — Relationships and cardinalities
| Relationship | Cardinality | On delete |
|---|---|---|
| User — Subscription | 1 : 0..* (at most 1 *current*) | RESTRICT |
| Plan — Subscription | 1 : 0..* | RESTRICT |
| User — Payment | 1 : 0..* | RESTRICT |
| Subscription — Payment | 0..1 : 0..* | SET NULL |
| User — VpnCredential | 1 : 0..* (1 active) | RESTRICT |
| Subscription — VpnCredential | 0..1 : 0..* | SET NULL |
| User — Team (owns) | 1 : 0..* | CASCADE |
| Subscription — Team | 0..1 : 0..1 | SET NULL |
| Team — User (members) | *..* (via team_user) | CASCADE |
| User — Testimonial (author / moderator) | 1 : 0..* / 0..1 : 0..* | CASCADE / SET NULL |
| User — ContactRequest (sender / handler) | 0..1 : 0..* | SET NULL |
| User — AuditLog (actor) | 0..1 : 0..* | SET NULL |

## III.10 — Business logic and rules (from the models)
1. **One active subscription per user** — DB partial-unique; a new current subscription cannot coexist with another (controllers catch the violation → 409).
2. **Trial once per account** — a user who has (or had) any subscription cannot start a new trial.
3. **Access = period, not status** — access is granted while `current_period_end` is in the future, even after a user cancels a *paid* period; a canceled *trial* ends immediately.
4. **Idempotent payment** — the pending→succeeded transition is a locked conditional update; webhook and active verification can both fire, the payment applies **once**.
5. **Activate only on verified data** — a `paid` checkout activates only if **amount and currency match** the record, else `amount_mismatch`/`currency_mismatch` (no activation).
6. **Subscription application** — same plan active → renewal (extend); same plan trialing → conversion; different current plan → plan change (old ends now); none → new (resuming a canceled plan starts after the days already paid).
7. **VPN provisioning** — one active peer per user; address from pool `10.66.0.0/16`, **never reused**; private key encrypted; revoked when no subscription grants access.
8. **Account deletion** — anonymise + soft-delete (keep billing history); admins and team owners cannot self-delete.
9. **Server-side amounts** — the client never sends the price; the server computes it from the plan.
10. **Audit** — every security/billing event is logged (append-only), without secrets.
