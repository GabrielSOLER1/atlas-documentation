#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
if [ "$#" -ne 1 ] || [ ! -s "$1" ]; then
    printf 'Utilisation : sh scripts/restore-test.sh backups/atlas-....sql\n' >&2
    exit 1
fi
restore_database="atlas_restore_$(date +%Y%m%d_%H%M%S)_$$"
docker compose exec -T db sh -c 'PGPASSWORD="$POSTGRES_PASSWORD" createdb -U "$POSTGRES_USER" "$1"' sh "$restore_database"
docker compose exec -T db sh -c 'PGPASSWORD="$POSTGRES_PASSWORD" psql -v ON_ERROR_STOP=1 --single-transaction -U "$POSTGRES_USER" -d "$1"' sh "$restore_database" < "$1"
# Upgrade restored data without seeding missing records.
docker compose run --rm --no-deps -e "DB_NAME=$restore_database" app php bin/console app:migrate
docker compose run --rm --no-deps -e "DB_NAME=$restore_database" app php bin/console app:verify
printf '\nBase de test restaurée : %s\n' "$restore_database"
printf 'Pour consulter cette base dans le navigateur :\n'
printf 'docker compose run --rm --no-deps -e DB_NAME=%s -e APP_PUBLIC_URL=https://localhost:8444 -p 127.0.0.1:8444:443 app apache2-foreground\n' "$restore_database"
printf 'Puis ouvrir https://localhost:8444\n'
