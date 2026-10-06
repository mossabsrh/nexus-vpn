# 10 — Fonctionnalités implémentées

> État au 2026-10-05. Chaque fonctionnalité renvoie aux exigences de `02-requirements.md`. Détails : pages (`04-frontend.md` §2), endpoints (`07-api.md`), logique serveur (`05-backend.md`).

| # | Fonctionnalité | Description | Côtés | Exigences | Endpoints principaux (`07-api.md`) | Pages / composants |
|---|---|---|---|---|---|---|
| F-01 | Site vitrine | Présentation, fonctionnalités, carte, statistiques (contenu statique), offres dynamiques | Frontend + Backend (offres) | FR-01 | §6 `GET pricing` | `/` (`hero-section`, `pricing-section`…) |
| F-02 | Témoignages | Liste publique des témoignages approuvés, dépôt par un utilisateur connecté, modération admin | Frontend + Backend | FR-02, FR-15, FR-25 | §10, §11 `admin/testimonials` | `testimonials-section`, `admin/moderation` |
| F-03 | Demande commerciale | Formulaire de contact pour l'offre Team, suivi par l'admin | Frontend + Backend | FR-03, FR-24 | §10 `POST contact`, §11 `admin/contact-requests` | `contact-section`, `admin/billing` (`ContactRequests`) |
| F-04 | Inscription avec essai | Création de compte + choix d'offre self-serve + essai de 7 jours + e-mails de vérification et de bienvenue | Frontend + Backend | FR-04, FR-29 | §4 `auth/register` | `auth-dialog`, `auth-provider` |
| F-05 | Connexion / session | Connexion, déconnexion, restauration de session au chargement | Frontend + Backend | FR-05 | §4 `auth/login`, `auth/logout`, `me`, `csrf-cookie` | `auth-dialog`, `auth-provider`, `lib/api.ts` |
| F-06 | Récupération de compte | Mot de passe oublié et réinitialisation par lien | Frontend + Backend | FR-06, FR-29 | §4 `auth/forgot-password`, `auth/reset-password` | `auth-dialog`, `/reset-password` |
| F-07 | Vérification d'e-mail | Lien signé temporaire, renvoi du lien | Frontend + Backend | FR-07, FR-29 | §4 `email/verify`, `email/verification-notification` | `/verify-email`, dashboard, `payment-dialog` |
| F-08 | Paramètres du compte | Changement d'e-mail (avec revérification) et de mot de passe | Frontend + Backend | FR-08 | §5 `account/email`, `account/password` | `account-menu` |
| F-09 | Essai gratuit | Démarrage d'un essai pour un compte sans historique | Frontend + Backend | FR-09 | §6 `subscriptions/trial` | `pricing-section` |
| F-10 | Paiement Chargily | Création du checkout, redirection, confirmation par webhook et vérification active, application à l'abonnement, reçu par e-mail | Frontend + Backend + Chargily | FR-10, FR-11, FR-27, FR-29 | §7 `checkout`, `payments/{id}/verify`, §12 webhook | `payment-dialog`, `/payment/result` |
| F-11 | Gestion de l'abonnement | Annulation, consultation de l'abonnement courant et des historiques | Frontend + Backend | FR-12, FR-13 | §5, §6 `subscriptions/cancel`, `account/subscriptions`, `account/payments` | `account-menu`, `/dashboard` |
| F-12 | Accès VPN WireGuard | État de l'accès, génération des clés et téléchargement du fichier `.conf` | Frontend + Backend | FR-14 | §8 `vpn/credentials`, `vpn/config` | `/dashboard` |
| F-13 | Cycle de vie automatique | Expiration, révocation VPN, rappels e-mail planifiés, réconciliation des paiements `pending` (`payments:reconcile`) | Backend (planificateur) | FR-28, FR-29 | — (commandes, `05-backend.md` §8) | — |
| F-14 | Équipes | Abonnement Team créé par l'admin, gestion des membres et des sièges par le propriétaire, accès VPN des membres | Frontend + Backend | FR-16, FR-17, FR-23 | §9 `team*`, §11 `admin/subscriptions/team` | `/dashboard` (carte Équipe), `admin/billing` (`TeamProvisioning`) |
| F-15 | Statistiques admin | Indicateurs utilisateurs, abonnements, conversion, revenus | Frontend + Backend | FR-18 | §11 `admin/stats` | `admin/stats` (`OverviewSection`, `AnalyticsSection`) |
| F-16 | Gestion des utilisateurs | Recherche, filtres, modification nom/e-mail/rôle | Frontend + Backend | FR-19 | §11 `admin/users` | `admin/accounts` |
| F-17 | Gestion des offres | CRUD des offres, activation, suppression protégée, invalidation du cache des prix | Frontend + Backend | FR-20 | §11 `admin/plans` | `admin/offers` |
| F-18 | Facturation admin | Listes des paiements et abonnements, remboursement enregistré, annulation | Frontend + Backend | FR-21, FR-22 | §11 `admin/payments`, `admin/subscriptions` | `admin/billing` |
| F-19 | Journal d'audit | Traçage et consultation des actions sensibles | Frontend + Backend | FR-26 | §11 `admin/audit-logs` | `admin/moderation` (`AuditLogSection`) |
| F-20 | Contrôle d'accès des pages | Redirection serveur des pages `/dashboard` et `/admin` selon la session et le rôle | Frontend (`proxy.ts`) + Backend (`/me`) | FR-05, FR-19… (prérequis) | §4 `me` | `proxy.ts` |
| F-21 | Outils d'exploitation | Export des pairs WireGuard, attribution du rôle admin | Backend (CLI) | FR-30, FR-31 | — (`05-backend.md` §8) | — |
| F-22 | Thème | Thème sombre (par défaut) / clair | Frontend | NFR-12 | — | `theme-toggle`, `ThemeProvider` |
| F-23 | Suppression de compte | Fermeture définitive en libre-service (confirmation par mot de passe + saisie de l'e-mail) : anonymisation + soft delete, historique de facturation conservé ; administrateurs et propriétaires d'équipe exclus | Frontend + Backend | FR-08 | §5 `account` (DELETE) | `account-menu` (zone de danger), `auth-provider` |

Fonctionnalités exposées par l'API sans interface : reçu d'un paiement (`GET account/payments/{id}`), renommage d'équipe (`PATCH team`) — voir `07-api.md` §13.
