# Master placeholder list — mémoire

> Every placeholder in the whole mémoire in one place, so nothing is forgotten when we come back to deployment, the mobile app, Chapter I and the final pages. **Update this file whenever a placeholder is added or filled.** Line numbers are approximate (they shift as the chapters are edited) — the *Placeholder* column gives a searchable description. Last updated: **2026-10-11** (after Chapter IV).

Legend for *Blocked by*: 👤 student · 🏢 EADN visit · 🚀 deployment (Phases 1–2) · 📱 mobile app resume (D-16) · 🗓️ real project dates (Q-18).

## Open placeholders

| # | Chapter · Section | File | What is needed | Blocked by |
|---|---|---|---|---|
| P1 | II · II.4.1 | `chapters/ch2.md` | Verbatim text of **Article 6 of Law 18-05** (e-commerce definition); the chapter currently paraphrases it accurately | 👤 |
| P2 | II–IV · footnotes | `chapters/ch2.md`, `chapters/ch3.md`, `chapters/ch4.md` | **Before printing: re-check every cited URL and add the real consultation time** (methodology guide format *(consulted on dd/mm/yyyy at hh:mm)*). The footnotes currently carry only the real date *(consulted on dd/mm/yyyy)* — the invented times were removed | 👤 |
| P3 | II.5.1 · III.4/III.5.4/III.6 · IV.1.1/IV.4.4 | `ch2.md`, `ch3.md`, `ch4.md` | **Native mobile app (Flutter)** — POSTPONED (D-16): add the mobile use-case diagram (III.5.4), mobile sequence diagrams (III.6) and the mobile interface walkthrough (IV.4.4), and remove the postponed marks | 📱 |
| P4 | III · III.12 | `chapters/ch3.md` | **Gantt chart** (Figure III.16): real project start dates + durations, then draw the chart | 🗓️ 👤 |
| P5 | I · all | `chapters/ch1.md` (not written) | **Chapter I — Host organisation (EADN)**: visit data (data sheet, org chart, departments, existing system, documents, roles, dates); interview sheet ready (`eadn-interview-sheet.md`) | 🏢 👤 |
| P6 | IV · IV.6.2 | `chapters/ch4.md` | **Live security scan** of the deployed site (header grade, TLS, re-check of 419/429/403 on the public URL); code-side security + manual checks are written now | 🚀 |
| P7 | IV · IV.6.3 | `chapters/ch4.md` | **Live performance / load test** (Lighthouse + small load test + cold-start note) on the deployed site | 🚀 |
| P8 | IV · IV.7.1 / IV.7.3 | `chapters/ch4.md` | **Live deployment evidence**: Render/Vercel dashboards, live `/up`, Chargily-webhook config, end-to-end run (payment + verification e-mail + `.conf` import); the deployment itself (render.yaml, Dockerfile, Brevo wiring D-17) is described now | 🚀 👤 |
| P10 | Preliminary pages | (assembled at the end) | **Cover, dedications, acknowledgements, abstracts (FR/AR/EN)** and the cover facts (institute, speciality, promotion, year, supervisor name + grade, author names); Arabic abstract checked by a native speaker | 👤 |
| P11 | Front matter | `lists.md` → final lists | **Page numbers** in the lists of figures / tables / code listings / abbreviations and the table of contents; added when the final Word document is assembled | (end) |
| P12 | IV · IV.7.2 | `chapters/ch4.md` | **Live CI/CD evidence**: a green GitHub Actions run and the automatic Render/Vercel deployments — after the repo is pushed to GitHub (the workflows themselves are done, see P9) | 🚀 |

> **Style decisions** raised by the full review are settled (see `review-report.md` §D): **D1** headings are bold + underlined (16/14/12, guide rule); **D2** each chapter opens with a title page (centred title, navy accent bar) and the body pages carry a navy running header with the chapter name and the page number; the chapter title pages themselves carry no header or page number.

## Filled / resolved
*(moved here as completed, with the date.)*

- **P9 — CI workflows at the repository root (G-11).** Done 2026-10-11: created `.github/workflows/backend-tests.yml` (PHPUnit on PHP 8.2 & 8.3) and `.github/workflows/frontend.yml` (pnpm lint + `tsc --noEmit` + build); removed the old inert copies under `nexus-vpn/.github/workflows/`. The **live run** evidence is now tracked as P12.
- **Test count refreshed.** 2026-10-11: the backend suite is **131 tests / 508 assertions** (was 130 before this session's `DemoDataSeederTest`); `docs/pfe/memoire-assets/tests/test-output.txt` regenerated and the test table built from it.
- **P13 — Figure IV.1 logos.** Done 2026-10-11: the official **WireGuard** dragon icon and **Chargily** mark (from their favicons) now appear both in the per-technology logos of IV.1 and in the Figure IV.1 overview montage (no more text badges).
- **Marketing claims (R-09).** Done 2026-10-11: the public site was aligned with the real implementation (Part 1); no longer a "mock-up" caveat in the mémoire.
- **Marketing claims — second pass.** Done 2026-10-11: removed the remaining unverifiable claims a second reviewer flagged (no-activity-logs, hidden-IP, DNS-encrypted, the server-node map, dead footer links and fake support tiers); recaptured the affected `memoire/` screenshots, re-cropped the admin overview and added the contact-request inbox. See `docs/pfe/17-changelog.md`.
- **Form / methodology pass.** Done 2026-10-11: A4 page size; chapter title pages without header or page number; footnotes reformatted to *(consulted on dd/mm/yyyy at hh:mm)* with AUTHOR (First) uppercase surnames, the marker after the technology name, and consecutive markers separated; continuous footnote numbering; all tables restyled to the 19/20 reference (full grid, repeated bold headers, merged data-dictionary entity column, purposeful column widths); code listings reflowed so no token breaks; the dense ERD split into two readable halves (III.14a/III.14b). Internal working language (gap/decision/Q-codes, "reference work", "honestly", "verified from the code") removed from all three chapters.
