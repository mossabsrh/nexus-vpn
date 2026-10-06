# Assets du mémoire — NexusVPN

Ensemble des éléments visuels et techniques pour le mémoire de PFE. Tout est en français et organisé par type.

```
memoire-assets/
├── screenshots/   Captures d'écran (générées par capture.mjs — voir screenshots/README.md)
├── database/      Modèle de données (ERD + description des tables)
├── diagrams/      Diagrammes Mermaid (architecture, cas d'usage, classes, séquences, déploiement)
├── code/          Extraits de code commentés (backend + frontend)
└── tests/         Résultats des tests + synthèse
```

## Rendu des diagrammes (Mermaid → PNG/SVG)

Chaque diagramme est fourni en **source Mermaid** (`.mmd`) **et exporté en PNG + SVG** (générés avec `@mermaid-js/mermaid-cli`). Pour régénérer après modification :

```bash
npx pnpm@10 dlx @mermaid-js/mermaid-cli -b white -i diagrams/01-architecture.mmd -o diagrams/01-architecture.png
npx pnpm@10 dlx @mermaid-js/mermaid-cli -b white -i diagrams/01-architecture.mmd -o diagrams/01-architecture.svg
```

---

## Diagrammes — chapitre « Conception »

| Figure | Fichier | Légende |
|---|---|---|
| Figure 1 | `diagrams/01-architecture.mmd` | Architecture globale : frontend Next.js, API Laravel, base de données, Chargily, Gmail SMTP, passerelle WireGuard. |
| Figure 2 | `diagrams/02-use-case.mmd` | Diagramme de cas d'utilisation (visiteur, utilisateur, administrateur). |
| Figure 3 | `diagrams/03-class-diagram.mmd` | Diagramme de classes des principaux modèles métier. |
| Figure 4 | `diagrams/04-seq-registration-verification.mmd` | Séquence : inscription + vérification de l'e-mail. |
| Figure 5 | `diagrams/05-seq-login.mmd` | Séquence : connexion (CSRF, limiteur, audit). |
| Figure 6 | `diagrams/06-seq-payment-chargily.mmd` | Séquence : paiement Chargily (webhook + vérification). |
| Figure 7 | `diagrams/07-seq-vpn-config.mmd` | Séquence : génération de la configuration WireGuard. |
| Figure 8 | `diagrams/08-seq-admin-delete-user.mmd` | Séquence : suppression d'un utilisateur par l'administrateur. |
| Figure 9 | `diagrams/09-deployment.mmd` | Diagramme de déploiement / structure du dépôt. |

## Base de données — chapitre « Conception »

| Figure | Fichier | Légende |
|---|---|---|
| Figure 10 | `database/erd.mmd` | Modèle entité-association (toutes les tables, colonnes, clés et relations). |
| — | `database/tables.md` | Dictionnaire des tables : rôle et colonnes de chaque table. |

## Captures d'écran — chapitres « Présentation de l'application » / « Implémentation »

**68 fichiers** générés par `screenshots/capture.mjs` : 17 vues × { desktop 1440 px, mobile 390 px } × { clair, sombre }. Chaque préfixe ci-dessous existe en 4 variantes `-desktop-light`, `-desktop-dark`, `-mobile-light`, `-mobile-dark`. Détail et régénération : `screenshots/README.md`.

| Figure | Fichier (préfixe) | Légende |
|---|---|---|
| Figure 11 | `screenshots/01-home-*` | Page d'accueil (offres, témoignages, carte, contact). |
| Figure 12 | `screenshots/02-auth-dialog-*` | Fenêtre de connexion / inscription. |
| Figure 13 | `screenshots/08-reset-password-*` | Réinitialisation du mot de passe. |
| Figure 14 | `screenshots/09-verify-email-*` | Vérification de l'e-mail. |
| Figure 15 | `screenshots/10-payment-result-*` | Résultat de paiement (retour Chargily). |
| Figure 16 | `screenshots/11-dashboard-*` | Tableau de bord utilisateur (abonnement, VPN, historique de facturation). |
| Figure 17 | `screenshots/12-admin-*` | Console d'administration (entrée). |
| Figure 18 | `screenshots/13-admin-overview-*` | Admin — vue d'ensemble. |
| Figure 19 | `screenshots/14-admin-accounts-*` | Admin — gestion des comptes (dont suppression). |
| Figure 20 | `screenshots/15-admin-offers-*` | Admin — gestion des offres. |
| Figure 21 | `screenshots/16-admin-subscriptions-*` | Admin — abonnements. |
| Figure 22 | `screenshots/17-admin-payments-*` | Admin — paiements et remboursements. |
| Figure 23 | `screenshots/18-admin-analytics-*` | Admin — statistiques / analytique. |
| Figure 24 | `screenshots/19-admin-comments-*` | Admin — modération des témoignages. |
| Figure 25 | `screenshots/20-admin-audit-log-*` | Admin — journal d'audit. |
| Figure 26 | `screenshots/22-state-delete-confirm-*` | État : confirmation de suppression d'un utilisateur. |
| Figure 27 | `screenshots/23-state-role-change-*` | État : confirmation de changement de rôle. |

## Extraits de code — chapitre « Implémentation »

| Fichier | Contenu |
|---|---|
| `code/backend.md` | Backend Laravel : webhook HMAC, activation idempotente, `closeAccount`, VPN, middleware admin, suppression admin, routes. |
| `code/frontend.md` | Frontend Next.js : client API + CSRF, authentification, zone de danger, suppression admin, `proxy.ts`. |

## Tests — chapitre « Tests et validation »

| Fichier | Contenu |
|---|---|
| `tests/summary.md` | Synthèse : 121 tests, tableau par fichier et couverture. |
| `tests/test-output.txt` | Sortie complète de `php artisan test` (121 réussis, 470 assertions). |

---

## Données de démonstration

Les captures s'appuient sur `database/seeders/DemoDataSeeder.php` (noms algériens ; abonnements actifs/essai/expiré/annulé ; paiements réussi/échoué/remboursé ; une équipe ; témoignages ; demandes commerciales ; journal d'audit). Le compte administrateur réel n'est jamais modifié.

```bash
php artisan db:seed --class=DemoDataSeeder   # idempotent, réexécutable
```
