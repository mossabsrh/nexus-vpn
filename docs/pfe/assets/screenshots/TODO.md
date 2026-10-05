# Captures d'écran à réaliser manuellement

> Captures que l'agent ne peut pas réaliser lui-même. Enregistrer les images dans ce dossier (`docs/pfe/assets/screenshots/`) avec le nom proposé, puis cocher la ligne. Ne jamais capturer de secrets (clés, mots de passe, `.env`, clé privée WireGuard visible). Utiliser un compte de démonstration.

## Site public

- [ ] `01-accueil-hero.png` — `/` en haut de page, thème sombre — Ch. 3, interfaces (vitrine)
- [ ] `02-accueil-tarifs.png` — `/#pricing`, bascule mensuel/annuel visible — Ch. 3, offres (F-01)
- [ ] `03-accueil-temoignages-contact.png` — sections témoignages et contact — Ch. 3 (F-02, F-03)
- [ ] `04-theme-clair.png` — `/` en thème clair — Ch. 3, utilisabilité (NFR-12)
- [ ] `05-mobile.png` — `/` en largeur mobile, menu ouvert — Ch. 3, responsive

## Authentification

- [ ] `06-inscription-choix-offre.png` — fenêtre d'inscription avec choix de l'offre — Ch. 3 (F-04)
- [ ] `07-connexion-erreur.png` — connexion avec mauvais mot de passe (message d'erreur) — Ch. 3 / sécurité
- [ ] `08-email-verification.png` — e-mail de vérification reçu (client mail ou journal) — Ch. 3 (F-07)
- [ ] `09-verify-email-ok.png` — `/verify-email` après succès — Ch. 3 (F-07)
- [ ] `10-reset-password.png` — `/reset-password` avec un lien valide — Ch. 3 (F-06)

## Espace client

- [ ] `11-dashboard-essai.png` — `/dashboard` d'un compte en essai — Ch. 3 (F-11)
- [ ] `12-dashboard-vpn.png` — carte « VPN access » avec bouton de téléchargement (masquer la clé publique si besoin) — Ch. 3 (F-12)
- [ ] `13-fichier-wireguard.png` — fichier `nexusvpn.conf` importé dans un client WireGuard, **clé privée masquée** — Ch. 3 (F-12)
- [ ] `14-dashboard-equipe.png` — carte Équipe côté propriétaire (sièges utilisés) — Ch. 3 (F-14)
- [ ] `15-menu-compte.png` — menu du compte et paramètres (e-mail/mot de passe) — Ch. 3 (F-08)

## Paiement (mode test Chargily)

- [ ] `16-paiement-dialogue.png` — fenêtre « Subscribe to … » avant redirection — Ch. 3 (F-10)
- [ ] `17-chargily-checkout.png` — page de paiement Chargily (mode test) — Ch. 3 (F-10)
- [ ] `18-paiement-resultat-succes.png` — `/payment/result?payment=…` après succès — Ch. 3 (F-10)
- [ ] `19-paiement-resultat-echec.png` — `/payment/result` après échec/annulation — Ch. 3 (F-10)
- [ ] `20-email-recu.png` — e-mail de reçu de paiement — Ch. 3 (F-10)

## Console d'administration

- [ ] `21-admin-overview.png` — `/admin`, section Overview — Ch. 3 (F-15)
- [ ] `22-admin-analytics.png` — section Analytics (graphiques) — Ch. 3 (F-15)
- [ ] `23-admin-accounts.png` — section Accounts avec filtres — Ch. 3 (F-16)
- [ ] `24-admin-offers.png` — section Offers (édition d'une offre) — Ch. 3 (F-17)
- [ ] `25-admin-subscriptions.png` — section Subscriptions avec création Team et demandes de contact — Ch. 3 (F-14, F-18, F-03)
- [ ] `26-admin-payments-refund.png` — section Payments avec la fenêtre de remboursement — Ch. 3 (F-18)
- [ ] `27-admin-moderation.png` — section Comments — Ch. 3 (F-02)
- [ ] `28-admin-audit.png` — section Audit log — Ch. 3 (F-19)
- [ ] `29-admin-refus.png` — tentative d'accès à `/admin` avec un compte non admin (redirection vers l'accueil) — Ch. 3 / sécurité (F-20)

## Tests et déploiement

- [ ] `30-tests-backend.png` — terminal : `php artisan test --compact` (94 réussis) — Ch. 4 (`12-testing.md`)
- [ ] `31-lint-tsc-build.png` — terminal : `pnpm lint`, `pnpm exec tsc --noEmit`, `pnpm build` — Ch. 4
- [ ] `32-render-service.png` — tableau de bord Render : service `nexus-vpn-api` sain et base `nexus-vpn-db` (**masquer les variables secrètes**) — Ch. 4 (`13-deployment.md`)
- [ ] `33-vercel-deploiement.png` — tableau de bord Vercel : déploiement réussi (**masquer les valeurs des variables**) — Ch. 4
- [ ] `34-headers-securite.png` — outils de développement du navigateur : en-têtes CSP/HSTS d'une réponse — Ch. 3/4, sécurité
