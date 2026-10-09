# 06 — Base de données

> Audit de référence du 2026-10-05. Schéma relevé sur la base locale SQLite avec `php artisan db:show` et `php artisan db:table <table>` (l'outil Boost `database-schema` n'était pas disponible dans la session ; ces commandes Artisan lisent le même schéma). Migrations listées avec `php artisan migrate:status` (**18 migrations** depuis l'ajout de Sanctum le 2026-10-07, toutes exécutées).

## 1. SGBD

| Environnement | SGBD | Source |
|---|---|---|
| Développement local | SQLite (`database/database.sqlite`) | `php artisan config:show database.default` → `sqlite` |
| Tests | SQLite en mémoire (`:memory:`) | `phpunit.xml` |
| Production | PostgreSQL | `render.yaml` (`DB_CONNECTION=pgsql`), extension `pdo_pgsql` dans le `Dockerfile` |

Les types ci-dessous sont ceux rapportés par SQLite (`integer`, `varchar`, `text`, `datetime`, `tinyint(1)` pour les booléens). ⚠️ À VÉRIFIER : les types exacts sous PostgreSQL (ex. `timestamp`, `boolean`, `bigint`) n'ont pas été relevés sur la base de production.

## 2. Tables métier

### `users`
| Colonne | Type | Contraintes |
|---|---|---|
| id | integer | PK, auto-incrément |
| name, email, password | varchar | email **unique** ; password haché (`hashed` cast) |
| role | varchar | défaut `'user'` (`user` \| `admin`) |
| email_verified_at | datetime | nullable |
| remember_token | varchar | nullable |
| created_at, updated_at, deleted_at | datetime | nullable ; `deleted_at` = **soft delete** |

### `plans`
| Colonne | Type | Contraintes |
|---|---|---|
| id | integer | PK |
| slug | varchar | **unique** (`^[a-z0-9-]+$` côté validation) |
| name | varchar | |
| description | text | nullable |
| monthly_price, yearly_price, yearly_total | integer | nullable ; montants entiers en DZD. `monthly_price` null ou 0 ⇒ plan « vente assistée » |
| currency | varchar | défaut `'DZD'` |
| features | text | nullable, JSON (cast `array`) |
| popular | tinyint(1) | défaut 0 |
| cta | varchar | nullable |
| is_active | tinyint(1) | défaut 1 |
| created_at, updated_at, deleted_at | datetime | soft delete |

### `subscriptions`
| Colonne | Type | Contraintes |
|---|---|---|
| id | integer | PK |
| user_id | integer | FK → `users.id`, **ON DELETE RESTRICT** |
| plan_id | integer | FK → `plans.id`, **ON DELETE RESTRICT** |
| seats | integer | défaut 1 |
| billing_period | varchar | défaut `'monthly'` (`monthly` \| `yearly`) |
| status | varchar(20) | défaut `'active'` (`trialing`, `active`, `canceled`, `expired`) |
| started_at, trial_ends_at, current_period_end, canceled_at | datetime | nullable |
| trial_reminder_sent_at, renewal_reminder_sent_at | datetime | nullable (rappels envoyés une seule fois) |
| created_at, updated_at, deleted_at | datetime | soft delete |

Index : `subscriptions_one_current_per_user` — **index unique partiel** sur `user_id` `WHERE status IN ('active','trialing') AND deleted_at IS NULL` (créé uniquement sous SQLite et PostgreSQL) ; index composé `(status, current_period_end)` pour la commande d'expiration.

### `payments`
| Colonne | Type | Contraintes |
|---|---|---|
| id | integer | PK |
| user_id | integer | FK → `users.id`, **ON DELETE RESTRICT** |
| subscription_id | integer | nullable, FK → `subscriptions.id`, ON DELETE SET NULL |
| amount | integer | montant entier (DZD) |
| currency | varchar | défaut `'DZD'` |
| status | varchar(20) | défaut `'pending'` (`pending`, `succeeded`, `failed`, `refunded`) |
| provider | varchar | nullable (`chargily`) |
| provider_reference | varchar | nullable, **unique** (id de checkout Chargily) |
| provider_response | text | nullable, JSON (masqué de la sérialisation) |
| metadata | text | nullable, JSON : `payment_id` (`pay_<uuid>`), `user_id`, `plan_id`, `billing_period` |
| paid_at, refunded_at | datetime | nullable |
| failure_reason, refund_reason | varchar | nullable |
| error_code | varchar(100) | nullable |
| created_at, updated_at, deleted_at | datetime | soft delete |

Index : unique `provider_reference` ; composé `(status, paid_at)` pour les statistiques.

### `teams`
| Colonne | Type | Contraintes |
|---|---|---|
| id | integer | PK |
| name | varchar | |
| owner_id | integer | FK → `users.id`, ON DELETE CASCADE |
| subscription_id | integer | nullable, FK → `subscriptions.id`, ON DELETE SET NULL |
| created_at, updated_at | datetime | |

### `team_user` (table pivot)
| Colonne | Type | Contraintes |
|---|---|---|
| team_id | integer | FK → `teams.id`, ON DELETE CASCADE |
| user_id | integer | FK → `users.id`, ON DELETE CASCADE |
| role | varchar | défaut `'member'` (`owner` \| `member`) |
| created_at | datetime | défaut `CURRENT_TIMESTAMP` |

Clé primaire composée `(team_id, user_id)`.

### `vpn_credentials`
| Colonne | Type | Contraintes |
|---|---|---|
| id | integer | PK |
| user_id | integer | FK → `users.id`, ON DELETE RESTRICT |
| subscription_id | integer | nullable, FK → `subscriptions.id`, ON DELETE SET NULL |
| public_key | varchar(64) | **unique** |
| private_key | text | chiffrée par l'application (cast `encrypted`) |
| address | varchar(45) | **unique** (adresse IP du tunnel) |
| status | varchar | défaut `'active'` (`active` \| `revoked`) |
| revoked_at | datetime | nullable |
| created_at, updated_at | datetime | |

Index composé `(user_id, status)`.

### `testimonials`
| Colonne | Type | Contraintes |
|---|---|---|
| id | integer | PK |
| user_id | integer | FK → `users.id`, ON DELETE CASCADE |
| content | text | 10–1000 caractères (validation) |
| status | varchar | défaut `'pending'` (`pending`, `approved`, `rejected`) |
| moderated_by | integer | nullable, FK → `users.id`, ON DELETE SET NULL |
| moderated_at | datetime | nullable |
| created_at, updated_at | datetime | |

Index composé `(status, created_at)`.

### `contact_requests`
| Colonne | Type | Contraintes |
|---|---|---|
| id | integer | PK |
| user_id | integer | nullable, FK → `users.id`, ON DELETE SET NULL (visiteur anonyme possible) |
| name, email | varchar | |
| company | varchar | nullable |
| seats | integer | nullable |
| message | text | |
| status | varchar | défaut `'new'` (`new` \| `handled`) |
| handled_by | integer | nullable, FK → `users.id`, ON DELETE SET NULL |
| handled_at | datetime | nullable |
| ip_address | varchar | nullable |
| created_at, updated_at | datetime | |

Index composé `(status, created_at)`.

### `audit_logs`
| Colonne | Type | Contraintes |
|---|---|---|
| id | integer | PK |
| actor_id | integer | nullable, FK → `users.id`, ON DELETE SET NULL |
| action | varchar | indexé (ex. `payment.succeeded`) |
| subject_type, subject_id | varchar, integer | nullable ; index composé. `subject_type` contient le **nom court de la classe** (`Payment`, `User`…), pas une relation polymorphe Eloquent |
| metadata | text | nullable, JSON |
| ip_address | varchar | nullable |
| created_at | datetime | défaut `CURRENT_TIMESTAMP`, indexé (pas de `updated_at` : journal en ajout seul) |

## 3. Tables techniques (framework)

| Table | Rôle |
|---|---|
| `sessions` | Sessions utilisateur (driver `database`) ; index sur `user_id` et `last_activity`. Utilisée aussi pour déconnecter les autres sessions après changement de mot de passe. |
| `password_resets` | Jetons de réinitialisation (`email` indexé, `token`, `created_at`) — nom configuré dans `config/auth.php`. |
| `cache`, `cache_locks` | Cache (ex. `pricing.plans`) et verrous. |
| `jobs`, `job_batches`, `failed_jobs` | Files d'attente. ⚠️ À VÉRIFIER : aucun job ni notification en file (`ShouldQueue`) n'a été trouvé dans `app/` ; ces tables semblent inutilisées. |
| `migrations` | Suivi des migrations. |
| `personal_access_tokens` | Jetons **Sanctum** de l'API mobile (`tokenable`, `token` haché unique, `abilities`, `expires_at`). Ajoutée le 2026-10-07. **App mobile reportée** ; back-end conservé. 0 ligne en dev. |

## 4. Relations (modèles Eloquent)

| Modèle | Relations |
|---|---|
| `User` | hasMany `Subscription`, `Payment`, `VpnCredential`, `Testimonial` ; hasMany `Team` (`ownedTeams`, via `owner_id`) ; belongsToMany `Team` (`teams`, pivot `role`, `created_at`) |
| `Plan` | hasMany `Subscription` |
| `Subscription` | belongsTo `User`, `Plan` (withTrashed) ; hasMany `Payment`, `VpnCredential` ; hasOne `Team` |
| `Payment` | belongsTo `User`, `Subscription` (withTrashed) ; plan retrouvé via `metadata.plan_id` (pas de clé étrangère) |
| `Team` | belongsTo `User` (`owner`), `Subscription` ; belongsToMany `User` (`members`) |
| `VpnCredential` | belongsTo `User`, `Subscription` |
| `Testimonial` | belongsTo `User` |
| `ContactRequest` | belongsTo `User` |
| `AuditLog` | belongsTo `User` (`actor`) |

Diagramme : `assets/diagrams/erd.mmd`.

## 5. Décisions de conception observées

* **Préservation de l'historique de facturation** : `users`, `plans`, `subscriptions`, `payments` utilisent le soft delete ; les clés étrangères `subscriptions.user_id`, `payments.user_id` et `vpn_credentials.user_id` sont en `RESTRICT`, ce qui interdit la suppression physique d'un utilisateur ayant un historique. Un plan référencé par des abonnements ne peut être que désactivé (`is_active = false`).
* **Statuts en chaînes** (`varchar(20)`) plutôt qu'en `enum` SQL, pour éviter les réécritures de colonnes selon le SGBD (commentaire dans la migration `2026_10_05_000012_harden_billing_schema`).
* **Invariant « un seul abonnement courant par utilisateur »** garanti par la base (index unique partiel) et non seulement par le code ; les contrôleurs interceptent `UniqueConstraintViolationException`. ⚠️ À VÉRIFIER : sous MySQL/MariaDB cet index n'est pas créé (non supporté sous cette forme), l'invariant n'y serait donc garanti que par le code.
* **Montants entiers** en dinars (DZD), sans décimales.
* **Idempotence des paiements** : `provider_reference` unique et transition d'état conditionnelle.
* **Adresses VPN uniques et jamais réutilisées** (`address` unique).

## 6. Migrations

| Migration | Contenu |
|---|---|
| `0001_01_01_000001_create_cache_table` | `cache`, `cache_locks` |
| `0001_01_01_000002_create_jobs_table` | `jobs`, `job_batches`, `failed_jobs` |
| `2026_06_18_000001_create_users_table` | `users` |
| `2026_06_18_000002_create_password_resets_table` | `password_resets` |
| `2026_06_18_000003_create_plans_table` | `plans` |
| `2026_06_18_000004_create_subscriptions_table` | `subscriptions` |
| `2026_06_18_000005_create_payments_table` | `payments` |
| `2026_06_18_000006_create_teams_table` | `teams` |
| `2026_06_18_000007_create_team_user_table` | `team_user` |
| `2026_06_18_000008_create_sessions_table` | `sessions` |
| `2026_09_15_000009_create_testimonials_table` | `testimonials` |
| `2026_09_15_000010_add_role_to_users_table` | `users.role` |
| `2026_10_01_000011_add_chargily_fields_to_payments_table` | champs fournisseur de paiement |
| `2026_10_05_000012_harden_billing_schema` | soft deletes, `plans.is_active`, FK `RESTRICT`, nouvelles colonnes de cycle de vie, index, dédoublonnage puis index unique partiel |
| `2026_10_05_000013_create_product_tables` | `teams.subscription_id`, `vpn_credentials`, `audit_logs` |
| `2026_10_05_000014_add_moderation_to_testimonials_table` | modération des témoignages |
| `2026_10_05_000015_create_contact_requests_table` | `contact_requests` |
| `2026_10_07_195743_create_personal_access_tokens_table` | `personal_access_tokens` (Sanctum, API mobile — app reportée) |

## 7. Données initiales (seeders)

* `PlansTableSeeder` : plans `essential` (1119 DZD/mois), `standard` (1679 DZD/mois, populaire) et `team` (vente assistée, sans prix). Idempotent, ne réécrit pas les plans modifiés ou supprimés par un admin (vérifié par les tests `ChargilyPaymentTest`).
* `DatabaseSeeder` : appelle `PlansTableSeeder` ; crée un compte de démonstration **uniquement** en environnement `local` ou `testing`.
* En production, `docker/entrypoint.sh` exécute `php artisan migrate --force --seed` à chaque démarrage du conteneur.
* Factories : `UserFactory`, `PlanFactory`.
