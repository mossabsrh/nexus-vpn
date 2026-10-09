# 18 — Glossaire et abréviations

> Pour la « liste des abréviations » du mémoire. Termes utilisés dans `docs/pfe/`.

## Abréviations

| Abréviation | Signification | Explication |
|---|---|---|
| API | Application Programming Interface | Interface programmatique ; ici l'API REST JSON de Laravel |
| CIB | Carte Interbancaire | Carte bancaire algérienne acceptée par Chargily |
| CLI | Command-Line Interface | Interface en ligne de commande (commandes Artisan) |
| CORS | Cross-Origin Resource Sharing | Mécanisme du navigateur autorisant (ou non) les requêtes entre origines différentes |
| CRUD | Create, Read, Update, Delete | Opérations de base sur une ressource |
| CSP | Content Security Policy | En-tête HTTP limitant les sources de scripts, styles, connexions… |
| CSRF | Cross-Site Request Forgery | Attaque forçant un navigateur authentifié à envoyer une requête non voulue |
| CSS | Cascading Style Sheets | Langage de mise en forme |
| DNS | Domain Name System | Résolution des noms de domaine |
| DZD | Dinar algérien | Devise des offres et paiements |
| ERD / MCD / MLD | Entity-Relationship Diagram / Modèle Conceptuel / Logique de Données | Modélisation de la base (Merise pour MCD/MLD) |
| FK / PK | Foreign Key / Primary Key | Clé étrangère / clé primaire |
| FR / NFR | Functional / Non-Functional Requirement | Exigence fonctionnelle / non fonctionnelle (`02-requirements.md`) |
| HMAC | Hash-based Message Authentication Code | Code d'authentification d'un message par hachage avec clé secrète ; ici HMAC-SHA256 pour le webhook Chargily |
| HSTS | HTTP Strict Transport Security | En-tête imposant HTTPS au navigateur |
| HTTP(S) | HyperText Transfer Protocol (Secure) | Protocole web (chiffré par TLS) |
| IP | Internet Protocol | Adresse réseau ; aussi l'adresse tunnel attribuée au client VPN |
| JSON | JavaScript Object Notation | Format d'échange de données de l'API |
| JWT | JSON Web Token | Jeton signé, alternative non retenue aux sessions |
| MCP | Model Context Protocol | Protocole utilisé par Laravel Boost pour les outils des assistants de développement |
| MVC | Modèle-Vue-Contrôleur | Architecture de Laravel |
| ORM | Object-Relational Mapping | Correspondance objets ↔ tables ; Eloquent dans Laravel |
| PFE | Projet de Fin d'Études | — |
| REST | Representational State Transfer | Style d'API fondé sur des ressources et les méthodes HTTP |
| SGBD | Système de Gestion de Bases de Données | SQLite, PostgreSQL |
| SMTP | Simple Mail Transfer Protocol | Protocole d'envoi des e-mails |
| SPA | Single-Page Application | Application web dont la navigation se fait côté client |
| SQL | Structured Query Language | Langage des bases relationnelles |
| SSR | Server-Side Rendering | Rendu des pages côté serveur |
| TLS | Transport Layer Security | Chiffrement des connexions HTTPS |
| UI / UX | User Interface / User Experience | Interface / expérience utilisateur |
| URL | Uniform Resource Locator | Adresse web |
| VPN | Virtual Private Network | Réseau privé virtuel chiffrant le trafic de l'utilisateur |
| XSS | Cross-Site Scripting | Injection de script dans une page web |

## Termes techniques

| Terme | Définition dans le contexte du projet |
|---|---|
| App Router | Système de routage de Next.js fondé sur le dossier `app/` |
| Artisan | Outil en ligne de commande de Laravel (`php artisan …`) |
| Blueprint (Render) | Fichier `render.yaml` décrivant services, base et variables |
| Chargily Pay | Passerelle de paiement algérienne (EDAHABIA, CIB) utilisée en version 2 de son API |
| Checkout | Session de paiement créée chez Chargily, identifiée par un id et une URL de paiement |
| Cookie `HttpOnly` | Cookie inaccessible à JavaScript |
| EDAHABIA | Carte de paiement d'Algérie Poste |
| Eloquent | ORM de Laravel |
| Fail-closed / fail-open | Comportement en cas de panne : refuser l'accès / le laisser passer |
| Idempotence | Propriété d'une opération dont la répétition n'a pas d'effet supplémentaire (application unique d'un paiement) |
| Index unique partiel | Index d'unicité limité aux lignes satisfaisant une condition (`status IN ('active','trialing')`) |
| Inertia.js | Bibliothèque reliant Laravel à un frontend React sans API ; héritée, non utilisée |
| Laravel Boost | Paquet de développement fournissant règles et outils MCP aux assistants IA |
| Middleware | Couche traversée par une requête avant le contrôleur (authentification, CSRF, limitation…) |
| Pair (peer) WireGuard | Client autorisé sur la passerelle, identifié par sa clé publique et son adresse tunnel |
| Proxy (Next.js 16) | Fichier `proxy.ts` (ex-middleware) exécuté avant le rendu des pages |
| Rate limiting | Limitation du nombre de requêtes par période |
| Sanctum (Laravel) | Paquet d'authentification par **jetons personnels** (Bearer) ; utilisé pour l'API mobile `/api/mobile/*` (sans session ni CSRF). App mobile reportée, back-end conservé |
| Jeton Bearer (Bearer token) | Jeton envoyé dans l'en-tête `Authorization: Bearer <token>` pour authentifier un client sans cookie de session (API mobile) |
| Brevo | Fournisseur d'envoi d'e-mails par **API HTTP** (anciennement Sendinblue) ; retenu pour la production car Render Free bloque le SMTP sortant (D-17) |
| Réécriture (rewrite) | Redirection interne transparente de `/backend-api/*` vers l'API Laravel |
| Seeder | Script d'insertion de données initiales |
| shadcn/ui | Collection de composants React copiés dans le projet, basés sur Radix UI et Tailwind |
| Soft delete | Suppression logique (colonne `deleted_at`) conservant la ligne |
| Tailwind CSS | Framework CSS utilitaire |
| Turbopack | Bundler de Next.js utilisé pour le build |
| v0 | Outil de Vercel générant des interfaces Next.js, utilisé pour créer le frontend initial |
| Webhook | Appel HTTP envoyé par un service externe (Chargily) pour notifier un événement |
| WireGuard | Protocole VPN moderne fondé sur des clés Curve25519 |
