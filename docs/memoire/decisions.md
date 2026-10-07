# Mémoire — decisions and tracking

> **Read this file first** at the start of every mémoire session (rule also in `CLAUDE.md`).
> Work plan: [`plan.md`](plan.md) (task-based). Mobile app analysis: [`mobile-facts.md`](mobile-facts.md).
> Language of this file: simple English (our discussion language). Project docs in `docs/pfe/` stay in French.
> Updated after every decision. Last update: 2026-10-07 (session 1, part 3 — project folder renamed to `E:\PFE\nexus-vpn`; mobile app analysed).

## 0. Status in one look

| Item | Status |
|---|---|
| Phase | Discussion / planning. **No chapter is written yet** (student's request). |
| Deadline | **Submit the mémoire on 15/10/2026.** Defense date: unknown. |
| Website | Finished and audited (0 functional bugs; 121 backend tests pass; lint/tsc/build pass) — `docs/pfe/18-audit-final.md`. |
| Deployment | **NOT deployed yet.** Still to do (plan §7). |
| Mobile app | Source received (`E:\PFE\nexus-vpn-feature-android-app`). ⚠️ **It is a Next.js *web* project, not a native Android app** (no Android/Gradle/APK, no PWA). Full analysis in [`mobile-facts.md`](mobile-facts.md). Student must decide what the "mobile app" really is → **Q-23**. |
| Host organization | EADN (Entreprise d'Appui au Développement du Numérique). Chapter I information still to collect (§9). |
| Language | English. |
| Final structure | Supervisor's structure, adapted to the real app (supervisor agreed). Gap handling proposed in §5, waiting for the student's choice. |

## 1. Reference material

In `docs/memoire-ref/` (checked 2026-10-07):

| File | What it is |
|---|---|
| `Mémoire_de_fin_d'études_Benoui_Sami_Mebbani_Abderrahmane.pdf` | The 19/20 reference. Byte-identical to the copy read in part 1. BTS "Développement des applications multiplateformes", INSFP Bou-Ismail, promotion 2022/2025, desktop + mobile inventory app "Moussadjel". French, ~102 pages, 2 students. |
| `Methodologie_Redaction_Memoire_BTS.pdf` | Official methodology guide "Méthodologie — Rédaction d'un Mémoire de BTS". It was **not** in the folder; Claude copied it from `E:\Le mémoire.pdf` on 2026-10-07 (identical, MD5 `cb565d22…`). |

Other files: supervisor's structure `C:\Users\My_Pc\Downloads\VPN_Portal_Thesis_Structure.txt`; student's English draft (General Introduction + VPN/e-commerce chapter) `C:\Users\My_Pc\Desktop\images\memoire.docx` (2026-04-30).

⚠️ `docs/memoire-ref/` is not git-ignored. If the repo is pushed to GitHub for deployment, another student's mémoire would be uploaded (P-05).

## 2. Priority rule and formatting rules

**Priority when sources conflict (D-04):** 1) methodology guide (parts order, cover, layout, fonts, citations, conclusion, bibliography) → 2) supervisor's structure (chapter content) → 3) 19/20 reference (example of style and level only — **never copy its sentences**).

### Formatting (methodology guide = rule)

- **Order of parts:** cover (cardboard) → blank page → title page (copy of cover) → **dedications → acknowledgements** → **list of figures → list of tables → list of abbreviations** → contents (short) → general introduction → chapters → general conclusion → bibliography → annexes → **table of contents (detailed, at the end)**. Abstracts FR/AR/EN: guide is silent → keep them (supervisor asks for them), placed after the acknowledgements.
- **Cover elements + sizes:** institute (18 bold) · "end-of-training thesis for the BTS diploma" (16 bold) · speciality (14 bold) · "THEME" (16 bold) · title (20 bold) + case study · "Presented by" / "Supervised by" (14 bold) with both students and the supervisor's name + grade · promotion (14 bold) + year.
- **Page:** margins 3 cm left, 2 cm right, 2.5 cm top/bottom. Page numbers **top-right**, numbering starts at the general introduction. Times New Roman 12, single line spacing.
- **Titles:** chapter 16 bold underlined; section 14 bold underlined; sub-section 12 bold underlined (the guide's example page shows bold italic instead → Q-12).
- **Problem statement:** main question (+ sub-questions), then hypotheses (one per question).
- **Figures/tables:** number + title + source under each; all listed at the start (number, title, page).
- **Citations:** numbered **footnotes** at the bottom of the page. Book: AUTHOR (First name): *Title*, publisher, edition, place, year, page. Website: exact address + date and time of access.
- **Conclusion (6 points):** reminder of the objective; summary of theory; main results; answer to the problem statement (hypotheses confirmed or not); value and limits; difficulties met.
- **Bibliography:** after the conclusion, no page numbers, alphabetical by author, grouped: books → journals → academic works → regulatory texts → reports/administrative documents → websites.
- **Annexes:** e.g. interview guide/questionnaire, documents, extra figures.

### Lessons from the 19/20 reference (style and level only)

- Ch I (~20 pages): real host study — data sheet, org chart, department missions, each real document (card + field table: type A/N/AN/D and size), each work position (tasks + tools), critique, suggestions.
- Ch III: one table per use case (Title, Actor, Description, Pre-condition, Nominal scenario, Alternative scenario); one use case diagram per application (web + mobile); a short paragraph before each sequence diagram; relational model in text form; data dictionary; business rules; cardinalities; Gantt chart.
- Ch IV: each tool with logo + paragraph; one "special technology" section; a code excerpt; a screenshot of every screen with a short caption — web, then mobile.
- Supervisor's Ch IV asks for much more than the reference (architecture, modules, security, tests, deployment, CI/CD, monitoring).

## 3. What writing in English changes (guide and reference are in French)

- Rules stay the same (layout, fonts, order, citations); only the wording is translated.
- **Cover wording (proposal):** "National Specialized Institute of Vocational Training — Bou-Ismaïl" with the official French name kept under it; "End-of-Training Thesis submitted for the Higher Technician Diploma (Brevet de Technicien Supérieur — BTS)"; "Speciality:" (official name, French in brackets if needed); "Theme:"; "Case study: EADN"; "Presented by:"; "Supervised by:"; "Promotion:".
- **Part titles:** Dedication · Acknowledgements · Abstract / Résumé / ملخص · List of Figures · List of Tables · List of Abbreviations · Contents · General Introduction · Problem Statement · Chapter I … · General Conclusion · Bibliography · Appendices · Table of Contents.
- **Abstracts:** same text in 3 languages (French, Arabic, English), each with keywords. Arabic checked by a native reader.
- **Captions:** "Figure III.2: …", "Table I.3: …", "Source: EADN" / "Source: prepared by the authors".
- **Proper names stay original:** "Entreprise d'Appui au Développement du Numérique (EADN)" + English translation in brackets at first use; EADN document titles kept in French/Arabic with a translation.
- **Our material is French and must be translated:** all `docs/pfe/` content, the 10 Mermaid diagrams (labels are French), table descriptions, glossary. The website UI is already in English (good for screenshots).
- **Dates:** dd/mm/yyyy (like the guide).
- To check: has the institute / methodology teacher approved English? (Q-15)

## 4. Working structure

Supervisor's structure (`VPN_Portal_Thesis_Structure.txt`), adapted to the real app (D-09) using the gap choices in §5, and ordered by the guide (D-04). Final headings will be written here once §5 is decided.

## 5. Gaps between the supervisor's structure and the real app — proposals

Status: **PROPOSED, waiting for the student's choice.** "Adapt" = keep the section but give it a title that matches the real app. "Future work" = mention in limits/perspectives. "Build" = new work before 15/10.

| ID | Supervisor expects | App reality | Proposal | Time if built |
|---|---|---|---|---|
| G-01 | 5 stakeholders (Sysadmin, Sales Mgr, Contract & Billing Mgr, Client, Tech Support) | Roles `user`, `admin` (+ team owner/member) | **Adapt**: real actors in Ch III + a matrix mapping each business role to the admin console sections it uses (Offers → Sales, Payments/Subscriptions → Billing, Contact requests/Comments → Support, Accounts/Audit → Sysadmin). Separate roles → **future work** | 2+ days |
| G-02 | Add to cart and checkout | One plan + monthly/yearly → Chargily | **Adapt**: "Subscribe and Checkout" (one active subscription per user, enforced by the database, so a cart has no use) | 1 day |
| G-03 | Configure VPN service | Plan + period; WireGuard config download | **Adapt**: "Choose a Plan and Get the VPN Configuration". Server/country choice → **future work** | 1+ day |
| G-04 | Generate invoices | E-mail receipt + receipt window in dashboard | **Adapt**: "View Payment Receipts". PDF invoice → **future work** | ½ day |
| G-05 | Contact support + Helpdesk/Ticketing + Support portal | Contact form + admin handling of contact requests; testimonial moderation | **Adapt**: "Contact the Company" use case; support side = contact requests + moderation in the admin console; Support Portal diagram merged into the admin diagram; ticketing → **future work** | ~2 days |
| G-06 | MFA | None (R-07) | **Future work**; present the existing protections (e-mail verification, lockout after 5 failed logins, rate limits, hashed passwords) | ½–1 day |
| G-07 | VPN "certificates & configs" | WireGuard key pairs + `.conf` | **Adapt** title: "VPN Account Provisioning (WireGuard Keys and Configuration Files)" | — |
| G-08 | Asynchronous notification system | E-mails sent during the request (R-19); scheduled reminders | **Adapt**: "Notification System (Transactional E-mails and Scheduled Tasks)". E-mail queue → **future work** | ½ day |
| G-09 | Performance & load testing | None | **Build (no code)**: Lighthouse report on the live site + small load test on a public endpoint | 1–2 h |
| G-10 | Security & penetration testing | Manual checks (419 CSRF, 429 lockout, 403 admin) + risk register | **Build (no code)**: free online scans of the live site (security headers, TLS) + document the manual tests already done | ~1 h |
| G-11 | CI/CD pipeline | None active; no GitHub remote | **Build (config only)**: GitHub Actions (backend tests + frontend lint/types/build) + auto-deploy on push by Render/Vercel | 1–2 h |
| G-12 | Monitoring and logging | stderr logs, `/up`, audit log | **Adapt** (describe Render/Vercel logs, `/up`, audit log) + optional free uptime monitor on `/up` | 15 min |
| G-13 | A VPN that really connects | No WireGuard gateway | **Adapt**: the portal is the sales + provisioning layer; the VPN gateway is run by the network team. Demo: test gateway values so a real `.conf` downloads and imports into the WireGuard app (stated honestly: no live tunnel). Real gateway → **future work** | ½–1 day + VPS |

## 6. Missing: deployment and mobile app — what the mémoire needs

### Deployment
- Production environment: real URLs (Vercel frontend, Render API, Render PostgreSQL), HTTPS.
- Screenshots: Vercel deployment success; Render service healthy + env vars page (values hidden); Render logs; the live site with the URL bar visible; Chargily dashboard webhook setting; a full test payment on Chargily's page → result page → dashboard updated; verification e-mail received in a real inbox.
- Deployment diagram with the real hosts (update `memoire-assets/diagrams/09-deployment.mmd`, in English).
- CI/CD and monitoring (G-11, G-12); live tests (G-09, G-10).
- Constraints: Render Free PostgreSQL expires 30 days after creation (R-02) → if the defense is after that, recreate it before the defense; Render API sleeps after 15 min (wake it before the demo); Chargily stays in test mode (TD-13). Secrets are entered only by the student, never shared in chat or docs.
- Before pushing: decide the GitHub repo (Q-21) and whether to git-ignore `docs/memoire-ref/` (P-05).

### Mobile app — source analysed 2026-10-07 (full details in [`mobile-facts.md`](mobile-facts.md))

**Finding:** `E:\PFE\nexus-vpn-feature-android-app` is **not a native Android app**. It is a **Next.js 16 / React 19 / TypeScript web project** (partly built with v0), with **no** Android/Gradle project, **no** APK, **no** PWA. The folder name is just the GitHub zip name of a branch `feature/android-app`.

- **What it is:** a smaller alternative web frontend — one public landing page (navbar, hero, stats, features, map, testimonials, pricing, footer; auth and payment dialogs) + one `/admin` dashboard (Overview, Accounts, Offers, Subscriptions, Analytics, Logs, Settings). It bundles a copy of our Laravel backend in `nexus-vpn/` and an unused MySQL `db/schema.sql`.
- **API + login:** calls `/backend-api/*` → `${NEXT_PUBLIC_API_URL}/api/*`; **same style** as our Laravel API (session cookie + CSRF, `credentials:'include'`), but auth calls skip CSRF and expect routes `/api/me`, `/api/auth/{login,register,logout}`, `/api/pricing`, `/api/admin/{users,plans}`. **Not confirmed to work with our backend** — route names/CSRF likely need alignment (check with `php artisan route:list`). No backend change made.
- ⚠️ **Fake payment:** the payment dialog collects raw card number/expiry/CVC and posts to a **mock** `/api/checkout` that returns a random fake receipt — it never uses Chargily. For a thesis on *secure* e-commerce payment this must be flagged, not presented as the real flow.
- **Git/secrets:** the folder is a sibling of the repo, has no `.git`, is not tracked by the main repo, and contains no secrets (only `.env.example`). It cannot be pushed by mistake via the main repo.

**Open decision (Q-23):** what is the "mobile app" in the mémoire? (a) present this as a responsive **web** client at phone width (honest, no emulator); (b) a real native app exists elsewhere / Mossab makes an APK → do a true mobile chapter; (c) drop mobile, list it as future work. See P-09.

Still needed from Mossab regardless: confirm whether a real Android app exists; the intended install method; and (if route b) framework/IDE/screens/APK.

## 7. Day-by-day plan until 15/10/2026 — PROPOSED

EADN is open Sunday–Thursday (Algerian weekend Fri–Sat): EADN visits must be on Wed 07, Thu 08 or Sun 11 at the latest.

| Day | Student (+ Mossab) | Claude |
|---|---|---|
| Wed 07/10 | Decide §5 and P-01…P-08. Book an EADN meeting for Thu 08 and ask for documents (§9). Ask Mossab for the mobile facts. Ask the supervisor when they can review drafts. | Update this file. On OK: printable EADN interview sheet; translate all diagrams to English. |
| Thu 08/10 | **EADN visit #1** (collect §9). Evening: create private GitHub repo + Render + Vercel accounts, enter secrets. Mobile facts from Mossab. | Guide the deployment step by step; add CI workflow (+ docs/changelog); draft **Ch II**. |
| Fri 09/10 | Review Ch II. Deployment screenshots + test payment + e-mail screenshot. Give real project dates (Gantt). | Draft **Ch III**; run live performance/security checks with you. |
| Sat 10/10 | Review Ch III. Mossab: mobile screenshots + details. | Draft **Ch IV** (incl. deployment, tests, mobile). |
| Sun 11/10 | **EADN visit #2** only if something is missing (last safe day). Review Ch IV. Send Ch II–IV to the supervisor. | Draft **Ch I** from the EADN material. |
| Mon 12/10 | Review Ch I. Write dedications + acknowledgements (both students). Check the Arabic abstract. | Draft General Introduction, Problem Statement + hypotheses, General Conclusion, 3 abstracts, abbreviations, bibliography, annexes. |
| Tue 13/10 | Full read-through by both students. Send the full draft to the supervisor. | Assemble the Word file: cover, guide layout, styles, numbered captions, automatic lists + table of contents; consistency check. |
| Wed 14/10 | Apply the supervisor's corrections (morning). Final PDF. **Print + bind** (cardboard cover) in the afternoon. | Apply corrections; final checks. |
| Thu 15/10 | **Submit.** | — |

Main risks: EADN information late (blocks Ch I); supervisor review time; mobile facts late; print shops busy near the deadline; limited RAM (run servers only when needed).

## 8. Information the student must provide (👤)

- [ ] Exact speciality name, promotion (as on the cover), academic year (Q-16)
- [ ] Full names: student + SERRAH Mossab (exact spelling on the cover)
- [ ] Supervisor: full name + grade; EADN internship supervisor (name + title) (Q-16)
- [ ] Internship dates at EADN (Q-18) and real project phases/dates (Gantt)
- [ ] All EADN information for Chapter I (§9)
- [ ] Institute logo; EADN logo/photo (with permission)
- [ ] Dedications (one per student) + acknowledgements
- [ ] Native reader for the Arabic abstract
- [ ] Mobile app facts and screenshots (§6)
- [ ] Sources already used while building the project
- [ ] Printing/submission rules: number of copies, binding, PDF required? (Q-19)

## 9. What to collect from EADN for Chapter I

Ask for **permission** to use each item; hide personal data (names, phone numbers, client data) in documents.

- **I.1 Overview:** full official name (French + Arabic), legal status (EPE, SPA…), creation date + creating legal text, parent ministry/group, mission, main activities/services, types of clients, key dates in its history, number of employees, a photo (building or logo).
- **I.2 Technical data sheet:** name, acronym, legal form, address, phone/fax, e-mail, website, creation date, sector, number of employees, sites/branches, capital (only if public).
- **I.3 Organizational chart:** the official org chart (photo or copy) + the department where you did your internship.
- **I.4 Departments:** the real names and missions of the departments that match Sales, IT & Network Infrastructure, Customer Service/Support, Administration & Finance (their services, rough headcount).
- **I.5 Existing system:**
  - Does EADN sell or provide VPN / secure remote access (or network/hosting services) to clients? If not VPN, which service's sales process should we study? (Q-14)
  - The current process step by step: client request → quote → contract/agreement → payment → account creation/activation (who creates VPN accounts/configs, how they are delivered) → support → renewal/termination. Tools used at each step (paper, e-mail, Excel, software), who does it, how long it takes, rough volume per month.
  - **Documents** (blank or anonymized copies/photos): request form, quote (devis), order form (bon de commande), contract/agreement (convention), invoice (facture), payment receipt, VPN access request/creation form, delivery/activation note, complaint/support form, renewal/termination letter. For each: name, issuer, receiver, internal/external, role, number of copies, and its fields (name, meaning, type A/N/AN/D, approximate size).
- **I.6 Positions:** for Sales Manager, Contract Manager, VPN Technical Support, System Administrator (or the real EADN equivalents): job title, department, number of people, tasks, tools used, documents they issue and receive. A short interview of each (questions go in the annexes).
- **I.7 Critique:** problems seen by staff (delays, errors, paper, no online payment, no self-service, no tracking…) and what they would improve.
- **Internship facts:** start and end dates, EADN supervisor (name + title), department.

## 10. Decisions (agreed)

| ID | Date | Decision |
|---|---|---|
| D-01 | 2026-10-07 | This file is the single tracking file for the mémoire; read it first every session (pointer in `CLAUDE.md` and in Claude's memory). |
| D-02 | 2026-10-07 | Discussion first: no chapter is written until the student asks. |
| D-03 | 2026-10-07 | Deployment and the mobile app count as **missing** until done / received. |
| D-04 | 2026-10-07 | Priority when sources conflict: methodology guide → supervisor's structure → 19/20 reference (style/level only, never copy its sentences). |
| D-05 | 2026-10-07 | Diploma: BTS at an INSFP; the methodology guide is the official rule. |
| D-06 | 2026-10-07 | Host organization: EADN (Entreprise d'Appui au Développement du Numérique). |
| D-07 | 2026-10-07 | Language of the mémoire: English. |
| D-08 | 2026-10-07 | Co-author (binôme): SERRAH Mossab — both names on the cover; he made the mobile app. |
| D-09 | 2026-10-07 | The supervisor accepts adapting the structure to the real app. |
| D-10 | 2026-10-07 | Submission deadline: 15/10/2026. Defense date unknown. |
| D-11 | 2026-10-07 | Reference files live in `docs/memoire-ref/` (guide copied there by Claude from `E:\Le mémoire.pdf`). |
| D-12 | 2026-10-07 | The work plan is **task-based, not date-based**, and lives in [`plan.md`](plan.md) (follow in order whatever the pace; deadline 15/10/2026 still holds). The old day-by-day plan in §7 is superseded. |
| D-13 | 2026-10-07 | Project folder renamed `E:\PFE\nexus-vpn-upgraded-main` → `E:\PFE\nexus-vpn`. Git still works (branch `main`, no remote). Paths updated in memory. |
| D-14 | 2026-10-07 | Applied P-05: `docs/memoire-ref/` and `docs/memoire/eadn/` added to the main `.gitignore` so the other student's mémoire is never pushed. |

## 11. Proposals waiting for a decision

| ID | Proposal |
|---|---|
| P-01 | Gap handling as in §5 (adapt most items, build only G-09/G-10/G-11 which are quick, future work for the rest). |
| P-02 | Day-by-day plan in §7. |
| P-03 | Workflow: Claude drafts each chapter in English as Markdown in `docs/memoire/chapters/`; student reviews; on 13/10 Claude builds the Word file with the guide's layout; student finishes in Word (cover check, page breaks, update fields). |
| P-04 | Diagrams: all translated to English; use case diagrams redrawn in real UML notation with PlantUML (Java is installed); sequence/class/ERD/deployment stay Mermaid (acceptable UML), exported to PNG. |
| P-05 | Add `docs/memoire-ref/` to `.gitignore` before any push (another student's work). |
| P-06 | Deploy from a **new private** GitHub repo created from the local git (history starts 2026-10-05, no `.env` committed) rather than the old `mossabsrh/nexus-vpn-upgraded`. |
| P-07 | Parts order exactly as the guide (§2): dedications before acknowledgements; short contents at the start + detailed table of contents at the end. |
| P-08 | Citations as footnotes (guide) + grouped bibliography at the end. |
| P-09 | Mobile app handling (Q-23). **Recommended:** if a real native app does **not** exist, present Mossab's build honestly as the **responsive web client** (screenshots at phone width, no "native app" claim, no emulator) **or** list mobile as future work — and never present the fake `/api/checkout` as a real payment. If Mossab really has a native app/APK elsewhere, switch to a true mobile chapter. |

## 12. Open questions

| ID | Question | Status |
|---|---|---|
| Q-01 | Right reference files? | ✅ Resolved (D-11) |
| Q-02 | Host organization? | ✅ Resolved: EADN (D-06) |
| Q-03 | Language? | ✅ Resolved: English (D-07) |
| Q-04 | Mobile app: framework, API used, login method | ✅ Resolved from the source: it is a **Next.js web app**, not native; calls `/backend-api` with session-cookie+CSRF style; fake checkout. See [`mobile-facts.md`](mobile-facts.md). New question Q-23 raised. |
| Q-05 | Can the structure be adapted? | ✅ Resolved: yes (D-09) |
| Q-06 | For each gap: build / adapt / future work? | Open → P-01 |
| Q-07 | Defense date | 🟡 Unknown; deadline 15/10 known (D-10) |
| Q-08 | Diagram tool | Open → P-04 |
| Q-09 | Real WireGuard server for the demo? | Open → G-13 proposal |
| Q-10 | Problem statement wording + hypotheses | Open (planned 12/10) |
| Q-11 | Mention of AI assistance in the mémoire? Check the institute's rule. | Open |
| Q-12 | Title style: bold underlined (rule text) vs bold italic (guide's example page) | Open — suggest the rule text; confirm with the methodology teacher |
| Q-13 | Exact theme title — proposal: "Design and Development of a VPN Service Sales Portal — Case Study: EADN" | Open |
| Q-14 | Does EADN sell/provide VPN or secure-access services? If not, which sales process do we study? | Open (ask EADN) |
| Q-15 | Has the institute approved writing in English? | Open (low risk) |
| Q-16 | Speciality, promotion, academic year, supervisor name + grade, EADN internship supervisor | Open |
| Q-17 | Keep the product name "NexusVPN" in the mémoire (portal proposed to EADN)? | Open |
| Q-18 | Internship dates at EADN | Open |
| Q-19 | Printing: number of copies, binding, PDF also required? Where to submit? | Open |
| Q-20 | When can the supervisor review drafts? | Open |
| Q-21 | Which GitHub repo to deploy from? | Open → P-06 |
| Q-22 | How does the live site send e-mails? Render Free blocks SMTP (plan 0.14): paid Render, or an HTTP e-mail API (Brevo/Mailgun/Postmark/Resend), or another host | Open (Claude explains; student decides) |
| Q-23 | What is the "mobile app" in the mémoire? (a) responsive web client at phone width; (b) a real native app/APK from Mossab → true mobile chapter; (c) drop mobile → future work | Open → P-09 (blocks the mobile screenshots and Phase 4) |

## 13. Next steps

1. Student decides P-01…P-08 (or changes them).
2. Today: book EADN for 08/10; ask Mossab for the mobile facts; ask the supervisor about review dates.
3. Follow the plan in §7 once approved.

## 14. Log of this file

| Date | Change |
|---|---|
| 2026-10-07 | Created: references read, comparison done, gaps and questions listed. |
| 2026-10-07 | Part 2: student's answers recorded (D-04…D-11); guide copied into `docs/memoire-ref/`; English implications (§3); gap proposals (§5); day-by-day plan (§7); EADN checklist (§9); proposals P-01…P-08; questions Q-14…Q-21. |
| 2026-10-07 | Part 3: created task-based [`plan.md`](plan.md) (D-12). Folder renamed to `E:\PFE\nexus-vpn` (D-13). Analysed Mossab's "android app" → it is a Next.js web app, not native (§6, [`mobile-facts.md`](mobile-facts.md), Q-04 resolved, Q-23/P-09 raised). Added Q-22 (e-mail). Git-ignored `docs/memoire-ref/` + `docs/memoire/eadn/` (D-14). |
