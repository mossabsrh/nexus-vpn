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

À ce commit, l'application était **fonctionnellement complète** au sens de `10-features.md` : les fonctionnalités F-01 à F-22 existaient toutes, l'audit des jours suivants n'ayant modifié aucun code. En particulier :

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
