#!/bin/sh
set -eu
# Los volúmenes se montan al arrancar; preparar aquí sus permisos.
mkdir -p /var/www/nuva/public/assets/uploads/environments /var/lib/php/sessions
chown www-data:www-data /var/www/nuva/public/assets/uploads/environments /var/lib/php/sessions
chmod 755 /var/www/nuva/public/assets/uploads/environments
chmod 700 /var/lib/php/sessions

if [ "${LOAD_DEMO_DATA:-0}" = "1" ]; then
    php /var/www/nuva/demostracion/cargar.php
fi
exec apache2-foreground
