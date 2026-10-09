# Step 2 — Audit of the existing docs (`docs/pfe/` + `memoire-assets/`)

> Every statement in `docs/pfe/` and `docs/pfe/memoire-assets/` was compared to the verified **ground truth** ([`ground-truth.md`](ground-truth.md), Step 1). Done 2026-10-10. This is the **finding list**; the fixes happen in **Step 5**.

## Overall verdict

The existing documentation is **exceptionally thorough and ~95 % accurate** (it came from a careful reference audit on 2026-10-05 and was kept up during the Chargily / account-deletion / Sanctum work). Most "errors" are **outdated facts** from before the Sanctum work (2026-10-07) and a few diagnostic "⚠️ À VÉRIFIER" notes that can now be resolved. Nothing is fundamentally wrong. The diagrams and the data dictionary match the real schema.

## A. Corrections needed (→ Step 5), with exact locations

### A1. Test count is stale: **110 / 121 → 130** (502 assertions)
Current state claims to fix (NOT the dated `17-changelog.md` rows, which are historical and stay):
- `02-requirements.md:98` (NFR-13 "110 tests")
- `00-project-context.md:39` ("110 tests"), `:62` ("110/110")
- `15-technical-decisions.md:64` (TD-07 "110 tests")
- `README.md:38` ("110 tests")
- `12-testing.md:127` ("121 réussis (470 assertions)"), `:164` ("121 réussis (470 assertions)") — my Sanctum update changed the §2 registry total but **missed these two §5/§6 lines**
- `18-audit-final.md:13` ("121 réussis (470 assertions)")
- `memoire-assets/README.md:81–82` ("121 tests")
- `memoire-assets/tests/summary.md:5,33` ("121 tests")
- `memoire-assets/tests/test-output.txt:165` ("121 passed") — **regenerate** from a real `php artisan test` run (130/502) in Step 5.

### A2. Sanctum / mobile token API missing (added 2026-10-07) — *[MOBILE backend exists; app postponed]*
- `06-database.md`: says "17 migrations" (now **18**) and lacks the **`personal_access_tokens`** table. Add it (marked as the mobile token store, app postponed).
- `09-technologies-and-packages.md`: `require` list lacks **`laravel/sanctum` ^4.3 (v4.3.3)**. Add it.
- `18-glossary.md`: add **Sanctum** (and "Bearer token").
- `15-technical-decisions.md` (TD-02): correctly says the **web** uses session, not tokens. Add a short note / new **TD-15**: the mobile client (postponed) uses Sanctum tokens, added without changing the web session flow.
- (Already updated during the Sanctum work: `05-backend.md` §6.1bis, `07-api.md` §4bis, `08-authentication-security.md` §1bis, `12-testing.md` §2 row, `17-changelog.md`.)

### A3. E-mail: record the **Brevo** decision (Render Free blocks SMTP)
- All e-mail docs describe **Gmail SMTP** (correct for local dev). **Production decision = Brevo HTTP API** (verified single sender, no domain needed). **Do not implement now — wire at deploy time.**
- Add a note to `13-deployment.md` (§1 + the Gmail SMTP box) and a new **TD-16** in `15-technical-decisions.md`: "Render Free blocks outbound SMTP (25/465/587) → production e-mail via Brevo HTTP API; local dev keeps Gmail SMTP." Also record in `decisions.md` (Q-22 resolved).
- `config/services.php` already wires `resend`/`postmark`; Brevo is added the same way (API key env) at deploy time.

### A4. `13-deployment.md` §3.4 scheduled tasks: **missing `payments:reconcile`**
The table lists only `subscriptions:expire` + `subscriptions:remind`. Reality (`routes/console.php`) also runs **`payments:reconcile` every 15 min** (added 2026-10-06; `10-features.md` F-13 already mentions it). Add it.

### A5. `20-memoire-mapping.md`: realign to OUR structure
It maps docs to a **generic** plan (Intro / Ch1 contexte+état-de-l'art / Ch2 analyse+conception / Ch3 réalisation / Ch4 tests+déploiement). OUR mémoire (supervisor's structure, decisions.md §4/§5) is: **Ch I host org (EADN) · Ch II VPN+e-commerce theory · Ch III analysis+design · Ch IV implementation+deployment**. Re-map accordingly, and note `03-architecture.md` is still **not created** (architecture currently lives in 04/05/07/13).

### A6. Diagrams (→ Step 5): translate FR→EN + fix notation
- `erd.mmd`, `01-architecture.mmd`, `02-use-case.mmd`, 5 sequence `.mmd`, `09-deployment.mmd`: **French → English** labels.
- `02-use-case.mmd` is a Mermaid **flowchart**, not real UML → **redraw as a proper UML use-case diagram** (PlantUML, P-04) with actors + system boundary.
- Add **mobile placeholders** where the second application will appear (architecture, use-case, a mobile sequence) and the `personal_access_tokens` entity to the ERD — all marked "postponed".
- `01-architecture.mmd` shows "Gmail SMTP" — update the e-mail node to reflect A3 (Brevo in production) or label it "E-mail service (SMTP local / Brevo prod)".
- Minor: `erd.mmd`/`06-database` subscription status list omits `past_due` (used in the UI) — add for completeness.

### A7. `memoire-assets/tests/`
- `test-output.txt`: regenerate (130/502).
- `summary.md`: update counts + add the `MobileAuthTest` row (9 tests).
- `memoire-assets/README.md`: update the test line (121→130) and verify the screenshot figure↔file mapping (prefixes skip 03–07, 21).

### A8. Stray folder
- `docs/pfe/assets/screenshots/TODO.md` exists — a **leftover duplicate** of `docs/pfe/memoire-assets/screenshots/`. Confirm and remove/merge in Step 5 (the real assets are under `memoire-assets/`).

### A9. "⚠️ À VÉRIFIER" notes I can now **resolve** (verified in Step 1)
- `09`/`13`: **`sodium`** — the official `php:8.3` image bundles it; WireGuard key generation works live → resolve the note (no explicit install needed, but can add `docker-php-ext-install sodium`-free confirmation).
- `09`: **`laravel/sail`** unused → confirmed (dev-only, `boost.json sail:false`).
- `06`: **`jobs`/`job_batches`/`failed_jobs`** unused → confirmed (no `ShouldQueue`; synchronous notifications, TD-08).
- `13`: **Node version** on Vercel → not pinned (no `engines`); local v22. State as-is.
- `06`: **PostgreSQL exact types** — not read on the live prod DB (local is SQLite). Keep as a noted limitation (L-12) or map SQLite→PostgreSQL types in the data dictionary.
- `19` L-16 (**CI**) is already **correct** (mislocated starter-kit workflows, PHP 8.4, inactive). The mémoire **plan's G-11** wording is what to fix: "move/fix the existing `tests.yml`/`lint.yml` to the repo-root `.github/` (backend tests + frontend lint/tsc/build)", not "create CI from scratch". (Per the student's instruction.)

## B. "⚠️ À VÉRIFIER" that only the **student / EADN** can answer (do NOT block)
- Academic context (`01` §1, `20` header): establishment (INSFP), speciality, year, supervisor, **EADN** host, binôme → now mostly known in `decisions.md` (EADN D-06, binôme D-08). Feed these in when writing.
- Project motivation / why the Algerian market / why Chargily / why WireGuard server-side / why Next.js over Inertia (`01` §3, several TDs) — the *technical* comparison is done; only the *personal reason* is the student's to state.
- Original "cahier des charges" to compare with the derived requirements (`02` header) — student.
- Whether the institute imposes a specific plan (`20`) — supervisor (decisions.md D-09: structure may be adapted).

## C. Per-file verdict

| File | Verdict |
|---|---|
| `00-project-context.md` | Correct; **110→130** test count (lines 39, 62). |
| `01-project-overview.md` | Correct; §1 academic context = student (EADN/binôme now known); "mobile client out of scope" line will change when mobile resumes. |
| `02-requirements.md` | Correct, excellent (31 FR / 14 NFR). **NFR-13 110→130**. No mobile FRs yet (postponed — OK). |
| `03-architecture.md` | **Missing (never created).** Architecture is in 04/05/07/13. Decide: create it, or keep distributed. |
| `04-frontend.md` | Verified accurate against the live UI (not re-read line-by-line; the component/API facts match ground truth). |
| `05-backend.md` | Correct; already has the mobile §6.1bis (Sanctum). |
| `06-database.md` | Correct; **17→18 migrations**, add `personal_access_tokens`; `past_due` status; resolve jobs-tables note. |
| `07-api.md` | Correct; has §4bis mobile. Verify the intro endpoint count vs the current 94 routes. |
| `08-authentication-security.md` | Correct; has §1bis mobile tokens; risk register R-01..R-27 (verified consistent). |
| `09-technologies-and-packages.md` | Correct, excellent; **add `laravel/sanctum`**; resolve sodium/sail notes; add Brevo note (prod e-mail). |
| `10-features.md` | Correct (F-01..F-23); F-13 already includes `payments:reconcile`. |
| `11-development-history.md` | Historical narrative — spot-checked; keep (dated). |
| `12-testing.md` | Registry updated to 130; **lines 127/164 still say 121** → fix. |
| `13-deployment.md` | Correct, excellent; add `payments:reconcile` (§3.4); add Brevo/Render-SMTP note. |
| `14-problems-and-solutions.md` | Historical P-xx log — keep (dated). |
| `15-technical-decisions.md` | Excellent (14 TDs); **TD-07 110→130**; add TD-15 (mobile Sanctum) + TD-16 (Brevo). |
| `16-sources.md` | Correct (5 Chargily sources); **needs official theory sources** for Ch II (→ Step 5 / gap report). |
| `17-changelog.md` | Correct; dated 110/121 rows are historical → **keep as-is**. |
| `18-audit-final.md` | Correct snapshot (2026-10-07); **121→130**; note filename clash with `18-glossary.md` (student asked for this path; optional renumber). |
| `18-glossary.md` | Excellent; **add Sanctum / Bearer token**. |
| `19-limitations-and-future-work.md` | Excellent and current (L-16 CI already right). |
| `20-memoire-mapping.md` | Re-map to OUR structure (A5); note `03-architecture.md` missing. |
| `README.md`, `AGENT-WORKFLOW.md` | Workflow docs; README **110→130**. |
| `memoire-assets/README.md` | 121→130; verify figure↔screenshot mapping. |
| `memoire-assets/database/{erd.mmd,tables.md}` | Accurate; FR→EN (erd); add `personal_access_tokens` (placeholder); `past_due`. |
| `memoire-assets/diagrams/*.mmd` | Accurate; FR→EN + use-case to real UML + mobile placeholders (Step 5). |
| `memoire-assets/code/{backend.md,frontend.md}` | Commented excerpts — verify still line-accurate in Step 5 (code largely unchanged + Sanctum additions). |
| `memoire-assets/tests/{summary.md,test-output.txt}` | **121→130**, regenerate output. |
| `docs/pfe/assets/screenshots/TODO.md` | Stray duplicate folder → remove/merge (A8). |

## D. What is MISSING entirely (→ hand to Step 4 gap report)
- `03-architecture.md` (or an explicit "architecture lives in X" note).
- **Official theory sources** (Laravel, Next.js, WireGuard, Chargily, OWASP, VPN/e-commerce references) with access dates — for Chapter II.
- The **mobile** application material — *postponed placeholder* everywhere.
- **Chapter I (EADN)** material — only the student/EADN can provide (interview sheet ready).
- Cover facts (speciality, promotion, supervisor name+grade) — student.
