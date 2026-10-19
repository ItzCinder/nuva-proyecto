<?php
?>
<?php $valid = [];
foreach ($browse['trials'] as $record) {
    if (!$record['invalid']) {
        $valid[] = $record;
    }
}
$people = array_unique(array_column($valid, 'participant_id'));
$measures = [];
foreach ($valid as $record) {
    foreach (array_keys($record['outputs']) as $key) {
        $measures[$key] = true;
    }
} ?>
<div class="statistics-overview">
    <div>
        <strong><?= count($valid) ?></strong>
        <span>Pruebas válidas</span>
    </div>
    <div>
        <strong><?= count($people) ?></strong>
        <span>Participantes con pruebas</span>
    </div>
    <div>
        <strong><?= count($measures) ?></strong>
        <span>Medidas disponibles</span>
    </div>
</div>
<section class="nuva-card tournament-panel">
    <div class="statistics-section-heading">
        <div>
            <h2 class="section-title">Últimas pruebas</h2>
            <p class="environment-muted">Abre una prueba para ver sus medidas y explicaciones.</p>
        </div>
        <a class="nuva-anchor" href="<?= nuva_escape(nuva_statistics_link($params, ['view' => 'trials'])) ?>#statistics-browser">Ver todas →</a>
    </div>
    <?php if ($valid): $trialRows = array_slice($valid, 0, 5);
        require __DIR__ . '/trial-table.php';
    else: ?>
    <p class="statistics-empty">No hay pruebas con estos filtros. Prueba otra selección o vuelve cuando se haya registrado una prueba.</p>
    <?php endif; ?>
</section>
