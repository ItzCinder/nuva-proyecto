<?php
use app\Models\Tournament;
use app\Models\Role;

$isEdit = $mode === 'edit';
$back = $isEdit ? '?page=tournament&id=' . (int)$tournament['id'] : '?page=environment&id=' . (int)$workspace['id'] . '&section=tournaments';
$disciplineOptions = [];
foreach (Tournament::DISCIPLINES as $categoryDisciplines) {
    foreach ($categoryDisciplines as $disciplineName) {
        $disciplineOptions[] = $disciplineName;
    }
}
$currentDiscipline = nuva_form_value('discipline', $isEdit && in_array($tournament['discipline'], $disciplineOptions, true) ? $tournament['discipline'] : '');
$customDiscipline = nuva_form_value('custom_discipline', $isEdit && !in_array($tournament['discipline'], $disciplineOptions, true) ? $tournament['discipline'] : '');
if ($customDiscipline !== '' && $currentDiscipline === '') {
    $currentDiscipline = 'other';
}

$format = nuva_form_value('format', $isEdit ? $tournament['format_code'] : '');
$hasTeam = nuva_form_value('has_team', $isEdit ? (string)$tournament['has_team'] : '0');
$manager = $workspace['role_name'] !== Role::ORGANIZER;
?>
<a class="nuva-back nuva-anchor tournament-back" href="<?= nuva_escape($back) ?>"><?= $isEdit ? 'Volver al torneo' : 'Torneos del entorno' ?></a>
<header class="tournament-heading">
    <p class="environment-eyebrow"><?= nuva_escape($workspace['name']) ?></p>
    <h1><?= $isEdit ? 'Configurar torneo' : 'Crear torneo' ?></h1>
    <p class="environment-muted">Define los datos y reglas de la competición. Podrás preparar sus inscripciones después de guardarla.</p>
</header>
<?php if (!$modules): ?>
<p class="environment-notice environment-notice--error">Este entorno no tiene módulos habilitados. Activa al menos uno desde la configuración del entorno.</p>
<?php endif; ?>
<form method="post" class="tournament-form" data-tournament-form data-started="<?= !empty($started) ? '1' : '0' ?>">
    <input type="hidden" name="csrf_token" value="<?= nuva_escape($_SESSION['csrf_token']) ?>">
    <section class="nuva-card tournament-panel">
        <h2 class="section-title">Información básica</h2>
        <div class="tournament-fields">
            <label class="environment-field">Nombre del torneo<input name="name" required maxlength="120" value="<?= nuva_escape(nuva_form_value('name', $tournament['name'] ?? '')) ?>" placeholder="Ej.: Copa de primavera">
            </label>
            <label class="environment-field tournament-span">Descripción<textarea name="description" maxlength="500" rows="3" placeholder="Cuenta de qué trata esta competición."><?= nuva_escape(nuva_form_value('description', $tournament['description'] ?? '')) ?></textarea>
            </label>
            <label class="environment-field">Disciplina<select name="discipline" required data-discipline>
                    <option value="">Selecciona una disciplina</option>
                    <?php foreach (Tournament::DISCIPLINES as $category => $names): ?>
                    <optgroup label="<?= nuva_escape(Tournament::CATEGORIES[$category]) ?>">
                        <?php foreach ($names as $name): ?>
                        <option value="<?= nuva_escape($name) ?>" <?= $currentDiscipline === $name ? 'selected' : '' ?>><?= nuva_escape($name) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                    <?php endforeach; ?>
                    <option value="other" <?= $currentDiscipline === 'other' ? 'selected' : '' ?>>Otra disciplina</option>
                </select>
            </label>
            <label class="environment-field" data-custom-discipline <?= $currentDiscipline === 'other' ? '' : 'hidden' ?>>¿Cuál es la disciplina?<input name="custom_discipline" maxlength="80" value="<?= nuva_escape($customDiscipline) ?>" placeholder="Ej.: Natación">
            </label>
            <p class="environment-muted tournament-span tournament-hint">Al elegir «Otra disciplina», aparecerá en la categoría «Otros» de los filtros.</p>
            <label class="environment-field">Inicio<input type="datetime-local" name="start_date" required value="<?= nuva_escape(nuva_form_value('start_date', $isEdit ? nuva_local_date($tournament['start_date'], 'Y-m-d\TH:i') : '')) ?>">
            </label>
            <label class="environment-field">Finalización<input type="datetime-local" name="end_date" required value="<?= nuva_escape(nuva_form_value('end_date', $isEdit ? nuva_local_date($tournament['end_date'], 'Y-m-d\TH:i') : '')) ?>">
            </label>
        </div>
    </section>
    <section class="nuva-card tournament-panel">
        <h2 class="section-title">Formato y participantes</h2>
        <div class="tournament-fields">
            <label class="environment-field">Formato<select name="format" required data-format>
                    <option value="">Selecciona un formato habilitado</option>
                    <?php foreach ($modules as $module): ?>
                    <option value="<?= nuva_escape($module['code']) ?>" <?= $format === $module['code'] ? 'selected' : '' ?>><?= nuva_escape($module['name']) ?></option>
                    <?php endforeach; ?>
                    <?php if ($isEdit && !in_array($format, array_column($modules, 'code'), true)): ?>
                    <option value="<?= nuva_escape($format) ?>" selected><?= nuva_escape($tournament['format_name']) ?> (deshabilitado para nuevos torneos)</option>
                    <?php endif; ?>
                </select>
            </label>
            <label class="environment-field">Modalidad<select name="has_team" data-has-team>
                    <option value="0" <?= $hasTeam === '0' ? 'selected' : '' ?>>Individual</option>
                    <option value="1" <?= $hasTeam === '1' ? 'selected' : '' ?>>Por equipos</option>
                </select>
            </label>
            <label class="environment-field">Cupo de <?= $hasTeam === '1' ? 'equipos' : 'participantes' ?><input type="number" name="max_participants" min="2" max="256" required value="<?= nuva_escape(nuva_form_value('max_participants', $isEdit ? (string)$tournament['max_participants'] : '16')) ?>">
            </label>
            <label class="environment-field" data-team-size <?= $hasTeam === '1' ? '' : 'hidden' ?>>Máximo de integrantes por equipo<input type="number" name="team_size" min="2" max="30" value="<?= nuva_escape(nuva_form_value('team_size', $isEdit ? (string)$tournament['team_size'] : '5')) ?>">
            </label>
            <label class="environment-field" data-rule="league" <?= $format === 'league' ? '' : 'hidden' ?>>Vueltas de la liga<select name="league_legs">
                    <option value="1" <?= nuva_form_value('league_legs', (string)($tournament['league_legs'] ?? 1)) === '1' ? 'selected' : '' ?>>Una vuelta</option>
                    <option value="2" <?= nuva_form_value('league_legs', (string)($tournament['league_legs'] ?? 1)) === '2' ? 'selected' : '' ?>>Ida y vuelta</option>
                </select>
            </label>
            <label class="environment-field" data-rule="swiss" <?= $format === 'swiss' ? '' : 'hidden' ?>>Rondas del sistema suizo<input type="number" name="swiss_rounds" min="2" max="12" value="<?= nuva_escape(nuva_form_value('swiss_rounds', (string)($tournament['swiss_rounds'] ?? 4))) ?>">
            </label>
            <label class="environment-check tournament-span" data-rule="elimination" <?= $format === 'elimination' ? '' : 'hidden' ?>>
                <input type="checkbox" name="third_place" value="1" <?= ($_SERVER['REQUEST_METHOD'] === 'POST' ? isset($_POST['third_place']) : !empty($tournament['third_place'])) ? 'checked' : '' ?>> Preparar partido por el tercer puesto</label>
            <p class="environment-muted tournament-span" data-format-help aria-live="polite">
            </p>
        </div>
    </section>
    <section class="nuva-card tournament-panel">
        <h2 class="section-title">Marcador y puntuación</h2>
        <p class="environment-muted">Adapta estas reglas a tu disciplina. El marcador del encuentro y los puntos de clasificación son independientes. Puedes registrar una decisión sin marcador, explicando el motivo.</p>
        <?php if (!empty($started)): ?>
        <p class="environment-notice">Ya hay encuentros: conserva las reglas y fechas actuales. El estado se actualiza al cerrar o reabrir rondas.</p>
        <?php endif; ?>
        <div class="tournament-fields">
            <label class="environment-field">Nombre del marcador<input name="score_label" maxlength="40" required value="<?= nuva_escape(nuva_form_value('score_label', $tournament['score_label'] ?? 'Marcador')) ?>" placeholder="Puntos, sets, segundos…">
            </label>
            <label class="environment-field">Criterio del marcador<select name="score_order">
                    <option value="higher">Gana el valor mayor</option>
                    <option value="lower" <?= nuva_form_value('score_order', $tournament['score_order'] ?? '') === 'lower' ? 'selected' : '' ?>>Gana el valor menor</option>
                </select>
            </label>
            <?php foreach (['points_win' => 'Puntos por victoria','points_draw' => 'Puntos por empate','points_loss' => 'Puntos por derrota'] as $key => $label): ?>
            <label class="environment-field"><?= $label ?><input name="<?= $key ?>" type="number" min="0" max="9999.99" step="0.01" required value="<?= nuva_escape(nuva_form_value($key, (string)($tournament[$key] ?? ['points_win' => 3,'points_draw' => 1,'points_loss' => 0][$key]))) ?>">
            </label>
            <?php endforeach; ?>
            <label class="environment-check">
                <input name="allow_draws" type="checkbox" value="1" <?= ($_SERVER['REQUEST_METHOD'] === 'POST' ? isset($_POST['allow_draws']) : ($tournament['allow_draws'] ?? true)) ? 'checked' : '' ?>> Admitir empates (excepto eliminación)</label>
        </div>
        <p class="environment-muted">Liga: puntos, diferencia del marcador y victorias. Suizo: puntos, Buchholz, victorias y diferencia. El orden del sorteo inicial resuelve la igualdad restante. Un pase libre en suizo otorga puntos de victoria; en liga es descanso sin puntos.</p>
    </section>
    <section class="nuva-card tournament-panel">
        <h2 class="section-title">Organización y visibilidad</h2>
        <div class="tournament-fields">
            <?php if ($manager): ?>
            <label class="environment-field">Organizador asignado<select name="organizer_id">
                    <option value="">Sin asignar todavía</option>
                    <?php foreach ($organizers as $person): ?>
                    <option value="<?= (int)$person['id'] ?>" <?= nuva_form_value('organizer_id', (string)($tournament['organizer_id'] ?? '')) === (string)$person['id'] ? 'selected' : '' ?>><?= nuva_escape($person['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="environment-field">Estado<select name="status">
                    <?php foreach (Tournament::STATUS as $status): ?>
                    <option value="<?= nuva_escape($status) ?>" <?= nuva_form_value('status', $tournament['status'] ?? 'Planificado') === $status ? 'selected' : '' ?>><?= nuva_escape($status) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="environment-check tournament-span">
                <input type="checkbox" name="published" value="1" <?= ($_SERVER['REQUEST_METHOD'] === 'POST' ? isset($_POST['published']) : !empty($tournament['published'])) ? 'checked' : '' ?>> Mostrar torneo en la consulta pública</label>
            <?php else: ?>
            <p class="environment-muted tournament-span">El administrador gestiona la asignación, el estado y la visibilidad pública.</p>
            <?php endif; ?>
        </div>
    </section>
    <div class="environment-actions">
        <button class="nuva-btn" type="submit" <?= !$modules && !$isEdit ? 'disabled' : '' ?>><?= $isEdit ? 'Guardar cambios' : 'Crear torneo' ?></button>
        <a class="nuva-anchor" href="<?= nuva_escape($back) ?>">Cancelar</a>
    </div>
</form>
