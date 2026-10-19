<?php
use App\Modules\Estadisticas\Services\Physics;

$inputLabels = [
    'distance' => 'Distancia del recorrido (m)',
    'time' => 'Tiempo del recorrido (s)',
    'segment_distance' => 'Distancia del tramo inicial (m)',
    'segment_time' => 'Tiempo del tramo inicial (s)',
    'mass' => 'Masa (kg)',
    'acceleration' => 'Aceleración medida (m/s²)',
    'speed' => 'Rapidez final medida (m/s)',
    'initial_speed' => 'Rapidez inicial medida (m/s)',
    'force' => 'Fuerza neta medida (N)',
    'work_distance' => 'Desplazamiento (m)',
    'angle' => 'Ángulo (°)'
];
?>
<?php require __DIR__ . '/browse.php'; ?>
