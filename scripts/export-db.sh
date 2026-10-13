#!/bin/sh
set -eu
umask 077
cd "$(dirname "$0")/.."
mkdir -p backups
temporary=$(mktemp backups/.atlas-XXXXXX)
trap 'rm -f "$temporary"' EXIT HUP INT TERM
docker compose exec -T db sh -c 'PGPASSWORD="$POSTGRES_PASSWORD" pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB" --clean --if-exists --no-owner --no-privileges' > "$temporary"
test -s "$temporary"
destination="backups/atlas-$(date +%Y%m%d-%H%M%S)-$$.sql"
mv "$temporary" "$destination"
printf 'Export prêt : %s\n' "$destination"
