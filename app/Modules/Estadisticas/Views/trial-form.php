<?php
use App\Modules\Estadisticas\Services\Physics;

$editing = $edit;
$values = [];
if ($editing) {
    // Recuperar las mediciones y los detalles del intento que se está corrigiendo.
    $values = $editing['inputs'] + $editing;
}
$retry = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'stat_trial';
if ($retry) {
    $values = $_POST;
}

$chosen = [];
if (!$retry) {
    $chosen = array_slice($activeTemplates, 0, 1);
}
if (isset($values['templates'])) {
    $chosen = $values['templates'];
}
if (!is_array($chosen)) {
    $chosen = [];
}

$reuse = true;
if ($retry) {
    $reuse = !empty($values['reuse_journey']);
} elseif ($editing) {
    $sameDistance = (string) ($values['distance'] ?? '') === (string) ($values['segment_distance'] ?? '');
    $sameTime = (string) ($values['time'] ?? '') === (string) ($values['segment_time'] ?? '');
    $reuse = $sameDistance && $sameTime;
}

$now = new DateTimeImmutable('now', new DateTimeZone('America/Montevideo'));
$trialDate = nuva_trial_value($values, 'date', $now->format('Y-m-d\TH:i'));
if (str_contains($trialDate, ' ')) {
    $trialDate = nuva_local_date($trialDate, 'Y-m-d\TH:i');
}
?>
<details class="nuva-card tournament-panel" open id="trial-form">
    <summary class="section-title"><?= $editing ? 'Corregir una prueba' : 'Añadir una prueba' ?></summary>
    <p class="environment-muted">Elige qué quieres conocer. Nuva te pedirá las medidas y hará las cuentas por ti.</p>
    <p><a class="nuva-anchor" href="?page=guide#estadisticas-cargar" target="_blank" rel="noopener">Ver un ejemplo paso a paso <span class="environment-muted">(abre otra pestaña)</span></a></p>
    <form method="post" class="environment-form" data-trial-form>
        <?php nuva_csrf_field(); ?>
        <input type="hidden" name="action" value="stat_trial">
        <input type="hidden" name="record_id" value="<?= nuva_escape($editing['record_id'] ?? '') ?>">
        <input type="hidden" name="revision" value="<?= (int)($editing['_revision'] ?? 0) ?>">
        <ol class="statistics-steps" aria-label="Pasos de la prueba" data-wizard-progress hidden>
            <li aria-current="step">1. Elegir</li>
            <li>2. Medir</li>
            <li>3. Guardar</li>
        </ol>
        <p class="environment-notice environment-notice--error" data-trial-error role="alert" hidden>
        </p>
        <section data-trial-step="0" aria-labelledby="trial-choose">
            <h3 id="trial-choose">¿Qué quieres conocer?</h3>
            <p class="environment-muted">Puedes elegir más de una opción. Para empezar, basta con una.</p>
            <div class="statistics-goals">
                <?php foreach ($activeTemplates as $code): $guide = Physics::TEMPLATES[$code]; ?>
                <label class="statistics-goal">
                    <input name="templates[]" type="checkbox" value="<?= $code ?>" <?= in_array($code, $chosen, true) ? 'checked' : '' ?>>
                    <span>
                        <strong><?= nuva_escape($guide['name']) ?></strong>
                        <small><?= nuva_escape($guide['purpose']) ?></small>
                    </span>
                </label>
                <?php endforeach; ?>
            </div>
            <label class="environment-field">¿Quién hizo la prueba?<select name="participant_id" required>
                    <option value="">Elige un participante</option>
                    <?php foreach ($participants as $participant): ?>
                    <option value="<?= (int)$participant['id'] ?>" <?= nuva_trial_value($values, 'participant_id') === (string)$participant['id'] ? 'selected' : '' ?>><?= nuva_escape($participant['name'] . ($participant['team_name'] ? ' · ' . $participant['team_name'] : '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </section>
        <section data-trial-step="1" aria-labelledby="trial-measure">
            <h3 id="trial-measure">Añade lo que mediste</h3>
            <p class="environment-muted">Usa los datos de un mismo intento. Los números de ejemplo son solo una ayuda: no se guardan como mediciones.</p>
            <div data-stat-group="motion">
                <div class="tournament-fields">
                    <?php nuva_measurement_field($values, 'distance', '¿Qué distancia recorrió?', 'm', true, 'Por ejemplo, de una marca a otra.', 'Ej.: 10');
nuva_measurement_field($values, 'time', '¿Cuánto tardó?', 's', true, 'Puedes escribir decimales, como 2,5.', 'Ej.: 2'); ?>
                </div>
                <label class="environment-check statistics-extra" data-stat-group="motion-optional">
                    <input type="checkbox" name="mrua" value="1" <?= !empty($values['mrua']) ? 'checked' : '' ?>> También quiero estimar cómo aumentó su rapidez</label>
                <p class="environment-muted" data-stat-group="model-explanation">Para esa estimación, la persona debe salir quieta y aumentar su rapidez de forma aproximadamente uniforme.</p>
            </div>
            <div data-stat-group="dynamics">
                <label class="environment-field">¿Qué datos tienes para estimar la fuerza?<select name="acceleration_source">
                        <option value="model">Una distancia y el tiempo que tardó desde que salió quieto</option>
                        <option value="measured" <?= nuva_trial_value($values, 'acceleration_source') === 'measured' ? 'selected' : '' ?>>Una aceleración medida con un sensor u otro instrumento</option>
                    </select>
                </label>
                <div data-stat-group="measured-acceleration">
                    <?php nuva_measurement_field($values, 'acceleration', 'Aceleración que indica el instrumento', 'm/s²', true, 'Copia la medida del instrumento. No es la velocidad.', 'Ej.: 5'); ?>
                </div>
            </div>
            <div data-stat-group="energy">
                <label class="environment-field">¿Qué datos tienes para calcular la energía?<select name="speed_source">
                        <option value="model">Una distancia y el tiempo que tardó desde que salió quieto</option>
                        <option value="measured" <?= nuva_trial_value($values, 'speed_source') === 'measured' ? 'selected' : '' ?>>La rapidez al inicio y al final, medidas con un instrumento</option>
                    </select>
                </label>
                <div data-stat-group="measured-speed">
                    <p class="environment-muted">Usa la rapidez en esos dos momentos, por ejemplo la que indica un sensor. El promedio de todo el recorrido no sirve para esta opción.</p>
                    <div class="tournament-fields">
                        <?php nuva_measurement_field($values, 'initial_speed', 'Rapidez al empezar', 'm/s', true, 'Escribe 0 si empezó quieto.');
nuva_measurement_field($values, 'speed', 'Rapidez al terminar', 'm/s', true); ?>
                    </div>
                </div>
            </div>
            <div data-stat-group="mass">
                <div class="tournament-fields">
                    <?php nuva_measurement_field($values, 'mass', '¿Cuántos kilos marca la balanza?', 'kg', true, 'Es la masa de la persona o del objeto que se movió.', 'Ej.: 70'); ?>
                </div>
            </div>
            <div data-stat-group="model" class="statistics-subpanel">
                <h4>Sobre el recorrido</h4>
                <p class="environment-muted">Nuva puede hacer una estimación con distancia y tiempo, siempre que se cumplan estas dos condiciones.</p>
                <label class="environment-check">
                    <input type="checkbox" name="from_rest" value="1" data-required <?= !empty($values['from_rest']) ? 'checked' : '' ?>> Empezó quieto, sin tomar impulso antes de la marca</label>
                <label class="environment-check">
                    <input type="checkbox" name="constant_acceleration" value="1" data-required <?= !empty($values['constant_acceleration']) ? 'checked' : '' ?>> Se movió en línea recta y fue ganando rapidez de forma aproximadamente uniforme</label>
                <p class="environment-muted">Si frenó, giró o cambió mucho el ritmo, esta estimación no es adecuada. Puedes medir solo distancia y tiempo, o usar medidas de un instrumento.</p>
                <div data-stat-group="reuse">
                    <label class="environment-check">
                        <input type="checkbox" name="reuse_journey" value="1" <?= $reuse ? 'checked' : '' ?>> Usar la misma distancia y tiempo que escribí arriba</label>
                </div>
                <div data-stat-group="segment">
                    <p class="environment-muted">Mide desde que empieza a moverse hasta la marca elegida. Puedes usar solo el primer tramo del recorrido.</p>
                    <div class="tournament-fields">
                        <?php nuva_measurement_field($values, 'segment_distance', 'Distancia hasta la marca', 'm', true, 'Elige una distancia que hayas medido.', 'Ej.: 10');
nuva_measurement_field($values, 'segment_time', 'Tiempo hasta esa marca', 's', true, 'Cuenta desde que empieza a moverse.', 'Ej.: 2'); ?>
                    </div>
                </div>
            </div>
            <details class="statistics-subpanel" data-stat-group="energy">
                <summary>También calcular el trabajo de una fuerza (opcional)</summary>
                <p class="environment-muted">Indica cuánta energía aporta o quita una fuerza durante el recorrido. Puedes dejar esta opción sin usar.</p>
                <label class="environment-field">¿Cómo quieres calcularlo?<select name="work_mode">
                        <option value="none">No lo necesito</option>
                        <option value="model" <?= nuva_trial_value($values, 'work_mode') === 'model' ? 'selected' : '' ?>>Estimar con el mismo recorrido desde que salió quieto</option>
                        <option value="measured" <?= nuva_trial_value($values, 'work_mode') === 'measured' ? 'selected' : '' ?>>Tengo una fuerza medida con un instrumento</option>
                    </select>
                </label>
                <div data-stat-group="measured-work">
                    <div class="tournament-fields">
                        <?php nuva_measurement_field($values, 'force', 'Fuerza neta que indica el instrumento', 'N', true, 'Debe ser la fuerza total, teniendo en cuenta las que se oponen.');
nuva_measurement_field($values, 'work_distance', 'Distancia durante la que actuó esa fuerza', 'm', true);
nuva_measurement_field($values, 'angle', 'Dirección de la fuerza respecto al movimiento', '°', true, '0°: a favor. 90°: de lado. 180°: en contra.'); ?>
                    </div>
                    <label class="environment-check">
                        <input type="checkbox" name="constant_force" value="1" data-required <?= !empty($values['constant_force']) ? 'checked' : '' ?>> La fuerza se mantuvo aproximadamente igual durante ese recorrido</label>
                </div>
            </details>
            <details class="statistics-fieldset">
                <summary>Margen de duda de las mediciones (opcional)</summary>
                <p class="environment-muted">La incertidumbre indica cuánto puede variar una medición por el instrumento y el procedimiento. Por ejemplo, ±0,02 s. Deja vacío un margen desconocido; escribir 0 significa declarar que no hay variación, no que se desconoce.</p>
                <label class="environment-check">
                    <input type="checkbox" name="independent_inputs" value="1" <?= !empty($values['independent_inputs']) ? 'checked' : '' ?>> Los márgenes de las mediciones de entrada se consideran independientes</label>
                <p class="environment-muted">Si no puedes justificarlo, deja esta opción sin marcar. Nuva no mostrará un margen calculado como si fuera conocido. Las cantidades derivadas del mismo tramo se calculan juntas para no contarlas como mediciones independientes.</p>
                <div class="tournament-fields">
                    <?php foreach ([
                        'distance' => ['Distancia del recorrido','m'],
                        'time' => ['Tiempo del recorrido','s'],
                        'segment_distance' => ['Distancia inicial','m'],
                        'segment_time' => ['Tiempo inicial','s'],
                        'mass' => ['Masa','kg'],
                        'acceleration' => ['Aceleración medida','m/s²'],
                        'speed' => ['Rapidez final medida','m/s'],
                        'initial_speed' => ['Rapidez inicial medida','m/s'],
                        'force' => ['Fuerza neta medida','N'],
                        'work_distance' => ['Desplazamiento del trabajo','m'],
                        'angle' => ['Ángulo','°']
                    ] as $key => [$label,$unit]) {
                        nuva_measurement_field($values, 'u_' . $key, 'Margen ± ' . $label, $unit);
                    } ?>
                </div>
            </details>
        </section>
        <section data-trial-step="2" aria-labelledby="trial-save">
            <h3 id="trial-save">Todo listo para guardar</h3>
            <p class="statistics-recap" data-trial-recap aria-live="polite">Nuva calculará los resultados con tus mediciones.</p>
            <p class="environment-muted">Estos detalles ayudan a comparar pruebas hechas de la misma manera. No necesitas usar términos de física.</p>
            <div class="tournament-fields">
                <label class="environment-field">¿Cómo hiciste la prueba?<input name="procedure" maxlength="200" required value="<?= nuva_escape(nuva_trial_value($values, 'procedure')) ?>" placeholder="Ej.: Corrí entre dos marcas y medí el tiempo">
                </label>
                <label class="environment-field">¿Con qué mediste?<input name="instrument" maxlength="200" required value="<?= nuva_escape(nuva_trial_value($values, 'instrument')) ?>" placeholder="Ej.: Cinta métrica, cronómetro y balanza">
                </label>
                <label class="environment-field tournament-span">¿Cómo era el lugar?<input name="conditions" maxlength="200" required value="<?= nuva_escape(nuva_trial_value($values, 'conditions')) ?>" placeholder="Ej.: Suelo plano y seco, al aire libre">
                </label>
            </div>
            <details class="statistics-subpanel">
                <summary>Nombre, fecha y enlace de la prueba</summary>
                <div class="tournament-fields">
                    <label class="environment-field">Nombre para reconocerla<input name="label" maxlength="80" required value="<?= nuva_escape(nuva_trial_value($values, 'label', 'Prueba ' . (count($trials) + 1))) ?>" placeholder="Ej.: Recorrido de 10 metros">
                    </label>
                    <label class="environment-field">Fecha y hora (Uruguay)<input name="date" type="datetime-local" required value="<?= nuva_escape($trialDate) ?>">
                    </label>
                    <label class="environment-field tournament-span">Enlace al video o registro (opcional)<input name="evidence" type="url" maxlength="1000" value="<?= nuva_escape(nuva_trial_value($values, 'evidence')) ?>" placeholder="https://...">
                    </label>
                </div>
            </details>
            <?php if ($editing): ?>
            <label class="environment-field">¿Por qué corriges la prueba?<textarea name="reason" required maxlength="500" rows="2"><?= nuva_escape(nuva_trial_value($values, 'reason')) ?></textarea>
            </label>
            <?php endif; ?>
        </section>
        <div class="statistics-wizard-actions">
            <button type="button" class="environment-button" data-trial-back hidden>Anterior</button>
            <span data-trial-position hidden aria-live="polite">
            </span>
            <button type="button" class="nuva-btn" data-trial-next hidden>Continuar</button>
            <button class="nuva-btn" data-trial-submit><?= $editing ? 'Guardar corrección' : 'Guardar prueba' ?></button>
        </div>
        <?php if ($editing): ?>
        <a class="nuva-anchor" href="?page=tournament-statistics&amp;id=<?= (int)$tournament['id'] ?>">Cancelar corrección</a>
        <?php endif; ?>
        <noscript>
            <p class="environment-muted">Completa los tres apartados y guarda la prueba al final.</p>
        </noscript>
    </form>
</details>
