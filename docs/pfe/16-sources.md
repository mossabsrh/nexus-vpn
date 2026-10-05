# 16 — Sources consultées

> Règle : ne figurent ici que les sources **réellement consultées** pour une décision ou une information importante (titre, URL, organisation, date d'accès, information retenue, pertinence). Aucune source n'est inventée.

## Sources externes

| # | Titre | URL | Organisation / auteur | Date d'accès | Information retenue | Pertinence pour le projet |
|---|---|---|---|---|---|---|
| — | — | — | — | — | — | — |

**Aucune source externe n'a été consultée** pendant l'audit de référence du 2026-10-05 : toute la documentation a été établie à partir du code, de la configuration du dépôt et de la sortie des commandes (`php artisan route:list`, `db:table`, `php artisan test`, `pnpm lint`, `tsc`, `pnpm build`). L'outil `search-docs` de Laravel Boost n'a pas été utilisé.

## Documents internes du dépôt utilisés

| Document | Utilisé pour |
|---|---|
| `DEPLOYMENT.md` | Procédure et limites du déploiement Render/Vercel (`13-deployment.md`) |
| `render.yaml`, `nexus-vpn/Dockerfile`, `nexus-vpn/docker/*` | Configuration de déploiement |
| `CLAUDE.md`, `nexus-vpn/CLAUDE.md`, `docs/pfe/AGENT-WORKFLOW.md` | Règles de travail et de documentation |

## À compléter pour le mémoire

Les comparaisons de `15-technical-decisions.md` et le chapitre « état de l'art » devraient s'appuyer sur des sources officielles à consulter et à ajouter ici (par exemple les documentations officielles de Laravel, Next.js, WireGuard, Chargily Pay, Vercel et Render, et les recommandations OWASP pour la sécurité). ⚠️ À VÉRIFIER : sources déjà utilisées par l'étudiant pendant le développement.
