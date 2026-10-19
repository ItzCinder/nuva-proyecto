<?php
?>
<section class="nuva-card tournament-panel" id="statistics-history">
    <h2 class="section-title">Historial del registro</h2>
    <p class="environment-muted">Abre una edición para consultar cómo estaba guardada.</p>
    <?php foreach ($history as $record): ?>
    <details class="statistics-history">
        <summary>Edición <?= (int)$record['edition'] ?><?= !empty($record['invalid']) ? ' · Anulada' : '' ?> · <?= nuva_escape(nuva_local_date($record['changed_at'])) ?></summary>
        <p class="environment-muted"><?= nuva_escape($record['actor_name']) ?></p>
        <?php if (isset($record['outputs'])): 
            require __DIR__ . '/trial-results.php';
        else: ?>
        <p><?= nuva_escape($record['target_name'] . ' · ' . $record['definition']['name']) ?>: <?= nuva_stat_number($record['value']) ?> <?= nuva_escape($record['definition']['unit']) ?></p>
        <?php endif; ?>
        <?php if ($record['reason']): ?>
        <p>Motivo: <?= nuva_escape($record['reason']) ?></p>
        <?php endif; ?>
    </details>
    <?php endforeach; ?>
</section>
