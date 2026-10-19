#!/bin/sh
# Guarda la base, las fotos y los registros de las últimas 24 horas.
# Ejecutar una vez al día para conservar el historial aunque Docker rote sus archivos.
set -eu
cd "$(dirname "$0")/.."
umask 077
backup_directory="backups/$(date -u +%Y-%m-%d_%H-%M-%S)"
mkdir -p "$backup_directory"

# El volcado incluye workspace_audit_logs: también contiene versiones de estadísticas.
# No se incluye la contraseña en los argumentos del cliente.
docker compose --env-file .env.docker exec -T database sh -c \
    'export MYSQL_PWD="$MARIADB_ROOT_PASSWORD"; exec mariadb-dump --user=root --single-transaction --databases NuvaBD' \
    > "$backup_directory/base.sql"
gzip "$backup_directory/base.sql"
docker compose --env-file .env.docker logs --since 24h --no-color > "$backup_directory/eventos.log"
gzip "$backup_directory/eventos.log"
# Las fotos subidas viven en un volumen, separado de la imagen de Apache.
docker compose --env-file .env.docker exec -T web tar -czf - -C /var/www/nuva/public/assets/uploads environments \
    > "$backup_directory/fotos.tar.gz"
echo "Respaldo creado en $backup_directory"
