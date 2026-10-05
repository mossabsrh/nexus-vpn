# 12 — Tests

## 1. Stratégie de test du backend

* Framework : **PHPUnit 11** (classes PHPUnit ; le fichier `tests/Pest.php` est un reste du starter kit, Pest n'est pas installé).
* Type : principalement des **tests de fonctionnalités** (`tests/Feature`) qui appellent les routes HTTP réelles et vérifient la réponse JSON, l'état de la base et les effets de bord.
* Base de données de test : SQLite en mémoire (`phpunit.xml` : `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, `APP_ENV=testing`).
* Appels externes (Chargily) simulés avec `Http::fake()` (`PaymentFlowTest`, `ChargilyPaymentTest`) ; e-mails interceptés avec `Notification::fake()`.
* Commande : `php artisan test --compact` (depuis `nexus-vpn/`).

## 2. Fichiers de test existants (backend)

### Tests de l'API réelle

| Fichier | Nb | Ce qui est couvert |
|---|---|---|
| `tests/Feature/Api/AuthenticationTest.php` | 4 | Inscription + authentification, mise à jour e-mail/mot de passe, connexion et lecture de `me`, déconnexion avec invalidation de session |
| `tests/Feature/Api/AccountSecurityTest.php` | 10 | Verrouillage après 5 échecs de connexion, réinitialisation du compteur, absence de fuite d'erreurs internes à l'inscription, refus des plans vente assistée, e-mails de vérification et bienvenue, changement d'e-mail avec revérification, lien de vérification signé, anti-énumération du mot de passe oublié, réinitialisation par jeton valide uniquement, en-têtes de sécurité et CORS sans joker sur `pricing` |
| `tests/Feature/Api/PaymentFlowTest.php` | 17 | Checkout (paiement enregistré avant l'appel, retour vers la page résultat), e-mail vérifié requis, plans non achetables, erreurs fournisseur masquées, idempotence webhook + vérification, conversion d'essai, renouvellement, réabonnement après annulation (jours conservés / départ immédiat), changement de plan, refus d'un second abonnement courant par la base, webhook pour checkout inconnu (404), échec enregistré, montant différent refusé, isolation entre utilisateurs, historique et reçus |
| `tests/Feature/Api/SubscriptionLifecycleTest.php` | 11 | Annulation d'essai (fin immédiate + révocation VPN), annulation payée (accès jusqu'à fin de période), annulation sans abonnement, ancienne route d'annulation supprimée, essai unique et interdit aux plans vente assistée, commande d'expiration, fin d'accès sans attendre le planificateur, rappels envoyés une fois, configuration WireGuard valide, prérequis (e-mail vérifié, serveur configuré), adresses tunnel distinctes |
| `tests/Feature/Api/AdminTest.php` | 2 | Refus de l'API admin aux utilisateurs normaux ; gestion des utilisateurs et des plans par un admin |
| `tests/Feature/Api/AdminBillingTest.php` | 9 | Accès admin uniquement, statistiques calculées sur des données réelles, liste utilisateurs paginée/filtrée, remboursement (fin d'abonnement + révocation VPN), filtres paiements/abonnements, désactivation des plans ayant un historique et mise à jour immédiate des prix, modération des témoignages, journal d'audit, abonnement Team avec limite de sièges |
| `tests/Feature/Api/ContactRequestTest.php` | 2 | Demande commerciale traitée par un admin ; validation et limitation de débit |
| `tests/Feature/ChargilyPaymentTest.php` | 13 | Création de checkout Chargily, seeders idempotents (ne réécrivent ni ne restaurent les plans modifiés/supprimés par un admin), essai à l'inscription, redirection des anciennes URL de retour, annulation, nouvel abonnement au lieu de réactiver un ancien, essai de 7 jours unique, contenu de `userPayload`, rejet d'une signature de webhook invalide, montant client ignoré, isolation des checkouts |

### Tests hérités du starter kit (pages Inertia non utilisées)

| Fichier | Nb | Ce qui est couvert |
|---|---|---|
| `tests/Feature/Auth/AuthenticationTest.php` | 4 | Écran de connexion Inertia, connexion/déconnexion web |
| `tests/Feature/Auth/EmailVerificationTest.php` | 3 | Écran et lien de vérification web |
| `tests/Feature/Auth/PasswordConfirmationTest.php` | 3 | Confirmation du mot de passe |
| `tests/Feature/Auth/PasswordResetTest.php` | 4 | Réinitialisation via les pages web |
| `tests/Feature/Auth/RegistrationTest.php` | 2 | Inscription via la page web |
| `tests/Feature/DashboardTest.php` | 2 | Accès à la page `dashboard` |
| `tests/Feature/Settings/PasswordUpdateTest.php` | 2 | Changement de mot de passe (page web) |
| `tests/Feature/Settings/ProfileUpdateTest.php` | 5 | Profil et suppression de compte (pages web) |
| `tests/Unit/ExampleTest.php` | 1 | Test d'exemple (`true is true`) |

Total : 94 tests (68 sur l'API réelle, 26 hérités).

### Zones non couvertes identifiées

* Équipe (`/api/team*`) : couverte uniquement par `AdminBillingTest::test_team_subscription_flow_with_seat_limits` (ajout/retrait de membres, limite de sièges, visibilité de la liste) ; `PATCH /api/team` (renommage) n'a pas de test identifié.
* Tests contre PostgreSQL (base de production) : aucun ; la suite tourne uniquement sur SQLite.
* Commande `vpn:peers` : vérifiée seulement dans `SubscriptionLifecycleTest` (présence de la clé publique dans la sortie), pas de test dédié.

## 3. Exécutions enregistrées (backend)

### 2026-10-05 — Référence backend (audit de base)

| Élément | Valeur |
|---|---|
| Commande | `php artisan test --compact` (dans `nexus-vpn/`) |
| Environnement | Windows 10, PHP 8.2.12, Laravel 12.60.2, PHPUnit 11.5.55, SQLite en mémoire |
| Commit testé | `e246b4d` (aucune modification du code applicatif) |
| Résultat | **94 réussis, 0 échec** (370 assertions) |
| Durée | 12.98 s (rapportée par PHPUnit) |
| Code de sortie | 0 |

Sortie (résumé) :

```
Tests:    94 passed (370 assertions)
Duration: 12.98s
```

## 4. Frontend (application Next.js)

### 4.1 Tests existants

Aucun test automatisé du frontend : pas de fichier `*.test.*` / `*.spec.*`, pas d'outil de test (Jest, Vitest, Playwright, Cypress) dans `package.json`, pas de script `test`. La qualité du frontend est vérifiée uniquement par l'analyse statique (ESLint), le contrôle de types (TypeScript) et le build de production.

### 4.2 Vérifications du 2026-10-05 — référence frontend (audit de base)

Environnement : Windows 10, Node.js v22.15.0, pnpm 10.34.6 (`npx pnpm@10`), dépendances déjà installées (`node_modules/` présent, aucune installation effectuée). Commit vérifié : `05f64b7` (aucune modification du code). Commandes exécutées depuis la racine du dépôt, l'une après l'autre.

| Vérification | Commande | Résultat | Durée (horloge) |
|---|---|---|---|
| Analyse statique | `pnpm lint` (`eslint .`) | **Réussi** : code de sortie 0, aucune erreur ni aucun avertissement affiché | ~48 s |
| Contrôle de types | `pnpm exec tsc --noEmit` | **Réussi** : code de sortie 0, aucune erreur | ~21 s |
| Build de production | `pnpm build` (`next build`, Next.js 16.2.6, Turbopack) | **Réussi** : code de sortie 0 ; « Compiled successfully in 20.8s », TypeScript terminé en 26.3s, 8/8 pages statiques générées | ~64 s |

Routes produites par le build (toutes pré-rendues en statique « ○ »), plus le Proxy :

```
Route (app)
┌ ○ /
├ ○ /_not-found
├ ○ /admin
├ ○ /dashboard
├ ○ /payment/result
├ ○ /reset-password
└ ○ /verify-email

ƒ Proxy (Middleware)
```
