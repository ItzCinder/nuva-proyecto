<?php

// Una sola entrada para toda la demostración. Nunca se puede ejecutar por HTTP.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once __DIR__ . '/DemoStories.php';
require_once __DIR__ . '/DemoData.php';
require_once __DIR__ . '/DemoStatistics.php';
require_once __DIR__ . '/DemoEnrichment.php';
nuva_load_environment();

$ownerId = 0;
foreach (array_slice($argv, 1) as $argument) {
    if (!str_starts_with($argument, '--owner-id=')) {
        fwrite(STDERR, "Uso: php demostracion/cargar.php [--owner-id=ID_DE_TU_CUENTA]\n");
        exit(1);
    }
    $ownerId = nuva_integer(substr($argument, strlen('--owner-id=')), 'Cuenta responsable');
}

$database = Database::getConnection();
try {
    $database->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
    $database->beginTransaction();
    $database->query('SELECT id FROM roles ORDER BY id FOR UPDATE')->fetchAll();
    $demo = new DemoData();
    $created = $demo->load($ownerId);
    $enrichment = new DemoEnrichment();
    $enrichment->load($ownerId);
    $database->commit();
    echo $created ? "Demostración completa: 64 usuarios ficticios, 6 entornos y 54 torneos.\n" : "El conjunto ya existía. Se revisó su ampliación sin duplicar entornos ni torneos.\n";
    $labels = ['entornos' => 'Entornos ampliados', 'torneos' => 'Torneos completos', 'pruebas' => 'Pruebas físicas completas', 'valores' => 'Valores manuales completos'];
    foreach ($enrichment->counts as $type => $count) {
        echo $labels[$type] . ': ' . $count . ".\n";
    }
    echo "Los ejemplos incluyen relatos, actas, controles de entrenamiento e historial de mediciones.\n";
    foreach ($demo->invitations as $invitation) {
        echo $invitation['name'] . ': código ' . $invitation['code'] . "\n";
    }
} catch (Throwable $exception) {
    if ($database->inTransaction()) {
        $database->rollBack();
    }
    nuva_log_error('Demostración completa', $exception);
    fwrite(STDERR, "No se pudo cargar la demostración. No se guardaron cambios parciales. Revisa la conexión y el esquema de Nuva.\n");
    exit(1);
}
