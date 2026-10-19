<?php use App\Modules\Estadisticas\Services\Physics;

?>
<p class="environment-muted"><?= nuva_escape($record['participant_name']) ?> · <?= nuva_escape(nuva_local_date($record['date'])) ?> · <?= $record['demonstration'] ? 'Datos de ejemplo' : 'Datos reales' ?></p>
<?php
$mainResults = [];
$otherResults = [];
foreach ($record['outputs'] as $metric => $measurement) {
    if (in_array($metric, ['speed', 'force', 'kinetic'], true)) {
        $mainResults[$metric] = $measurement;
    } else {
        $otherResults[$metric] = $measurement;
    }
}
?>
<div class="statistics-results">
    <?php foreach ($mainResults as $metric => $measurement): require __DIR__ . '/measurement-result.php'; endforeach; ?>
</div>
<?php if ($otherResults): ?>
<details class="statistics-subpanel">
    <summary>Otros resultados de la prueba (<?= count($otherResults) ?>)</summary>
    <p class="environment-muted">Puedes consultar aquí el cambio de rapidez, de energía o el trabajo, según lo que registraste.</p>
    <div class="statistics-results">
        <?php foreach ($otherResults as $metric => $measurement): require __DIR__ . '/measurement-result.php'; endforeach; ?>
    </div>
</details>
<?php endif; ?>
<details>
    <summary>Ver mediciones y procedimiento</summary>
    <?php
    $templateNames = [];
    foreach ($record['templates'] as $templateCode) {
        $templateNames[] = Physics::TEMPLATES[$templateCode]['name'];
    }
    ?>
    <p class="environment-muted">Versión de cálculo <?= (int)$record['version'] ?> · Edición <?= (int)$record['edition'] ?></p>
    <p>Plantillas: <?= nuva_escape(implode(' · ', $templateNames)) ?>.</p>
    <?php if (in_array('dynamics', $record['templates'], true)): ?>
    <p>La aceleración para dinámica procede de <?= ($record['inputs']['acceleration_source'] ?? '') === 'measured' ? 'una medición independiente' : 'la estimación del tramo inicial desde el reposo' ?>.</p>
    <?php endif; ?>
    <?php if (in_array('energy', $record['templates'], true)): ?>
    <p>La rapidez para energía procede de <?= ($record['inputs']['speed_source'] ?? '') === 'measured' ? 'una medición local en el instante estudiado' : 'la rapidez final estimada del tramo inicial' ?>. <?= ($record['inputs']['work_mode'] ?? '') === 'measured' ? 'El trabajo utiliza una fuerza neta declarada constante, su dirección y el desplazamiento medido.' : '' ?></p>
    <?php endif; ?>
    <dl class="statistics-inputs">
        <?php foreach ($inputLabels as $key => $label): ?>
        <?php if (isset($record['inputs'][$key])): ?>
        <div>
            <dt><?= nuva_escape($label) ?></dt>
            <dd><?= nuva_stat_number($record['inputs'][$key]) ?>
                <?php if (isset($record['inputs']['u_' . $key])): ?>
                ± <?= nuva_stat_number($record['inputs']['u_' . $key]) ?>
                <?php endif; ?>
            </dd>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>
    </dl>
    <p>
        <strong>Procedimiento:</strong> <?= nuva_escape($record['procedure']) ?><br>
        <strong>Instrumentos:</strong> <?= nuva_escape($record['instrument']) ?><br>
        <strong>Condiciones:</strong> <?= nuva_escape($record['conditions']) ?></p>
    <?php if ($record['evidence']): ?>
    <a class="nuva-anchor" href="<?= nuva_escape($record['evidence']) ?>" target="_blank" rel="noopener noreferrer">Referencia de evidencia ↗</a>
    <?php endif; ?>
    <?php foreach ($record['notes'] as $note): ?>
    <p class="environment-muted"><?= nuva_escape($note) ?></p>
    <?php endforeach; ?>
</details>
