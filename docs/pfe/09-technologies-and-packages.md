# 09 — Technologies et paquets

> Versions installées relevées dans `nexus-vpn/composer.lock` le 2026-10-05 ; contraintes déclarées dans `nexus-vpn/composer.json`.

## Backend (`nexus-vpn/`)

### Environnement d'exécution

| Technologie | Version | Rôle |
|---|---|---|
| PHP | contrainte `^8.2` ; 8.2.12 en local (`php -v`) ; image Docker `php:8.3-apache-bookworm` en production | Langage du backend |
| Laravel Framework | v12.60.2 | Framework de l'API REST |
| Apache | (image Docker officielle PHP) | Serveur HTTP en production, `mod_rewrite` et `mod_headers` activés |
| SQLite / PostgreSQL | — | SGBD local et tests / production (voir `06-database.md`) |
| Extensions PHP | `bcmath`, `mbstring`, `pdo_pgsql`, `zip` (installées dans le `Dockerfile`) ; `sodium` (utilisée pour les clés WireGuard) | ⚠️ À VÉRIFIER : `sodium` n'est pas installée explicitement dans le `Dockerfile` ; elle est normalement incluse dans l'image PHP officielle |

### Dépendances de production (`require`)

| Paquet | Version | Où / pourquoi | Statut |
|---|---|---|---|
| `laravel/framework` | v12.60.2 | Routage, Eloquent ORM, validation, sessions, CSRF, rate limiting, notifications e-mail, planificateur, client HTTP (appels Chargily), chiffrement (`encrypted` cast de la clé privée VPN) | utilisé |
| `laravel/tinker` | v2.11.1 | Console REPL pour le débogage | outil |
| `inertiajs/inertia-laravel` | v2.0.24 | Adaptateur Inertia du starter kit (`HandleInertiaRequests`, pages `/`, `dashboard`, `settings/*`) | **hérité, non utilisé par le frontend réel** |
| `tightenco/ziggy` | v2.6.2 | Exposition des routes nommées au JavaScript (`@routes` dans `app.blade.php`) | **hérité, non utilisé par le frontend réel** |

Aucun SDK Chargily n'est utilisé : l'intégration passe par le client HTTP de Laravel (`Http::withToken()`) vers l'API REST Chargily Pay v2. Aucune bibliothèque WireGuard externe : les clés sont générées avec l'extension PHP `sodium`.

### Dépendances de développement (`require-dev`)

| Paquet | Version | Rôle |
|---|---|---|
| `phpunit/phpunit` | 11.5.55 | Framework de tests (tests de fonctionnalités dans `tests/Feature`) |
| `fakerphp/faker` | v1.24.1 | Données factices pour les factories |
| `mockery/mockery` | 1.6.12 | Doubles de test |
| `nunomaduro/collision` | v8.9.4 | Affichage des erreurs en console / lanceur `php artisan test` |
| `laravel/pint` | v1.29.1 | Formatage du code PHP (style Laravel) |
| `laravel/boost` | v2.4.8 | Serveur MCP et règles pour les agents IA de développement (`boost.json`, `CLAUDE.md`) |
| `laravel/mcp` | v0.7.0 | Dépendance de Laravel Boost (protocole MCP) |
| `laravel/roster` | v0.5.1 | Dépendance de Laravel Boost (détection des paquets installés) |
| `laravel/pail` | v1.2.6 | Suivi des logs en temps réel |
| `laravel/sail` | v1.60.0 | Environnement Docker de développement Laravel. ⚠️ À VÉRIFIER : semble inutilisé (`boost.json` : `"sail": false`) |

### Services externes

| Service | Usage | Configuration |
|---|---|---|
| Chargily Pay v2 | Paiement en ligne (DZD) : checkouts, webhooks signés HMAC-SHA256 | `CHARGILY_MODE`, `CHARGILY_SECRET_KEY` |
| WireGuard | Protocole VPN ; configuration client générée par l'API, pairs exportés via `vpn:peers` | `VPN_*` |
| Serveur SMTP / mailer | E-mails de vérification, réinitialisation, bienvenue, reçu, rappels | `MAIL_*` ⚠️ À VÉRIFIER : fournisseur utilisé en production |
| Render (hébergement) | Déploiement du conteneur Docker du backend et base PostgreSQL | `render.yaml` (racine du dépôt) |

### Paquets JavaScript dans `nexus-vpn/package.json`

Les dépendances npm de `nexus-vpn/` (`@inertiajs/react`, React 19, Radix UI, Tailwind CSS 4, Vite, ESLint, Prettier…) appartiennent au **starter kit hérité** (pages Inertia sous `resources/js/`) et ne sont pas utilisées par l'application réelle.

## Frontend (application Next.js à la racine)

À compléter (étape 2).
