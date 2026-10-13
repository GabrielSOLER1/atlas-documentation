# Protocole de validation — Gabriel

Ne marquer un essai comme réussi qu’après exécution. Les tests DNS et l’accès depuis VM3 seront réalisés avec Ange, une fois son infrastructure configurée.

| Essai | Manipulation | Résultat attendu | Preuve à conserver |
| --- | --- | --- | --- |
| Accès au portail | Ouvrir l’adresse de l’instance | La page de connexion apparaît | Capture de la page avec l’URL |
| Connexion refusée | Utiliser un mot de passe incorrect | Message d’échec, aucune session ouverte | Capture du message |
| Consultation | Se connecter comme utilisateur et ouvrir les deux procédures | Les deux contenus distincts sont lisibles | Deux captures |
| Recherche | Rechercher « incident », puis un terme absent | La liste est filtrée, puis un message indique qu’aucun résultat n’est trouvé | Capture des deux états |
| Accès protégé | Sans connexion, appeler `/api/documents` | HTTP 401, aucune donnée documentaire renvoyée | Réponse HTTP |
| Droits utilisateur | Connecté comme utilisateur, ouvrir `/#/procedures/1/modifier` | La modification est refusée | Capture du refus |
| Droits serveur | Lancer le test automatique | La tentative de modification utilisateur est refusée avec HTTP 403 | Sortie de `smoke.py` |
| Administration | Se connecter comme Gabriel, modifier un résumé, enregistrer et rouvrir la procédure | La modification est conservée | Captures avant/après |
| Procédure absente | Ouvrir `/#/procedures/999999` | Message clair ; retour à la liste disponible | Capture de l’erreur |
| Diagnostic | Après une mauvaise connexion, consulter les journaux | Trace « Connexion refusée » identifiable | Extrait de journal |
| Restauration | Exporter, récupérer le fichier sauvegardé avec Ange, puis utiliser `restore-test.sh` | Vérification des données réussie | Sortie de restauration |
| Application restaurée | Lancer la commande affichée par le script ; ouvrir le port 8081 et se connecter | Comptes et contenus récupérés utilisables | Capture du portail restauré |
| Intégration avec Ange | Depuis VM3, consulter `https://atlas.u5` | Connexion et consultation fonctionnent par DNS et reverse proxy | Capture depuis VM3 |

## Gestion des comptes et lecture des guides

| Essai | Manipulation | Résultat attendu |
| --- | --- | --- |
| Créer un compte | Comme administrateur, ouvrir « Comptes & accès », puis « Créer un compte » | Le membre apparaît avec son rôle et un statut actif |
| Validation des données | Envoyer un e-mail invalide, un mot de passe trop court ou une adresse déjà utilisée | Message clair ; aucun compte supplémentaire créé |
| Changer un rôle | Choisir un autre rôle et enregistrer | Le rôle est conservé ; l’ancienne session du membre est invalidée |
| Désactiver | Confirmer la désactivation d’un compte ayant déjà une session ouverte | Le prochain accès est refusé, puis une nouvelle connexion est aussi refusée |
| Réactiver | Réactiver le compte et se reconnecter | Le compte retrouve son accès avec son rôle actuel |
| Préserver l’administration | Essayer de désactiver son propre compte administrateur ou de retirer son rôle | Action refusée ; un administrateur actif reste disponible |
| Droits serveur | Comme utilisateur, appeler les routes de gestion des comptes | HTTP 403 ; aucun compte créé ou modifié |
| Sommaire | Cliquer sur une étape du guide | L’étape reçoit le focus et devient visible sans changer de route |
| Texte sûr | Ajouter du texte ressemblant à du HTML dans une procédure de test | Le texte reste affiché comme du texte, sans exécution |
| Lecture mobile | Ouvrir le guide sur une largeur de 390 px | Sommaire accessible, texte lisible et aucune barre de défilement horizontale de la page |

Les tests automatisés des comptes se lancent avec `python3 tests/users.py URL_INSTANCE_TEST`. Ils créent un compte de validation puis le désactivent ; utiliser une base séparée du portail principal.

## Démonstration de Gabriel

1. Expliquer pourquoi les utilisateurs ont besoin d’un point central de documentation.
2. Montrer la connexion et les deux procédures.
3. Montrer qu’un utilisateur ne peut pas modifier, puis effectuer une modification comme administrateur.
4. Expliquer que les droits sont aussi contrôlés par Symfony.
5. Présenter l’export et la vérification de la base restaurée.

Les tests automatiques locaux ne prouvent pas le fonctionnement du DNS, du dépôt Restic ou du réseau de VM3.


## Portail interne complet

- Vérifier qu’un visiteur déconnecté voit seulement la connexion et les informations publiques.
- Créer un guide avec étape, note, checklist et commande ; réorganiser les blocs et ouvrir l’aperçu avant publication.
- Vérifier le refus de création de compte et de documentation sans administrateur connecté.
- Contrôler catégories, aide, confidentialité et les deux types de 404 (navigation Vue et URL serveur inconnue).
- Vérifier dans le journal l’identité de la connexion et la publication, puis utiliser les filtres.
- Valider HTTPS avec `certs/ca.crt`, les redirections, les cookies et les en-têtes avec `tests/security.py`.
- Utiliser `tests/internal.py` pour publication, journal et limitation des connexions sur la base isolée.
- Tester la session à 30 minutes avec `tests/session.py`, uniquement dans le conteneur de validation nommé.
- Importer le certificat public de l’autorité sur VM3 avant les essais HTTPS par le proxy interne.

## Profils, domaine et roadmap

- Vérifier le certificat pour `atlas.u5`, et la conservation de l’autorité déjà approuvée.
- Migrer une copie d’une ancienne base deux fois : nombre de comptes et hachages inchangés, administrateur renommé, autres adresses conservées.
- Tester `/profil` depuis le nom du compte ; enregistrer puis rouvrir les champs et préférences.
- Vérifier une photo JPEG/PNG/WebP, le recadrage, la suppression, les images invalides et le refus sans CSRF.
- Confirmer qu’un compte n’accède pas à la photo ou aux favoris d’un autre compte.
- Ajouter un favori depuis une carte et le retrouver dans Mes favoris ; vérifier recherche et tri.
- Ouvrir une étape de la roadmap, suivre son lien interne, cocher puis recharger : progression conservée.
- Refuser la roadmap à un utilisateur ordinaire, y compris via l’API.
- Contrôler le menu, le profil et le graphe à 390 px ; aucune largeur ne doit déborder.
- Tester l’alerte de sortie d’un profil modifié sans enregistrer.
- Vérifier la correspondance des numéros du sommaire avec les étapes, et la copie d’un exemple.

Les scripts `tests/profile.py` et `tests/roadmap.py` utilisent les mêmes variables privées de validation que les autres tests. Ne pas exécuter les tests de mutation sur la base principale.


## Expérience de consultation et rédaction

`node frontend/tests-experience.mjs` vérifie la mutualisation des requêtes, la recherche sans accents, les réponses arrivant après un changement de compte, la priorité de la dernière actualisation de session, les brouillons privés et leur expiration. Les dépendances du frontend doivent être installées.

`python3 tests/editor.py URL_INSTANCE_TEST` vérifie deux éditeurs administrateur : une publication fondée sur une version périmée reçoit HTTP 409 et ne remplace pas la version récente. Les versions invalides et les publications par un utilisateur ordinaire sont refusées. Utiliser les mêmes variables privées que les autres tests, dans une base isolée.

Les parcours navigateur couvrent la palette au clavier et son focus, les filtres conservés dans l’URL, les présentations en cartes/liste, les favoris, les lectures récentes, le sommaire, la taille du texte et les trois ambiances. Vérifier aussi le brouillon après rechargement, l’ajout/duplication/déplacement des blocs, la copie en aperçu sans publication, la publication avec Ctrl/⌘ S et le nettoyage à la déconnexion. Contrôler le panneau mobile, Tab/Échap et l’absence de débordement à 320 et 390 pixels.
