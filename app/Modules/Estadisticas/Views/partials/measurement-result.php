<?php
use App\Modules\Estadisticas\Services\Physics;

$shown = nuva_measurement($measurement);
$resultNames = ['force' => 'Fuerza del movimiento', 'kinetic' => 'Energía al final'];
?>
<div class="statistics-result">
    <strong><?= nuva_escape($resultNames[$metric] ?? Physics::METRICS[$metric][0]) ?></strong>
    <span><?= $shown[0] ?> <?= nuva_escape($measurement['unit']) ?></span>
    <small><?= nuva_escape(Physics::RESULT_HELP[$metric]) ?></small>
    <small><?= $measurement['estimated'] ? 'Estimación · ' : '' ?><?= $measurement['uncertainty'] === null ? 'No se indicó un margen completo' : 'Margen ± ' . $shown[1] . ' ' . nuva_escape($measurement['unit']) ?></small>
</div>
