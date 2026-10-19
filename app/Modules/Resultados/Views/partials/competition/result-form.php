<?php
$resultButton = 'Registrar resultado';
if ($match['status'] === 'Finalizado') {
    $resultButton = 'Corregir resultado';
}
?>
<details>
    <summary><?= $resultButton ?></summary>
    <form method="post" class="environment-form competition-result-form">
        <?php nuva_csrf_field(); ?>
        <input type="hidden" name="action" value="save_result">
        <input type="hidden" name="match_id" value="<?= (int) $match['id'] ?>">
        <input type="hidden" name="revision" value="<?= (int) $match['revision'] ?>">
        <div class="competition-score-inputs">
            <label class="environment-field">
                <?= nuva_escape($tournament['score_label'] . ' · ' . $match['name_1']) ?>
                <input type="number" name="score_1" step="0.01" min="-999999.99" max="999999.99" value="<?= nuva_escape($match['score_1'] ?? '') ?>">
            </label>
            <label class="environment-field">
                <?= nuva_escape($tournament['score_label'] . ' · ' . $match['name_2']) ?>
                <input type="number" name="score_2" step="0.01" min="-999999.99" max="999999.99" value="<?= nuva_escape($match['score_2'] ?? '') ?>">
            </label>
        </div>
        <label class="environment-field">
            Decisión
            <select name="outcome" required>
                <option value="">Selecciona el resultado</option>
                <option value="1" <?php if ($selectedOutcome === '1') echo 'selected'; ?>>Gana <?= nuva_escape($match['name_1']) ?></option>
                <option value="2" <?php if ($selectedOutcome === '2') echo 'selected'; ?>>Gana <?= nuva_escape($match['name_2']) ?></option>
                <?php if ($tournament['allow_draws'] && $tournament['format_code'] !== 'elimination'): ?>
                <option value="draw" <?php if ($selectedOutcome === 'draw') echo 'selected'; ?>>Empate</option>
                <?php endif; ?>
            </select>
        </label>
        <label class="environment-field">
            Detalle o motivo
            <textarea name="notes" maxlength="500" rows="2" placeholder="Obligatorio en correcciones, decisiones sin marcador o desempates."><?= nuva_escape($match['notes']) ?></textarea>
        </label>
        <button class="nuva-btn">Guardar resultado</button>
    </form>
    <?php if ($match['status'] === 'Finalizado'): ?>
    <form method="post" class="environment-form competition-result-form" data-confirm="¿Anular este resultado? La clasificación se recalculará.">
        <?php nuva_csrf_field(); ?>
        <input type="hidden" name="action" value="clear_result">
        <input type="hidden" name="match_id" value="<?= (int) $match['id'] ?>">
        <input type="hidden" name="revision" value="<?= (int) $match['revision'] ?>">
        <label class="environment-field">
            Motivo de anulación
            <input name="notes" required maxlength="500">
        </label>
        <button class="environment-button">Anular resultado</button>
    </form>
    <?php endif; ?>
</details>
