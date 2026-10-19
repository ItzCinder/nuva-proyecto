<?php

// En Apache se puede usar .env. En Docker se reciben variables del contenedor.
function nuva_load_environment(): void
{
    $values = [];
    $path = dirname(__DIR__, 2) . '/.env';
    if (is_file($path)) {
        $values = parse_ini_file($path, false, INI_SCANNER_RAW);
        if ($values === false) {
            throw new RuntimeException('No se pudo leer la configuración de Nuva.');
        }
    }

    $keys = [
        'DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASSWORD', 'DB_CHARSET',
        'GOOGLE_CLIENT_ID', 'GOOGLE_CLIENT_SECRET', 'GOOGLE_REDIRECT_URI',
        'SESSION_SECURE_COOKIE', 'LOAD_DEMO_DATA',
    ];
    foreach ($keys as $key) {
        $containerValue = getenv($key);
        if ($containerValue !== false) {
            $values[$key] = $containerValue;
        }
    }
    foreach ($values as $key => $value) {
        $_ENV[$key] = $value;
    }
}
