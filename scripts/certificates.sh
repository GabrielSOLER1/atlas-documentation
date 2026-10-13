#!/bin/sh
set -eu
umask 077
cd "$(dirname "$0")/.."
mkdir -p certs
if [ ! -s certs/ca.key ] || [ ! -s certs/ca.crt ]; then
    openssl req -x509 -newkey rsa:3072 -sha256 -nodes -days 3650 -keyout certs/ca.key -out certs/ca.crt -subj '/CN=Atlas Internal CA' -addext 'basicConstraints=critical,CA:TRUE' -addext 'keyUsage=critical,keyCertSign,cRLSign' 2>/dev/null
fi
if [ ! -s certs/server.key ] || [ ! -s certs/server.crt ] || ! openssl x509 -in certs/server.crt -checkend 604800 -noout >/dev/null 2>&1; then
    openssl req -new -newkey rsa:3072 -nodes -keyout certs/server.key -out certs/server.csr -subj '/CN=atlas.local' 2>/dev/null
    printf '%s\n' 'subjectAltName=DNS:atlas.local,DNS:localhost,IP:127.0.0.1' 'basicConstraints=critical,CA:FALSE' 'keyUsage=critical,digitalSignature,keyEncipherment' 'extendedKeyUsage=serverAuth' > certs/server.ext
    openssl x509 -req -in certs/server.csr -CA certs/ca.crt -CAkey certs/ca.key -CAcreateserial -out certs/server.crt -days 365 -sha256 -extfile certs/server.ext 2>/dev/null
fi
printf 'Internal certificate ready. Import certs/ca.crt on client devices.\n'
