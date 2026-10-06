# 11 — Historique du développement

> Récit des phases de développement. Le journal concis des changements est dans `17-changelog.md`.

## 1. Avertissement sur les sources

Le dépôt git local a été initialisé le **2026-10-05** avec un unique commit contenant l'état existant du projet (`067e68d`). **L'historique antérieur n'a pas été suivi dans ce dépôt** : il ne peut donc pas être reconstitué à partir de git.

Indices disponibles (non probants) :

* les **noms des migrations** Laravel portent des dates qui suggèrent un ordre de construction (voir §2.3) ; ces dates sont choisies au moment de la création des fichiers et ne prouvent pas une chronologie réelle ;
* `DEPLOYMENT.md` mentionne un dépôt GitHub `mossabsrh/nexus-vpn-upgraded`, qui pourrait contenir un historique plus ancien.

⚠️ À VÉRIFIER : dates réelles de début et des grandes étapes du projet, existence d'un historique sur le dépôt GitHub, et éventuelle répartition du travail en binôme.

## 2. État initial du projet (commit `067e68d`, 2026-10-05)

### 2.1 Contenu

Commit `chore: initial commit of existing project state` : 358 fichiers (51 834 lignes ajoutées), dont 238 dans `nexus-vpn/` (backend), 79 dans `components/`, 8 dans `app/`, ainsi que la configuration (`package.json`, `pnpm-lock.yaml`, `next.config.mjs`, `proxy.ts`, `render.yaml`, `DEPLOYMENT.md`) et les règles de travail (`CLAUDE.md`, `docs/pfe/AGENT-WORKFLOW.md`).

### 2.2 Fonctionnalités déjà présentes

À ce commit, l'application était **fonctionnellement complète** au sens de `10-features.md` : les fonctionnalités F-01 à F-22 existaient toutes, l'audit documentaire qui a suivi n'ayant modifié aucun code. Une grande partie de ces fonctionnalités venait toutefois d'être construite dans les deux jours précédents, avant le versionnement (voir §2.5). En particulier :

* frontend Next.js 16 (vitrine, tableau de bord, console d'administration, pages de retour) et protection `proxy.ts` ;
* API Laravel 12 de 48 endpoints, paiement Chargily avec webhook HMAC et idempotence, provisionnement WireGuard, équipes, audit, planificateur ;
* schéma de base de données en 17 migrations ;
* 94 tests backend passant (`12-testing.md`) ; lint, contrôle de types et build frontend sans erreur ;
* configuration de déploiement Vercel + Render (Docker, PostgreSQL).

### 2.3 Ordre de construction suggéré par les migrations

| Date dans le nom | Migrations | Phase probable |
|---|---|---|
| (fichiers `0001_01_01_*`) | `cache`, `jobs` | Squelette Laravel (starter kit React/Inertia) |
| 2026-06-18 | `users`, `password_resets`, `plans`, `subscriptions`, `payments`, `teams`, `team_user`, `sessions` | Modèle de données initial : comptes, offres, abonnements, paiements, équipes |
| 2026-09-15 | `testimonials`, `users.role` | Témoignages et rôle administrateur |
| 2026-10-01 | champs Chargily sur `payments` | Intégration du paiement Chargily |
| 2026-10-05 | durcissement du schéma de facturation, `vpn_credentials`, `audit_logs`, modération des témoignages, `contact_requests` | Fiabilisation de la facturation, provisionnement VPN, audit, modération, demandes commerciales |

⚠️ À VÉRIFIER : correspondance entre ces dates et le calendrier réel du projet.

### 2.4 Évolution architecturale visible dans le code

Le backend a été créé à partir du **Laravel React Starter Kit** (Inertia + React), puis l'interface a été déplacée vers une application **Next.js séparée** (générée initialement avec v0) qui consomme l'API Laravel. Les restes de cette première architecture sont décrits dans `05-backend.md` §10 et `04-frontend.md` §12. Les URL de retour Chargily « historiques » (`/payment/success`, `/payment/failed`) redirigées vers le frontend témoignent de cette transition. ⚠️ À VÉRIFIER : date et raison de cette migration d'architecture.

### 2.5 Phases juste avant le versionnement (2026-10-04 → 2026-10-05)

> Reconstitué à partir des journaux des sessions de développement assisté (Claude Code), et non de git : ces changements sont tous **inclus dans le commit initial `067e68d`**, sans commit propre. Les résultats de tests cités ont été réellement exécutés pendant ces sessions.

**Phase A — Mise en route locale (2026-10-04).** Création de `nexus-vpn/.env` à partir de `.env.example` (`APP_URL` et `FRONTEND_URL` locaux, l'exemple pointant vers Vercel bloquait le CORS), installation des dépendances Composer et pnpm, base SQLite, migrations et seeder des offres. Vérifié : `/up` → 200, page d'accueil → 200, `/backend-api/pricing` renvoie les offres.

**Phase B — Passe de débogage (2026-10-04).** Corrections : montant de paiement fourni par le client (P-05), vérification d'un checkout d'un autre utilisateur, paiement appliqué deux fois (P-03), abonnement annuel créé avec une période mensuelle, table de réinitialisation de mot de passe mal nommée (P-14), formulaires e-mail / mot de passe appelant des fonctions inexistantes (P-15), `role: null` à l'inscription (P-16), lint et `tsc` analysant `nexus-vpn/vendor` (P-17). Résultat : `php artisan test` 36 réussis / 4 échecs → **43/43**, `pnpm build` réussi, `tsc` 0 erreur.

**Phase C — Durcissement et compléments fonctionnels (2026-10-05).** Traitement d'une liste de contrôle complète (sécurité, facturation, administration, produit) :

* sécurité : CSRF sur toutes les routes de session (fin des exemptions `api/admin/*`, `api/testimonials`, `skipCsrf`), limites de débit nommées, verrouillage après 5 échecs de connexion, erreurs d'inscription non divulguées (P-18), en-têtes de sécurité, suppression du CORS `*` sur `/api/pricing`, garde `proxy.ts` (P-07), arrêt en production si `APP_DEBUG=true`, compte de démonstration limité à `local`/`testing` ;
* facturation : activation idempotente sous verrou (P-03), webhook Chargily (événements `paid/failed/canceled/expired`, 404 pour checkout inconnu, contrôle du montant), remboursements enregistrés par l'admin (Chargily v2 n'a pas d'API de remboursement), un seul abonnement courant par utilisateur garanti par index unique partiel (P-01), suppressions logiques ;
* produit : tableau de bord utilisateur, page de résultat de paiement, réinitialisation de mot de passe et vérification d'e-mail en API + pages, équipes, provisionnement WireGuard, commandes planifiées `subscriptions:expire` / `subscriptions:remind`, e-mails transactionnels, formulaire de contact Team ;
* administration : vraies statistiques (fin des chiffres fictifs, P-19), paiements/abonnements/remboursements, modération des témoignages, journal d'audit, pagination et recherche des comptes.

Résultat : 5 nouvelles migrations, **90/90 tests backend** ; `pnpm lint` 0 problème, `tsc` 0 erreur, `pnpm build` réussi après suppression de `typescript.ignoreBuildErrors` ; parcours vérifiés en direct (419 sans jeton CSRF, 403/307 pour un non-admin, 429 après 5 échecs de connexion).

**Phase D — Revue et corrections ponctuelles (2026-10-05).** Seeder des offres qui écrasait les modifications de l'admin à chaque démarrage du conteneur (P-09), jours payés perdus lors d'un réabonnement (P-10), bouton « Save name » de la console d'administration paraissant toujours désactivé (P-20). Résultat : 94/94 tests backend (référence de `12-testing.md`).

## 3. Phase de documentation (2026-10-05)

Mise en place du suivi git et de la documentation PFE, **sans modification du code applicatif**.

| Étape | Commit | Contenu |
|---|---|---|
| Initialisation | `067e68d` | Dépôt git créé, état existant versionné (avec les règles `CLAUDE.md` et `AGENT-WORKFLOW.md`) |
| Correction des règles | `e246b4d` | Référence obsolète à Inertia retirée de `AGENT-WORKFLOW.md` (le contrat est une API REST JSON) |
| Audit 1/4 — backend | `05f64b7` | `05-backend.md`, `06-database.md`, `09` (backend), `12-testing.md`, `assets/diagrams/erd.mmd` ; 94 tests exécutés : tous réussis |
| Audit 2/4 — frontend | `98ed54c` | `04-frontend.md`, `09` (frontend), `12` (frontend) ; lint, `tsc` et build réussis |
| Audit 3/4 — contrat, sécurité, déploiement | `4e9c025` | `07-api.md`, `08-authentication-security.md` (25 risques), `13-deployment.md` |
| Audit 4/4 — synthèse | voir `17-changelog.md` | Présentation, exigences, fonctionnalités, historique, décisions, glossaire, limites, correspondance avec le mémoire, contexte projet |
