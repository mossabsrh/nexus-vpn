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
| A6 | Footnotes: AUTHOR (First name): *Title*, publisher, place, year, page; regulatory = type+number+date+domain; website = URL + **date & time** (p7) | `AUTHOR (First): *Title*…` used; websites have the date | ✅ format compliant; website **time** missing → P2 (student) |
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
| B5 | Each chapter opens on a **decorative title page**; a **coloured running header** carries the chapter name | Chapters start on a new page; no title page / running header | ⚠️ optional polish — decision **D2** (not required by the guide) |
| B6 | Headings are **navy-coloured** | Headings are black | ✅ guide does not require colour → we keep black (decision D1 covers colour) |
| B7 | Chapter title "Chapitre II : …" (colon) | "CHAPTER II — …" (em-dash, English) | ℹ️ English style; kept (decision D3) |
| B8 | Table/figure captions **centred**, italic | Captions now **centred** (title bold, source italic) | ✅ matches |
| B9 | Depth per chapter | Ours meets/exceeds, per the supervisor's extra sections (architecture, modules, security, tests, deployment, CI) | ✅ |

## C. Content and consistency (all chapters)

| # | Check | Result |
|---|---|---|
| C1 | English spelling — one variety everywhere | ✅ **British** throughout (-ise/-isation, behaviour, modelling, anonymise, catalogue…). Exceptions are justified: "visualizing" is inside a verbatim OMG UML quote; "International **Organization** for Standardization" (ISO) and "**Organisation** for Economic Co-operation" (OECD) are official names |
| C2 | Cross-references (figure/table/section/gap numbers) | ✅ valid. Ch IV tables were renumbered IV.1–IV.4 after the logo change; all in-text refs updated; no stale Table IV.5/IV.6; Figure IV.1–IV.31, sections IV.1–IV.7, gaps G-05–G-13 all resolve |
| C3 | No leftover draft notes/comments in the output | ✅ the `<!-- … -->` headers, the `## Notes` heading and the trailing `---` are stripped by `preprocess.py`; only the **intended** mobile/Gantt/live-evidence placeholder call-outs remain |
| C4 | Facts match the code | ✅ re-verified during writing; the public site's marketing claims were aligned with the code in Part 1 |
| C5 | Consistent terminology | ✅ WireGuard®, Chargily Pay v2, EDAHABIA/CIB, DZD, 131 tests / 508 assertions, pool 10.66.0.0/16 used consistently |
| C6 | Supervisor structure completeness | ✅ II.1–II.6, III.1–III.12, IV.1–IV.7 all present with their subsections |

## D. Decisions for the student

- **D1 — Heading style (Q-12).** ✅ **Resolved (student: bold + underlined).** Applied via `make_reference.py` (Heading 1/2/3/4 = 16/14/12/12, bold + underlined). The guide's own example (p8, bold-italic) and the reference (bold + navy, no underline) were the alternatives.
- **D2 — Reference polish.** ✅ **Resolved (student: add them).** Each chapter now opens on a **decorative title page** (navy accent bar + centred, underlined chapter title mid-page; content starts on the next page) and every page carries a **navy running-header bar** with the chapter name (a `STYLEREF` field, so it follows the chapter automatically) on the left and the page number on the right — keeping the page number top-right per the guide. *(Minor: the running header also shows on the title page, i.e. the chapter name appears twice there; suppressing it would need per-chapter Word sections — left as-is.)*
- **D3 — Chapter-title separator.** We use "CHAPTER II — Title" (em-dash); the reference uses a colon. Kept the em-dash as the English norm. → change to a colon if preferred.

## Summary

No correctness or consistency problems were found in the written chapters. The form matches the guide on every measurable rule; the two form items that were off (heading underline, caption alignment) are fixed. The remaining open items are the three **style decisions** in §D and the items that can only be completed at final assembly (preliminary pages, page-numbering start, general conclusion's 6 points, grouped bibliography) and by the student (footnote access times) — all already tracked in `placeholders.md`.
