<?php
use App\Modules\Estadisticas\Services\StatisticsBrowser;

?>
<section class="nuva-card tournament-panel" id="statistics-manual">
    <h2 class="section-title">Estadísticas manuales</h2>
    <?php if ($browse['manualTypes']): ?>
    <form method="get" action="#statistics-browser" class="statistics-filters">
        <input name="page" type="hidden" value="tournament-statistics">
        <input name="id" type="hidden" value="<?= (int)$tournament['id'] ?>">
        <input name="view" type="hidden" value="manual">
        <input name="origin" type="hidden" value="<?= nuva_escape($browse['origin']) ?>">
        <label class="environment-field">Estadística<select name="stat">
                <?php foreach ($browse['manualTypes'] as $sid => $name): ?>
                <option value="<?= (int)$sid ?>" <?= $sid === $browse['manualType'] ? 'selected' : '' ?>><?= nuva_escape($name) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button class="environment-button">Ver estadística</button>
    </form>
    <?php $manualSummaries = [];
    foreach ($summaries as $record) {
        if ((int) $record['stat_type_id'] !== $browse['manualType']) {
            continue;
        }
        if ($browse['origin'] !== 'all' && (bool) $record['demonstration'] !== ($browse['origin'] === 'demo')) {
            continue;
        }
        $manualSummaries[] = $record;
    }
        $page = StatisticsBrowser::page($manualSummaries, $_GET['p'] ?? ''); ?>
    <div class="statistics-table-wrap">
        <table class="statistics-table">
            <thead>
                <tr>
                    <th>Persona o equipo</th>
                    <th>Resumen</th>
                    <th>Cómo se calcula</th>
                    <th>Registros</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($page['rows'] as $record): ?>
                <tr>
                    <th scope="row"><?= nuva_escape($record['target_name']) ?><?= $record['demonstration'] ? ' · Ejemplo' : '' ?></th>
                    <td><?= nuva_stat_number($record['summary']) ?> <?= nuva_escape($record['definition']['unit']) ?></td>
                    <td><?= nuva_escape($rules[$record['definition']['rule']]) ?></td>
                    <td><?= (int)$record['count'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php require __DIR__ . '/pagination.php'; ?>
    <details class="statistics-subpanel">
        <summary>Últimos valores guardados</summary>
        <div class="statistics-table-wrap">
            <table class="statistics-table">
                <thead>
                    <tr>
                        <th>Persona o equipo</th>
                        <th>Valor</th>
                        <th>Fecha</th>
                        <th>Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $manualPage = StatisticsBrowser::page($browse['manual'], $_GET['values_page'] ?? '');
        foreach ($manualPage['rows'] as $record): ?>
                    <tr>
                        <th scope="row"><?= nuva_escape($record['target_name']) ?><?= $record['invalid'] ? ' · Anulado' : '' ?><?= $record['demonstration'] ? ' · Ejemplo' : '' ?></th>
                        <td><?= nuva_stat_number($record['value']) ?> <?= nuva_escape($record['definition']['unit']) ?></td>
                        <td><?= nuva_escape(nuva_local_date($record['date'])) ?></td>
                        <td>
                            <a class="nuva-anchor" href="<?= nuva_escape(nuva_statistics_link($params, ['view' => 'manual','record' => $record['record_id'],'kind' => 'manual'])) ?>#statistics-detail">Ver →</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($manualPage['pages'] > 1): ?>
        <nav class="statistics-pagination" aria-label="Páginas de valores">
            <?php if ($manualPage['current'] > 1): ?>
            <a class="nuva-anchor" href="<?= nuva_escape(nuva_statistics_link($params, ['values_page' => $manualPage['current'] - 1])) ?>#statistics-manual">← Anteriores</a>
            <?php endif; ?>
            <span>Página <?= $manualPage['current'] ?> de <?= $manualPage['pages'] ?></span>
            <?php if ($manualPage['current'] < $manualPage['pages']): ?>
            <a class="nuva-anchor" href="<?= nuva_escape(nuva_statistics_link($params, ['values_page' => $manualPage['current'] + 1])) ?>#statistics-manual">Siguientes →</a>
            <?php endif; ?>
        </nav>
        <?php endif; ?>
    </details>
    <?php else: ?>
    <p class="statistics-empty">No hay valores manuales con estos filtros.</p>
    <?php endif; ?>
</section>
<?php if ($browse['manualSelected']): $record = $browse['manualSelected']; ?>
<article class="nuva-card tournament-panel" id="statistics-detail">
    <h2 class="section-title"><?= nuva_escape($record['target_name'] . ' · ' . $record['definition']['name']) ?></h2>
    <p><?= nuva_escape($record['definition']['description']) ?></p>
    <p><?= nuva_stat_number($record['value']) ?> <?= nuva_escape($record['definition']['unit']) ?> · <?= nuva_escape(nuva_local_date($record['date'])) ?><?= $record['invalid'] ? ' · Anulado' : '' ?><?= $record['demonstration'] ? ' · Ejemplo' : '' ?></p>
    <?php 
    $isManualRecord = true;
    require __DIR__ . '/record-actions.php'; ?>
</article>
<?php endif; ?>
