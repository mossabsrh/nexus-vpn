# 00 — Contexte du projet (à lire en premier)

> Résumé pour reprendre le travail sans rescanner le projet. Mis à jour le 2026-10-05 (fin de l'audit de référence). Détails : liens vers les fichiers numérotés ; index et questions ouvertes : `README.md`.

## Projet

**NexusVPN** : plateforme web de vente et de gestion d'un service VPN WireGuard (essai de 7 jours, paiement Chargily en DZD, configuration WireGuard téléchargeable, équipes, console d'administration). PFE ; contexte académique à compléter (`01-project-overview.md`).

## Pile technique

| Côté | Technologies | Détails |
|---|---|---|
| Frontend (racine) | Next.js 16.2.6 (App Router, Turbopack), React 19.2, TypeScript 5.7 strict, Tailwind CSS 4.2, shadcn/ui (Radix), framer-motion, next-themes ; pnpm | `04-frontend.md`, `09` |
| Backend (`nexus-vpn/`) | Laravel 12.60 (PHP ^8.2 ; 8.2 local, 8.3 Docker), PHPUnit 11, Pint, Laravel Boost | `05-backend.md`, `09` |
| Base de données | SQLite (local, tests en mémoire), PostgreSQL (production) ; 17 migrations | `06-database.md` |
| Services | Chargily Pay v2 (mode test), WireGuard (passerelle externe), SMTP | `13-deployment.md` |
| Hébergement | Vercel (frontend) + Render Free (Docker PHP/Apache + PostgreSQL, Francfort) | `13-deployment.md` |

## Organisation des dossiers

```
/                     Frontend Next.js
├── app/              Routes : /, /dashboard, /admin, /payment/result, /verify-email, /reset-password
├── components/       Composants métier ; admin/ (console) ; ui/ (shadcn, majoritairement inutilisés)
├── hooks/, lib/      use-pricing ; lib/api.ts (apiFetch, CSRF, 419) ; pricing, utils
├── proxy.ts          Garde serveur de /dashboard et /admin (appel /api/me)
├── next.config.mjs   CSP + en-têtes, rewrite /backend-api/* → {NEXT_PUBLIC_API_URL}/api/*
├── db/, styles/, public/   Restes inutilisés (schéma MySQL obsolète, CSS doublon, images)
├── render.yaml, DEPLOYMENT.md   Déploiement
├── docs/pfe/         Documentation PFE (ce dossier)
└── nexus-vpn/        Backend Laravel
    ├── app/Http/Controllers/Api/   9 contrôleurs de l'API réelle
    ├── app/Models/                 Logique métier (Payment, Subscription, VpnCredential, …)
    ├── app/Console/Commands/       subscriptions:expire, subscriptions:remind, vpn:peers
    ├── routes/api.php              48 endpoints ; routes/console.php (planification, admin:grant)
    ├── routes/web.php, auth.php, settings.php   Starter kit Inertia hérité (+ redirections Chargily)
    ├── database/                   migrations, seeders (PlansTableSeeder idempotent), factories
    ├── tests/Feature/              94 tests (68 API réelle, 26 hérités)
    └── Dockerfile, docker/         Image Render (migrate --seed + schedule:work au démarrage)
```

## Modules clés

* **Contrat** : navigateur → `/backend-api/*` (même origine) → Laravel `/api/*` ; session cookie + CSRF (`XSRF-TOKEN` / `X-XSRF-TOKEN`, rejeu sur 419). Référence complète : `07-api.md`.
* **Authentification** : sessions Laravel (pas de Sanctum/JWT), e-mail vérifié requis pour payer et pour le VPN, rôles `user`/`admin` (middleware `admin`), `proxy.ts` fail-closed pour `/admin`. `08-authentication-security.md`.
* **Abonnements** : statuts `trialing`/`active`/`canceled`/`expired` ; un seul abonnement courant par utilisateur (index partiel) ; accès = période en cours (`grantingAccess`). `05` §7.1.
* **Paiement** : montant serveur, paiement enregistré avant Chargily, webhook HMAC + vérification active, `markSucceeded` idempotent, pas de renouvellement automatique. `05` §7.2.
* **VPN** : clés Curve25519 générées en PHP (`sodium`), clé privée chiffrée (`encrypted`), adresses du pool `10.66.0.0/16` jamais réutilisées ; synchronisation de la passerelle manuelle (`vpn:peers`). `05` §7.3.
* **Admin** : statistiques, utilisateurs, offres, abonnements/Team, paiements/remboursements, contact, modération, audit (`AuditLog::record`). `10-features.md`.

## Conventions

* Backend : structure Laravel 12 (middleware dans `bootstrap/app.php`), validation `$request->validate()` dans les contrôleurs, sérialisation `toApiArray()` en camelCase (exceptions snake_case : checkout, admin plans/users), 12 limiteurs nommés dans `AppServiceProvider`. Suivre `nexus-vpn/CLAUDE.md` (Boost ; seule dérogation : `docs/pfe/` autorisé).
* Frontend : composants client, `apiFetch` pour tout appel API, formulaires en `useState`, alias `@/`.
* Commandes de vérification : `php artisan test --compact` (dans `nexus-vpn/`) ; `pnpm lint`, `pnpm exec tsc --noEmit`, `pnpm build` (racine ; via `npx pnpm@10` si pnpm absent). Routes : `php artisan route:list --except-vendor`. Schéma : outil Boost `database-schema` ou, s'il n'est pas connecté, `php artisan db:table <table>`.
* Documentation en français ; commits Conventional Commits ; journaliser dans `17-changelog.md`.

## État actuel (2026-10-05)

* Code inchangé depuis le commit initial `067e68d` ; documentation de référence complète (commits `e246b4d` → `4e9c025` + synthèse finale).
* Tests : backend 94/94 réussis ; frontend : lint, types et build réussis ; aucun test frontend.
* Exigences : 31 FR, 14 NFR (`02`) ; 22 fonctionnalités (`10`) ; 13 problèmes résolus (`14`) ; 12 décisions (`15`).
* Risques ouverts principaux (`08` §11) : R-01 révocation VPN non propagée à la passerelle, R-02 base Render Free expirant après 30 jours, R-03 routes héritées actives, R-04 planificateur en veille, R-05 `TRUSTED_PROXIES="*"`.
* À produire : `03-architecture.md`, diagrammes (seul `assets/diagrams/erd.mmd` existe), captures (`assets/screenshots/TODO.md`), réponses aux questions de `README.md`.
