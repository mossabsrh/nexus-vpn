# 01 — Présentation du projet

> Rédigé le 2026-10-05 à partir du code (audit de référence, étape 4/4). Les éléments de contexte académique et les motivations ne sont pas déductibles du code : ils sont marqués ⚠️ À VÉRIFIER.

## 1. Contexte académique

| Élément | Valeur |
|---|---|
| Type de travail | Projet de fin d'études (PFE) |
| Établissement, filière, année universitaire | ⚠️ À VÉRIFIER |
| Encadrant(s) (académique / entreprise) | ⚠️ À VÉRIFIER |
| Organisme d'accueil (le cas échéant) | ⚠️ À VÉRIFIER |
| Réalisé seul ou en binôme | ⚠️ À VÉRIFIER (`DEPLOYMENT.md` cite un dépôt GitHub `mossabsrh/nexus-vpn-upgraded`, différent du compte git local) |

## 2. Objectif

**NexusVPN** est une plateforme web de **commercialisation et de gestion d'un service VPN** fondé sur **WireGuard**. Elle permet à un client de découvrir l'offre, de créer un compte avec un essai gratuit de 7 jours, de payer un abonnement en ligne en dinars algériens (DZD) via **Chargily Pay**, puis de télécharger un fichier de configuration WireGuard personnel. Une console d'administration permet de piloter les utilisateurs, les offres, la facturation et la modération.

## 3. Problème traité

D'après les fonctionnalités implémentées, le projet répond aux besoins suivants :

* vendre un service par abonnement avec un **moyen de paiement local** (Chargily : cartes EDAHABIA et CIB, montants en DZD, d'après le texte de `payment-dialog.tsx`) ;
* **automatiser le cycle de vie** de l'accès : essai, paiement, renouvellement, annulation, expiration, rappels par e-mail ;
* **provisionner automatiquement** les identifiants VPN (paire de clés WireGuard, adresse tunnel) et les révoquer quand l'accès prend fin ;
* gérer des **offres d'entreprise** (équipes avec sièges) vendues par l'équipe commerciale ;
* donner à l'administrateur une **vision chiffrée** (revenus, conversions d'essai) et un **journal d'audit**.

⚠️ À VÉRIFIER : la motivation initiale du projet (besoin réel d'une entreprise, projet personnel, sujet proposé par l'encadrant…) et le choix du marché algérien.

## 4. Utilisateurs cibles

| Profil | Besoin |
|---|---|
| Visiteur | Découvrir le service, comparer les offres, contacter l'équipe commerciale |
| Client individuel | Essai gratuit, paiement en ligne, téléchargement de la configuration VPN, suivi des paiements |
| Entreprise (propriétaire d'équipe et membres) | Accès VPN pour plusieurs collaborateurs sous un même abonnement |
| Administrateur de la plateforme | Gestion des offres, des clients, de la facturation, de la modération et de l'audit |

Détail des acteurs et des exigences : `02-requirements.md`.

## 5. Fonctionnalités principales

Liste détaillée et liens vers le code : `10-features.md`.

1. Site vitrine (présentation, offres dynamiques, témoignages modérés, formulaire de contact).
2. Comptes utilisateurs : inscription avec essai de 7 jours, connexion, vérification d'e-mail, réinitialisation et changement de mot de passe/e-mail.
3. Abonnements : essai, paiement mensuel ou annuel, annulation, expiration et rappels automatiques.
4. Paiement en ligne Chargily avec confirmation par webhook signé et vérification active.
5. Accès VPN : génération et téléchargement d'une configuration WireGuard, révocation automatique en base.
6. Équipes (offre Team) : gestion des membres dans la limite des sièges.
7. Console d'administration : statistiques, utilisateurs, offres, abonnements, paiements/remboursements, demandes commerciales, modération, journal d'audit.

## 6. Périmètre

### Inclus (implémenté)

* Application web complète : frontend Next.js (`04-frontend.md`) et API Laravel (`05-backend.md`), base PostgreSQL/SQLite (`06-database.md`).
* Intégration de paiement Chargily Pay v2 (mode test configuré).
* Génération des configurations client WireGuard et export des pairs pour la passerelle.
* Déploiement Vercel + Render (`13-deployment.md`).

### Hors périmètre (absent du code)

* Le **serveur VPN lui-même** (installation et exploitation de la passerelle WireGuard) : seules ses coordonnées sont configurées ; la synchronisation des pairs est manuelle.
* Applications clientes VPN (mobile/desktop) : l'utilisateur importe le fichier `.conf` dans un client WireGuard existant.
* Renouvellement automatique / prélèvement récurrent (paiements ponctuels uniquement).
* OpenVPN, multi-serveurs/multi-pays, limitation du nombre d'appareils, kill switch, split tunneling : **non implémentés** et, depuis le 2026-10-11, **plus annoncés** sur le site ni dans les offres (contenu marketing aligné sur le réel ; voir `04-frontend.md` §13). Restent des perspectives (`19-limitations-and-future-work.md`).
* Interface multilingue (interface en anglais uniquement).

Limites et perspectives : `19-limitations-and-future-work.md`.
