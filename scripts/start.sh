#!/bin/sh
set -eu
umask 077
cd "$(dirname "$0")/.."
command -v docker >/dev/null
command -v openssl >/dev/null
if [ ! -f .env ]; then
    printf 'APP_PORT=8080\nAPP_HTTPS_PORT=8443\nAPP_PUBLIC_URL=https://localhost:8443\nAPP_ENFORCE_HTTPS=1\nAPP_SECRET=%s\nDB_PASSWORD=%s\n' "$(openssl rand -hex 32)" "$(openssl rand -hex 24)" > .env
fi
if ! grep -q '^BOOTSTRAP_ADMIN_PASSWORD=' .env; then
    printf '\nBOOTSTRAP_ADMIN_EMAIL=gabriel@atlas.local\nBOOTSTRAP_ADMIN_NAME=Gabriel Soler\nBOOTSTRAP_ADMIN_PASSWORD=%s\n' "$(openssl rand -hex 20)" >> .env
fi
if grep -q 'replace-with-' .env; then
    printf 'Replace the example secrets in .env before starting Atlas.\n' >&2
    exit 1
fi
sh scripts/certificates.sh
if [ "${1:-}" = "--offline" ]; then
    docker compose up -d --no-build --pull never --wait --wait-timeout 90
else
    docker compose up -d --build --wait --wait-timeout 90
fi
printf '\nAtlas is ready at your APP_PUBLIC_URL (default https://localhost:8443).\n'
printf 'On a new installation, the initial administrator credentials are in .env.\n'
