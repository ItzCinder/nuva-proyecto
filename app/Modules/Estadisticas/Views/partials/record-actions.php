<?php
$editParameter = 'edit';
$editAnchor = '#trial-form';
$invalidateAction = 'stat_invalidate';
$recordLabel = 'prueba';
$historyKind = null;
if ($isManualRecord) {
    $editParameter = 'manual_edit';
    $editAnchor = '#manual-form';
    $invalidateAction = 'stat_manual_invalidate';
    $recordLabel = 'valor';
    $historyKind = 'manual';
}
?>
<div class="environment-actions">
    <a class="nuva-anchor" href="<?= nuva_escape(nuva_statistics_link($params, ['view' => 'history', 'history' => $record['record_id'], 'kind' => $historyKind])) ?>#statistics-history">Ver historial →</a>
    <?php if ($manager && !$record['invalid']): ?>
    <a class="nuva-anchor" href="<?= nuva_escape(nuva_statistics_link($params, [$editParameter => $record['record_id']])) ?><?= $editAnchor ?>">Corregir</a>
    <details>
        <summary>Anular <?= $recordLabel ?></summary>
        <form method="post" class="environment-form">
            <?php nuva_csrf_field(); ?>
            <input type="hidden" name="action" value="<?= $invalidateAction ?>">
            <input type="hidden" name="record_id" value="<?= nuva_escape($record['record_id']) ?>">
            <input type="hidden" name="revision" value="<?= (int) $record['_revision'] ?>">
            <label class="environment-field">Motivo
                <input name="reason" required maxlength="500">
            </label>
            <button class="environment-button">Confirmar anulación</button>
        </form>
    </details>
    <?php endif; ?>
</div>
