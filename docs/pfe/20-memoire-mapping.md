# 20 — Correspondance avec les chapitres du mémoire

> Plan type utilisé : Introduction générale ; Chapitre 1 (contexte et état de l'art) ; Chapitre 2 (analyse et conception) ; Chapitre 3 (réalisation) ; Chapitre 4 (tests et déploiement, s'il est séparé du chapitre 3) ; Conclusion et perspectives ; Annexes. ⚠️ À VÉRIFIER : plan imposé par l'établissement.

| Fichier `docs/pfe/` | Introduction | Ch. 1 Contexte / état de l'art | Ch. 2 Analyse et conception | Ch. 3 Réalisation | Ch. 4 Tests et déploiement | Conclusion / perspectives | Annexes |
|---|---|---|---|---|---|---|---|
| `00-project-context.md` | | | | (aide-mémoire, non cité) | | | |
| `01-project-overview.md` | ✔ objectif, problématique | ✔ contexte, utilisateurs cibles | ✔ périmètre | | | | |
| `02-requirements.md` | | | ✔ acteurs, FR, NFR | | | | |
| `03-architecture.md` (à créer) | | | ✔ architecture globale | ✔ | | | |
| `04-frontend.md` | | | ✔ structure de l'interface | ✔ frontend | | | |
| `05-backend.md` | | | ✔ workflows métier | ✔ backend | | | |
| `06-database.md` | | | ✔ modèle de données | ✔ migrations, contraintes | | | ✔ dictionnaire de données |
| `07-api.md` | | | ✔ contrat d'interface | ✔ communication front ↔ back | | | ✔ référence complète des endpoints |
| `08-authentication-security.md` | | ✔ notions de sécurité web | ✔ choix de sécurité | ✔ mécanismes implémentés | ✔ registre des risques | ✔ risques ouverts | |
| `09-technologies-and-packages.md` | | ✔ technologies étudiées | | ✔ environnement de développement | | | ✔ inventaire des paquets |
| `10-features.md` | | | ✔ lien FR ↔ fonctionnalités | ✔ présentation des fonctionnalités (avec captures) | | | |
| `11-development-history.md` | | | | ✔ démarche et phases | | | |
| `12-testing.md` | | | | | ✔ tests et résultats | | ✔ sorties de commandes |
| `13-deployment.md` | | | | | ✔ déploiement | | ✔ variables d'environnement |
| `14-problems-and-solutions.md` | | | | ✔ difficultés et solutions | | | |
| `15-technical-decisions.md` | | ✔ comparaison des solutions | ✔ justification des choix | | | | |
| `16-sources.md` | | ✔ | | | | | ✔ bibliographie / webographie |
| `17-changelog.md` | | | | | | | ✔ |
| `18-glossary.md` | | | | | | | ✔ liste des abréviations (début du mémoire) |
| `19-limitations-and-future-work.md` | | | | | | ✔ limites et perspectives | |
| `assets/diagrams/erd.mmd` | | | ✔ diagramme entité-association (base du MCD/MLD) | | | | |
| Diagrammes à produire (cas d'utilisation, classes, séquences, activités, déploiement, architecture, MCD/MLD) | | | ✔ | ✔ séquences | ✔ déploiement | | |
| `assets/screenshots/` (`TODO.md`) | | | | ✔ interfaces | ✔ tests, déploiement | | ✔ |

`03-architecture.md` n'a pas encore été créé (non demandé dans l'audit de référence) ; l'architecture est actuellement décrite dans `04`, `05`, `07` et `13`.
