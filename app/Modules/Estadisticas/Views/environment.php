<?php use App\Modules\Estadisticas\Services\Physics;

?>
<section class="nuva-card tournament-panel">
    <h2 class="section-title">Disponibles para los torneos</h2>
    <p class="environment-muted">Activa las estadísticas que podrán usar los torneos del entorno. Después, elígelas en la pestaña Estadísticas de cada torneo. Desactivarlas no borra los datos guardados.</p>
    <form method="post" class="environment-form">
        <?php nuva_csrf_field(); ?>
        <input type="hidden" name="action" value="stat_environment">
        <input type="hidden" name="revision" value="<?= (int)$catalogue['_revision'] ?>">
        <?php foreach (Physics::TEMPLATES as $code => $template): ?>
        <label class="environment-check">
            <input name="templates[]" type="checkbox" value="<?= $code ?>" <?= in_array($code, $catalogue['templates'], true) ? 'checked' : '' ?>> <?= nuva_escape($template['name']) ?></label>
        <?php endforeach; ?>
        <?php foreach ($definitions as $definition): ?>
        <label class="environment-check">
            <input name="manual[]" type="checkbox" value="<?= (int)$definition['id'] ?>" <?= in_array((string)$definition['id'], array_map('strval', $catalogue['manual']), true) ? 'checked' : '' ?>> <?= nuva_escape($definition['name']) ?> · <?= $definition['target'] === 'team' ? 'Equipos' : 'Personas' ?> · <?= nuva_escape($definition['unit'] ?: 'Sin unidad') ?> · <?= nuva_escape($rules[$definition['rule']]) ?></label>
        <?php endforeach; ?>
        <button class="nuva-btn">Guardar cambios</button>
    </form>
</section>
<section class="nuva-card tournament-panel">
    <h2 class="section-title">Crear una estadística manual</h2>
    <p class="environment-muted">Puedes registrar asistencias, faltas, puntos o tiempos. Elige si quieres sumar los valores, calcular un promedio o mostrar el mayor, el menor o el último.</p>
    <form method="post" class="environment-form">
        <?php nuva_csrf_field(); ?>
        <input type="hidden" name="action" value="stat_definition">
        <div class="tournament-fields">
            <label class="environment-field">Nombre<input name="name" maxlength="80" required>
            </label>
            <label class="environment-field">Unidad (opcional)<input name="unit" maxlength="50" placeholder="Ej.: puntos, s, %">
            </label>
            <label class="environment-field tournament-span">¿Para qué sirve?<textarea name="description" maxlength="500" required rows="2">
                </textarea>
            </label>
            <label class="environment-field">Tipo de número<select name="data_type">
                    <option value="integer">Sin decimales (1, 2, 3…)</option>
                    <option value="decimal">Con decimales (1,5; 2,25…)</option>
                </select>
            </label>
            <label class="environment-field">Se aplica a<select name="target">
                    <option value="person">Personas inscritas</option>
                    <option value="team">Equipos inscritos</option>
                </select>
            </label>
            <label class="environment-field">Cómo resumir varios registros<select name="rule">
                    <?php foreach ($rules as $value => $label): ?>
                    <option value="<?= $value ?>"><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <button class="environment-button">Crear estadística</button>
        <p class="environment-muted">Después de crearla, actívala en las opciones del entorno y en cada torneo donde quieras usarla.</p>
    </form>
</section>
