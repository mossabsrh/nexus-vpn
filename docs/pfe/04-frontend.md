# 04 — Frontend (application Next.js à la racine du dépôt)

> Audit de référence réalisé le 2026-10-05 (étape 2/4 : frontend uniquement).
> Sources : code de `app/`, `components/`, `hooks/`, `lib/`, `proxy.ts`, `next.config.mjs`, `package.json`, `pnpm-lock.yaml`, résultat de `pnpm build`.
> Le contrat détaillé frontend ↔ backend (forme des requêtes/réponses) sera documenté à l'étape 3 dans `07-api.md`.

## 1. Rôle et architecture générale

Le frontend est l'**interface utilisateur réelle** de NexusVPN : site vitrine (présentation, offres, témoignages, contact), espace client (`/dashboard`) et console d'administration (`/admin`). Il est développé avec **Next.js 16 (App Router)**, **React 19** et **TypeScript 5.7**, stylé avec **Tailwind CSS 4** et des composants **shadcn/ui** (Radix UI).

Le frontend **ne possède pas de base de données ni de logique métier** : toutes les données proviennent de l'API Laravel (`nexus-vpn/`, voir `05-backend.md`). Les pages sont des **composants client** (`"use client"`) qui appellent l'API depuis le navigateur ; le rendu serveur se limite au squelette statique (toutes les routes sont pré-rendues en statique d'après `pnpm build`).

Taille du code (lignes, fichiers suivis par git) : environ **5 570 lignes** de code applicatif (`app/`, `components/` hors `ui/`, `hooks/`, `lib/`, `proxy.ts`) et **6 440 lignes** de primitives UI générées (`components/ui/`, 57 fichiers).

Organisation :

| Dossier / fichier | Contenu |
|---|---|
| `app/` | Routes App Router (pages), layout racine, `globals.css` |
| `components/` | Composants métier (sections de la page d'accueil, dialogues, fournisseur d'authentification) |
| `components/admin/` | Sections de la console d'administration |
| `components/ui/` | Primitives shadcn/ui (style « new-york ») générées |
| `hooks/` | `use-pricing` (plans), `use-mobile`, `use-toast` |
| `lib/` | `api.ts` (client HTTP), `pricing.ts` (type + formatage des prix), `utils.ts` (`cn()`) |
| `proxy.ts` | Contrôle d'accès côté serveur (Next.js 16 « Proxy », ex-middleware) |
| `next.config.mjs` | En-têtes de sécurité (CSP…) et réécriture `/backend-api` |

## 2. Routes (App Router)

Liste confirmée par la sortie de `pnpm build` (toutes « ○ Static ») :

| Route | Fichier | Rôle | Accès |
|---|---|---|---|
| `/` | `app/page.tsx` | Page d'accueil (vitrine) : Hero, statistiques, fonctionnalités, carte des serveurs, témoignages, tarifs, contact, pied de page. Les sections sont chargées par `next/dynamic` avec un squelette de chargement. Connexion/inscription par fenêtre modale. | Public |
| `/dashboard` | `app/dashboard/page.tsx` | Espace client : abonnement, accès VPN (téléchargement du fichier WireGuard), équipe, historiques paginés des paiements et abonnements | Utilisateur connecté (`proxy.ts`) |
| `/admin` | `app/admin/page.tsx` | Console d'administration à barre latérale : vue d'ensemble, comptes, offres, abonnements, paiements, statistiques, commentaires, journal d'audit | Admin uniquement (`proxy.ts` + contrôle client) |
| `/payment/result` | `app/payment/result/page.tsx` | Page de retour Chargily : vérifie le paiement `?payment={id}` (jusqu'à 6 tentatives espacées de 3 s si le statut est encore `pending`) | Public, mais affiche le résultat seulement si l'utilisateur est connecté |
| `/verify-email` | `app/verify-email/page.tsx` | Cible du lien de vérification d'e-mail (`id`, `hash`, `expires`, `signature`) | Public |
| `/reset-password` | `app/reset-password/page.tsx` | Cible du lien de réinitialisation (`token`, `email`) | Public |
| `/_not-found` | (généré) | Page 404 par défaut de Next.js | Public |

Il n'y a **pas de route API Next.js** (`app/api/`) ni de Server Actions : le frontend ne sert que des pages.

## 3. Layouts

Un seul layout : `app/layout.tsx` (racine), sans layouts imbriqués.

```
RootLayout (html lang="en", polices Geist / Geist Mono via next/font)
└── ThemeProvider (next-themes, attribut "class", thème par défaut "dark", thème système désactivé)
    └── AuthProvider (contexte d'authentification)
        └── page
```

Mise en page partagée par composants (et non par layouts) :

* `components/page-shell.tsx` : `PageShell` (en-tête avec logo et retour à l'accueil, utilisé par `/dashboard`, `/payment/result`, `/verify-email`, `/reset-password`), plus `Card`, `Notice`, `formatAmount`, `formatDate` ;
* `/admin` : `SidebarProvider` + `Sidebar` de shadcn/ui ; la section active est un état React (pas de sous-routes).

## 4. Composants

### 4.1 Primitives génériques (`components/ui/`)

57 composants shadcn/ui (Radix UI + `class-variance-authority` + `tailwind-merge`). Seuls quelques-uns sont **importés par le code applicatif** : `button`, `dialog`, `input`, `label`, `sidebar` (qui utilise lui-même `sheet`, `separator`, `skeleton`, `tooltip`, `input`, `button` et `hooks/use-mobile`) et `textarea`. Les autres (accordion, calendar, carousel, chart, command, drawer, form, input-otp, toast, sonner…) ont été générés mais ne sont pas utilisés.

### 4.2 Composants métier importants

| Composant | Rôle |
|---|---|
| `auth-provider.tsx` | Contexte React d'authentification : état `user`/`status`, actions `login`, `signup`, `logout`, `startTrial`, `cancelSubscription`, `updateEmail`, `updatePassword`, `resendVerification`, `refresh`. Définit les types `AuthUser` et `SubscriptionSummary`. |
| `auth-dialog.tsx` | Fenêtre modale à trois modes : connexion, inscription (avec **choix d'un plan self-serve** pour l'essai de 7 jours), mot de passe oublié. |
| `account-menu.tsx` | Menu du compte dans la barre de navigation : liens tableau de bord / admin (si `role === "admin"`), paramètres (changement d'e-mail et de mot de passe), annulation de l'abonnement avec confirmation, déconnexion. |
| `navbar.tsx` | Barre de navigation, ancres de section, menu mobile, bouton de thème, `AccountMenu` ou `AuthDialog`. |
| `pricing-section.tsx` | Affiche les plans de l'API (bascule mensuel/annuel) et choisit l'action selon l'état du visiteur : démarrer l'essai, s'inscrire, payer (`PaymentDialog`), ou « contacter » pour les plans vente assistée (`selfServe === false`). |
| `payment-dialog.tsx` | Confirme l'offre et la période, appelle `POST /checkout` puis redirige le navigateur vers l'URL Chargily ; gère l'erreur `email_unverified` (bouton de renvoi du lien). |
| `testimonials-section.tsx` | Liste des témoignages approuvés et formulaire de dépôt (utilisateur connecté). |
| `contact-section.tsx` | Formulaire de demande commerciale (offre Team). |
| `hero-section.tsx`, `stats-section.tsx`, `features-section.tsx`, `map-section.tsx`, `footer.tsx` | Contenu marketing statique animé (`framer-motion`). |
| `admin/shared.tsx` | Utilitaires admin : hook `useAdminList` (liste paginée liée aux paramètres de requête), `adminRequest` (mutation JSON), `Pagination`, `StatTile`, `Panel`. |
| `admin/accounts.tsx`, `offers.tsx`, `billing.tsx`, `moderation.tsx`, `stats.tsx` | Sections de la console : utilisateurs, plans, abonnements / équipes / demandes de contact / paiements et remboursements, modération et journal d'audit, statistiques. Les graphiques de `stats.tsx` sont construits à la main (sans `recharts`). |

## 5. Gestion de l'état et formulaires

* **État global** : un seul contexte React, `AuthProvider` (utilisateur courant). Au montage, il appelle `GET /me` ; le statut vaut `loading`, `authenticated`, `guest` (401/419) ou `error` (réseau/serveur), ce qui permet d'afficher un message distinct quand le serveur est injoignable.
* **Plans tarifaires** : `hooks/use-pricing.ts` garde la liste en **cache au niveau du module** (une seule requête `/pricing` partagée entre la section tarifs et la fenêtre d'inscription), sans prix de repli codé en dur (choix explicite : ne jamais afficher un prix différent de celui facturé).
* **État local** : `useState`/`useEffect` dans chaque page/composant (listes, pagination, filtres, messages).
* **Formulaires** : contrôlés à la main avec `useState` et validation HTML (`required`, `type="email"`). `react-hook-form`, `zod` et `@hookform/resolvers` sont installés mais **non utilisés** par le code applicatif (seul `components/ui/form.tsx`, lui-même inutilisé, importe `react-hook-form`).
* Pas de bibliothèque de gestion d'état (Redux, Zustand…) ni de cache de requêtes (SWR, React Query).

## 6. Accès aux données : `lib/api.ts`

Toutes les requêtes passent par `apiFetch(path, init, timeoutMs = 10000)` :

1. **URL** : préfixe `apiBaseUrl = "/backend-api"` (même origine que le site).
2. **En-têtes** : `Accept: application/json` ; `Content-Type: application/json` si un corps est envoyé.
3. **Cookies** : `credentials: "include"` (cookie de session Laravel).
4. **CSRF** : pour les méthodes non sûres (autres que GET/HEAD/OPTIONS), `ensureCsrfCookie()` appelle `GET /backend-api/csrf-cookie` si le cookie `XSRF-TOKEN` est absent (une seule requête partagée si plusieurs appels sont simultanés) ; la valeur décodée du cookie est envoyée dans l'en-tête `X-XSRF-TOKEN`.
5. **Expiration du jeton (419)** : en cas de réponse 419 sur une requête modifiante, le cookie CSRF est **rafraîchi de force** et la requête est **rejouée une seule fois**.
6. **Délai** : `AbortController` avec délai par défaut de 10 s (8 s pour `/me`, 15 s pour le fichier VPN, 20 s pour le checkout et la vérification de paiement).

`apiErrorMessage(response, fallback)` transforme une réponse d'erreur JSON en message unique : première erreur de validation Laravel (`errors`), sinon `message`, sinon `error`, sinon le message par défaut. Les erreurs réseau et les délais dépassés sont convertis en messages lisibles dans `auth-provider.tsx`.

### Pages et composants qui appellent l'API (aperçu)

| Appelant | Endpoints (préfixe `/backend-api`) |
|---|---|
| `auth-provider.tsx` | `/me`, `/auth/login`, `/auth/register`, `/auth/logout`, `/subscriptions/trial`, `/subscriptions/cancel`, `/account/email`, `/account/password`, `/email/verification-notification` |
| `auth-dialog.tsx` | `/auth/forgot-password` |
| `hooks/use-pricing.ts` | `/pricing` |
| `testimonials-section.tsx` | `/testimonials` (GET, POST) |
| `contact-section.tsx` | `/contact` |
| `payment-dialog.tsx` | `/checkout` |
| `app/payment/result` | `/payments/{id}/verify` |
| `app/verify-email` | `/email/verify/{id}/{hash}?expires&signature` |
| `app/reset-password` | `/auth/reset-password` |
| `app/dashboard` | `/account/subscriptions`, `/account/payments`, `/vpn/credentials`, `/vpn/config`, `/team`, `/team/members` (POST, DELETE) |
| `components/admin/*` | `/admin/stats`, `/admin/users`, `/admin/plans`, `/admin/subscriptions`, `/admin/subscriptions/team`, `/admin/payments`, `/admin/payments/{id}/refund`, `/admin/testimonials`, `/admin/audit-logs`, `/admin/contact-requests` |

Le détail (méthodes, corps, réponses) sera établi à l'étape 3.

## 7. Liaison avec le backend : réécriture `/backend-api`

Définie dans `next.config.mjs` :

* `rewrites()` : `/backend-api/:path*` → `${backendUrl}/api/:path*`.
* `backendUrl` = variable d'environnement **`NEXT_PUBLIC_API_URL`** (origine du service Laravel, sans `/api` ni barre finale), avec repli sur un serveur Laravel local en développement.
* Sur Vercel (`process.env.VERCEL`), le build **échoue volontairement** si `NEXT_PUBLIC_API_URL` n'est pas définie (évite un déploiement qui pointerait vers lui-même).

Conséquence architecturale : le navigateur ne parle qu'à l'origine du frontend. Les cookies de session et `XSRF-TOKEN` émis par Laravel sont donc des **cookies de première partie** du domaine du frontend, ce qui évite les problèmes de cookies tiers et de CORS côté navigateur. D'après `DEPLOYMENT.md`, le frontend est hébergé sur **Vercel** et l'API sur **Render**.

## 8. Authentification côté client et protection serveur (`proxy.ts`)

### 8.1 Flux côté client

1. Au chargement, `AuthProvider` appelle `GET /me` pour savoir si une session existe.
2. Connexion / inscription via `AuthDialog` → `POST /auth/login` ou `POST /auth/register` (avec `plan_slug`) ; la réponse contient l'objet `user`, stocké dans le contexte. Aucune donnée d'authentification n'est stockée dans `localStorage`/`sessionStorage` : la session repose uniquement sur le cookie HTTP de Laravel.
3. Déconnexion → `POST /auth/logout`, puis l'utilisateur est vidé du contexte même si la requête échoue.
4. `/admin` vérifie aussi côté client (`status`, `user.role`) et redirige vers `/` si la session change pendant l'utilisation.

### 8.2 Protection côté serveur (`proxy.ts`)

`proxy.ts` (convention « Proxy » de Next.js 16, ex-`middleware.ts`) s'applique aux chemins `/admin/:path*` et `/dashboard/:path*` :

| Situation | `/dashboard` | `/admin` |
|---|---|---|
| Aucun cookie | redirection vers `/` | redirection vers `/` |
| `GET {NEXT_PUBLIC_API_URL}/api/me` (cookie transmis, délai 4 s) → 401 ou 419 | redirection vers `/` | redirection vers `/` |
| Réponse OK | page servie | page servie seulement si `user.role === "admin"`, sinon redirection |
| Autre erreur HTTP ou exception (backend injoignable, délai) | page servie (**fail-open**, la page affiche son propre état d'erreur) | redirection vers `/` (**fail-closed**) |

Ainsi un non-administrateur ne reçoit jamais le code HTML de la console. L'autorisation réelle reste assurée par Laravel sur chaque appel API (middleware `auth` et `admin`).

## 9. En-têtes de sécurité (frontend)

Configurés dans `next.config.mjs` pour toutes les routes (`/(.*)`) :

* `Content-Security-Policy` : `default-src 'self'` ; `script-src 'self' 'unsafe-inline'` (+ `'unsafe-eval'` en développement uniquement) ; `style-src 'self' 'unsafe-inline'` ; `img-src 'self' blob: data:` ; `font-src 'self' data:` ; `connect-src 'self'` ; `object-src 'none'` ; `base-uri 'self'` ; `form-action 'self'` ; `frame-ancestors 'none'`. Pas de nonces (commentaire : aucun script tiers, tous les appels API sont de même origine).
* `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy: camera=(), microphone=(), geolocation=()`.

`connect-src 'self'` est possible précisément grâce à la réécriture `/backend-api`. La redirection vers Chargily se fait par navigation (`window.location.assign`), non soumise à `connect-src`.

## 10. Styles et thème

* **Tailwind CSS 4.2** configuré « CSS-first » : `app/globals.css` contient `@import 'tailwindcss'`, `@import 'tw-animate-css'`, les variables de couleur (format OKLCH) et un bloc `@theme inline` ; PostCSS via `@tailwindcss/postcss` (`postcss.config.mjs`). Il n'y a pas de fichier `tailwind.config.*`.
* **shadcn/ui** : `components.json` (style `new-york`, couleur de base `neutral`, variables CSS, icônes `lucide`).
* **Thème** : `next-themes`, sombre par défaut, variantes `.dark` et `.light` définies dans `globals.css` (`@custom-variant dark`), bascule par `theme-toggle.tsx` (navbar et `PageShell`).
* Utilitaires visuels personnalisés (fond pointillé, effet « verre », halos) et animations `framer-motion` sur les sections marketing.
* Polices Geist et Geist Mono chargées par `next/font/google` (auto-hébergées au build).
* Images : `images.unoptimized: true` (pas d'optimisation d'images Next.js).

## 11. Dossier `db/`

`db/schema.sql` (+ `db/README.md`) est un **ancien script MySQL** créant une base `nexus_vpn` avec 7 tables (dont `users`, `plans`, `subscriptions`, `payments`, `teams`). Il n'est référencé par aucun code ni configuration du frontend, et il ne correspond plus au schéma réel géré par les migrations Laravel (pas de `vpn_credentials`, `audit_logs`, `testimonials`, `contact_requests`, ni soft deletes). C'est un **reste inutilisé** ; la source de vérité est `nexus-vpn/database/migrations` (voir `06-database.md`).

## 12. Restes du générateur et fichiers inutilisés

Le projet frontend a été généré initialement avec **v0 (Vercel)** : `.gitignore` liste des fichiers internes du bac à sable v0 (`__v0_runtime_loader.js`, `__v0_devtools.tsx`, `.v0-trash/`, `.snowflake/`…), le nom du paquet est resté `my-project`, et le dossier `components/ui/` contient l'ensemble complet de composants shadcn/ui généré. Éléments identifiés comme non utilisés :

* `styles/globals.css` : doublon plus ancien de `app/globals.css`, importé nulle part (le layout importe `./globals.css`) ;
* `db/` (voir §11) ;
* fichiers de `public/` : `placeholder*.{jpg,png,svg}`, `icon.svg`, `icon-dark-32x32.png`, `icon-light-32x32.png`, `apple-icon.png` ne sont référencés par aucun fichier de code ; ⚠️ À VÉRIFIER : les icônes ne sont pas déclarées dans `metadata` et ne sont pas dans `app/`, le site n'aurait donc pas de favicon personnalisé ;
* la plupart des composants `components/ui/` (§4.1), `hooks/use-toast.ts` (le `Toaster` n'est monté nulle part) ;
* dépendances installées mais non importées par le code applicatif : `@vercel/analytics`, `zod`, `@hookform/resolvers`, `date-fns`, ainsi que celles utilisées uniquement par des primitives UI inutilisées (`react-hook-form`, `recharts`, `sonner`, `embla-carousel-react`, `cmdk`, `vaul`, `input-otp`, `react-day-picker`, `react-resizable-panels`) ;
* `autoprefixer` est déclaré mais n'est pas utilisé dans `postcss.config.mjs` (Tailwind 4 l'intègre).

## 13. Contenu et langue

* L'interface est en **anglais** (`<html lang="en">`), alors que le checkout Chargily est demandé en `locale: 'fr'` par le backend.
* Les chiffres marketing (87 pays, 99,98 % de disponibilité, 10 Gbit/s par nœud, nombre de nœuds par région sur la carte) sont **du contenu statique codé en dur** dans `stats-section.tsx` et `map-section.tsx`, non issu de l'API ni d'une infrastructure mesurée. ⚠️ À VÉRIFIER : à présenter comme maquette marketing dans le mémoire (le backend ne gère qu'une passerelle WireGuard configurée par variables d'environnement).
