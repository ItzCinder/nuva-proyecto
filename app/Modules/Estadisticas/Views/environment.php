<?php use App\Modules\Estadisticas\Services\Physics;

?>
<section class="nuva-card tournament-panel">
    <h2 class="section-title">Disponibles para los torneos</h2>
    <p class="environment-muted">Elige qué podrán usar los torneos de este entorno. Si desactivas una opción, lo que ya se guardó se conserva. En los torneos existentes tendrás que elegirla también desde su pestaña Estadísticas.</p>
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
        <button class="nuva-btn">Guardar opciones disponibles</button>
    </form>
</section>
<section class="nuva-card tournament-panel">
    <h2 class="section-title">Crear una estadística manual</h2>
    <p class="environment-muted">Útil para asistencias, faltas, puntuaciones u otros valores de tu disciplina. Tú escribes los valores y Nuva los reúne como elijas: por ejemplo, sumar los puntos o mostrar el mejor tiempo.</p>
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
        <p class="environment-muted">Después de crearla, habilítala en el catálogo y selecciónala en cada torneo.</p>
    </form>
</section>
