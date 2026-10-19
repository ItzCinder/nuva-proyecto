<?php
use App\Modules\Estadisticas\Services\StatisticsBrowser;

?>
<?php $page = StatisticsBrowser::page($browse['trials'], $_GET['p'] ?? ''); ?>
<section class="nuva-card tournament-panel" id="statistics-trials">
    <h2 class="section-title">Pruebas registradas <span class="statistics-count"><?= $page['total'] ?></span>
    </h2>
    <p class="environment-muted">Una fila por intento. Todos sus resultados están dentro de Ver prueba.</p>
    <?php if ($page['rows']): $trialRows = $page['rows'];
        require __DIR__ . '/trial-table.php';
        require __DIR__ . '/pagination.php';
    else: ?>
    <p class="statistics-empty">No hay pruebas con estos filtros.</p>
    <?php endif; ?>
</section>
<?php if ($browse['selected']): $record = $browse['selected']; ?>
<article class="nuva-card tournament-panel" id="statistics-detail">
    <div class="statistics-section-heading">
        <h2 class="section-title"><?= nuva_escape($record['label']) ?></h2>
        <a class="nuva-anchor" href="<?= nuva_escape(nuva_statistics_link($params, ['view' => 'trials'])) ?>#statistics-browser">Cerrar detalle</a>
    </div>
    <?php if ($record['invalid']): ?>
    <p class="environment-notice environment-notice--error">Prueba anulada: <?= nuva_escape($record['reason']) ?></p>
    <?php endif; ?>
    <?php 
    require __DIR__ . '/trial-results.php';
    
    $isManualRecord = false;
    require __DIR__ . '/record-actions.php'; ?>
</article>
<?php endif; ?>
