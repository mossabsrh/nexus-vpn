# Description des tables de la base de données

> Base **PostgreSQL** en production, **SQLite** en local et pour les tests. Diagramme : [`erd.mmd`](erd.mmd).
> Les tables techniques du framework (`sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `password_resets`, `migrations`) ne sont pas détaillées ici.

## Vue d'ensemble

| Table | Rôle |
|---|---|
| `users` | Comptes (clients et administrateurs), authentification, rôle. |
| `plans` | Catalogue des offres d'abonnement (tarifs, fonctionnalités). |
| `subscriptions` | Abonnements des utilisateurs à une offre (essai, actif, annulé, expiré). |
| `payments` | Paiements Chargily et leur statut ; historique de facturation. |
| `vpn_credentials` | Identifiants WireGuard (clés + adresse tunnel) par utilisateur. |
| `teams` | Équipes (offre vente assistée) rattachées à un abonnement. |
| `team_user` | Table pivot : membres d'une équipe (relation N-N). |
| `testimonials` | Témoignages clients soumis à modération. |
| `contact_requests` | Demandes commerciales (offre Team) envoyées par les visiteurs. |
| `audit_logs` | Journal d'audit des actions sensibles (sécurité, facturation). |

---

## `users`
Comptes utilisateurs. Authentification par session (pas de jeton). Suppression logique (`deleted_at`) : un compte supprimé est **anonymisé** puis *soft-deleted* (voir `User::closeAccount`).

| Colonne | Type | Clé / note |
|---|---|---|
| `id` | bigint | PK |
| `name` | string | Nom affiché |
| `email` | string | **Unique**, normalisé en minuscules |
| `email_verified_at` | datetime? | Null tant que l'e-mail n'est pas vérifié |
| `password` | string | Empreinte **bcrypt** (cast `hashed`) |
| `role` | string | `user` ou `admin` |
| `remember_token` | string? | « Se souvenir de moi » |
| `created_at`, `updated_at`, `deleted_at` | datetime? | Horodatage + soft delete |

## `plans`
Offres d'abonnement. Prix stockés en **entiers (centimes/dinars)**. Suppression refusée si l'offre a un historique (désactivation via `is_active`).

| Colonne | Type | Clé / note |
|---|---|---|
| `id` | bigint | PK |
| `slug` | string | **Unique** (`essential`, `standard`, `team`…) |
| `name`, `description` | string | Libellés |
| `monthly_price`, `yearly_price`, `yearly_total` | int? | Tarifs (null = vente assistée) |
| `currency` | string | `DZD` |
| `features` | json | Liste des fonctionnalités |
| `popular` | bool | Mise en avant |
| `cta` | string | Libellé du bouton |
| `is_active` | bool | Offre visible / achetable |
| `deleted_at` | datetime? | Soft delete |

## `subscriptions`
Abonnement d'un utilisateur à une offre. Un seul abonnement « courant » par utilisateur (index partiel unique). FK `user_id` et `plan_id` en **RESTRICT** (préserve l'historique).

| Colonne | Type | Clé / note |
|---|---|---|
| `id` | bigint | PK |
| `user_id` | bigint | FK → `users` (RESTRICT) |
| `plan_id` | bigint | FK → `plans` (RESTRICT) |
| `seats` | int | Nombre de sièges (équipe) |
| `billing_period` | string | `monthly` / `yearly` |
| `status` | string | `trialing`, `active`, `canceled`, `expired`, `past_due` |
| `started_at`, `trial_ends_at`, `current_period_end`, `canceled_at` | datetime? | Cycle de vie |
| `trial_reminder_sent_at`, `renewal_reminder_sent_at` | datetime? | Rappels (une fois/période) |
| `created_at`, `updated_at`, `deleted_at` | datetime? | Horodatage + soft delete |

## `payments`
Paiements Chargily. FK `user_id` en **RESTRICT**, `subscription_id` en **SET NULL**. Conservés (anonymisés) même après suppression du compte.

| Colonne | Type | Clé / note |
|---|---|---|
| `id` | bigint | PK |
| `user_id` | bigint | FK → `users` (RESTRICT) |
| `subscription_id` | bigint? | FK → `subscriptions` (SET NULL) |
| `amount` | int | Montant |
| `currency` | string | `DZD` |
| `status` | string | `pending`, `succeeded`, `failed`, `refunded` |
| `provider` | string | `chargily` |
| `provider_reference` | string? | ID du checkout Chargily |
| `provider_response`, `metadata` | json? | Données du fournisseur |
| `failure_reason`, `error_code` | string? | Échec / incohérence (`amount_mismatch`…) |
| `paid_at`, `refunded_at`, `refund_reason` | datetime?/string? | Paiement / remboursement |
| `created_at`, `updated_at`, `deleted_at` | datetime? | Horodatage + soft delete |

## `vpn_credentials`
Identifiants WireGuard. Clé privée **chiffrée** (cast `encrypted`, clé `APP_KEY`) et masquée de la sérialisation. FK `user_id` en RESTRICT, `subscription_id` en SET NULL.

| Colonne | Type | Clé / note |
|---|---|---|
| `id` | bigint | PK |
| `user_id` | bigint | FK → `users` (RESTRICT) |
| `subscription_id` | bigint? | FK → `subscriptions` (SET NULL) |
| `public_key` | string | Clé publique Curve25519 |
| `private_key` | text | **Chiffrée** |
| `address` | string | Adresse tunnel (pool `10.66.0.0/16`, jamais réutilisée) |
| `status` | string | `active` / `revoked` |
| `revoked_at` | datetime? | Date de révocation |

## `teams`
Équipe (offre vente assistée). FK `owner_id` en CASCADE, `subscription_id` en SET NULL.

| Colonne | Type | Clé / note |
|---|---|---|
| `id` | bigint | PK |
| `name` | string | Nom de l'équipe |
| `owner_id` | bigint | FK → `users` (CASCADE) |
| `subscription_id` | bigint? | FK → `subscriptions` (SET NULL) |

## `team_user` (pivot)
Membres d'une équipe (relation N-N `teams` ↔ `users`). Les deux FK en CASCADE.

| Colonne | Type | Clé / note |
|---|---|---|
| `team_id` | bigint | FK → `teams` (CASCADE) |
| `user_id` | bigint | FK → `users` (CASCADE) |
| `role` | string | Rôle dans l'équipe (`member`…) |
| `created_at` | datetime | Date d'ajout |

## `testimonials`
Témoignages clients. FK `user_id` en CASCADE (auteur), `moderated_by` en SET NULL (administrateur).

| Colonne | Type | Clé / note |
|---|---|---|
| `id` | bigint | PK |
| `user_id` | bigint | FK → `users` (CASCADE) |
| `content` | text | Texte du témoignage |
| `status` | string | `pending`, `approved`, `rejected` (défaut `pending`) |
| `moderated_by` | bigint? | FK → `users` (SET NULL) |
| `moderated_at` | datetime? | Date de modération |

## `contact_requests`
Demandes commerciales (offre Team). FK `user_id` et `handled_by` en SET NULL.

| Colonne | Type | Clé / note |
|---|---|---|
| `id` | bigint | PK |
| `user_id` | bigint? | FK → `users` (SET NULL), si connecté |
| `name`, `email`, `company` | string | Coordonnées |
| `seats` | int? | Nombre de sièges souhaités |
| `message` | text | Message |
| `status` | string | `new` (défaut) / `handled` |
| `handled_by` | bigint? | FK → `users` (SET NULL) |
| `handled_at` | datetime? | Date de traitement |
| `ip_address` | string? | IP d'origine |

## `audit_logs`
Journal d'audit (immuable : seulement `created_at`). FK `actor_id` en SET NULL : l'historique survit à la suppression de l'acteur.

| Colonne | Type | Clé / note |
|---|---|---|
| `id` | bigint | PK |
| `actor_id` | bigint? | FK → `users` (SET NULL) |
| `action` | string | Ex. `payment.succeeded`, `account.deleted`, `auth.login_failed` |
| `subject_type`, `subject_id` | string?/bigint? | Objet concerné (polymorphe léger) |
| `metadata` | json? | Données non sensibles |
| `ip_address` | string? | IP de l'acteur |
| `created_at` | datetime | Horodatage (pas d'`updated_at`) |
