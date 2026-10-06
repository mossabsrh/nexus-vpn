# Tests — synthèse

- **Commande** : `php artisan test` (dans `nexus-vpn/`)
- **Base de données** : SQLite en mémoire (`:memory:`, isolée de la base de dev)
- **Résultat** : **121 tests réussis, 0 échec** (470 assertions), ~13 s
- **Sortie complète** : [`test-output.txt`](test-output.txt)

> Cadre : PHPUnit 11 (pas de Pest). Principalement des **tests de fonctionnalités** (`tests/Feature`) qui appellent les routes HTTP réelles et vérifient la réponse JSON, l'état de la base et les effets de bord.

## Tests de l'API réelle et des commandes

| Fichier | Nb | Couverture |
|---|---|---|
| `Api/AuthenticationTest.php` | 4 | Inscription + authentification, connexion/`me`, déconnexion |
| `Api/AccountSecurityTest.php` | 12 | Verrouillage après 5 échecs, e-mails de vérification/bienvenue, lien signé, **renvoi du lien**, anti-énumération, réinitialisation par jeton, en-têtes de sécurité/CORS |
| `Api/AccountDeletionTest.php` | 5 | `DELETE /api/account` : anonymisation + conservation de l'historique, impossibilité de se reconnecter, mot de passe requis (422), blocage admin/propriétaire d'équipe (409) |
| `Api/AdminUserDeletionTest.php` | 6 | `DELETE /api/admin/users/{user}` : anonymisation, suppression d'un autre admin, refus (soi-même, dernier admin, propriétaire d'équipe) (422), refus non-admin (403) |
| `Api/LoginAfterDeletionTest.php` | 3 | Régression : connexion active (e-mail insensible à la casse), compte fermé non connectable (401), e-mail libéré réutilisable |
| `Api/PaymentFlowTest.php` | 23 | Checkout, e-mail vérifié requis, idempotence webhook + vérification, contrôle montant **et** devise, alerte « payé après échec », envoi conditionnel du webhook, cycle d'abonnement |
| `Api/SubscriptionLifecycleTest.php` | 11 | Annulation d'essai/payée, essai unique, expiration, rappels, configuration WireGuard, adresses distinctes |
| `Api/AdminTest.php` | 2 | Refus de l'API admin aux non-admins ; gestion utilisateurs/offres |
| `Api/AdminBillingTest.php` | 9 | Accès admin, statistiques réelles, remboursement, filtres, désactivation de plan, modération, audit, équipe (sièges) |
| `Api/ContactRequestTest.php` | 2 | Demande commerciale traitée ; validation et limitation de débit |
| `ChargilyPaymentTest.php` | 13 | Checkout Chargily, seeders idempotents, essai, anciennes URL de retour, annulation, réabonnement, signature webhook invalide, montant client ignoré |
| `ReconcilePaymentsTest.php` | 5 | Commande `payments:reconcile` : activation périmée, maintien en attente, expiration (`/checkouts/{id}/expire`), période de grâce, aucune action sans clé |

## Tests hérités du starter kit (pages Inertia non utilisées)

| Fichier | Nb |
|---|---|
| `Auth/*`, `Settings/*`, `DashboardTest`, `Unit/ExampleTest` | 26 |

**Total : 121 tests** (95 sur l'API réelle et les commandes, 26 hérités).
