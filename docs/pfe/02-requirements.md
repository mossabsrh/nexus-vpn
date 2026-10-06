# 02 — Analyse des besoins

> Exigences **dérivées du code implémenté** (audit du 2026-10-05) : chaque exigence fonctionnelle correspond à des endpoints (`07-api.md`) et/ou des pages (`04-frontend.md`). Ce n'est pas un cahier des charges initial. ⚠️ À VÉRIFIER : existe-t-il un cahier des charges d'origine à comparer avec cette liste ?

## 1. Acteurs

| Acteur | Type | Rôle |
|---|---|---|
| Visiteur | Humain, non authentifié | Consulte le site et les offres, envoie une demande commerciale, crée un compte |
| Utilisateur (client) | Humain, authentifié (`role = user`) | Gère son compte, son abonnement, ses paiements et son accès VPN |
| Propriétaire d'équipe | Utilisateur spécialisé (`teams.owner_id`) | Gère les membres de l'équipe dans la limite des sièges |
| Membre d'équipe | Utilisateur spécialisé (pivot `team_user`) | Bénéficie de l'accès VPN de l'abonnement d'équipe |
| Administrateur | Humain, authentifié (`role = admin`) | Pilote la plateforme via `/admin` |
| Chargily Pay | Système externe | Héberge la page de paiement, notifie le résultat par webhook, répond aux vérifications |
| Planificateur | Système interne (`schedule:work`) | Expire les abonnements, révoque les accès, envoie les rappels |
| Opérateur serveur | Humain avec accès console au serveur | Exécute les commandes Artisan `admin:grant` et `vpn:peers` |

Les relations d'héritage (propriétaire et membre sont des utilisateurs, l'administrateur est un utilisateur avec un rôle) seront représentées dans le diagramme de cas d'utilisation (à produire).

## 2. Exigences fonctionnelles

### Visiteur

| ID | Exigence |
|---|---|
| FR-01 | Le visiteur peut consulter la page d'accueil et la liste des offres actives avec leurs prix mensuels et annuels. |
| FR-02 | Le visiteur peut consulter les témoignages approuvés. |
| FR-03 | Le visiteur peut envoyer une demande commerciale (offre Team) avec nom, e-mail, entreprise, nombre de sièges et message. |
| FR-04 | Le visiteur peut créer un compte en choisissant une offre self-serve ; un essai gratuit de 7 jours démarre automatiquement. |
| FR-05 | L'utilisateur peut se connecter et se déconnecter. |
| FR-06 | L'utilisateur peut demander un lien de réinitialisation du mot de passe et définir un nouveau mot de passe. |

### Utilisateur (client)

| ID | Exigence |
|---|---|
| FR-07 | L'utilisateur peut vérifier son adresse e-mail via un lien signé et redemander un lien. |
| FR-08 | L'utilisateur peut modifier son e-mail et son mot de passe, et supprimer définitivement son compte, en confirmant son mot de passe actuel. La suppression anonymise les données personnelles et conserve l'historique de facturation (administrateurs et propriétaires d'équipe exclus). |
| FR-09 | L'utilisateur sans historique d'abonnement peut démarrer un essai gratuit de 7 jours (une seule fois par compte). |
| FR-10 | L'utilisateur dont l'e-mail est vérifié peut payer une offre self-serve, mensuelle ou annuelle, via Chargily (essai converti, renouvellement ou changement d'offre). |
| FR-11 | L'utilisateur voit le résultat de son paiement au retour de Chargily. |
| FR-12 | L'utilisateur peut annuler son abonnement (fin immédiate pour un essai, accès conservé jusqu'à la fin de la période payée sinon). |
| FR-13 | L'utilisateur consulte son tableau de bord : abonnement courant, historique paginé des abonnements et des paiements. |
| FR-14 | L'utilisateur disposant d'un accès actif et d'un e-mail vérifié peut télécharger sa configuration WireGuard et voir l'état de son accès VPN. |
| FR-15 | L'utilisateur peut publier un témoignage, visible après modération. |

### Équipe

| ID | Exigence |
|---|---|
| FR-16 | Le propriétaire d'équipe consulte l'équipe et ajoute/retire des membres (comptes existants) dans la limite des sièges. |
| FR-17 | Le membre d'équipe bénéficie de l'accès VPN tant que l'abonnement d'équipe est actif. |

### Administrateur

| ID | Exigence |
|---|---|
| FR-18 | L'administrateur consulte des statistiques : utilisateurs, abonnements par offre, conversion des essais, revenus par mois. |
| FR-19 | L'administrateur recherche, filtre et modifie les utilisateurs (nom, e-mail, rôle). |
| FR-20 | L'administrateur crée, modifie, active/désactive et supprime des offres (suppression refusée si l'offre a un historique). |
| FR-21 | L'administrateur consulte les paiements et enregistre un remboursement (avec fin optionnelle de l'abonnement). |
| FR-22 | L'administrateur consulte et annule les abonnements. |
| FR-23 | L'administrateur crée un abonnement Team (propriétaire, sièges, durée). |
| FR-24 | L'administrateur traite les demandes commerciales. |
| FR-25 | L'administrateur modère (approuve, rejette, supprime) les témoignages. |
| FR-26 | L'administrateur consulte le journal d'audit. |

### Systèmes et opérations

| ID | Exigence |
|---|---|
| FR-27 | Le système confirme les paiements par webhook Chargily signé et par vérification active, sans double application. |
| FR-28 | Le planificateur expire les abonnements terminés et révoque les identifiants VPN qui ne sont plus couverts. |
| FR-29 | Le système envoie les e-mails transactionnels : vérification, réinitialisation, bienvenue, reçu de paiement, rappels de fin d'essai/de période, fin d'accès. |
| FR-30 | L'opérateur exporte les pairs WireGuard actifs pour la passerelle (`vpn:peers`). |
| FR-31 | L'opérateur attribue le rôle administrateur à un compte existant (`admin:grant`). |

**Total : 31 exigences fonctionnelles.** Exigences partiellement exposées (API sans interface) : reçu d'un paiement et renommage d'équipe (`07-api.md` §13).

## 3. Exigences non fonctionnelles

Seules les propriétés réellement implémentées sont listées ; aucune mesure de performance n'a été effectuée.

| ID | Catégorie | Exigence | Mise en œuvre |
|---|---|---|---|
| NFR-01 | Sécurité | Authentification par session cookie `HttpOnly`, protégée contre le CSRF | `08-authentication-security.md` §1, §4 |
| NFR-02 | Sécurité | Limitation de débit des opérations sensibles | 12 limiteurs nommés (`08` §5) |
| NFR-03 | Sécurité | Mots de passe hachés (bcrypt), réponses anti-énumération, sessions invalidées après changement de mot de passe | `08` §2 |
| NFR-04 | Sécurité | Secrets uniquement côté serveur, clés privées WireGuard chiffrées au repos | `08` §8, §10 |
| NFR-05 | Sécurité | En-têtes de sécurité (CSP, HSTS, X-Frame-Options…) des deux côtés | `08` §9 |
| NFR-06 | Sécurité | Autorisation vérifiée côté serveur sur chaque appel, contrôle de rôle avant rendu de `/admin` | `08` §3 |
| NFR-07 | Intégrité | Montants calculés côté serveur, paiements idempotents, un seul abonnement courant par utilisateur garanti par la base | `05-backend.md` §7.2, `06-database.md` §5 |
| NFR-08 | Traçabilité | Journal d'audit des événements de sécurité et de facturation | `05-backend.md` §7.5 |
| NFR-09 | Conservation | Historique de facturation préservé (soft deletes, clés étrangères `RESTRICT`, désactivation des offres) | `06-database.md` §5 |
| NFR-10 | Performance | Cache des offres (10 min, invalidé à chaque modification), index sur les colonnes filtrées, pagination, pages pré-rendues statiques, chargement différé des sections de l'accueil | `05` §5, `06` §2, `04` §2 |
| NFR-11 | Robustesse / disponibilité | Délais d'attente sur les appels réseau, messages d'erreur distincts (session invalide vs serveur injoignable), échecs d'e-mail non bloquants, contrôle de santé `/up` | `04` §5–6, `05` §9, `13-deployment.md` |
| NFR-12 | Utilisabilité | Interface responsive (barre latérale mobile), thème sombre/clair, messages d'erreur lisibles issus de la validation | `04` §10 |
| NFR-13 | Maintenabilité | TypeScript strict, ESLint, conventions Laravel 12, 110 tests backend automatisés | `12-testing.md` |
| NFR-14 | Portabilité / déploiement | Configuration par variables d'environnement, image Docker, SQLite en local et PostgreSQL en production | `13-deployment.md` |

**Total : 14 exigences non fonctionnelles.**
