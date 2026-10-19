<?php
$scheduledDate = '';
if ($match['scheduled_at']) {
    $scheduledDate = nuva_local_date($match['scheduled_at'], 'Y-m-d\TH:i');
}
?>
<details>
    <summary>Fecha y disponibilidad</summary>
    <form method="post" class="environment-form competition-result-form">
        <?php nuva_csrf_field(); ?>
        <input type="hidden" name="action" value="schedule">
        <input type="hidden" name="match_id" value="<?= (int) $match['id'] ?>">
        <input type="hidden" name="revision" value="<?= (int) $match['revision'] ?>">
        <label class="environment-field">
            Fecha y hora (Uruguay)
            <input type="datetime-local" name="scheduled_at" value="<?= nuva_escape($scheduledDate) ?>">
        </label>
        <label class="environment-check">
            <input type="checkbox" name="suspended" value="1" <?php if ($match['status'] === 'Suspendido') echo 'checked'; ?>>
            Encuentro suspendido
        </label>
        <button class="environment-button">Guardar fecha y estado</button>
    </form>
</details>
