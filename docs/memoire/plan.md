# Mémoire — work plan (task-based)

> **Status: DRAFT — waiting for the student's approval.**
> Task-based, not date-based: follow it in order whatever the pace. Hard deadline still: **submit on 15/10/2026**.
> Decisions and open questions live in [`decisions.md`](decisions.md). Read both at the start of every mémoire session.
> Assumes proposals **P-01** (gap handling), **P-03** (Markdown drafts → Word at the end) and **P-04** (diagram tools) are accepted. If not, the tasks that use them change.

## How to use this plan

- **Checkbox:** ☐ to do · ☑ done · ➖ dropped (say why in decisions.md). Claude ticks tasks when done and adds new tasks at the end of the right phase with the next number.
- **Who:** Student (you) · Mossab · Claude · Supervisor · EADN · Institute.
- **Needs:** tasks that must be done first. "—" = can start any time.
- 🔴 = **critical path**: a delay here delays the submission.
- **Lanes** (work that can run at the same time):
  - **A** — Claude writing: figures, chapters, Word file.
  - **B** — deployment and live tests: the student acts, Claude guides.
  - **C** — mobile app: Mossab.
  - **D** — the student's requests, reviews and personal pages.
- **Rules:**
  - Secrets are entered only by the student; never in chat, docs or git.
  - Any code or config change also updates `docs/pfe/` and `17-changelog.md`.
  - Commits only when the student asks.
  - Run servers only when needed (RAM is limited).
  - Never copy sentences from the 19/20 reference.

Output folders (created when needed):

| Folder | Content |
|---|---|
| `docs/memoire/chapters/` | Chapter drafts (Markdown, English) |
| `docs/memoire/figures/` | English diagrams + PNG |
| `docs/memoire/screenshots/` | New screenshots (deployment, mobile) |
| `docs/memoire/eadn/` | Visit notes and anonymized documents |
| `docs/memoire/output/` | Word and PDF files |

---

## Phase 0 — Decisions and requests (Lane D) — start now, all in parallel

| ✓ | # | Task | Who | Needs | Produces |
|---|---|---|---|---|---|
| ☐ | 0.1 | 🔴 Decide gap handling G-01…G-13 (decisions.md §5, P-01) | Student | — | decisions.md §5 final |
| ☐ | 0.2 | 🔴 Decide writing workflow (P-03) | Student | — | decisions.md |
| ☐ | 0.3 | Decide diagram tools (P-04) | Student | — | decisions.md |
| ☐ | 0.4 | Decide P-05 (git-ignore `docs/memoire-ref/` + `docs/memoire/eadn/`) and P-06 (new private GitHub repo) | Student | — | decisions.md |
| ☐ | 0.5 | Confirm parts order (P-07), footnote citations (P-08), title style (Q-12) — ask the methodology teacher if unsure | Student | — | decisions.md |
| ☐ | 0.6 | Final theme title + case-study wording (Q-13); keep product name "NexusVPN"? (Q-17) | Student + Supervisor | — | decisions.md |
| ☐ | 0.7 | Collect cover facts: speciality, promotion, academic year, supervisor name + grade, exact spelling of both names (Q-16) | Student | — | decisions.md §8 |
| ☐ | 0.8 | Ask the institute: number of copies, binding, PDF required, where/whom to submit, English OK (Q-15, Q-19) | Student + Institute | — | decisions.md |
| ☐ | 0.9 | Ask the supervisor: how they want drafts (format) and how long a review takes (Q-20) | Student + Supervisor | — | decisions.md |
| ☐ | 0.10 | Ask Mossab for the mobile facts (decisions.md §6 list) | Student → Mossab | — | request sent |
| ☐ | 0.11 | Contact EADN to **book** the visit (booking only — the visit is 9.3) and ask which documents they can share. Book early: EADN needs notice and is closed Fri–Sat | Student + EADN | — | appointment |
| ☐ | 0.12 | Real project timeline: start date, phases, internship dates (Q-18) | Student + Mossab | — | decisions.md |
| ☐ | 0.13 | AI-assistance mention: check the institute's rule (Q-11) | Student | — | decisions.md |
| ☐ | 0.14 | 🔴 Choose how the live site sends e-mails: Render Free blocks SMTP (Q-22). Options: a) paid Render instance; b) HTTP e-mail API (e.g. Brevo, Mailgun, Postmark, Resend) — small code/config change; c) another host | Student (Claude explains) | — | decisions.md |

## Phase 1 — Deployment (Lane B) — 🔴 Chapter IV needs it, and the EADN visit waits for Chapter IV

| ✓ | # | Task | Who | Needs | Produces |
|---|---|---|---|---|---|
| ☐ | 1.1 | Apply P-05: git-ignore reference + EADN folders | Claude | 0.4 | `.gitignore` |
| ☐ | 1.2 | Baseline check: backend tests, lint, types, build all green | Claude | — | fresh test count |
| ☐ | 1.3 | 🔴 Implement the e-mail choice from 0.14 (if HTTP API: package, config, tests, docs + changelog; student creates the API key) | Claude + Student | 0.14 | working mailer config |
| ☐ | 1.4 | 🔴 Create the private GitHub repo and push | Student (Claude guides) | 0.4, 1.1, 1.2 | repo URL |
| ☐ | 1.5 | Add CI workflow (backend tests + frontend lint/types/build) + docs 12/13 + changelog | Claude | 1.4 | `.github/workflows/ci.yml`, green run |
| ☐ | 1.6 | 🔴 Render: Blueprint from `render.yaml`; service healthy on `/up` | Student (Claude guides) | 1.3, 1.4 | API URL |
| ☐ | 1.7 | 🔴 Render: enter secrets (Chargily test key, mail credentials/API key, VPN test gateway values if G-13 = adapt) | Student only | 1.6 | configured service |
| ☐ | 1.8 | 🔴 Vercel: import repo, set `NEXT_PUBLIC_API_URL`, deploy | Student (Claude guides) | 1.6 | site URL |
| ☐ | 1.9 | 🔴 Render: set `FRONTEND_URL` to the Vercel URL; redeploy | Student | 1.8 | — |
| ☐ | 1.10 | 🔴 Chargily dashboard: webhook URL `https://<render>/api/webhooks/chargily` | Student | 1.6 | webhook set |
| ☐ | 1.11 | First admin on production (`admin:grant` in the Render shell); decide demo data on the live site (yes/no) | Student (Claude guides) | 1.9 | admin account |
| ☐ | 1.12 | 🔴 Live smoke test: register → verify e-mail → login → Chargily test payment → webhook activation → dashboard → config download → admin pages | Student + Claude | 1.7–1.11 | pass/fail list |
| ☐ | 1.13 | Record deployment facts in `docs/pfe/13-deployment.md` (URLs, steps, resolved ⚠️) + changelog; DB creation date → expiry date in decisions.md (R-02) | Claude | 1.12 | docs updated |
| ☐ | 1.14 | Optional: free uptime monitor on `/up` (G-12) | Student | 1.12 | monitor |

## Phase 2 — Live tests and deployment evidence (Lane B) — after deployment

| ✓ | # | Task | Who | Needs | Produces |
|---|---|---|---|---|---|
| ☐ | 2.1 | 🔴 Deployment screenshots: GitHub Actions green run, Vercel deployment, Render service + env page (values hidden) + logs, Chargily webhook setting, live home with URL bar | Student | 1.5, 1.12 | `screenshots/deployment/` |
| ☐ | 2.2 | 🔴 Live flow screenshots: Chargily payment page, result page, updated dashboard, verification + receipt e-mails in the inbox, `.conf` imported in the WireGuard app | Student | 1.12 | `screenshots/deployment/` |
| ☐ | 2.3 | 🔴 Performance: Lighthouse (home, dashboard; desktop + mobile) + small load test on a public endpoint | Claude (+ Student runs Lighthouse in Chrome) | 1.12 | reports |
| ☐ | 2.4 | 🔴 Security: online header + TLS scans of the live site; re-run manual checks on live (CSRF 419, lockout 429, admin 403) | Student (scans) + Claude (checks) | 1.12 | results + screenshots |
| ☐ | 2.5 | 🔴 Record 2.3–2.4 in `docs/pfe/12-testing.md` + `memoire-assets/tests/` + changelog | Claude | 2.3, 2.4 | docs updated |

## Phase 3 — Web figures, tables and screenshots (Lane A) — parallel with Phases 1, 2, 4

| ✓ | # | Task | Who | Needs | Produces |
|---|---|---|---|---|---|
| ☐ | 3.1 | Translate the 10 Mermaid diagrams to English; export PNG | Claude | 0.3 | `figures/` |
| ☐ | 3.2 | UML use case diagrams: global, client, admin (with the support side per G-05) | Claude | 0.1, 0.3 | `figures/` |
| ☐ | 3.3 | Sequence diagrams for the chosen use cases (add missing: browse offers, cancel subscription, contact…) | Claude | 0.1, 3.1 | `figures/` |
| ☐ | 3.4 | Class diagram in English, checked against the models | Claude | 3.1 | `figures/` |
| ☐ | 3.5 | Relational model (text form) + data dictionary (name, meaning, type, size, constraint) | Claude | — | tables (Markdown) |
| ☐ | 3.6 | Role/permission matrix (G-01) | Claude | 0.1 | table |
| ☐ | 3.7 | Business rules list + relationships and cardinalities list | Claude | 3.4 | lists |
| ☐ | 3.8 | Gantt chart | Claude | 0.12 | `figures/` |
| ☐ | 3.9 | Missing web screenshots: checkout dialog, team management, account settings, contact form, validation error and success states | Claude (capture script) or Student | — | screenshots |
| ☐ | 3.10 | Pick the screenshots for the mémoire (mostly desktop light, a few mobile-responsive) + captions | Claude proposes, Student approves | 3.9 | selection list |
| ☐ | 3.11 | Tool logos (frameworks, DBMS, Docker, Git/GitHub, IDE, Vercel, Render, Chargily, WireGuard, modelling tools) | Claude (Student if download blocked) | — | `figures/logos/` |
| ☐ | 3.12 | Short, commented code excerpts in English | Claude | — | snippets |
| ☐ | 3.13 | Deployment diagram with the real hosts (English) | Claude | 1.13 | `figures/` |

## Phase 4 — Mobile app part (Lane C)

> ⚠️ **Finding (2026-10-07):** the "android app" source (`E:\PFE\nexus-vpn-feature-android-app`) is a **Next.js web project, not a native Android app** — no Android/Gradle/APK, no PWA. See [`mobile-facts.md`](mobile-facts.md). Tasks 4.4–4.8 depend on the student's decision **Q-23** (web client / real native app / drop). Until Q-23 is decided, 4.4–4.8 are on hold.

| ✓ | # | Task | Who | Needs | Produces |
|---|---|---|---|---|---|
| ☑ | 4.1 | Deliver the source: received at `E:\PFE\nexus-vpn-feature-android-app` (branch zip) | Mossab | 0.10 | source files |
| ☑ | 4.2 | Study the source; write the facts down | Claude | 4.1 | [`mobile-facts.md`](mobile-facts.md) |
| ☑ | 4.3 | Integration check: it calls `/backend-api` with session-cookie+CSRF style, expects routes `/api/me,/auth/*,/pricing,/admin/*`; checkout is a **mock** (no Chargily). **Not confirmed with our backend** — needs `php artisan route:list` + CSRF/route alignment if we ever wire it live. Any backend change = new decision | Claude | 4.2 | decisions.md §6 |
| ☐ | 4.3b | 🔴 Decide Q-23 (what the "mobile app" is) — blocks 4.4–4.8 | Student (+ Mossab) | 4.2 | decisions.md |
| ☐ | 4.4 | 🔴 Screenshots. **If Q-23=web:** run the app at phone width in Chrome (no emulator), capture every screen + key states → `screenshots/mobile/`. **If Q-23=native:** Mossab screenshots the real app on a device/emulator. **If Q-23=drop:** skip | Claude or Mossab | 4.3b | `screenshots/mobile/` |
| ☐ | 4.5 | Mobile use case diagram + tables (only if Q-23 ≠ drop; "mobile-only" use cases only if a native app really adds any) | Claude | 3.2, 4.3b | `figures/` + tables |
| ☐ | 4.6 | Mobile/web-client sequence diagram(s) (at least one) if Q-23 ≠ drop | Claude | 4.3b | `figures/` |
| ☐ | 4.7 | Architecture figure: web (+ mobile/web-client) + backend, drawn to match Q-23 | Claude | 3.1, 4.3b | `figures/` |
| ☐ | 4.8 | Install/build info: **web** → `pnpm build`/host; **native** → APK from Mossab. Mobile tests if any | Claude / Mossab | 4.3b | facts |

## Phase 5 — Chapter II: VPN services and e-commerce (Lane A) → `chapters/ch2.md`

| ✓ | # | Task | Who | Needs | Produces |
|---|---|---|---|---|---|
| ☐ | 5.1 | Working sources list: official docs, standards, laws, competitor pages, with access dates | Claude; Student checks links | 0.2 | `docs/memoire/sources.md` |
| ☐ | 5.2 | Intro + II.1 VPN fundamentals (II.1.1 concepts, II.1.2 architecture, II.1.3 protocols + WireGuard/OpenVPN/IPsec table), starting from the student's own draft | Claude | 5.1 | ch2 |
| ☐ | 5.3 | II.2 Types of VPN (II.2.1 remote access, II.2.2 site-to-site, II.2.3 client-server) | Claude | 5.1 | ch2 |
| ☐ | 5.4 | II.3 Security and compliance (II.3.1 encryption/authentication, II.3.2 protocols, II.3.3 ISO/IEC 27001, GDPR, Algerian law 18-07) | Claude | 5.1 | ch2 |
| ☐ | 5.5 | II.4 E-commerce (II.4.1 definition, II.4.2 business models, II.4.3 e-sales process; Algerian e-payment context, law 18-05) | Claude | 5.1 | ch2 |
| ☐ | 5.6 | II.5 Portal design principles (II.5.1 UX, II.5.2 payment security, II.5.3 rights management, II.5.4 traceability) | Claude | 5.1 | ch2 |
| ☐ | 5.7 | II.6 Competitive analysis (II.6.1 competitors table, II.6.2 pros and cons) + conclusion | Claude; Student validates facts | 5.1 | ch2 |
| ☐ | 5.8 | 🔴 Review Ch II → fixes | Student (+ Mossab) | 5.2–5.7 | ch2 approved |

## Phase 6 — Chapter III: analysis and design (Lane A) → `chapters/ch3.md`

| ✓ | # | Task | Who | Needs | Produces |
|---|---|---|---|---|---|
| ☐ | 6.1 | Intro + III.1 Project overview (+ architecture figure) | Claude | 0.1, 4.7 | ch3 |
| ☐ | 6.2 | III.2 Stakeholders (adapted per G-01) | Claude | 0.1 | ch3 |
| ☐ | 6.3 | III.3 Role and permission mapping | Claude | 3.6 | ch3 |
| ☐ | 6.4 | III.4.1–III.4.10 use case tables: Authenticate · Browse VPN offers · Choose a plan and get the VPN configuration · Subscribe and checkout · Process payment · Manage VPN accounts · View billing history · View payment receipts · Manage subscriptions · Contact the company | Claude | 0.1 | ch3 |
| ☐ | 6.5 | Mobile use case tables | Claude | 4.5 | ch3 |
| ☐ | 6.6 | III.5 Use case diagrams with text: III.5.1 global, III.5.2 client, III.5.3 admin (+ support side), III.5.4 mobile | Claude | 3.2, 4.5 | ch3 |
| ☐ | 6.7 | III.6 Sequence diagrams, one paragraph each (web + mobile) | Claude | 3.3, 4.6 | ch3 |
| ☐ | 6.8 | III.7 Class diagram + explanation | Claude | 3.4 | ch3 |
| ☐ | 6.9 | III.8 Relational database model | Claude | 3.5 | ch3 |
| ☐ | 6.10 | III.9 Data dictionary | Claude | 3.5 | ch3 |
| ☐ | 6.11 | III.10 Business logic and rules | Claude | 3.7 | ch3 |
| ☐ | 6.12 | III.11 Relationships and cardinalities | Claude | 3.7 | ch3 |
| ☐ | 6.13 | III.12 Gantt chart + conclusion | Claude | 3.8 | ch3 |
| ☐ | 6.14 | 🔴 Review Ch III → fixes | Student (+ Mossab for mobile) | 6.1–6.13 | ch3 approved |

## Phase 7 — Chapter IV: implementation and deployment (Lane A) → `chapters/ch4.md`

| ✓ | # | Task | Who | Needs | Produces |
|---|---|---|---|---|---|
| ☐ | 7.1 | Intro + IV.1.1 Frameworks (backend, frontend, mobile) | Claude | 3.11, 4.2 | ch4 |
| ☐ | 7.2 | IV.1.2 DBMS (PostgreSQL, SQLite) | Claude | 3.11 | ch4 |
| ☐ | 7.3 | IV.1.3 Security and payment technologies (hashing, sessions, CSRF, HMAC, Chargily, WireGuard crypto; MFA → future work) | Claude | 0.1 | ch4 |
| ☐ | 7.4 | IV.1.4 Dev tools (IDE, Git/GitHub, Docker, pnpm/Composer, CI) | Claude | 0.13, 1.5 | ch4 |
| ☐ | 7.5 | IV.1.5 Modelling tools | Claude | 0.3 | ch4 |
| ☐ | 7.6 | IV.2.1 Architectural pattern (separate frontend + monolithic API; why not microservices) | Claude | — | ch4 |
| ☐ | 7.7 | IV.2.2 Presentation, business and data layers | Claude | — | ch4 |
| ☐ | 7.8 | IV.2.3 Third-party integrations (Chargily, e-mail service, WireGuard gateway) | Claude | 0.14 | ch4 |
| ☐ | 7.9 | IV.3.1–IV.3.4 Modules: offers and pricing · order (checkout) management · billing and payment · subscription management | Claude | 0.1 | ch4 |
| ☐ | 7.10 | IV.3.5–IV.3.8 Modules: VPN provisioning (WireGuard) · user profile · authentication and access control · contact and moderation | Claude | 0.1 | ch4 |
| ☐ | 7.11 | IV.4.1 Client interface walkthrough (screenshots + captions) | Claude | 3.10 | ch4 |
| ☐ | 7.12 | IV.4.2 Administrator interface | Claude | 3.10 | ch4 |
| ☐ | 7.13 | IV.4.3 Support side (contact requests, moderation) | Claude | 0.1, 3.10 | ch4 |
| ☐ | 7.14 | IV.4.4 Mobile interface | Claude | 4.4 | ch4 |
| ☐ | 7.15 | IV.5.1 Security hardening (SQLi/XSS, CSRF, rate limits, headers) + code excerpt | Claude | 3.12 | ch4 |
| ☐ | 7.16 | IV.5.2 Payment gateway integration (flow, HMAC webhook, idempotency) + code excerpt | Claude | 3.12 | ch4 |
| ☐ | 7.17 | IV.5.3 Notification system (e-mails + scheduled tasks) | Claude | 0.1, 0.14 | ch4 |
| ☐ | 7.18 | IV.5.4 Reporting and analytics | Claude | — | ch4 |
| ☐ | 7.19 | IV.6.1 Unit and integration tests | Claude | 1.2 | ch4 |
| ☐ | 7.20 | 🔴 IV.6.2 Security and penetration testing | Claude | 2.5 | ch4 |
| ☐ | 7.21 | 🔴 IV.6.3 Performance and load testing | Claude | 2.5 | ch4 |
| ☐ | 7.22 | 🔴 IV.7.1 Production environment | Claude | 1.13, 3.13 | ch4 |
| ☐ | 7.23 | IV.7.2 CI/CD pipeline | Claude | 1.5, 2.1 | ch4 |
| ☐ | 7.24 | IV.7.3 Monitoring and logging + conclusion | Claude | 2.1 | ch4 |
| ☐ | 7.25 | 🔴 Review Ch IV → fixes | Student + Mossab (mobile parts) | 7.1–7.24 | ch4 approved |

## Phase 8 — Supervisor review of Chapters II–IV

| ✓ | # | Task | Who | Needs | Produces |
|---|---|---|---|---|---|
| ☐ | 8.1 | 🔴 Build a review copy (Word or PDF) of Ch II–IV with figures | Claude | 5.8, 6.14, 7.25 | `output/review-ch2-4.docx` |
| ☐ | 8.2 | 🔴 Send it to the supervisor | Student | 8.1, 0.9 | sent |
| ☐ | 8.3 | Review and return comments (runs while Phases 9–10 go on) | Supervisor | 8.2 | comments |
| ☐ | 8.4 | Apply the supervisor's comments to Ch II–IV | Claude + Student | 8.3 | ch2–ch4 updated |

## Phase 9 — EADN visit — only after Ch II–IV are finished and sent to the supervisor

| ✓ | # | Task | Who | Needs | Produces |
|---|---|---|---|---|---|
| ☐ | 9.1 | Printable interview sheet + document checklist (decisions.md §9) | Claude | — | `eadn/interview-sheet` |
| ☐ | 9.2 | Confirm the appointment | Student + EADN | 0.11, 8.2 | date confirmed |
| ☐ | 9.3 | 🔴 Visit EADN: interviews, documents, org chart, data sheet, photos, internship facts, permissions | Student (+ Mossab) + EADN | 9.1, 9.2 | notes, copies, photos |
| ☐ | 9.4 | 🔴 Type up the material (anonymized): notes, document fields, photo list | Student (Claude formats) | 9.3 | `docs/memoire/eadn/` |
| ☐ | 9.5 | If something is missing: follow-up call/e-mail or second visit | Student + EADN | 9.4 | missing facts |

## Phase 10 — Chapter I: host organization (Lane A) → `chapters/ch1.md`

| ✓ | # | Task | Who | Needs | Produces |
|---|---|---|---|---|---|
| ☐ | 10.1 | 🔴 Intro + I.1 Overview + I.2 Technical data sheet | Claude | 9.4 | ch1 |
| ☐ | 10.2 | I.3 Organizational chart (redrawn) + I.4 Departments (I.4.1 sales, I.4.2 IT and network, I.4.3 customer service, I.4.4 administration and finance) | Claude | 9.4 | ch1 + figure |
| ☐ | 10.3 | I.5.1 Definition of the service studied + I.5.2 current workflow (with a process figure) | Claude | 9.4 | ch1 + figure |
| ☐ | 10.4 | I.5.3 Document study (one card + field table per document) | Claude | 9.4 | ch1 |
| ☐ | 10.5 | I.6 Roles and stakeholders (I.6.1–I.6.4 position tables) | Claude | 9.4 | ch1 |
| ☐ | 10.6 | I.7 Critique, limitations, suggested improvements (linked to the portal's features) + conclusion | Claude | 10.3–10.5 | ch1 |
| ☐ | 10.7 | Consistency pass: align Ch III actors/roles and wording with Ch I (Q-14 outcome) | Claude | 10.6, 8.4 | ch1–ch3 aligned |
| ☐ | 10.8 | 🔴 Review Ch I (+ EADN checks the facts if they wish) | Student (+ EADN) | 10.7 | ch1 approved |

## Phase 11 — Introduction, problem statement, conclusion, abstracts, preliminary texts

| ✓ | # | Task | Who | Needs | Produces |
|---|---|---|---|---|---|
| ☐ | 11.1 | 🔴 Problem statement: main question, sub-questions, hypotheses (Q-10) | Claude drafts options, Student chooses | 10.8 | `chapters/intro.md` |
| ☐ | 11.2 | 🔴 General introduction (context, problem, objectives, method, chapter outline) | Claude | 11.1 | `chapters/intro.md` |
| ☐ | 11.3 | 🔴 General conclusion: the guide's 6 points; hypotheses confirmed or not; limits + future work (§5 gaps) | Claude | 11.1 | `chapters/conclusion.md` |
| ☐ | 11.4 | Abstract (English) + keywords | Claude | 11.2, 11.3 | `chapters/abstracts.md` |
| ☐ | 11.5 | Résumé (French) + mots-clés | Claude | 11.4 | `chapters/abstracts.md` |
| ☐ | 11.6 | ملخص (Arabic) draft + native check | Claude drafts, Student checks | 11.4 | `chapters/abstracts.md` |
| ☐ | 11.7 | Dedications (one per student) — any time | Student, Mossab | — | `chapters/front.md` |
| ☐ | 11.8 | Acknowledgements — any time | Student (+ Mossab) | — | `chapters/front.md` |
| ☐ | 11.9 | List of abbreviations (all chapters) | Claude | 8.4, 10.8 | `chapters/front.md` |

## Phase 12 — Bibliography and annexes

| ✓ | # | Task | Who | Needs | Produces |
|---|---|---|---|---|---|
| ☐ | 12.1 | Final bibliography: grouped as the guide says, alphabetical, access dates; every footnote matches an entry | Claude; Student checks links | 5.1, 10.8, 11.3 | `chapters/bibliography.md` |
| ☐ | 12.2 | Annexes: interview guide, anonymized EADN documents, API endpoint list, env var names (no values), test summary, extra screenshots | Claude + Student (scans) | 2.5, 9.4 | `chapters/annexes.md` |

## Phase 13 — Word assembly and formatting

| ✓ | # | Task | Who | Needs | Produces |
|---|---|---|---|---|---|
| ☐ | 13.1 | Word template per the guide: margins, Times New Roman 12, title styles, page numbers top-right from the introduction, caption and footnote styles (can be done early) | Claude | 0.5 | template |
| ☐ | 13.2 | Cover + title page (logos, wording, font sizes from the guide) | Claude; Student checks | 0.6, 0.7, 13.1 | cover |
| ☐ | 13.3 | 🔴 Insert all parts in the guide's order; figures/tables with numbered captions + source | Claude | 8.4, 10.8, 11.1–11.9, 12.1, 12.2, 13.2 | `output/memoire.docx` |
| ☐ | 13.4 | Lists of figures and tables, short contents at the front, detailed table of contents at the end (Word fields) | Claude | 13.3 | docx |
| ☐ | 13.5 | 🔴 Open in Word: update fields, fix page breaks and image sizes, check numbering | Student | 13.4 | docx |
| ☐ | 13.6 | English spelling and grammar pass; consistent terms | Claude + Student (Word spell check) | 13.3 | docx |

## Phase 14 — Final review

| ✓ | # | Task | Who | Needs | Produces |
|---|---|---|---|---|---|
| ☐ | 14.1 | 🔴 Full read-through by both students — understand every part, the jury will ask | Student, Mossab | 13.5, 13.6 | notes |
| ☐ | 14.2 | 🔴 Send the full draft to the supervisor | Student | 14.1 | sent |
| ☐ | 14.3 | 🔴 Final review | Supervisor | 14.2 | comments |
| ☐ | 14.4 | 🔴 Apply final corrections | Claude + Student | 14.3 | docx |
| ☐ | 14.5 | 🔴 Final checklist (see below) | Claude + Student | 14.4 | all ☑ |
| ☐ | 14.6 | 🔴 Export the final PDF; check fonts, images and page numbers in the PDF | Student (+ Claude) | 14.5 | `output/memoire.pdf` |

## Phase 15 — Print and submit

| ✓ | # | Task | Who | Needs | Produces |
|---|---|---|---|---|---|
| ☐ | 15.1 | 🔴 Print the required copies; cardboard cover binding | Student | 14.6, 0.8 | printed copies |
| ☐ | 15.2 | 🔴 Submit (+ PDF if required) — **by 15/10/2026** | Student + Institute | 15.1 | submitted |
| ☐ | 15.3 | Back up the final files (Word, PDF, sources); commit when the student asks | Student + Claude | 15.2 | backup |

---

## Final checklist (task 14.5)

- ☐ Parts in the guide's order; the cover has every required element; the title page is a copy of the cover.
- ☐ Margins 3 / 2 / 2.5 / 2.5 cm; Times New Roman 12, single spacing; title styles; page numbers top-right, starting at the General Introduction.
- ☐ Every figure and table is numbered, titled, has a source, appears in the lists and is mentioned in the text.
- ☐ All citations are footnotes; every source is in the bibliography and every entry is cited; websites have an access date.
- ☐ The problem statement has hypotheses; the conclusion answers them and contains the 6 points.
- ☐ No sentence copied from the 19/20 reference or from any source without citation.
- ☐ No secrets, keys or passwords, and no personal data of EADN staff or clients, in the text, screenshots or annexes.
- ☐ Names (students, supervisor, EADN) are spelled the same everywhere; English is consistent; every abbreviation is listed.
- ☐ Gaps are handled as decided: adapted titles used, future-work items listed in the conclusion.
- ☐ Web and mobile parts are both present; screenshots are readable.

## Critical path (what blocks everything)

```
0.1 + 0.2 (decisions)
  → Ch II, III, IV written (5 → 6 → 7)
      ↑ needs deployment: 0.14 → 1.3 → 1.4 → 1.6–1.12 → 2.x
      ↑ needs the mobile decision: 0.10 → 4.1/4.2/4.3 (done) → 4.3b (Q-23) → 4.4
  → 8.1 → 8.2 (Ch II–IV sent to supervisor)
  → 9.3 EADN visit → 9.4
  → Ch I (10.1 → 10.8)
  → 11.1 → 11.2 / 11.3
  → 13.3 → 13.5 → 14.1 → 14.2 → 14.3 (supervisor) → 14.4 → 14.5 → 14.6
  → 15.1 → 15.2
```

Four things can stop the whole chain:
1. **Deployment** — especially the e-mail problem on Render (0.14).
2. **The mobile decision** (Q-23 / 4.3b) — the source is in, but it is a web app, not a native app.
3. **The EADN visit** (9.3) — EADN is closed Fri–Sat; book early (0.11).
4. **The supervisor's final review time** (14.3).

## What can run in parallel

- **From the start, at the same time:**
  - Lane A: 3.x, then 5.x → 6.x.
  - Lane B: 1.x → 2.x.
  - Lane C: 4.x.
  - Lane D: Phase 0, plus 11.7 / 11.8 (dedications, acknowledgements).
  - Claude can also do 9.1 (interview sheet) and 13.1 (Word template) at any time.
- Chapter II doesn't need deployment or mobile, so it can be written first while Lanes B and C progress.
- **While the supervisor reviews Ch II–IV (8.3):** the EADN visit and Chapter I (Phases 9–10).
- **After Ch I:** 11.4–11.6 (abstracts) and 12.1–12.2 (bibliography, annexes) alongside 13.1–13.2.

## Minimum version (if time runs out before 15/10)

**Never cut:**
- Everything the guide requires: cover, title page, lists, introduction, problem statement + hypotheses, conclusion with the 6 points, bibliography, page layout.
- **Ch I core:** overview, data sheet, org chart, current workflow, 2–3 key documents, critique.
- **Ch II core:** VPN basics + protocol table, e-commerce basics, competitor table.
- **Ch III core:** actors, permission matrix, the main use case tables, global/client/admin diagrams, 3–4 sequence diagrams, class diagram, relational model, data dictionary, business rules.
- **Ch IV core:** technologies, architecture, modules, client + admin screenshots, security, payment integration, unit/integration tests.
- The three abstracts, the supervisor's final review, printing.

**Can be reduced:**
- II.2 and II.5 shorter.
- Simple use cases grouped.
- Sequence diagrams limited to authentication, checkout + payment, VPN configuration and admin management.
- Key screenshots only.
- Live tests reduced to one Lighthouse report + one header scan.
- CI/CD and monitoring as short paragraphs.
- Annexes = interview guide + API list.
- A simple Gantt chart.

**Fallbacks:**
- **If deployment is blocked:** describe the prepared deployment (`render.yaml`, Docker, Vercel) with the deployment diagram, run the tests locally, and state clearly that the live deployment is pending. Last resort only.
- **Mobile (Q-23):** the source is a web app, not native. Simplest honest route = present it as the responsive **web client** (phone-width screenshots, no emulator), or list mobile as future work. Only promise a native-app chapter if Mossab really has an APK. Never present the fake `/api/checkout` as a real payment.
- **If the supervisor's first review (8.3) is late:** don't wait. Continue with Phases 9–10 and add the comments in 8.4.

## Log of this plan

| Date | Change |
|---|---|
| 2026-10-07 | Created (draft for approval): 15 phases, task-based; replaces the day-by-day plan. Added 0.14 / 1.3 because Render Free blocks SMTP. |
| 2026-10-07 | Phase 4 updated after analysing Mossab's source (it is a Next.js web app, not native): ticked 4.1–4.3, added 4.3b (decide Q-23), rewrote 4.4–4.8 to branch on Q-23. Updated critical path, risks, fallback. See [`mobile-facts.md`](mobile-facts.md). |
