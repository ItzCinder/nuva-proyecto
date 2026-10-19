<?php
// Comprueba la conexión sin mostrar datos de configuración ni abrir una sesión.
require_once dirname(__DIR__) . '/app/bootstrap.php';
nuva_load_environment();
nuva_security_headers();
header('Content-Type: text/plain; charset=UTF-8');
try {
    $database = Database::getConnection();
    $database->query('SELECT 1');
    echo 'OK';
} catch (Throwable $exception) {
    http_response_code(503);
    echo 'No disponible';
}
