# Captures d'écran — mode d'emploi

Les captures sont générées par le script Playwright [`capture.mjs`](capture.mjs). Elles ne sont pas committées en binaire ; régénérez-les sur une machine disposant de Playwright (l'environnement de développement ici est contraint en RAM et ne peut pas faire tourner simultanément Playwright + les deux serveurs).

## Procédure

```bash
# 1. Backend (dans nexus-vpn/)
php artisan serve --port=8000

# 2. Données de démonstration (si pas déjà fait)
php artisan db:seed --class=DemoDataSeeder

# 3. Frontend (à la racine du dépôt)
npx pnpm@10 dev          # http://localhost:3000

# 4. Playwright (à la racine)
npx pnpm@10 add -D playwright
npx playwright install chromium

# 5. Génération des captures
ADMIN_EMAIL="mohamed.belkacemi2004@gmail.com" ADMIN_PASSWORD="12345678" \
  node docs/pfe/memoire-assets/screenshots/capture.mjs
```

Le script produit des **captures plein écran**, pour chaque page, en **desktop (1440 px)** et **mobile (390 px)**, en thème **clair** et **sombre**. Nommage : `NN-page-viewport-theme[-etat].png`.

## Pages et états couverts

| Fichier (préfixe) | Page / état | Authentification |
|---|---|---|
| `01-home-*` | Accueil (hero, offres, témoignages, contact, pied de page) | public |
| `02-auth-dialog-*` | Modale de connexion / inscription | public |
| `08-reset-password-*` | Réinitialisation du mot de passe | public (lien) |
| `09-verify-email-*` | Vérification de l'e-mail | public (lien) |
| `10-payment-result-*` | Résultat de paiement | public (retour Chargily) |
| `11-dashboard-*` | Tableau de bord utilisateur (abonnement, VPN, facturation, profil) | connecté |
| `12-admin-*` | Console d'administration (vue d'ensemble) | admin |
| `13-admin-accounts-*` | Admin — comptes (dont bouton Supprimer) | admin |
| `14-admin-billing-*` | Admin — facturation (paiements, abonnements) | admin |
| `15-admin-offers-*` | Admin — offres | admin |
| `16-admin-moderation-*` | Admin — modération des témoignages + audit | admin |
| `17-admin-stats-*` | Admin — statistiques | admin |

Chaque préfixe donne 4 fichiers : `-desktop-light`, `-desktop-dark`, `-mobile-light`, `-mobile-dark`.

## États supplémentaires à capturer manuellement (optionnel)

Le script capture les pages et la modale d'authentification. Pour les états interactifs fins, les déclencher à la main puis capturer :

- **Erreurs de validation** : soumettre un formulaire vide (inscription, connexion, contact).
- **Dialogues de confirmation** : suppression d'un compte (zone de danger du menu compte), suppression d'un utilisateur et changement de rôle (admin → Comptes).
- **États vides** : filtrer les comptes sur un critère sans résultat.
- **Messages de succès** : après mise à jour du profil ou annulation d'abonnement.
- **E-mail reçu** : ouvrir l'e-mail de vérification dans la boîte Gmail du projet (vue du message).
