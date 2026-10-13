<?php
// Borra NuvaBD COMPLETA, incluidas cuentas reales. Se entrega para mantenimiento local y nunca se ejecuta automáticamente.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (($argv[1] ?? '') !== '--confirmar=ELIMINAR-NuvaBD') {
    fwrite(STDERR,"Esto borra TODOS los datos de NuvaBD. Antes, guarda una copia si la necesitas.\nUso: php database/borrar_base_de_datos.php --confirmar=ELIMINAR-NuvaBD\n");
    exit(1);
}
$env=parse_ini_file(dirname(__DIR__).'/.env',false,INI_SCANNER_RAW);
if (!$env || ($env['DB_NAME'] ?? '') !== 'NuvaBD') { fwrite(STDERR,"La base configurada no es NuvaBD.\n"); exit(1); }
$dsn='mysql:host='.($env['DB_HOST'] ?? 'localhost').';port='.(int)($env['DB_PORT'] ?? 3306).';charset=utf8mb4';
$db=new PDO($dsn,$env['DB_USER'] ?? '',$env['DB_PASSWORD'] ?? '',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$db->exec('DROP DATABASE `NuvaBD`');
echo "NuvaBD eliminada.\n";
