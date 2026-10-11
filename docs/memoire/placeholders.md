# Master placeholder list — mémoire

> Every placeholder in the whole mémoire in one place, so nothing is forgotten when we come back to deployment, the mobile app, Chapter I and the final pages. **Update this file whenever a placeholder is added or filled.** Line numbers are approximate (they shift as the chapters are edited) — the *Placeholder* column gives a searchable description. Last updated: **2026-10-11** (after Chapter IV).

Legend for *Blocked by*: 👤 student · 🏢 EADN visit · 🚀 deployment (Phases 1–2) · 📱 mobile app resume (D-16) · 🗓️ real project dates (Q-18).

## Open placeholders

| # | Chapter · Section | File | What is needed | Blocked by |
|---|---|---|---|---|
| P1 | II · II.4.1 | `chapters/ch2.md` | Verbatim text of **Article 6 of Law 18-05** (e-commerce definition); the chapter currently paraphrases it accurately | 👤 |
| P2 | II & IV · footnotes | `chapters/ch2.md`, `chapters/ch4.md` | **Exact access time** for each website footnote (the guide wants date + time); dates are set (10–11 Oct 2026) | 👤 |
| P3 | II.5.1 · III.4/III.5.4/III.6 · IV.1.1/IV.4.4 | `ch2.md`, `ch3.md`, `ch4.md` | **Native mobile app (Flutter)** — POSTPONED (D-16): add the mobile use-case diagram (III.5.4), mobile sequence diagrams (III.6) and the mobile interface walkthrough (IV.4.4), and remove the postponed marks | 📱 |
| P4 | III · III.12 | `chapters/ch3.md` | **Gantt chart** (Figure III.15): real project start dates + durations, then draw the chart | 🗓️ 👤 |
| P5 | I · all | `chapters/ch1.md` (not written) | **Chapter I — Host organisation (EADN)**: visit data (data sheet, org chart, departments, existing system, documents, roles, dates); interview sheet ready (`eadn-interview-sheet.md`) | 🏢 👤 |
| P6 | IV · IV.6.2 | `chapters/ch4.md` | **Live security scan** of the deployed site (header grade, TLS, re-check of 419/429/403 on the public URL); code-side security + manual checks are written now | 🚀 |
| P7 | IV · IV.6.3 | `chapters/ch4.md` | **Live performance / load test** (Lighthouse + small load test + cold-start note) on the deployed site | 🚀 |
| P8 | IV · IV.7.1 / IV.7.3 | `chapters/ch4.md` | **Live deployment evidence**: Render/Vercel dashboards, live `/up`, Chargily-webhook config, end-to-end run (payment + verification e-mail + `.conf` import); the deployment itself (render.yaml, Dockerfile, Brevo wiring D-17) is described now | 🚀 👤 |
| P10 | Preliminary pages | (assembled at the end) | **Cover, dedications, acknowledgements, abstracts (FR/AR/EN)** and the cover facts (institute, speciality, promotion, year, supervisor name + grade, author names); Arabic abstract checked by a native speaker | 👤 |
| P11 | Front matter | `lists.md` → final lists | **Page numbers** in the lists of figures / tables / code listings / abbreviations and the table of contents; added when the final Word document is assembled | (end) |
| P12 | IV · IV.7.2 | `chapters/ch4.md` | **Live CI/CD evidence**: a green GitHub Actions run and the automatic Render/Vercel deployments — after the repo is pushed to GitHub (the workflows themselves are done, see P9) | 🚀 |
| P13 | IV · IV.1 (Figure IV.1) | `figures/tech-stack.png` | *Optional polish:* WireGuard and Chargily are shown as branded text badges (no standard icon-set logo); their official brand logos can be swapped in at assembly if wanted (sources in `figures/logos-sources.md`) | 👤 |

## Filled / resolved
*(moved here as completed, with the date.)*

- **P9 — CI workflows at the repository root (G-11).** Done 2026-10-11: created `.github/workflows/backend-tests.yml` (PHPUnit on PHP 8.2 & 8.3) and `.github/workflows/frontend.yml` (pnpm lint + `tsc --noEmit` + build); removed the old inert copies under `nexus-vpn/.github/workflows/`. The **live run** evidence is now tracked as P12.
- **Test count refreshed.** 2026-10-11: the backend suite is **131 tests / 508 assertions** (was 130 before this session's `DemoDataSeederTest`); `docs/pfe/memoire-assets/tests/test-output.txt` regenerated and Table IV.5 built from it.
