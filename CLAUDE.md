# PFE Documentation Workflow (project owner rules)

For the mémoire, always read docs/memoire/decisions.md first.

@docs/pfe/AGENT-WORKFLOW.md

## Project structure

- FRONTEND = Next.js app at the repo root. Follow the conventions of the existing frontend code.
- BACKEND = Laravel API in `nexus-vpn/`. When working there, follow `nexus-vpn/CLAUDE.md` (Laravel Boost rules).
- CONTRACT = REST API calls from the frontend to Laravel through `/backend-api`. Document this contract in `docs/pfe/07-api.md`: endpoint, method, auth, request/response shape, and which frontend page/component calls it.

Always know which side a change belongs to. Do not mix unrelated frontend and backend changes. When a change touches both sides, update the contract documentation.

## Evidence rules

- **Never invent values that must come from reality** (dates, times, numbers, results, sources, quotes). If a value is unknown, say so and ask — never fill it with a plausible-looking guess.
- Backend routes: use `php artisan route:list --except-vendor` (from `nexus-vpn/`) and the Boost `database-schema` tool.
- Record test evidence in `docs/pfe/12-testing.md`: test file, what it covers, command, result. Never record results that were not actually run.
- When `search-docs` or any documentation informs an important decision, add the official page (title + URL) to `docs/pfe/16-sources.md`. Never cite "search-docs" itself.

## Tools and plugins (project owner rule, 2026-10-11)

Before every task, check whether a plugin, skill, MCP server or tool would give a **better result**, and use it. You have standing permission to **install and use** any such tool from **trusted, official sources** (never expose secrets). Examples that apply here:

- **Graphviz** (`dot`) — installed 2026-10-11 (winget `Graphviz.Graphviz`, `C:\Program Files\Graphviz\bin\dot.exe`). Render PlantUML with real `dot` (`java -jar <plantuml.jar> -graphvizdot "C:\Program Files\Graphviz\bin\dot.exe" -tpng …`) rather than the `-Playout smetana` fallback. (Note: for the existing use-case diagrams, Smetana and dot produce equivalent output, so they were not re-rendered.)
- **Mermaid-CLI** — render via the Playwright Chromium (`-p <puppeteer-config.json>`, `PUPPETEER_SKIP_DOWNLOAD=true`, `-b white -s 3`).
- **Official Word/docx, PDF, PPTX skills, pandoc, python-docx, LibreOffice** — use for the **final** mémoire assembly/conversion and to check the produced `.docx`/`.pdf` (per P-03, the Word file is built at the end).

If a part already produced would be **clearly** better with a newly available tool, redo it and say what changed; if it would not be clearly better, do not churn it.

## Documentation

After every code addition or change, update `docs/` in the same change — add what is new, update what changed, and do not rewrite what is already correct. In practice this means updating the affected `docs/pfe/` files (e.g. `05-backend.md`, `07-api.md`, `12-testing.md`, the relevant feature/decision/limitation entries) and always appending a row to `docs/pfe/17-changelog.md`. Historical/dated records (changelog rows, past test-run snapshots, development history) are kept as written; only present-tense "current state" statements are updated.
