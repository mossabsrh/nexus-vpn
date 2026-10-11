# Screenshots — guide

Two sets live here:

1. **`memoire/` — print-usable captures for the mémoire (USE THESE).** 30 images, desktop **1440×900** viewport, **light** theme, `deviceScaleFactor: 2` (sharp text), each cropped to **one section / component** (not full-page), so every figure is readable when printed at **~16 cm** wide on A4. Dialogs and cards are element-cropped. Verified one by one.
2. **Legacy full-page set (this folder's root) — kept for reference.** 68 captures (17 views × desktop/mobile × light/dark). These are full-page, floor-to-ceiling images — **unusable as A4 figures**; kept only as an archive. Documented in the French section at the bottom.

---

## 1. Mémoire captures — `memoire/` (English)

Generated on `DemoDataSeeder` data with Playwright (Chromium). Public pages and the demo client (`yacine.benali@demo.nexusvpn.dz`), team owner (`rachid.zidane@…`), prospect (`bilal.ferhat@…`) and failed-payment (`walid.toumi@…`) demo accounts; admin console as the real admin. **No real data was modified** (payment and account dialogs were only *opened*, never submitted).

Mémoire chapter map: these illustrate **Chapter IV — Implementation and Deployment**, mostly **§IV.4 Interface walkthrough** (IV.4.1 client, IV.4.2 admin, IV.4.3 support); several also support IV.2 (architecture), IV.3 (modules) and IV.5 (critical components). A few public-site shots can also illustrate Chapter I/II context.

### Public site (visitor)
| File | Caption (EN) | Mémoire section |
|---|---|---|
| `01-home-navbar-hero.png` | Landing page — navigation bar and hero with the live VPN-status mock-up | IV.4.1 (client) · Ch. I/II context |
| `02-features.png` | "Why" section — the six privacy/security feature cards | IV.4.1 · IV.3 |
| `03-map-stats.png` | Network section — private-routing-over-WireGuard illustration (decorative world map, no server nodes) | IV.4.1 |
| `04-testimonials.png` | Customer testimonials tied to verified accounts | IV.4.1 · IV.3.8 — ⚠ see cleanup note |
| `05-pricing-cards.png` | Pricing — Essential / Standard / Team plan cards (DZD) | IV.4.1 · IV.3.1 (offers & pricing) |
| `06-contact-form.png` | "Talk to us about the Team plan" sales-contact form | IV.4.1 · IV.3.8 (support / contact requests) |
| `07-footer.png` | Page footer — product and support links, brand blurb and WireGuard trademark (footer only) | IV.4.1 |

### Authentication (IV.3.7 auth & RBAC)
| File | Caption (EN) | Mémoire section |
|---|---|---|
| `08-auth-signin.png` | Sign-in dialog | IV.3.7 |
| `09-auth-signup.png` | Sign-up dialog with 7-day trial and plan choice | IV.3.7 |
| `10-auth-validation-error.png` | Sign-up validation error ("Email and password are required.") | IV.3.7 · IV.5.1 |
| `11-auth-forgot-password.png` | "Reset your password" request dialog | IV.3.7 |
| `27-auth-success-message.png` | Success message after requesting a reset link | IV.3.7 |

### Client dashboard
| File | Caption (EN) | Mémoire section |
|---|---|---|
| `12-dashboard-subscription-vpn.png` | Client dashboard — subscription, VPN access, payment & subscription history (active plan) | IV.4.1 · IV.3.4 · IV.3.5 |
| `13-dashboard-team-management.png` | Team owner view — member list, seats used, add/remove members | IV.4.1 · IV.3 (team seats) |
| `15-account-settings.png` | "Modify Account" dialog — general tab, cancel subscription, danger zone | IV.4.1 · IV.3.6 (profile) |
| `25-dashboard-empty-state.png` | Empty/prospect dashboard — no subscription, no payments yet | IV.4.1 |
| `26-dashboard-failed-payment.png` | Dashboard showing a failed payment in the history | IV.4.1 · IV.3.3 |

### Subscription & payment (IV.3.3 billing · IV.5.2 gateway)
| File | Caption (EN) | Mémoire section |
|---|---|---|
| `14-receipt-dialog.png` | Payment receipt dialog (reference, amount, Chargily checkout id) | IV.3.3 |
| `16-payment-dialog.png` | Chargily checkout dialog — plan, amount, "Continue to Chargily" | IV.3.3 · IV.5.2 |

### Administration console (IV.4.2 admin · IV.4.3 support)
| File | Caption (EN) | Mémoire section |
|---|---|---|
| `17-admin-overview.png` | Admin overview — KPIs and 6-month revenue chart | IV.4.2 |
| `18-admin-accounts.png` | Admin — accounts, roles and subscription status | IV.4.2 (RBAC) |
| `19-admin-offers.png` | Admin — manage the public offers/plans | IV.4.2 · IV.3.1 — ⚠ see cleanup note |
| `20-admin-subscriptions.png` | Admin — subscriptions by plan and Team provisioning | IV.4.2 · IV.3.4 — ⚠ see cleanup note |
| `21-admin-payments.png` | Admin — payments list with statuses and refunds | IV.4.2 · IV.3.3 — ⚠ see cleanup note |
| `22-admin-analytics.png` | Admin — analytics (signups, conversion, revenue) | IV.4.2 |
| `23-admin-comments.png` | Admin — testimonial moderation queue (pending) | IV.4.3 (support) |
| `28-admin-contact-requests.png` | Admin — contact-request inbox ("Team inquiries") | IV.4.3 (support) |
| `24-admin-audit-log.png` | Admin — security & billing audit log | IV.4.2 · IV.5.1 |

### Responsive design (web on a phone viewport — NOT the postponed mobile app)
| File | Caption (EN) | Mémoire section |
|---|---|---|
| `90-mobile-home.png` | Landing page at 390 px — responsive layout | IV.4 (responsive design figure) |
| `91-mobile-dashboard.png` | Client dashboard at 390 px — responsive layout | IV.4 (responsive design figure) |

> ⏸️ The native **Flutter mobile app is POSTPONED** (decision D-16). §IV.4.4 stays a marked placeholder. The two images above are the **responsive website** on a phone-sized viewport, not the mobile app.

### ✅ Data cleanup done (2026-10-10)
All four figures were **re-captured after cleaning the test/junk data** (DB backed up first; only non-demo, non-admin test data removed, foreign keys respected): the inappropriate approved testimonial, a junk offer "b" (slug "a") + its trial/subscription/VPN/pending payment, a soft-deleted junk plan "dgdtgy", the whole `nexusvpn.pfe@gmail.com` test account + its payment/subscription/VPN, and a junk contact request. The demo accounts, the real admin account, and the audit log were left intact. These four now match the rest of the set — nothing further to do.

### ✅ Marketing-claims cleanup and recapture (2026-10-11)
The public site was aligned with what the product actually implements (no unverifiable claims). **Recaptured:** `01-home-navbar-hero` (hero eyebrow, subtext, trust row and the decorative status card now show protocol facts, not "no activity logs" / "hidden IP" / "DNS encrypted"), `02-features` (first card reworded to the precise "no VPN traffic data" statement), `03-map-stats` (the world map no longer shows server nodes or a backbone — it illustrates private routing), `05-pricing-cards` (plan features/descriptions carry no fake "Priority/Dedicated/Email support" tiers), `07-footer` (dead links and social icons removed; footer-only crop), `90-mobile-home`. **Re-cropped:** `17-admin-overview` (empty area removed). **Added:** `28-admin-contact-requests` (contact-request inbox, IV.4.3). Captured on a fresh `migrate:fresh --seed` + `DemoDataSeeder` database.

### How to regenerate the `memoire/` set
Backend on :8000 and the **production** frontend on :3000 (`npx pnpm@10 start`), then run the capture script from the repo root:
- viewport `1440×900`, `deviceScaleFactor: 2`, `colorScheme: 'light'`, `localStorage.theme='light'`;
- **viewport frames** for full-width sections (public pages, dashboards, admin tabs);
- **element screenshots** for dialogs (`[role="dialog"]`) and the team card (`section` holding the "Team ·" heading);
- auth via the API login endpoint with the XSRF cookie; demo accounts above, password `password`.

---

## 2. Jeu complet (ancien) — captures plein écran (français, archive)

**68 captures** (17 vues × 4 combinaisons : desktop 1440 px / mobile 390 px, thèmes clair / sombre) générées avec Playwright (Chromium) sur les données de `DemoDataSeeder`. Ce sont des **captures plein écran** (page entière), **inutilisables telles quelles comme figures A4** — conservées comme archive. Nommage : `NN-page-viewport-theme[-etat].png`.

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

### Procédure (pour régénérer l'ancien jeu)

```bash
# 1. Backend (dans nexus-vpn/)
php artisan serve --port=8000
# 2. Données de démonstration
php artisan db:seed --class=DemoDataSeeder
# 3. Frontend (racine)
npx pnpm@10 dev
# 4. Playwright (racine)
npx pnpm@10 add -D playwright && npx playwright install chromium
# 5. Génération
ADMIN_EMAIL="mohamed.belkacemi2004@gmail.com" ADMIN_PASSWORD="12345678" \
  node docs/pfe/memoire-assets/screenshots/capture.mjs
```
