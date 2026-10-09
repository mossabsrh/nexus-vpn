# Step 4 — Gap report (what we need vs what we have, and where to get it)

> Compares **content-spec.md** (what each section needs, Step 3) with **ground-truth.md** + **docs-audit.md** (what we have, Steps 1–2). For every gap: **where to get it** (source code, website, database, packages, web references, screenshots, definitions, diagrams). Updated at the end of Step 6 with what is then complete. Done 2026-10-10.

**Status legend:** ✅ have · 🔨 Claude builds now (no deploy/EADN needed) · ⏳ Claude builds after deployment · 👤 student only · 🏢 EADN only · ⏸️ mobile (postponed placeholder).

## 0. Big picture
- **Chapters II, III, IV: ~70 % already exists** in `docs/pfe/` + diagrams + 68 screenshots + code excerpts. The gaps are mostly **translation (FR→EN), real-UML redraw, adaptation per §5, and a few BUILD items** — not new research.
- **Chapter I: ~0 %** — needs the EADN visit (interview sheet ready).
- **Live test evidence + deployment screenshots:** after deployment (Phase 1–2).
- **Nothing is blocked on mobile** (postponed placeholders).

## 1. Chapter I — Host organization (EADN) → almost all 👤🏢 (do NOT block)
| Section | Have | Missing | Where to get it |
|---|---|---|---|
| I.1–I.7 (overview, data sheet, org chart, departments, existing system, documents, roles, critique) | the **interview sheet** (`eadn-interview-sheet.md`) + the §9 checklist | all the real EADN facts, documents, org chart, photos, positions | 🏢 **EADN visit (11–12/10)** → 👤 student types up anonymised notes → 🔨 Claude formats + drafts Ch I |
| Internship facts (dates, EADN supervisor name+title) | — | the facts | 👤 student |

## 2. Chapter II — VPN & e-commerce theory → mostly 🔨 + sources
| Section | Have | Missing | Where to get it |
|---|---|---|---|
| II.1 VPN fundamentals + protocol table | student's English draft (`Desktop\images\memoire.docx`); WireGuard facts in `ground-truth`/`15` | the written section + protocol comparison table + **sources** | 🔨 write from draft + 🌐 **web references**: WireGuard whitepaper, IETF/NIST, OpenVPN/IPsec docs |
| II.2 Types of VPN | — | the section + figures | 🔨 + 🌐 sources |
| II.3 Security & compliance (ISO 27001, GDPR, law 18-07) | our security measures (`08`) | the theory + **standards/laws** | 🔨 + 🌐 ISO/IEC 27001, GDPR (EUR-Lex), Algerian law **18-07** (JORADP) |
| II.4 E-commerce (+ Algerian e-payment, law 18-05) | Chargily integration facts | definitions + Algerian e-commerce law | 🔨 + 🌐 law **18-05**, Chargily docs (`16-sources`) |
| II.5 Portal design principles | our CSP/CSRF/RBAC/audit (`08`, `ground-truth`) | the principles, tied to what we built | 🔨 + 🌐 **OWASP** (ASVS/Top 10) |
| II.6 Competitive analysis | plan markets | competitors table + pros/cons | 🔨 + 🌐 NordVPN/ProtonVPN/… ; 👤 student validates |
| **Bibliography for Ch II** | `16-sources` (5 Chargily) | **all official theory sources** with access dates | 🔨 collect + 🌐 — **the single biggest Ch II gap** |

## 3. Chapter III — Analysis & design → mostly ✅/🔨 (translate + adapt)
| Section | Have | Missing | Where to get it |
|---|---|---|---|
| III.1 overview + architecture figure | `01`,`04`,`05`,`ground-truth`; `01-architecture.mmd` (FR) | EN architecture figure (mobile = future) | 🔨 translate/redraw (figure 3.14) |
| III.2 stakeholders (G-01) | `02-requirements` actors | the 5-role→app mapping + matrix | 🔨 from routes + middleware |
| III.3 role/permission matrix | routes, `EnsureUserIsAdmin`, `proxy.ts` | the matrix table | 🔨 from **source code** |
| III.4 use-case tables (10, titles adapted §5) | `10-features`, `07-api`, `ground-truth` | one table per UC (Title/Actor/Pre/Nominal/Alt) | 🔨 from **code + website** |
| III.5 use-case diagrams (global/client/admin/support) | `02-use-case.mmd` (Mermaid flowchart, FR) | **real UML** diagrams (EN); support merged into admin (G-05); ⏸️ mobile placeholder | 🔨 **PlantUML** (P-04) |
| III.6 sequence diagrams | 5 `.mmd` (FR): register, login, payment, vpn, admin-delete | EN; add missing (browse, cancel, contact); paragraphs; ⏸️ mobile placeholder | 🔨 translate + add |
| III.7 class diagram | `03-class-diagram.mmd` (EN-ish) | EN polish; add `PersonalAccessToken` ⏸️ | 🔨 |
| III.8 relational model (text) | schema (`06`, `erd.mmd`) | the R(…) textual model | 🔨 from **database** |
| III.9 data dictionary | `tables.md`, `erd.mmd` (FR) | EN; add `personal_access_tokens` ⏸️, `past_due` | 🔨 translate |
| III.10 business rules | models, `05-backend` | the rules list (EN) | 🔨 from **source code** |
| III.11 relationships & cardinalities | ERD | the list (EN) | 🔨 |
| III.12 Gantt | — | real project phases/dates | 👤 student (dates, Q-18) → 🔨 chart |

## 4. Chapter IV — Implementation & deployment → ✅/🔨 + ⏳ (after deploy)
| Section | Have | Missing | Where to get it |
|---|---|---|---|
| IV.1 technologies (+ logos, versions) | `09-technologies`, `ground-truth` (exact versions) | EN section + **logos**; MFA→future (G-06); +Sanctum (A2); ⏸️ mobile | 🔨 + 🌐 **logos** (official brand assets) |
| IV.2 architecture (pattern/layers/APIs) | `13`,`15` TD-01/02 | EN section; Chargily + **Brevo** + WireGuard | 🔨 |
| IV.3 modules (8) | `05-backend`,`07-api`,`10-features` | one subsection per module (adapt §5: G-05 helpdesk→contact+moderation, G-07 VPN provisioning) | 🔨 from **code** |
| IV.4 interface walkthrough | **68 screenshots** (current, verified) | selection + captions; capture missing **states** (validation error, success, checkout dialog, team mgmt, account settings, contact) ; ⏸️ mobile | 🔨 select + ⏳ capture missing (needs frontend running) |
| IV.5 critical components (security/payment/notifications/reporting) | `code/backend.md`, `08`, `ground-truth` | EN section + **code excerpts** (CSRF/headers, HMAC webhook, scheduler, stats) | 🔨 from **source code** |
| IV.6 testing (unit/integration, security, performance) | `12-testing` (**130 tests**) | the tests table (EN); **G-10** header/TLS scans + manual-check evidence; **G-09** Lighthouse + load test | 🔨 tests table now; ⏳ **live scans after deploy** (free online tools) |
| IV.7 deployment (prod env, CI/CD, monitoring) | `13-deployment`, `render.yaml`, Dockerfile | EN section + **deployment diagram** + screenshots; **G-11** move/fix CI to repo-root `.github/`; **G-12** logs + `/up` + audit (+ uptime monitor) | 🔨 CI fix (config only) + ⏳ deployment screenshots after deploy |

## 5. Preliminary pages, intro, conclusion, bibliography, annexes
| Part | Have | Missing | Where |
|---|---|---|---|
| Cover/title facts + logos | theme, authors (D-08), EADN (D-06) | speciality, promotion, year, supervisor name+grade, institute+EADN logos | 👤 student (cover facts, Q-16) |
| Dedications / acknowledgements | — | the texts | 👤 student + Mossab |
| Abstracts FR/AR/EN | — | drafts; Arabic native check | 🔨 draft + 👤 Arabic check |
| General introduction / problem statement | `01`, context | written intro; problem + **hypotheses** | 🔨 draft options → 👤 choose (Q-10); needs Ch I |
| General conclusion | `19-limitations` | the 6 points + hypotheses verdict + future work | 🔨 |
| Bibliography | `16-sources` | grouped EN bibliography incl. Ch II sources | 🔨 |
| Annexes | `07-api`, `.env.example`, `12-testing` | interview guide, anonymised EADN docs, API list, env-var names, test summary, extra screenshots | 🔨 + 🏢 EADN docs |

## 6. ONLY the student / EADN can provide (do NOT block — parallel track)
- 🏢 **EADN:** everything in Chapter I (visit 11–12/10, interview sheet ready), permission for logo/photos/documents.
- 👤 **Student:** cover facts (speciality, promotion, academic year, supervisor name+grade, exact name spelling); internship dates + EADN supervisor; **real project phases/dates** (Gantt); dedications/acknowledgements; Arabic abstract check; validate Ch II competitor facts; choose the problem-statement wording.

## 7. "BUILD now" checklist for Step 5 (no deploy, no EADN) — what Claude fills immediately
1. **Fix the existing docs** per docs-audit.md §A (test counts →130, add Sanctum to `06`/`09`/`18-glossary`, Brevo note in `13`+TD-16, `payments:reconcile` in `13`, realign `20-mapping`, regenerate `tests/test-output.txt`, remove stray `assets/` folder).
2. **Glossary / definitions** — extend `18-glossary` (Sanctum, Bearer token, Brevo…).
3. **Data dictionary + relational model (EN)** — from the verified schema.
4. **Role/permission matrix** — from routes + middleware.
5. **Use-case tables (10, EN, adapted §5)** — from code + site.
6. **Diagrams → EN + real UML** — translate the 9 `.mmd` to English, redraw the use-case as PlantUML, add mobile placeholders + `personal_access_tokens`; export PNG.
7. **Code excerpts (EN comments)** — verify/refresh `code/backend.md`+`frontend.md` (+ Sanctum).
8. **Ch II theory sources** — collect official references (WireGuard, OWASP, ISO/GDPR, Algerian laws 18-05/18-07, Next.js/Laravel) with access dates → extend `16-sources`.
9. **Technology logos** — gather official logos for IV.1.
10. **CI fix (config only)** — move/fix `tests.yml`/`lint.yml` to repo-root `.github/` (backend tests + frontend lint/tsc/build) — **plan it; apply when the repo is pushed**.

## 8. "BUILD after deployment" (Phase 1–2, with the student)
- Deployment screenshots (Render/Vercel/GitHub Actions/Chargily webhook/live site), live payment + e-mail evidence, `.conf` import.
- Performance (Lighthouse + small load test) and security (header/TLS scans) on the live site; record in `12-testing`.
- Deployment diagram with the real URLs.
- Wire **Brevo** e-mail (D-17) at deploy time.

## 9. Still-open student decisions that shape content (from decisions.md)
P-01 gap handling (§5), P-03 writing workflow, P-04 diagram tools, Q-10 problem statement, Q-12 title style, Q-13 theme title, Q-14 (does EADN sell VPN?), Q-16 cover facts, Q-17 keep name "NexusVPN", Q-18 dates. **None block the Step-5 BUILD-now list** except where marked 👤.
