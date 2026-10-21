<details class="nuva-card tournament-panel" open>
    <summary class="section-title">Elegir estadísticas de este torneo</summary>
    <p class="environment-muted">Elige qué estadísticas se pueden cargar y cuáles se muestran. Si desactivas la carga, los datos guardados se conservan.</p>
    <?php if ($tournament['published']): ?>
    <p class="environment-notice">Este torneo es público. Cualquier persona podrá ver las estadísticas marcadas en Mostrar, incluidas sus mediciones y detalles.</p>
    <?php endif; ?>
    <form method="post" class="environment-form">
        <?php nuva_csrf_field(); ?>
        <input type="hidden" name="action" value="stat_configuration">
        <input type="hidden" name="revision" value="<?= (int)$configuration['_revision'] ?>">
        <div class="statistics-table-wrap">
            <table class="statistics-table">
                <thead>
                    <tr>
                        <th>Plantilla o estadística</th>
                        <th>Permitir registrar</th>
                        <th>Mostrar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($options as $code => $info): ?>
                    <tr>
                        <th scope="row"><?= nuva_escape($info['name']) ?>
                            <?php if (!$info['enabled']): ?>
                            <small class="environment-muted"> · Desactivada en el entorno</small>
                            <?php endif; ?>
                        </th>
                        <td>
                            <input aria-label="Nuevas cargas de <?= nuva_escape($info['name']) ?>" type="checkbox" name="load[]" value="<?= nuva_escape($code) ?>" <?= $info['enabled'] ? '' : 'disabled' ?> <?= $info['enabled'] && !empty($config[$code]['load']) ? 'checked' : '' ?>>
                        </td>
                        <td>
                            <input aria-label="Mostrar <?= nuva_escape($info['name']) ?>" type="checkbox" name="visible[]" value="<?= nuva_escape($code) ?>" <?= !empty($config[$code]['visible']) ? 'checked' : '' ?>>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <button class="environment-button">Guardar cambios</button>
    </form>
</details>
