<?php use App\Modules\Estadisticas\Services\Physics;

?>
<div class="statistics-table-wrap">
    <table class="statistics-table statistics-list">
        <caption class="visually-hidden">Pruebas y datos principales. Abre una prueba para ver todos sus resultados.</caption>
        <thead>
            <tr>
                <th>Participante y prueba</th>
                <th>Fecha</th>
                <th>Dato principal</th>
                <th>
                    <span class="visually-hidden">Detalle</span>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($trialRows as $record): $metric = array_key_first($record['outputs']);
                $main = $metric !== null ? $record['outputs'][$metric] : null; ?>
            <tr>
                <th scope="row">
                    <strong><?= nuva_escape($record['participant_name']) ?></strong>
                    <small title="<?= nuva_escape($record['label']) ?>"><?= nuva_escape($record['label']) ?><?= $record['invalid'] ? ' · Anulada' : '' ?><?= $record['demonstration'] ? ' · Ejemplo' : '' ?></small>
                </th>
                <td><?= nuva_escape(nuva_local_date($record['date'])) ?></td>
                <td>
                    <?php if ($main): $shown = nuva_measurement($main); ?>
                    <strong><?= $shown[0] ?> <?= nuva_escape($main['unit']) ?></strong>
                    <small><?= nuva_escape(Physics::METRICS[$metric][0]) ?><?= $record['invalid'] ? ' · Anulado' : '' ?></small>
                    <?php else: ?>
                    Sin indicador visible
                    <?php endif; ?>
                </td>
                <td>
                    <a class="nuva-anchor" aria-label="Ver <?= nuva_escape($record['participant_name'] . ' · ' . $record['label']) ?>" href="<?= nuva_escape(nuva_statistics_link($params, ['view' => 'trials','record' => $record['record_id']])) ?>#statistics-detail">Ver prueba →</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
