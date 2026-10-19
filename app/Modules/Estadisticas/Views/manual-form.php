<?php
$summaryHelp = [
    'latest' => 'Nuva mostrará el valor más reciente.',
    'sum' => 'Nuva sumará los valores registrados.',
    'average' => 'Nuva calculará el promedio de los valores.',
    'min' => 'Nuva mostrará el menor valor registrado.',
    'max' => 'Nuva mostrará el mayor valor registrado.',
];
$editingManual = !empty($manualEdit);
$manualValues = [
    'record_id' => $manualEdit['record_id'] ?? '',
    'revision' => $manualEdit['_revision'] ?? 0,
    'stat_type_id' => $manualEdit['stat_type_id'] ?? '',
    'person_id' => '',
    'team_id' => '',
    'value' => $manualEdit['value'] ?? '',
    'date' => (new DateTimeImmutable('now', new DateTimeZone('America/Montevideo')))->format('Y-m-d\TH:i'),
    'reason' => '',
];
if ($editingManual) {
    $manualValues['date'] = nuva_local_date($manualEdit['date'], 'Y-m-d\TH:i');
    if ($manualEdit['definition']['target'] === 'team') {
        $manualValues['team_id'] = $manualEdit['target_id'];
    } else {
        $manualValues['person_id'] = $manualEdit['target_id'];
    }
}
$retryManual = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'stat_manual';
if ($retryManual) {
    // Si hay un error, conservar lo escrito y abrir el formulario en lugar de borrarlo.
    foreach ($manualValues as $key => $value) {
        $manualValues[$key] = '';
        if (is_string($_POST[$key] ?? null)) {
            $manualValues[$key] = $_POST[$key];
        }
    }
    $editingManual = $manualValues['record_id'] !== '';
}
?>
<details id="manual-form" class="nuva-card tournament-panel" open>
    <summary class="section-title"><?= $editingManual ? 'Corregir estadística manual' : 'Registrar estadística manual' ?></summary>
    <form method="post" class="environment-form" data-manual-form>
        <?php nuva_csrf_field(); ?>
        <input name="action" type="hidden" value="stat_manual">
        <input name="record_id" type="hidden" value="<?= nuva_escape($manualValues['record_id']) ?>">
        <input name="revision" type="hidden" value="<?= (int) $manualValues['revision'] ?>">
        <p class="environment-muted" data-manual-help>Elige la estadística, la persona o el equipo y escribe el valor que registraste.</p>
        <div class="tournament-fields">
            <label class="environment-field">Estadística
                <select name="stat_type_id" required>
                    <?php foreach ($activeDefinitions as $definition): ?>
                        <option value="<?= (int) $definition['id'] ?>" data-target="<?= $definition['target'] ?>" data-number-type="<?= nuva_escape($definition['data_type'] ?? 'decimal') ?>" data-description="<?= nuva_escape($definition['description'] . ' ' . $summaryHelp[$definition['rule']]) ?>" data-unit="<?= nuva_escape($definition['unit']) ?>" <?= (string) $manualValues['stat_type_id'] === (string) $definition['id'] ? 'selected' : '' ?>>
                            <?= nuva_escape($definition['name'] . ' · ' . ($definition['unit'] ?: 'Sin unidad')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="environment-field" data-manual-person>Participante
                <select name="person_id">
                    <?php foreach ($participants as $participant): ?>
                        <option value="<?= (int) $participant['id'] ?>" <?= (string) $manualValues['person_id'] === (string) $participant['id'] ? 'selected' : '' ?>><?= nuva_escape($participant['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="environment-field" data-manual-team>Equipo
                <select name="team_id">
                    <?php foreach ($teams as $team): ?>
                        <option value="<?= (int) $team['id'] ?>" <?= (string) $manualValues['team_id'] === (string) $team['id'] ? 'selected' : '' ?>><?= nuva_escape($team['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="environment-field"><span>Valor <span data-manual-unit></span></span>
                <input type="number" step="0.00000001" name="value" required value="<?= nuva_escape($manualValues['value']) ?>">
            </label>
            <label class="environment-field">Fecha y hora
                <input type="datetime-local" name="date" required value="<?= nuva_escape($manualValues['date']) ?>">
            </label>
        </div>
        <?php if ($editingManual): ?>
            <label class="environment-field">Motivo de la corrección
                <input name="reason" maxlength="500" required value="<?= nuva_escape($manualValues['reason']) ?>">
            </label>
        <?php endif; ?>
        <button class="environment-button">Guardar valor manual</button>
    </form>
</details>
