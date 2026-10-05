# PFE Development and Documentation Workflow

You are working on my existing final-year project (PFE).

From now on, follow a structured development AND documentation workflow.

The goal is not only to build and fix the application. The goal is also to continuously prepare the technical information and evidence that I will need later to write my final PFE report (mémoire).

# 1. PROJECT ORGANIZATION

Treat the project as two main technical parts:

* FRONTEND
* BACKEND

Whenever possible, analyze and work on them independently.

If a task affects both sides, coordinate them through their data contract (the REST API called through `/backend-api`).

You may work on frontend and backend in parallel when there is no dependency preventing it.

Do not mix unrelated frontend and backend changes.

Always know which part of the system a change belongs to.

# 2. DEDICATED PFE DOCUMENTATION FOLDER

Maintain a dedicated folder for all information useful for the final PFE report:

```
docs/
└── pfe/
    ├── AGENT-WORKFLOW.md
    ├── README.md
    ├── 00-project-context.md
    ├── 01-project-overview.md
    ├── 02-requirements.md
    ├── 03-architecture.md
    ├── 04-frontend.md
    ├── 05-backend.md
    ├── 06-database.md
    ├── 07-api.md
    ├── 08-authentication-security.md
    ├── 09-technologies-and-packages.md
    ├── 10-features.md
    ├── 11-development-history.md
    ├── 12-testing.md
    ├── 13-deployment.md
    ├── 14-problems-and-solutions.md
    ├── 15-technical-decisions.md
    ├── 16-sources.md
    ├── 17-changelog.md
    ├── 18-glossary.md
    ├── 19-limitations-and-future-work.md
    ├── 20-memoire-mapping.md
    └── assets/
        ├── screenshots/
        ├── diagrams/
        └── other/
```

You may adapt this structure when the project requires it, but keep the same principle: everything important for the PFE must be organized and easy to find.

# 3. WHAT TO DOCUMENT

Continuously collect and maintain information relevant to the PFE, when applicable.

## Project overview

* Project objective
* Problem being solved
* Target users
* Main functionalities
* Scope of the project

## Architecture

* Global architecture
* Frontend architecture
* Backend architecture
* Database architecture
* Communication between components
* Request/data flow
* Important modules

## Frontend

* Framework
* Main libraries
* Components
* Pages
* State management
* Communication with the backend
* Authentication flow
* Important UI/UX decisions
* Important reusable components

## Backend

* Framework
* Controllers
* Services
* Models
* Middleware
* Routes
* Authentication
* Authorization
* Business logic
* Important backend workflows

## Database

* DBMS
* Tables
* Relationships
* Important columns
* Constraints
* Indexes
* Migrations
* Important database decisions

## API and data contract

* Important routes and endpoints
* HTTP methods
* Purpose
* Authentication requirements
* Request/response structure (including Inertia page props)
* Important validation rules
* Relationships between frontend and backend

## Technologies and packages

Maintain a clear inventory of the important technologies and packages used by the project.

For each important dependency, document when relevant:

* Package/library name
* Version
* Where it is used
* Why it is used
* What role it plays in the project

Read package manifests such as `package.json` and `composer.json`.

Do NOT copy dependency folders such as `node_modules`, `vendor`, build output or caches. Document the useful information about dependencies, not generated directories.

## Security

* Authentication
* Authorization
* CSRF protection
* Rate limiting
* Validation
* Password security
* Secrets management
* Webhook verification
* Other important security mechanisms

## Testing

* Tests that exist
* What they cover
* Important validation procedures
* Build/type checks
* Manual testing procedures
* Important bugs discovered during testing

## Problems and solutions

This section is especially important.

Whenever a significant problem is discovered, document:

* Problem
* Cause
* Investigation
* Solution
* Files/components affected
* Result
* Any important lesson or technical decision

This becomes material for the "problems encountered and solutions" section of the mémoire.

# 4. DOCUMENT EVERY IMPORTANT CHANGE

Whenever you make an important code change, update the appropriate PFE documentation at the same time:

* backend route/controller change → update API/backend documentation
* database schema → update database documentation
* frontend architecture → update frontend/architecture documentation
* authentication → update security documentation
* package/dependency → update technologies/packages documentation
* major feature → update features documentation
* important bug fix → update problems-and-solutions documentation
* architectural decision → update technical-decisions documentation

Do not wait until the end of the project. The documentation must evolve together with the project.

# 5. CHANGE HISTORY

Maintain `docs/pfe/17-changelog.md`.

For meaningful changes, record:

* Date (YYYY-MM-DD)
* Area (Frontend / Backend / Database / DevOps / Security)
* Change
* Reason
* Result
* Commit hash (when available)

Keep entries concise. Do not log meaningless micro-edits.

# 6. EXTERNAL SOURCES

Whenever you use an external source to make an important technical decision, add it to `docs/pfe/16-sources.md`.

For each source, record when available:

* Source title
* URL
* Organization/author
* Date accessed
* What information was taken from it
* Why it was relevant to the project

Prefer official documentation. Do not invent sources. Do not add sources that were not actually consulted.

# 7. EVIDENCE FOR THE PFE

When useful, preserve or generate lightweight evidence:

* Architecture diagrams
* Database diagrams
* API documentation
* Important screenshots
* Testing evidence
* Build/test results
* Important command outputs
* Relevant configuration explanations

Store visual material under `docs/pfe/assets/`.

Do NOT store unnecessary duplicated files. Do NOT store secrets.

# 8. CODE AND DOCUMENTATION MUST STAY CONSISTENT

The code is the source of truth.

Never document something that the current code does not actually implement.

When documentation becomes outdated because the implementation changes:

1. Update the code.
2. Update the relevant PFE documentation.
3. Update the changelog if the change is significant.

Do not leave contradictory documentation.

# 9. TOKEN-EFFICIENCY RULE

Do NOT reread the entire project every time.

At the start of every session, read `docs/pfe/00-project-context.md` first and rely on it instead of rescanning the project.

When updating documentation:

* inspect only what is necessary
* reuse reliable information already documented
* read only changed/relevant files
* do not repeatedly scan unrelated directories
* do not dump source code into documentation
* do not duplicate the same information across many files

# 10. DO NOT DOCUMENT GENERATED OR SENSITIVE CONTENT

Never copy or store:

* passwords
* API keys
* tokens
* private credentials
* real secret values
* `.env` secret values
* huge generated files
* `node_modules`, `vendor`, caches
* unnecessary build output

Document the configuration concept without exposing secret values.

# 11. WHEN ADDING A NEW FEATURE

1. Understand the requirement.
2. Determine whether it affects frontend, backend, database, or multiple layers.
3. Implement it.
4. Test it.
5. Update the relevant PFE documentation.
6. Update architecture/API/database documentation when necessary.
7. Add the feature to the changelog if significant.
8. Record important technical decisions.

# 12. WHEN FIXING A BUG

For every significant bug:

1. Identify the symptom.
2. Find the root cause.
3. Fix the root cause.
4. Test the fix.
5. Document the problem, root cause, solution, affected components and verification result.

Do not simply write "bug fixed". Preserve the technical story for the mémoire.

# 13. FINAL PFE READINESS

Keep `docs/pfe/` in a state where another person could understand:

* what the project is and why it was built
* how it is architected
* which technologies and packages are used, and why
* how the frontend, backend and database work
* how frontend and backend communicate
* how authentication and security work
* what features were implemented
* what problems were encountered and how they were solved
* what testing was performed
* what technical decisions were made
* which external sources were consulted

# 14. WORKING PRINCIPLE

You are not only the developer of the project. You are also maintaining its technical documentation for the PFE.

Whenever the project meaningfully changes, update the relevant PFE documentation automatically, without waiting for me to ask.

Do not create documentation for trivial edits.

Keep documentation accurate, structured, concise, and useful for an academic final-year project.

# 15. FIRST RUN: BASELINE AUDIT

When I ask for the baseline audit:

1. Inspect the project structure, manifests, configuration, routes, models, migrations and main pages once.
2. Create all `docs/pfe/` files and fill them with the CURRENT real state of the project.
3. Reconstruct past development history from the git log (if available) into `11-development-history.md`, clearly marked as "reconstructed from git history".
4. Create `00-project-context.md`: a short summary (max ~150 lines) of the stack, folder layout, key modules, conventions and current status.

Keep `00-project-context.md` updated whenever the project changes.

# 16. DOCUMENTATION LANGUAGE

Write all PFE documentation in French.

Keep code identifiers, package names, commands and file paths in their original form.

Use consistent technical terminology across all files.

# 17. VERIFIED VS. UNCERTAIN INFORMATION

Never guess. If something cannot be confirmed from the code or configuration, mark it explicitly:

> ⚠️ À VÉRIFIER : <what is uncertain and why>

Never invent performance numbers, test results, user counts or metrics. Only record values from a real command output or measurement, and state how they were obtained.

# 18. ACADEMIC DIAGRAMS

Produce diagrams as text-based, versionable sources (Mermaid or PlantUML) in `docs/pfe/assets/diagrams/`.

When applicable, maintain:

* Use case diagram (actors and their use cases)
* Class diagram (derived from the real models)
* Sequence diagrams for the main workflows (authentication, main business flow, payment, etc.)
* Activity diagrams for complex processes
* Database diagrams: ERD, plus MCD / MLD (Merise)
* Deployment diagram
* Global architecture diagram

Each diagram must reflect the current code. Update it when the related code changes.

# 19. REQUIREMENTS AND ANALYSIS

In `02-requirements.md`, maintain:

* Actors and their roles
* Functional requirements, numbered (FR-01, FR-02…)
* Non-functional requirements (security, performance, usability, maintainability…)

Link each feature in `10-features.md` to its requirement ID.

# 20. TECHNICAL DECISIONS WITH JUSTIFICATION

For every important decision in `15-technical-decisions.md`, record:

* Context
* Alternatives considered
* Comparison (advantages / disadvantages)
* Final choice and justification
* Consequences

The mémoire must justify WHY a technology or approach was chosen, not only WHAT was used.

# 21. ADDITIONAL FILES

* `18-glossary.md`: abbreviations and technical terms (for the "liste des abréviations")
* `19-limitations-and-future-work.md`: current limitations, known issues and possible improvements (for the conclusion and "perspectives")
* `20-memoire-mapping.md`: a table mapping each `docs/pfe/` file to the mémoire chapter where it will be used

Difference between history files:

* `11-development-history.md` = narrative of development phases (what was built in each phase and why)
* `17-changelog.md` = concise dated list of individual meaningful changes

# 22. SCREENSHOTS AND EVIDENCE YOU CANNOT CAPTURE

If you cannot capture a screenshot or run something yourself, add it to `docs/pfe/assets/screenshots/TODO.md` with:

* What to capture
* Which page / URL / state
* Which mémoire section it is for

I will capture it manually.

# 23. GIT AND END-OF-TASK REPORT

* Use clear commit messages following Conventional Commits (`feat:`, `fix:`, `docs:`, `refactor:`, `test:`).
* Reference the commit hash in changelog entries when available.
* Ask me before destructive operations: deleting data, rewriting existing migrations, major refactors, or changing the authentication system.

At the end of each task, give me a short report:

* What changed (Frontend / Backend / Database / Other)
* How it was tested
* Which `docs/pfe/` files were updated
* Anything marked À VÉRIFIER
