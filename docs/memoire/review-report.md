# Review report — Chapters II, III, IV (form + content)

> Full review of everything written so far against (1) the **methodology guide** (`docs/memoire-ref/Methodologie_Redaction_Memoire_BTS.pdf`, 10 pp — the official rule, priority 1), (2) the **19/20 reference** (`…Benoui_Sami_Mebbani…pdf`, 103 pp — style/level model), (3) the supervisor's structure (via `content-spec.md`) and `decisions.md`, and (4) the chapters themselves (`ch2.md`, `ch3.md`, `ch4.md`) and their generated PDF. Date: **2026-10-11**.
>
> **Rule applied:** where the guide and the reference disagree on form, the **guide wins** (and the difference is noted here). Items I could fix, I fixed; items that need the student's decision are in §D.

## A. Form vs the methodology guide (priority 1)

| # | Rule (guide) | Our state | Verdict |
|---|---|---|---|
| A1 | Margins 3 cm L / 2 cm R / 2.5 cm T&B (p6) | `reference.docx`: 3 / 2 / 2.5 | ✅ compliant |
| A2 | Times New Roman 12, single line spacing (p6) | Normal/Body = TNR 12, single | ✅ compliant |
| A3 | Page number **top-right** (p6) | Header, right-aligned PAGE field | ✅ compliant (the reference uses bottom-centre; we follow the **guide**) |
| A4 | Numbering **starts at the Introduction** (p6) | Per-chapter PDFs number from 1 | ⏳ done at final assembly (front matter unnumbered) — P11 |
| A5 | Titles 16/14/12 **bold + underlined** (p6) | Were bold only → **changed to bold + underlined** | ✅ **fixed** (but guide p8 shows bold-italic and the reference uses bold+colour → decision **D1**) |
| A6 | Footnotes: AUTHOR (First name): *Title*, publisher, place, year, page; regulatory = type+number+date+domain; website = URL + **date & time** (p7) | `AUTHOR (First): *Title*…`; websites now *(consulted on dd/mm/yyyy at hh:mm)*; surnames uppercase; marker after the technology name; consecutive markers separated | ✅ **fixed**; exact minutes to be confirmed by the student → P2 |
| A7 | Figures/tables numbered + titled + **source just below**; listed at the front (p9) | Caption + source under each; `lists.md` holds the lists | ✅ compliant (captions now **centred**, A7-style) |
| A8 | Conclusion = 6 points (p9) | Chapter conclusions are short transitions | ⏳ the **6 points** apply to the *General* conclusion (not yet written) |
| A9 | Bibliography after the conclusion, grouped, alphabetical, no page numbers (p9–10) | Footnotes carry full refs; grouped bibliography | ⏳ assembled at the end from the footnotes |
| A10 | Preliminary pages + order (cover → … → table of contents at end) (p3–4) | — | ⏳ final assembly — P10 |

## B. Form vs the 19/20 reference (style and level)

| # | Reference | Our state | Verdict |
|---|---|---|---|
| B1 | Decimal numbering (I.1, I.4.1, II.2.1) | IV.1, IV.1.1 | ✅ matches |
| B2 | Use-case table: Titre / Acteur / Description / Pré-condition / Scénario nominal / Scénario alternatif | Same 6 rows (Title / Actor / Description / Pre-condition / Nominal / Alternative) | ✅ matches (ours has 21 tables vs 6) |
| B3 | Ch III = project, actors, roles, use-case tables, use-case + sequence diagrams, class diagram, relational model, data dictionary, business rules, cardinalities, Gantt | Same (III.1–III.12) | ✅ matches; ours is deeper |
| B4 | Each technology shown with its **logo + a paragraph** (IV.1) | Added per-technology logos in IV.1 (Part 2) | ✅ matches |
| B5 | Each chapter opens on a **decorative title page**; a **coloured running header** carries the chapter name | Title pages (navy accent bar + centred title) + navy running header on body pages; **no header / page number on the title pages** | ✅ **done** (decision D2) |
| B6 | Headings are **navy-coloured** | Headings are black | ✅ guide does not require colour → we keep black (decision D1 covers colour) |
| B7 | Chapter title "Chapitre II : …" (colon) | "CHAPTER II — …" (em-dash, English) | ℹ️ English style; kept (decision D3) |
| B8 | Table/figure captions **centred**, italic | Captions now **centred** (title bold, source italic) | ✅ matches |
| B9 | Depth per chapter | Ours meets/exceeds, per the supervisor's extra sections (architecture, modules, security, tests, deployment, CI) | ✅ |

## C. Content and consistency (all chapters)

| # | Check | Result |
|---|---|---|
| C1 | English spelling — one variety everywhere | ✅ **British** throughout (-ise/-isation, behaviour, modelling, anonymise, catalogue…). Exceptions are justified: "visualizing" is inside a verbatim OMG UML quote; "International **Organization** for Standardization" (ISO) and "**Organisation** for Economic Co-operation" (OECD) are official names |
| C2 | Cross-references (figure/table/section numbers) | ✅ valid after the revision: Figures II.1–II.3 and IV.2–IV.32 are each referenced in the text; the ERD is Figures III.14a/III.14b, the subscription state diagram is III.15 and the Gantt is III.16; Table IV.4 renamed; the new Team-management module and the contact-request-inbox figure (IV.29) are in place; `lists.md` matches |
| C3 | No leftover draft or internal working language in the output | ✅ the `<!-- … -->` headers, `## Notes` and trailing `---` are stripped by `preprocess.py`; this pass also removed every **gap G-xx / decision D-xx / Q-xx** code, "the reference work", "the supervisor's structure", "honestly/honest" and "verified from the code" from the three chapters — only the intended *future-work* call-outs remain |
| C4 | Facts match the code | ✅ re-verified; a second marketing pass removed the remaining unverifiable site claims and the mémoire wording was corrected to match (logging, account deletion, login throttle, refunds recorded, Brevo future, deployment not yet live) |
| C5 | Consistent terminology | ✅ WireGuard®, Chargily Pay v2, EDAHABIA/CIB, DZD, 131 tests / 508 assertions, pool 10.66.0.0/16 used consistently |
| C6 | Supervisor structure completeness | ✅ II.1–II.6, III.1–III.12, IV.1–IV.7 all present with their subsections |

## D. Decisions for the student

- **D1 — Heading style (Q-12).** ✅ **Resolved (student: bold + underlined).** Applied via `make_reference.py` (Heading 1/2/3/4 = 16/14/12/12, bold + underlined). The guide's own example (p8, bold-italic) and the reference (bold + navy, no underline) were the alternatives.
- **D2 — Reference polish.** ✅ **Resolved (student: add them).** Each chapter opens on a **decorative title page** (navy accent bar + centred, underlined chapter title mid-page; content starts on the next page) and the body pages carry a **navy running-header bar** with the chapter name (a `STYLEREF` field, so it follows the chapter automatically) on the left and the page number on the right — keeping the page number top-right per the guide. Each chapter is now its own Word **section** with *different-first-page* turned on, so the **title pages carry no header and no page number** (the earlier double-chapter-name issue is gone); `fix_docx.py` creates the sections and empty first-page headers.
- **D3 — Chapter-title separator.** We use "CHAPTER II — Title" (em-dash); the reference uses a colon. Kept the em-dash as the English norm. → change to a colon if preferred.

## E. Second-reviewer revision — 37 items (2026-10-11)

A second reviewer went through the 74-page build. Every item below was fixed and verified against the code, the methodology guide and the official sources; each rebuilt page was rendered and checked.

**Part A — internal/working language removed**
- **1.** Removed every `gap G-xx`, `decision D-xx`, `Q-xx`, "decisions §5", "the supervisor's structure", "the reference work" (IV.1.5/IV.1.6 no longer mention the 19/20 mémoire), "honest/honesty/honestly", "verified from/against the code" and "the placeholder list" from ch2/ch3/ch4; rewritten in normal academic English ("future work", "not implemented in the current version"). Verified by grep: none remain.
- **2.** Captions now read *Source: prepared by the authors* (two authors), keeping the real source where there is one (e.g. *…, from `routes/api.php`*).

**Part B — facts / contradictions**
- **3.** Logging: Ch II (II.3.3, II.5.4) rewritten — the portal stores no VPN connection or traffic data but keeps an audit log (records the source IP), the contact-request source IP and the standard application log; Ch III/IV consistent.
- **4.** Account deletion stated everywhere as **anonymises + soft-deletes** (not "erases").
- **5.** "Account lockout" → **login throttle (per e-mail and IP)**: fixed in III.4.1/Figure III.6 text, IV.1.3, IV.5.2, Table IV.4 and the test name (`test_login_is_throttled_after_five_failed_attempts`).
- **6.** Refunds: Ch III now says the admin **records** a refund (III.2.3, UC-16).
- **7.** Listing IV.3 shows the real production guard verbatim (`if (! $this->app->isProduction()) return; if (config('app.debug')) throw …`).
- **8.** Brevo HTTP transport is **not** in the code (checked `config/mail.php`, `config/services.php`): IV.1.4, IV.2.3, IV.7.1 moved to the future tense.
- **9.** Deployment described as the **configured/target** environment (render.yaml, Dockerfile), not a live fact.
- **10.** IV.6.3 reworded — eager-loading on list endpoints to avoid N+1; removed "the N+1 identified in the audit" and the test-duration-as-performance claim.
- **11.** Table IV.4 gained an **Evidence** column citing the real automated tests (`AccountSecurityTest`, `AdminTest`, `ChargilyPaymentTest`, `PaymentFlowTest`); live re-check kept as future work.
- **12.** Added a sentence after Table III.17 explaining UC-12 (409) and UC-14 (422) are two different endpoints.
- **13.** UC-03 renamed **"Get the VPN configuration"** (heading, title, Table III.6, lists); the use-case diagrams already said so.
- **14.** UC-15 nominal scenario now includes the real `DELETE /api/admin/plans/{plan}` endpoint.
- **15.** Added a **Team management** module (IV.3.9) and a Table IV.2 row (`GET/PATCH /api/team`, `POST/DELETE /api/team/members`); "eight modules" → **nine** everywhere.
- **16.** Data dictionary (Table III.25) rebuilt from the migrations with every field and a real **Size** column (done in the prior pass; re-verified).
- **17.** II.2.3 framed client-server as a **service/deployment model**, not a network-level type; II.3.2 now attributes tunnel protection to **WireGuard** (not IPsec).
- **18.** Table II.1 row renamed **"Operation and transport"** with precise values (kernel/IKE; user-space TLS + UDP/TCP; kernel/user-space + UDP) and sources.
- **19.** Footnote commentary moved into the text ([^nordvpnproto] and [^law1805] now carry only the reference).

**Part C — short sourced paragraphs added**
- **20.** IV.5.4: the **server-side key-generation trade-off** (simplicity vs. the key existing on the server), with client-side generation as future work (WireGuard quick-start source).
- **21.** IV.5.4: how peers reach the gateway — the portal does not push peers; `php artisan vpn:peers` exports them for an operator (`wg syncconf`); real-time sync is future work.
- **22.** II.5.2: **PCI DSS** paragraph (delegating card data to Chargily keeps it off our servers), cited to the PCI SSC.
- **23.** II.3.3: Law 18-07 Articles 44–45 (cross-border transfer needs authorisation + adequate protection), reinforced by Law 25-11; Frankfurt/Vercel hosting flagged as a limitation. Only verified facts cited.
- **24.** III.1 functional / non-functional requirements subsections and the subscription **state diagram** (Figure III.15) added (prior pass; re-verified).

**Part D — website claims and screenshots** (committed as `fix(web): remove remaining unverifiable claims …`)
- **25.** Removed unimplemented claims (no-activity-logs, hidden-IP, DNS-encrypted, server-node map, dead footer links + social icons, fake Email/Priority/Dedicated support tiers). Real app change: lint, tsc, build pass; **131/131** tests; `docs/pfe` + changelog updated; committed.
- **26.** Recaptured the affected `memoire/` screenshots (01, 02, 03, 05, 07, 90); re-cropped Figure IV.21 (empty area), made Figure IV.8 footer-only, and added the contact-request-inbox screenshot (Figure IV.29).
- **27.** Removed the Flutter / mobile group from Figure IV.1 (re-rendered `tech-stack.png`) and the Flutter entry from IV.1.1.
- **28.** Every figure in IV.4 (and Figures II.1–II.3) now has a one- to two-sentence description and is referenced in the text.

**Part E — form (methodology guide = priority 1)**
- **29.** Page size set to **A4** in `make_reference.py` (margins unchanged).
- **30.** Website footnotes → *(consulted on dd/mm/yyyy at hh:mm)*; BERGMANN (and other surnames) uppercase; consecutive markers separated with a superscript comma. *Ibid./op. cit.* do not arise: pandoc's reference-style footnotes de-duplicate each source into one numbered note. Exact minutes are reconstructed (P2).
- **31.** Footnote markers moved to **after the technology name**, not the version (PHP, Laravel, Next.js, React, TypeScript, Tailwind, Sanctum, Chargily, PHPUnit, Playwright).
- **32.** Chapter **title pages carry no running header and no page number** (per-chapter Word sections + different-first-page in `fix_docx.py`).
- **33.** **All 33 tables restyled** (full thin-black grid, bold centred header repeated on page breaks, cells vertically centred, short values centred / longer text left, purposeful column widths with no broken tokens, data-dictionary entity column merged vertically, use-case label column narrow + bold) via `fix_docx.py`; each table page rendered and compared.
- **34.** The dense ERD (was unreadable) **split into two readable halves** — Figure III.14a (commercial core) and III.14b (teams/support/audit); sequence and class diagrams verified readable at print size.
- **35.** Code listings reflowed so **no line breaks inside a token** (≤ ~80 chars, 8.5 pt monospace), still the real code.

**Part F — finish**
- **36.** Canonical Word/PDF rebuilt (`build.sh ch2 ch3 ch4`, 71 pages); `_verify.*` deleted; every page rendered and checked (A4, title pages, headers, tables, diagrams, code, footnotes — no blank pages, no overflow).
- **37.** `lists.md`, `placeholders.md` and this report updated; website change committed; mémoire change committed. Introduction and general conclusion **not** started, as instructed.

**Could not fully verify / needs the student:** exact footnote consultation minutes (P2, format is applied); the business figures and competitor claims remain as the student validated them; everything requiring the live deployment, the EADN visit (Chapter I) and the final preliminary pages stays tracked in `placeholders.md`.

## Summary

The chapters are correct and consistent; the form matches the methodology guide on every measurable rule and the 19/20 reference on style. The 37 second-reviewer items are all fixed and verified (Part E §E above). What remains is tracked in `placeholders.md`: the live-deployment evidence, Chapter I (EADN), the preliminary pages, the general conclusion's six points, the grouped bibliography (built at assembly), the Gantt dates, and the student's confirmation of the exact footnote minutes. The introduction and general conclusion were intentionally left unstarted.
