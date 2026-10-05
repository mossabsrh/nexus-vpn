# PFE Documentation Workflow (project owner rules)

@docs/pfe/AGENT-WORKFLOW.md

## Project structure

* FRONTEND = Next.js app at the repo root. Follow the conventions of the existing frontend code.
* BACKEND = Laravel API in `nexus-vpn/`. When working there, follow `nexus-vpn/CLAUDE.md` (Laravel Boost rules).
* CONTRACT = REST API calls from the frontend to Laravel through `/backend-api`. Document this contract in `docs/pfe/07-api.md`: endpoint, method, auth, request/response shape, and which frontend page/component calls it.

Always know which side a change belongs to. Do not mix unrelated frontend and backend changes. When a change touches both sides, update the contract documentation.

## Evidence rules

* Backend routes: use `php artisan route:list --except-vendor` (from `nexus-vpn/`) and the Boost `database-schema` tool.
* Record test evidence in `docs/pfe/12-testing.md`: test file, what it covers, command, result. Never record results that were not actually run.
* When `search-docs` or any documentation informs an important decision, add the official page (title + URL) to `docs/pfe/16-sources.md`. Never cite "search-docs" itself.
