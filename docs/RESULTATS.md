# Résultats de vérification

**Date :** 13 octobre 2026.  
**Environnement :** Docker Desktop sur le poste de Gabriel, avec l’image applicative PHP 8.4/Apache et PostgreSQL 17. Tests exécutés en local ; les VM de l’infrastructure SISR ne sont pas utilisées ici.

| Vérification effectuée | Résultat |
| --- | --- |
| Syntaxe des fichiers PHP et de la configuration Symfony | Réussite |
| Configuration des services Symfony et des routes | Réussite |
| Construction de l’interface Vue avec Vite | Réussite |
| Construction de l’image Docker et démarrage Compose | Réussite |
| Disponibilité de PostgreSQL et de `/api/health` | HTTP 200 |
| Lecture sans connexion | HTTP 401 |
| Mot de passe incorrect ou connexion sans jeton CSRF | HTTP 401 |
| Connexion utilisateur et administrateur | Réussite pour les deux comptes |
| Liste et lecture des deux procédures | Réussite |
| Procédure inexistante | HTTP 404 avec réponse JSON lisible |
| Modification demandée par un utilisateur | HTTP 403 |
| Modification administrateur sans jeton CSRF | HTTP 403 |
| Titre vide lors d’une modification | HTTP 400 |
| Modification administrateur, relecture et remise du contenu initial | Réussite |
| Déconnexion et refus de lecture après déconnexion | Réussite, puis HTTP 401 |
| Interface navigateur : connexion, recherche filtrée, recherche sans résultat, consultation et enregistrement | Réussite |
| Export PostgreSQL | Fichier SQL généré et non vide |
| Import dans une base distincte, sans initialisation des données | Réussite ; deux procédures et deux comptes valides |
| Tests HTTP complets sur une instance utilisant cette base restaurée | Réussite |

Le script `tests/smoke.py` a réussi sur le portail principal (port 8080), puis sur un portail temporaire utilisant la base restaurée (port 8081). Ce portail temporaire a été arrêté à la fin des vérifications. Le portail principal et sa base restent démarrés.

## Preuves conservées

- [Portail connecté comme Gabriel](preuves/gabriel_portail.png).
- [Confirmation d’enregistrement d’une procédure](preuves/gabriel_modification.png).
- Export SQL : `backups/atlas-20261013-095001-38121.sql`.
- Base de restauration : `atlas_restore_20261013_095028_38147`.

La recherche et l’enregistrement ont aussi été vérifiés dans le navigateur. Le contenu initial des procédures a été conservé après les essais ; la date de mise à jour a été actualisée.

## À valider avec Ange

Résolution de `atlas.local`, accès depuis VM3 par le reverse proxy de VM1, sauvegarde Restic sur VM2 et récupération de l’export depuis un instantané Restic. La restauration PostgreSQL testée ici ne constitue pas une validation du dépôt Restic d’Ange.


## Mise à jour : interface, guides et comptes

Vérifications réalisées le 13 octobre 2026 après la refonte :

- Palette bleu nuit, ivoire et indigo ; effets de survol sur les cartes, boutons, icônes et liens du sommaire.
- Procédures découpées en sections, titres et paragraphes ; contenu texte conservé sans HTML interprété.
- Tests du découpage des procédures : deux tests Node réussis.
- Connexion, droits, consultation et modification : tests HTTP de régression réussis sur l’instance isolée.
- Création de compte, validation des champs, refus des doublons et protection CSRF : réussis.
- Accès aux routes de comptes refusé aux utilisateurs ordinaires : HTTP 403.
- Changement de rôle : nouvelle connexion requise et nouveau rôle appliqué.
- Désactivation : session existante bloquée à la prochaine requête et nouvelle connexion refusée.
- Réactivation : connexion à nouveau possible.
- Désactivation ou retrait de son propre rôle administrateur : HTTP 409.
- Création, changement de rôle et désactivation aussi vérifiés depuis les formulaires Vue.
- Lecture mobile à 390 px : pas de débordement horizontal de la page.
- Restauration d’un export antérieur à cette évolution : schéma mis à jour, données vérifiées sans ajout de données de démonstration.
- Vérification de la base de test après ajout de comptes désactivés : réussite.

Les comptes de validation ont été créés uniquement dans la base `atlas_restore_20261013_101443_39171`. Le portail principal conserve les deux comptes d’origine. Le conteneur temporaire sur le port 8081 a été arrêté à la fin des essais.

### Nouvelles preuves

- [Administration du portail principal](preuves/comptes_portail.png).
- [Compte créé, rôle changé puis désactivé sur l’instance isolée](preuves/comptes_validation.png).
- [Nouvelle présentation d’une procédure](preuves/procedure_refonte.png).
- [Lecture mobile](preuves/procedure_mobile.png).


## Version complète du portail interne

Vérifications du 13 octobre 2026 :

| Contrôle | Résultat |
| --- | --- |
| Connexion autonome sans menu privé ni inscription publique | Vérifié dans le navigateur |
| Logo local, favicon et fond discret | Vérifiés |
| Création par blocs, note, checklist, aperçu et publication | Publication réussie sur la base isolée |
| Accès à la publication réservé aux administrateurs | HTTP 401 / 403 pour les autres profils |
| Validation des types et du nombre de blocs | Requêtes invalides refusées |
| Création de comptes réservée à un administrateur connecté | Vérifiée côté serveur et navigation |
| Désactivation et changement de rôle | Sessions bloquées / invalidées |
| Journal local : identité, résultat, actions, périodes et filtres | Vérifié via API et navigateur |
| Limitation après cinq échecs | HTTP 429 au sixième essai |
| Session inactive depuis plus de 30 minutes | HTTP 401, session supprimée |
| Certificat TLS validé avec l’autorité interne | Vérification HTTPS réussie, sans désactiver la validation |
| Redirection HTTP de l’application et des assets | HTTP 308 vers HTTPS |
| Cookie Secure, CSP, anti-iframe et cache no-store | Vérifiés |
| 404 côté Vue et vraie réponse HTTP 404 | Vérifiées |
| Aide, confidentialité publique et catégories | Vérifiées |
| Connexion et lecture sur une largeur de 390 px | Aucun débordement horizontal de la page |
| Base initialement vide | Un seul compte administrateur créé |
| Démarrage avec images locales sans construction ni téléchargement | Services sains |

Les mutations de validation ont utilisé `atlas_restore_20261013_110210_40972`. La base `atlas_install_verify` a servi uniquement à vérifier l’initialisation. Le portail principal conserve ses comptes et guides ; son réseau, son schéma et sa configuration de sécurité sont mis à jour.

Les parcours visuels ont utilisé un conteneur isolé sur la boucle locale, avec HTTP autorisé uniquement pour cette validation. Le déploiement principal impose HTTPS. Ce conteneur temporaire est arrêté à la fin des essais. La confiance dans l’autorité locale n’a pas été ajoutée automatiquement au système ; elle doit être installée sur les clients autorisés.

### Preuves de la version complète

- [Connexion indépendante](preuves/atlas_connexion.png).
- [Connexion mobile](preuves/atlas_connexion_mobile.png).
- [Documentation publiée avec blocs](preuves/atlas_publication.png).
- [Journal local](preuves/atlas_activite.png).
- [Page 404](preuves/atlas_404.png).
- [Architecture et fonctionnement](RAPPORT_ARCHITECTURE.md).

Le retour à la connexion après expiration a aussi été vérifié lors d’un rechargement HTML : redirection vers la page dédiée, et non affichage d’une réponse JSON brute. La lecture mobile structurée et le refus d’accès à l’administration pour un utilisateur ont été confirmés sur la version finale.

### Réplication depuis les sources Git — 13 octobre 2026

Les règles Git conservent désormais `backend/.env` (valeurs de développement) et excluent le `.env` privé à la racine, les certificats, les sauvegardes et les archives d’images. Les anciens mots de passe ont été retirés des tests ; les tests authentifiés exigent des identifiants fournis dans l’environnement local.

Une copie composée uniquement des fichiers publiables a démarré avec une base neuve, dans le projet Docker séparé `atlas-clone-check`. Les deux services étaient sains, le HTTPS validé et un seul administrateur initial présent. Les tests de connexion, comptes, publication, journal, CSRF et limitation des tentatives ont réussi avec des mots de passe générés pour cette copie.

La commande locale `app:reset-password` a refusé un mot de passe trop court et un compte inexistant. Un renouvellement valide a invalidé la session précédente, refusé l’ancien mot de passe et permis la connexion avec le nouveau. Aucun mot de passe n’a été affiché dans les résultats.

Les deux anciens accès du portail principal ont ensuite été renouvelés à la demande du propriétaire. Les nouvelles valeurs sont conservées uniquement dans le fichier privé `.env.credentials`, protégé en lecture et écriture pour son propriétaire et exclu du dépôt comme des images Docker. Les connexions HTTPS ont été vérifiées avec ces nouveaux accès. Le mot de passe de l’administrateur initial dans le `.env` local a également été synchronisé.
