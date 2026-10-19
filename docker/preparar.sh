#!/bin/sh
# Genera contraseñas distintas sin mostrarlas en pantalla.
set -eu
cd "$(dirname "$0")/.."
if [ -f .env.docker ]; then
    echo '.env.docker ya existe. Se conservó su configuración.'
    exit 0
fi
umask 077
application_password=$(openssl rand -hex 24)
root_password=$(openssl rand -hex 24)
sed -e "s/^NUVA_DB_PASSWORD=$/NUVA_DB_PASSWORD=$application_password/" \
    -e "s/^NUVA_DB_ROOT_PASSWORD=$/NUVA_DB_ROOT_PASSWORD=$root_password/" \
    .env.docker.example > .env.docker
echo 'Configuración creada. Completa los datos de Google en .env.docker.'
