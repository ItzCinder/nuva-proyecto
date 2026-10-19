<h2 class="section-title">Actividad del entorno</h2>
<p class="environment-muted">Los últimos 100 cambios: qué pasó, quién lo hizo y cuándo. Horarios de Uruguay.</p>
<div class="environment-members">
    <?php foreach ($logs as $log): $activity = $log['display']; ?>
    <article class="nuva-card environment-panel environment-activity">
        <div class="environment-heading">
            <h3><?= nuva_escape($activity['title']) ?></h3>
            <time class="environment-muted"><?= nuva_escape(nuva_local_date($log['created_at'])) ?></time>
        </div>
        <p class="environment-activity-subject"><?= nuva_escape($activity['subject']) ?></p>
        <p class="environment-muted">Por <?= nuva_escape($log['actor_name']) ?></p>
        <?php if ($activity['changes']): ?>
        <details class="environment-audit-detail">
            <summary>Ver qué cambió</summary>
            <div class="environment-audit-table-wrap">
                <table class="environment-audit-table">
                    <caption class="visually-hidden">Cambios de esta actividad</caption>
                    <thead><tr><th scope="col">Dato</th><th scope="col">Antes</th><th scope="col">Después</th></tr></thead>
                    <tbody>
                        <?php foreach ($activity['changes'] as $change): ?>
                        <tr>
                            <th scope="row"><?= nuva_escape($change['label']) ?></th>
                            <td><?= nuva_escape($change['before']) ?></td>
                            <td><?= nuva_escape($change['after']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </details>
        <?php endif; ?>
        <?php if ($activity['url']): ?>
        <a class="nuva-anchor" href="<?= nuva_escape($activity['url']) ?>">Consultar la medición →</a>
        <?php endif; ?>
    </article>
    <?php endforeach; ?>
    <?php if (!$logs): ?>
    <div class="collection__empty nuva-card"><h3>Todavía no hay actividad</h3></div>
    <?php endif; ?>
</div>
