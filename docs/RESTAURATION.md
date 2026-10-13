# Récupérer la base principale

Cette procédure remplace les données applicatives par celles de l’export choisi. Tester d’abord ce fichier avec `restore-test.sh`, puis choisir avec Ange l’instantané à récupérer. Le dépôt Restic et la récupération de son fichier relèvent d’Ange.

1. Identifier le fichier PostgreSQL réellement récupéré et vérifier que son test de restauration réussit.
2. Conserver un export de l’état actuel, s’il est encore lisible : `sh scripts/export-db.sh`.
3. Arrêter l’application pour éviter des écritures : `docker compose stop app`.
4. Réimporter le fichier validé depuis le dossier du projet :

```sh
docker compose exec -T db sh -c 'PGPASSWORD="$POSTGRES_PASSWORD" psql -v ON_ERROR_STOP=1 --single-transaction -U "$POSTGRES_USER" -d "$POSTGRES_DB"' < backups/FICHIER_VALIDE.sql
```

Remplacer `backups/FICHIER_VALIDE.sql` par le vrai chemin. `ON_ERROR_STOP` et la transaction empêchent de valider une restauration partielle en cas d’erreur SQL.

5. Si l’import réussit, adapter le schéma d’un ancien export sans recréer de données de démonstration :

```sh
docker compose exec -T db sh -c 'PGPASSWORD="$POSTGRES_PASSWORD" psql -v ON_ERROR_STOP=1 --single-transaction -U "$POSTGRES_USER" -d "$POSTGRES_DB"' < backend/database/schema.sql
```

Puis vérifier les données :

```sh
docker compose run --rm --no-deps app php bin/console app:verify
```

6. Redémarrer l’application : `docker compose start app`.
7. Depuis le poste de test, vérifier la connexion, les deux procédures et les droits. Conserver les preuves.

En cas d’échec de l’import, garder l’application arrêtée, consulter le message d’erreur et utiliser avec Ange un autre export validé. Cette procédure traite la récupération des données ; elle ne réinstalle pas une VM ou un service PostgreSQL défaillant.
