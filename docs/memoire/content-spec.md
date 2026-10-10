# Step 3 — What each chapter/section needs (content specification)

> For **our** mémoire = the supervisor's structure (`VPN_Portal_Thesis_Structure.txt`), ordered and formatted by the **methodology guide**, adapted per **decisions.md §5** (gap choices), at the **level of detail of the 19/20 reference**. Done 2026-10-10.
>
> **Sources used (verified, not invented):** the supervisor structure (re-read in full today); the methodology rules + the 19/20 reference's per-chapter detail level, digested in **decisions.md §2** from the full read of both PDFs in an earlier session (⚠️ the PDFs can't be re-extracted now: no `pdftotext`/poppler in this environment — the digest in §2 is the reference); what we actually have in **ground-truth.md** / `docs/pfe/`.
> Legend for "Source": **HAVE** = already in docs/code; **BUILD** = Claude produces it (no new app code); **STUDENT/EADN** = only they can provide; **⏸️MOBILE** = postponed placeholder.

## Global formatting (methodology guide — applies to the whole document)
- **Parts order:** cardboard cover → blank page → title page (copy of cover) → **dedications → acknowledgements** → abstracts FR/AR/EN (after acknowledgements) → list of figures → list of tables → list of abbreviations → short contents → **General Introduction** → **Problem Statement** → Chapters I–IV → **General Conclusion** → Bibliography → Appendices → detailed table of contents (at the end).
- **Page:** margins 3 cm left / 2 cm right / 2.5 cm top & bottom; Times New Roman 12, single spacing; page numbers **top-right, starting at the General Introduction**.
- **Cover sizes (bold):** institute 18 · "End-of-training thesis for the BTS diploma" 16 · speciality 14 · "THEME" 16 · title 20 + case study · "Presented by"/"Supervised by" 14 · promotion 14 + year.
- **Titles:** chapter 16 bold underlined · section 14 · sub-section 12 (confirm bold-underlined vs bold-italic — Q-12).
- **Figures/tables:** every one numbered + titled + **source** under it, and listed at the front (number, title, page), and mentioned in the text.
- **Citations:** numbered **footnotes**. Book: AUTHOR (First name): *Title*, publisher, edition, place, year, page. Website: full address + **date & time of access**.
- **Conclusion — 6 points:** objective reminder · theory summary · main results · answer to the problem statement (hypotheses confirmed/not) · value & limits · difficulties met.
- **Bibliography:** after the conclusion, no page numbers, alphabetical by author, **grouped** (books → journals → academic works → regulatory texts → reports → websites).
- **Everything in English** (D-07); abstracts also FR + AR; proper names (EADN) kept original + translation at first use; our French material (docs/pfe, diagram labels) must be translated.

## Preliminary pages
| Part | Needs | Source |
|---|---|---|
| Cover + title page | institute, speciality, theme ("Design and Development of a VPN Service Sales Portal — Case study: EADN"), both authors, supervisor (name+grade), promotion/year, logos (institute + EADN) | STUDENT (facts, logos) + BUILD (layout) |
| Dedications (1 per author) | free text | STUDENT + Mossab |
| Acknowledgements | free text (supervisor, EADN, institute) | STUDENT |
| Abstracts FR / AR / EN | ~150–200 words each + keywords; same content in 3 languages; Arabic checked by a native reader | BUILD (draft) + STUDENT (Arabic check) |
| Lists (figures, tables, abbreviations) | auto-generated from the final document; abbreviations from `18-glossary.md` | BUILD |

## General Introduction
Needs (reference level ~2–3 pages): context (VPN market + Algerian e-payment), the general problem, project objectives, method/approach, and the **chapter outline**. **Source:** BUILD (from `01-project-overview.md` + EADN context once collected). Needs a few **sources** (VPN/e-commerce context).

## Problem Statement
Needs: a **main question** + sub-questions, then **hypotheses** (one per question) — methodology rule. Grounded in EADN's current situation (Ch I critique). **Source:** BUILD options, STUDENT chooses (Q-10); needs Ch I (EADN) first.

## CHAPTER I — Presentation of the Host Organization (EADN)
> Reference level: **~20 pages**, very concrete: data sheet, org chart, each department's mission, each real **document** (card + field table), each **work position** (tasks + tools), critique + suggestions. **Almost all STUDENT/EADN** (interview sheet ready: `eadn-interview-sheet.md`). Chapter written **after** the EADN visit (plan Phase 10).

| Section | Needs | Source |
|---|---|---|
| I.1 Overview | official name (FR+AR), legal status, creation date + legal text, parent ministry/group, mission, activities, client types, history, headcount, a photo | STUDENT/EADN |
| I.2 Technical data sheet | the data-sheet table (name, acronym, legal form, address, contacts, creation, sector, headcount, sites, capital) | STUDENT/EADN |
| I.3 Organizational chart | official org chart (figure, redrawn) + the internship department marked | STUDENT/EADN + BUILD (redraw) |
| I.4 Departments (I.4.1 Sales, I.4.2 IT & Network, I.4.3 Customer Service, I.4.4 Admin & Finance) | real names + missions + rough headcount of the departments near the project | STUDENT/EADN |
| I.5 Existing system (I.5.1 definition of the VPN sales service, I.5.2 current workflow, I.5.3 document review) | the current process step-by-step (figure); **document cards** (issuer/receiver/role/copies + field table: name, meaning, type A/N/AN/D, size) | STUDENT/EADN + BUILD (format) — Q-14 (does EADN sell VPN? else which service) |
| I.6 Roles (I.6.1 Sales Mgr, I.6.2 Contract Mgr, I.6.3 VPN Support, I.6.4 SysAdmin) | per-position table: title, dept, headcount, tasks, tools, documents in/out; short interview each (→ annex) | STUDENT/EADN |
| I.7 Critique (I.7.1 critique, I.7.2 limitations, I.7.3 improvements) | problems staff see (delays, paper, no online payment, no self-service…) + wished improvements → **links to our portal's features** | STUDENT/EADN + BUILD (link to features) |
| Conclusion | transition to the solution | BUILD |

## CHAPTER II — Overview of VPN Services and E-commerce (theory / état de l'art)
> Reference level: definitions + comparison tables + figures, each fact **sourced** (footnotes). **Source:** mostly BUILD from the student's existing English draft (`Desktop\images\memoire.docx`) + **official sources** (Ch II's main gap — `16-sources.md` has only Chargily so far). No EADN, no deployment, no mobile → can be written first.

| Section | Needs | Source |
|---|---|---|
| II.1 VPN fundamentals (II.1.1 concepts, II.1.2 architecture, II.1.3 protocols) | definitions; a protocol **comparison table** (WireGuard / OpenVPN / IPsec: speed, crypto, use) + a diagram | BUILD + SOURCES (WireGuard, IETF/NIST) |
| II.2 Types of VPN (remote-access, site-to-site, client-server) | definitions + a figure each | BUILD + SOURCES |
| II.3 Security & compliance (encryption/auth, protocols, ISO/GDPR) | crypto/auth concepts; **ISO/IEC 27001, GDPR**, and Algerian **law 18-07** (personal data) | BUILD + SOURCES (ISO, GDPR, JORADP 18-07) |
| II.4 E-commerce (definition, business models, e-sales process) | definitions; Algerian e-payment context (**law 18-05** e-commerce) | BUILD + SOURCES |
| II.5 Portal design principles (UI/UX, payment security, rights mgmt, traceability) | principles; map to what we implemented (CSP, CSRF, RBAC, audit log) | BUILD (ties to ground truth) + SOURCES (OWASP) |
| II.6 Competitive analysis (competitors, pros/cons) | a **competitors table** (e.g. NordVPN/ProtonVPN/Algerian ISPs) + pros/cons | BUILD + SOURCES; STUDENT validates facts |
| Conclusion | — | BUILD |

## CHAPTER III — System Analysis and Design
> Reference level: **one table per use case** (Title, Actor, Description, Pre-condition, Nominal scenario, Alternative scenario); **use-case diagram per application**; a short paragraph before each sequence diagram; **relational model (text)**, **data dictionary**, business rules, cardinalities, **Gantt**. We HAVE most of this in `docs/pfe` + diagrams (need EN + real UML). Adapted per decisions.md §5. **Open III.4/III.5 with a brief UML explanation** (actors, use cases, associations) as the reference does, and include an **actor description table** (reference Tableau III.1) before the use-case tables.

| Section | Needs | Source |
|---|---|---|
| III.1 Project overview (+ architecture figure) | overview text + **web architecture figure** (mobile marked as future) | HAVE (`01`, `04`, `05`, `ground-truth`) + BUILD (EN figure 3.14) |
| III.2 Stakeholders (III.2.1–III.2.5 the 5 business roles) | **G-01 adapt:** map the 5 business roles (SysAdmin, Sales, Contract/Billing, Client, Support) to the real `user`/`admin` + team roles, with a role→console-section matrix | HAVE (`02-requirements` actors) + BUILD (mapping) |
| III.3 Role & permission mapping | the **role/permission matrix** | BUILD (from routes + `EnsureUserIsAdmin` + proxy.ts) |
| III.4 Use case specs (III.4.1–III.4.10) | one **table per use case**, titles **adapted** per §5: Authenticate · Browse offers · **Choose a plan & get the VPN config** (G-03) · **Subscribe & checkout** (G-02, one sub per user) · Process payment · Manage VPN accounts · View billing history · **View payment receipts** (G-04) · Manage subscriptions · **Contact the company** (G-05) | BUILD (from ground truth) |
| III.5 Use case diagrams (III.5.1 global, III.5.2 client, III.5.3 admin, III.5.4 support) | **real UML** use-case diagrams; **G-05:** merge "Support Portal" into the admin diagram (contact requests + moderation); **III.5.4 support** = admin-side support; **⏸️ mobile diagram = placeholder** | BUILD (PlantUML, P-04) |
| III.6 Sequence diagrams | ≥ web flows: auth, checkout+payment+webhook, VPN config, admin delete; one paragraph each; **⏸️ mobile sequence = placeholder** | HAVE (5 `.mmd`) + BUILD (EN, add missing) |
| III.7 Class diagram | real UML class diagram | HAVE (`03-class-diagram.mmd`) + BUILD (EN, add `PersonalAccessToken` ⏸️) |
| III.8 Relational database model | **relational model in text form** (R(…) notation) | BUILD (from schema) |
| III.9 Data dictionary | full data dictionary (name, meaning, type, size, constraint) | HAVE (`tables.md`, `erd.mmd`) + BUILD (EN, add `personal_access_tokens` ⏸️, `past_due`) |
| III.10 Business logic & rules | business rules list (one current sub/user, idempotent payment, trial-once, access until period end, VPN address never reused…) | HAVE (`05-backend`, models) + BUILD |
| III.11 Relationships & cardinalities | relationships + cardinalities list | HAVE (ERD) + BUILD |
| III.12 Project planning (Gantt) | a **Gantt chart** of the real project phases | STUDENT (real dates, Q-18) + BUILD (chart) |
| Conclusion | — | BUILD |

## CHAPTER IV — Implementation and Deployment
> Reference level: each tool with **logo + paragraph**; a "special technology" section; a **code excerpt**; a **screenshot of every screen** with a caption (web, then mobile). The supervisor asks for **much more** than the reference (architecture, 8 modules, security, tests, deployment, CI/CD, monitoring). We HAVE almost everything in `docs/pfe` + 68 screenshots + code excerpts; adapted per §5. The reference's **"special technology" deep-dive** (Datamatrix) → ours = a focused section on **WireGuard** (or Chargily); its **modelling tool** (Astah UML) → ours = **PlantUML / Mermaid** (IV.1.5).

| Section | Needs | Source |
|---|---|---|
| IV.1 Technologies (IV.1.1 frameworks, IV.1.2 DBMS, IV.1.3 security & payment incl. MFA, IV.1.4 DevTools, IV.1.5 modelling) | each tech with **logo + short paragraph + version**; **MFA → future work** (G-06, we have email-verify + lockout + rate limits instead); ⏸️ mobile framework = placeholder | HAVE (`09-technologies`, `ground-truth`) + BUILD (logos 3.11) |
| IV.2 Architecture (IV.2.1 pattern, IV.2.2 layers, IV.2.3 third-party APIs) | "separate frontend + monolithic API, why not microservices"; presentation/business/data layers; Chargily + **Brevo (prod e-mail)** + WireGuard gateway | HAVE (`13`, `15` TD-01/02, `ground-truth`) + BUILD |
| IV.3 Modules (IV.3.1 offers&pricing, IV.3.2 order mgmt, IV.3.3 billing&payment, IV.3.4 subscription, IV.3.5 VPN provisioning [G-07 "certificates & configs" → WireGuard keys+.conf], IV.3.6 user profile, IV.3.7 auth & RBAC, IV.3.8 helpdesk [G-05 → contact requests + moderation; ticketing → future work]) | one subsection per module: what it does + key endpoints + a figure/snippet | HAVE (`05-backend`, `07-api`, `10-features`) + BUILD |
| IV.4 Interface walkthrough (IV.4.1 client, IV.4.2 admin, IV.4.3 support) | **screenshot of every screen** + caption; support = contact requests + moderation; **⏸️ IV.4.4 mobile = placeholder** | HAVE (68 screenshots) + BUILD (selection 3.10; capture missing states) |
| IV.5 Critical components (IV.5.1 security hardening SQLi/XSS, IV.5.2 payment gateway, IV.5.3 async notifications, IV.5.4 reporting) | **code excerpts** + explanation: CSRF/rate-limit/headers; Chargily HMAC webhook + idempotency; e-mail + scheduler (G-08: "async" → transactional e-mails + scheduled tasks; queue → future work); admin stats | HAVE (`code/backend.md`, `08`, `ground-truth`) + BUILD |
| IV.6 QA & testing (IV.6.1 unit/integration, IV.6.2 security/pen-test, IV.6.3 performance/load) | **130 tests** table; **G-10 build:** free online header/TLS scans + document manual checks (419/429/403); **G-09 build:** Lighthouse + small load test on a public endpoint | HAVE (`12-testing`, 130 tests) + BUILD (live scans after deploy, Phase 2) |
| IV.7 Deployment (IV.7.1 production env, IV.7.2 CI/CD, IV.7.3 monitoring) | production env (Render Docker + Postgres + Vercel + **Brevo**), deployment diagram, screenshots; **G-11 CI:** move/fix the existing `tests.yml`/`lint.yml` to repo-root `.github/` (backend tests + frontend lint/tsc/build); **G-12 monitoring:** Render/Vercel logs + `/up` + audit log (+ optional uptime monitor) | HAVE (`13-deployment`, render.yaml) + BUILD (after deploy, Phase 1–2) |
| Conclusion | — | BUILD |

## General Conclusion
Needs the **6 methodology points** + which hypotheses are confirmed + **limits & future work** (from `19-limitations` + the §5 future-work items: MFA, multi-server, OpenVPN, mobile app resume, proration, PDF invoices, queue, i18n). **Source:** BUILD.

## Bibliography & Appendices
| Part | Needs | Source |
|---|---|---|
| Bibliography | grouped, alphabetical, access dates; **every footnote matches an entry**. Main gap = Ch II theory sources | HAVE (`16-sources` Chargily) + BUILD (official sources) |
| Appendices | interview guide (EADN), anonymised EADN documents, **API endpoint list**, **env var names** (no values), **test summary**, extra screenshots | HAVE (`07-api`, `.env.example`, `12-testing`) + STUDENT/EADN (docs) |

## Summary of what Step 3 reveals
- **~70 % of Chapters II–IV content already exists** (in `docs/pfe` + diagrams + screenshots + code excerpts) and needs mainly **English translation, real-UML redraw, and adaptation** per §5 — not new research.
- **Chapter I is almost entirely STUDENT/EADN** (visit first).
- **Main BUILD gaps:** Ch II official sources; role/permission matrix; use-case tables; relational model + data-dictionary (EN); EN/UML diagrams; Gantt (needs real dates); live test evidence + deployment screenshots (after deploy); logos.
- **⏸️ Mobile** appears only as marked placeholders until resumed.
