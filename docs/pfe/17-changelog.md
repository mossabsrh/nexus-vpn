# 17 — Journal des modifications

> Liste concise et datée des changements significatifs (le récit par phases est dans `11-development-history.md`). Les changements antérieurs au 2026-10-05 ne sont pas tracés.

| Date | Domaine | Changement | Raison | Résultat | Commit |
|---|---|---|---|---|---|
| 2026-10-05 | DevOps | Initialisation du dépôt git et versionnement de l'état existant (frontend, backend, configuration de déploiement, règles `CLAUDE.md` / `AGENT-WORKFLOW.md`) | Disposer d'un historique traçable pour le développement et le mémoire | 358 fichiers versionnés sur `main` | `067e68d` |
| 2026-10-05 | Documentation | Remplacement de la mention « Inertia page props » par « JSON payloads exchanged through `/backend-api` » dans `AGENT-WORKFLOW.md` | La règle décrivait l'ancienne architecture Inertia, pas le contrat REST réel | Règles cohérentes avec l'architecture | `e246b4d` |
| 2026-10-05 | Backend / Base de données / Documentation | Audit de référence du backend : `05-backend.md`, `06-database.md`, `09` (backend), `12-testing.md`, `erd.mmd` | Documenter l'état réel de l'API pour le mémoire | 73 routes, 17 migrations documentées ; `php artisan test` : 94 réussis / 0 échec | `05f64b7` |
| 2026-10-05 | Frontend / Documentation | Audit de référence du frontend : `04-frontend.md`, `09` (frontend), `12` (frontend) | Documenter l'interface Next.js | `pnpm lint`, `tsc --noEmit`, `pnpm build` réussis | `98ed54c` |
| 2026-10-05 | Sécurité / DevOps / Documentation | Contrat API (`07-api.md`), sécurité et registre des risques (`08-authentication-security.md`), déploiement (`13-deployment.md`) | Formaliser le contrat frontend ↔ backend et les risques | 48 endpoints documentés (3 non utilisés par le frontend), 25 risques recensés (non corrigés) | `4e9c025` |
| 2026-10-05 | Documentation | Synthèse : présentation, exigences (31 FR, 14 NFR), fonctionnalités, historique, problèmes (13), décisions (12), sources, glossaire, limites, correspondance mémoire, captures à réaliser, index et contexte projet | Compléter la base documentaire du PFE | Dossier `docs/pfe/` complet | commit `docs(pfe): complete baseline (overview, requirements, history, decisions, context)` (hash à reporter) |
