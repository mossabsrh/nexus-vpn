# 18 — Audit final de qualité et de débogage (avant soutenance)

> Audit complet réalisé le **2026-10-07** sur la branche `main` (dernier commit avant audit : `fc0a8cc`).
> Objectif : vérifier, avant la soutenance du PFE, que l'ensemble de l'application (frontend Next.js + API Laravel) fonctionne, est sûr, et ne contient pas de bug bloquant.
> Méthode : exécution de la suite de tests, revue de code exhaustive (backend + frontend), et **tests de bout en bout sur le build de production** (Playwright, un contexte navigateur à la fois pour rester dans ~2,6 Go de RAM libre).

## 0. Résumé exécutif

**Aucun bug fonctionnel n'a été trouvé.** Les 121 tests backend passent, le lint, la vérification de types TypeScript et le build de production réussissent, et tous les parcours réels testés en direct fonctionnent (visiteur, inscription/connexion, tableau de bord, paiement Chargily en mode test, administration, contrôle d'accès). L'envoi d'e-mails via Gmail SMTP a été vérifié en direct. Deux observations mineures (cosmétiques/performance) ont été ajoutées au registre des risques (R-26, R-27) ; elles ne nécessitent aucune correction avant la soutenance. Le code n'a donc **pas été modifié** : l'état audité est l'état final.

| Vérification | Résultat |
|---|---|
| Tests backend (`php artisan test`) | ✅ 121 réussis (470 assertions) |
| Lint ESLint (`pnpm lint`) | ✅ aucun avertissement |
| Types (`tsc --noEmit`) | ✅ aucune erreur |
| Build de production (`next build`) | ✅ réussi |
| Parcours E2E (Playwright, prod) | ✅ 21/22 (le 1 restant = artefact de test, voir §3) |
| Envoi e-mail Gmail SMTP | ✅ envoyé en direct (≈ 6 s, synchrone) |
| Paiement Chargily (mode test) | ✅ checkout créé (`pay.chargily.dz`) |
| Secrets versionnés | ✅ aucun (`.env` ignoré, `.env.example` complet) |
| Bugs fonctionnels | **0** |

## 1. Périmètre et méthode

1. **Base de référence.** Sauvegarde de la base de développement (`database.sqlite.bak-audit-20261007-*`), puis exécution de la suite de tests, du lint, de la vérification de types et du build.
2. **Revue de code (lecture seule).**
   * *Backend* : les 9 contrôleurs API, les 6 modèles métier, le service `ChargilyGateway`, le middleware admin, `bootstrap/app.php`, `AppServiceProvider`, les routes (`api.php`, `web.php`, `console.php`), et la configuration (`cors`, `session`, `services`, `app`).
   * *Frontend* : client API (`lib/api.ts`), `proxy.ts`, fournisseur d'authentification, toutes les pages (`/`, `/dashboard`, `/admin`, `/verify-email`, `/reset-password`), les boîtes de dialogue (authentification, paiement, compte) et tous les composants d'administration.
   * *Configuration* : `.env.example`, `next.config.mjs`, recherche de secrets dans l'historique Git.
3. **Tests de bout en bout (build de production).** Backend `php artisan serve` + frontend `pnpm start` (jamais `dev`), pilotés par Playwright (Chromium headless), un contexte à la fois.
4. **Vérifications en direct ciblées.** Envoi réel d'un e-mail de vérification via Gmail SMTP (vers la boîte de l'administrateur) ; création réelle d'un checkout Chargily en mode test.
5. **Nettoyage.** Suppression des scripts E2E temporaires et de l'unique paiement de test créé ; arrêt des deux serveurs ; arbre de travail Git laissé propre.

## 2. Ce qui a été vérifié et fonctionne

### 2.1 Visiteur / page d'accueil
* La page d'accueil se charge sans erreur JavaScript (hormis le `401` attendu, voir R-26). Titre, sections (héros, statistiques, fonctionnalités, carte, témoignages, offres, contact, pied de page) rendues via `next/dynamic`.
* Les **offres sont bien affichées** (Essential, Standard, Team ; prix mensuel/annuel ; bascule Mensuel/Annuel). Elles se chargent côté client (appel `/pricing` + animation à l'apparition).
* **Bascule de thème** clair/sombre fonctionnelle (classe `dark`/`light` sur `<html>`).
* **Formulaire de contact (Team)** présent et fonctionnel ; validation serveur (nom, e-mail, message ≥ 10 caractères) et limitation de débit.

### 2.2 Authentification
* **Mauvais mot de passe** → `401` avec message générique ; **aucune énumération** d'e-mails (mot de passe oublié répond toujours pareil).
* **Verrouillage anti-force brute** : après 5 échecs pour un couple e-mail + IP, réponse `429` (vérifié en direct : `401×5` puis `429`), en plus du limiteur de débit par IP.
* **Inscription** : création du compte + essai de 7 jours + connexion + **envoi de l'e-mail de vérification via Gmail SMTP** (vérifié en direct).
* **Réinitialisation du mot de passe** : lien par e-mail vers le frontend, qui appelle l'API ; invalide les autres sessions.
* **Vérification de l'e-mail** : lien signé (`signed:relative`, 60 min), reconstruit côté frontend et validé côté backend.

### 2.3 Tableau de bord utilisateur
* Rendu correct pour un utilisateur de démonstration (abonnement, accès VPN, historique de paiements et d'abonnements, équipe). États gérés : chargement, invité (redirection), erreur (bouton « réessayer »), connecté.
* **Section VPN** : affiche correctement « les serveurs sont en cours de configuration » car aucune passerelle WireGuard n'est configurée en local (`VPN_SERVER_*` absents) — comportement attendu.
* **Checkout Chargily (mode test)** : `POST /checkout` renvoie une URL de paiement réelle (`pay.chargily.dz`). Le montant est imposé par l'offre (le montant fourni par le client est ignoré).
* Annulation d'abonnement, changement d'e-mail/mot de passe, **suppression de compte** (zone de danger, confirmation par mot de passe + saisie de l'e-mail) : logique et garde-fous vérifiés par revue et tests.

### 2.4 Administration
* Les **8 onglets** (Overview, Accounts, Offers, Subscriptions, Payments, Analytics, Comments, Audit log) se chargent **sans erreur de console ni note d'échec de chargement**.
* Recherche, filtres, pagination, édition d'utilisateur, changement de rôle (avec confirmation), suppression d'utilisateur (avec confirmation et garde-fous), gestion des offres, remboursements, modération des témoignages, demandes commerciales, abonnement d'équipe : présents et correctement protégés.
* **Garde-fous vérifiés en direct** : un admin ne peut pas se supprimer lui-même (`422`) ; côté code, impossible de supprimer le dernier admin, de retirer son propre rôle admin, ou de supprimer un propriétaire d'équipe.

### 2.5 Contrôle d'accès (vérifié en direct)
| Scénario | Résultat attendu | Observé |
|---|---|---|
| Visiteur non connecté → `/admin` | redirection vers `/` | ✅ `307` → `/` |
| Visiteur non connecté → `/dashboard` | redirection vers `/` | ✅ `307` → `/` |
| Utilisateur normal → `/admin` (page) | redirection (rôle vérifié dans `proxy.ts`) | ✅ redirigé vers `/` |
| Utilisateur normal → API admin | refus | ✅ `403` |
| Admin → API admin | autorisé | ✅ `200` |
| Lecture du paiement d'un autre utilisateur | refus | ✅ `404` (vérifié par revue + tests) |

### 2.6 Sécurité et configuration
* **En-têtes de sécurité** présents sur le frontend : `Content-Security-Policy` (sans script tiers), `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`, `Permissions-Policy`. Backend : en-têtes via `SecurityHeaders`.
* **CSRF** sur toutes les routes API à session (jeton `X-XSRF-TOKEN`), **webhook Chargily** hors session et vérifié par **HMAC-SHA256** (`hash_equals`).
* **Autorisation** systématique : vérification de propriété sur les paiements, middleware admin, garde-fous « soi-même / dernier admin / propriétaire d'équipe ».
* **Données sensibles** jamais exposées : `password`/`remember_token` (modèle `User`), `provider_response` (`Payment`), `private_key` chiffré (`VpnCredential`) sont masqués ; les réponses passent par des `toApiArray()` en liste blanche.
* **Paiement idempotent** : transition `pending → succeeded` sous verrou de ligne (`lockForUpdate`), rejouable sans double activation.
* **Aucun secret versionné** : `.env` ignoré, `.env.example` complet avec des gabarits, seules des clés de test factices (`test_sk_demo_secret`) apparaissent dans les tests.
* **Garde de production** (`AppServiceProvider`) : refuse de démarrer si `APP_DEBUG=true` en production et journalise les valeurs par défaut de développement.

## 3. Le point « 21/22 » expliqué (pas un bug)

Le seul contrôle E2E non concluant est l'assertion de test « les offres sont présentes immédiatement après `networkidle` ». Les sections de la page d'accueil sont chargées paresseusement (`next/dynamic`) et les cartes d'offres apparaissent **après défilement** (animation `whileInView` de framer-motion) et après l'appel client `/pricing`. En rechargeant avec défilement puis attente, les offres s'affichent correctement (Essential, Standard, « /month », boutons d'essai présents). Il s'agit donc d'une **limite du scénario de test automatisé**, pas d'un défaut de l'application. Conséquence réelle : les prix ne sont pas dans le HTML initial (choix d'architecture SPA ; acceptable pour une page marketing).

## 4. Observations mineures ajoutées au registre des risques

Aucune ne justifie de correction avant la soutenance. Détails complets dans `08-authentication-security.md`.

* **R-26 (Faible, cosmétique)** — Au chargement de toute page, le client appelle `GET /backend-api/me` pour détecter une session. Pour un **visiteur non connecté**, l'API répond `401` (sémantique REST correcte) ; le navigateur journalise alors « Failed to load resource: 401 » dans la console. L'application gère ce cas proprement (statut « invité »). Aucune donnée exposée, aucune promesse non gérée. *Correction possible mais non souhaitable* : faire répondre `/me` en `200 {user:null}` changerait le contrat de l'API — non appliqué.
* **R-27 (Faible, performance)** — La sérialisation d'une page de paiements appelle `Payment::plan()` (`Plan::withTrashed()->find()`) une fois par paiement possédant un `plan_id`. Mesuré : 4 requêtes pour 9 paiements (seuls ceux avec un `plan_id` déclenchent une recherche), sur une table `plans` de 5 lignes. Impact négligeable à l'échelle du projet ; optimisable par mémoïsation si le volume croît. Non corrigé (toucherait plusieurs appelants).

## 5. Limites connues à garder en tête pour la démo

Ces points sont **documentés** et pour la plupart déjà dans le registre des risques. Ils ne sont pas des bugs mais des limites assumées (gratuité des services, périmètre PFE).

| Réf. | Limite | À quoi penser pendant la démo |
|---|---|---|
| R-01 / R-09 | La passerelle WireGuard réelle n'est pas déployée ; les chiffres marketing (87 pays, 99,98 %, kill switch…) sont des maquettes. | Le fichier `.conf` est généré correctement, mais **le tunnel ne connecte pas à un vrai serveur**. Présenter la génération de configuration, pas une connexion live. |
| — | Chargily en **mode test**. | Montrer la redirection vers Chargily ; utiliser les cartes de test. Ne pas présenter comme un paiement réel. |
| R-19 | E-mails **synchrones** (pas de file d'attente) : l'envoi via Gmail prend ≈ 6 s. | Lors d'une inscription en direct, l'interface peut sembler lente ~6 s le temps de l'envoi SMTP. Préparer ce point ou inscrire à l'avance. |
| R-02 | PostgreSQL Render Free expire 30 jours après création. | Vérifier que la base de production n'a pas expiré **avant** la soutenance. |
| R-04 | Le planificateur dort avec le conteneur Render Free après 15 min d'inactivité. | Expirations/rappels différés ; l'accès reste vérifié en temps réel. Réveiller le service avant la démo. |
| R-05 | `TRUSTED_PROXIES` : comportement exact des en-têtes Render/Vercel à vérifier en production. | Sans incidence en local. |
| R-25 | Remboursements **enregistrés** uniquement (Chargily Pay v2 n'a pas d'API de remboursement). | Le remboursement se fait dans le tableau de bord Chargily, puis est consigné dans l'application. |
| R-20 | Routes/pages héritées Inertia (`/` sur le domaine Laravel). | Démontrer **uniquement** le frontend Vercel/Next, jamais la racine du backend Render. |

## 6. Conseils pour la démonstration de soutenance

**À montrer (parcours fiables) :**
1. Accueil → offres → bascule de thème → formulaire de contact Team.
2. Inscription (ou connexion d'un compte préparé) → tableau de bord → statut d'abonnement.
3. Paiement : « Continuer vers Chargily » → page Chargily (mode test).
4. Administration : connexion admin → parcours des onglets (statistiques réelles, comptes, paiements, journal d'audit).
5. Contrôle d'accès : se déconnecter puis tenter `/admin` → redirection.

**À préparer / éviter :**
* **Préparer les comptes à l'avance** (admin + un compte avec abonnement actif) pour éviter l'attente SMTP de ~6 s en direct.
* **Ne pas** présenter de connexion VPN réelle (pas de passerelle déployée) : montrer la génération du `.conf`.
* **Ne pas** ouvrir la console du navigateur sans savoir expliquer le `401` sur `/me` (R-26 : c'est la vérification de session d'un visiteur, gérée proprement).
* **Vérifier avant la soutenance** : base Render non expirée (R-02) et service réveillé (R-04).

**Questions probables du jury et réponses :**
* *« Comment les paiements sont-ils sécurisés ? »* → webhook signé HMAC-SHA256, activation idempotente sous verrou de ligne, montant imposé par l'offre et revérifié (montant + devise), réconciliation planifiée des paiements en attente.
* *« Et si on supprime son compte ? »* → anonymisation + suppression logique, l'historique de facturation est conservé (obligations comptables), l'e-mail est libéré, les sessions et jetons sont purgés.
* *« Un utilisateur peut-il voir les données d'un autre ? »* → non : vérification de propriété (`404`) sur chaque ressource, middleware admin, et `proxy.ts` qui refuse l'UI admin aux non-admins.

## 7. Conclusion

Le projet est **prêt pour la soutenance** sur le plan de la qualité et de la robustesse : aucune régression, aucun bug fonctionnel, parcours critiques vérifiés en conditions de production. Les limites restantes sont connues, documentées et liées au périmètre du PFE ou à la gratuité des services, non à des défauts de code.
