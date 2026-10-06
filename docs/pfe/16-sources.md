# 16 — Sources consultées

> Règle : ne figurent ici que les sources **réellement consultées** pour une décision ou une information importante (titre, URL, organisation, date d'accès, information retenue, pertinence). Aucune source n'est inventée.

## Sources externes

| # | Titre | URL | Organisation / auteur | Date d'accès | Information retenue | Pertinence pour le projet |
|---|---|---|---|---|---|---|
| 1 | Chargily Pay v2 — API Reference : Create a Checkout | https://dev.chargily.com/pay-v2/api-reference/checkouts/create | Chargily | 2026-10-06 | Liste des paramètres du corps (`amount`, `currency` ISO minuscule, `success_url` requis, `failure_url`, `webhook_endpoint`, `chargily_pay_fees_allocation`, `locale`, `metadata`) | Confirme le format d'appel et le nom `webhook_endpoint` (TD-03) |
| 2 | Chargily Pay v2 — Checkout object | https://dev.chargily.com/pay-v2/api-reference/checkouts/checkout-object | Chargily | 2026-10-06 | Statuts possibles : `pending`, `processing`, `paid`, `failed`, `canceled` (pas de `expired`) ; champ `webhook_endpoint` ; `amount` entier | Validation des statuts traités et de la vérification montant/devise (R-10) |
| 3 | Chargily Pay v2 — Expire a Checkout | https://dev.chargily.com/pay-v2/api-reference/checkouts/expire | Chargily | 2026-10-06 | `POST /checkouts/{id}/expire` ; expiration automatique après 30 min ; n'expire qu'un checkout `pending` (400 si déjà expiré, 403 si non `pending`) | Base de la commande `payments:reconcile` (R-06) |
| 4 | Chargily Pay v2 — Webhooks | https://dev.chargily.com/pay-v2/webhooks | Chargily | 2026-10-06 | Endpoint via tableau de bord ou paramètre par checkout (nommé `webhook_url` sur cette page) ; en-tête `signature` ; HMAC-SHA256 du corps brut avec la clé secrète ; répondre 200 | Confirme la vérification HMAC existante et la contradiction de nommage `webhook_url`/`webhook_endpoint` (TD-03) |
| 5 | Chargily Pay v2 — Test and Live Mode | https://dev.chargily.com/pay-v2/test-and-live-mode | Chargily | 2026-10-06 | Chaque environnement a ses propres URL et clés ; le mode test permet de tester l'intégration sans argent réel | Justifie le mode test uniquement (TD-13) |

> Pages consultées le 2026-10-06 pour l'intégration Chargily (le guide « universel » fourni par l'étudiant a été recoupé avec la documentation officielle ci-dessus ; en cas de divergence, la documentation officielle a fait foi — voir TD-03 et `14-problems-and-solutions.md`).

**Aucune source externe n'avait été consultée** pendant l'audit de référence du 2026-10-05 : toute la documentation avait été établie à partir du code, de la configuration du dépôt et de la sortie des commandes (`php artisan route:list`, `db:table`, `php artisan test`, `pnpm lint`, `tsc`, `pnpm build`). L'outil `search-docs` de Laravel Boost n'a pas été utilisé.

## Documents internes du dépôt utilisés

| Document | Utilisé pour |
|---|---|
| `DEPLOYMENT.md` | Procédure et limites du déploiement Render/Vercel (`13-deployment.md`) |
| `render.yaml`, `nexus-vpn/Dockerfile`, `nexus-vpn/docker/*` | Configuration de déploiement |
| `CLAUDE.md`, `nexus-vpn/CLAUDE.md`, `docs/pfe/AGENT-WORKFLOW.md` | Règles de travail et de documentation |

## À compléter pour le mémoire

Les comparaisons de `15-technical-decisions.md` et le chapitre « état de l'art » devraient s'appuyer sur des sources officielles à consulter et à ajouter ici (par exemple les documentations officielles de Laravel, Next.js, WireGuard, Chargily Pay, Vercel et Render, et les recommandations OWASP pour la sécurité). ⚠️ À VÉRIFIER : sources déjà utilisées par l'étudiant pendant le développement.
