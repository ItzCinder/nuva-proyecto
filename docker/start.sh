#!/bin/sh
set -eu
if [ "${LOAD_DEMO_DATA:-0}" = "1" ]; then
    php /var/www/nuva/demostracion/cargar.php
fi
exec apache2-foreground
