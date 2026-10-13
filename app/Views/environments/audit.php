<?php
$actions = ['create' => 'Creación', 'update' => 'Modificación', 'join' => 'Incorporación', 'leave' => 'Baja de miembro', 'role_change' => 'Cambio de rol', 'revoke' => 'Revocación', 'organizer_removed' => 'Retiro de organizador'];
$actions += ['matches_generate'=>'Generación de encuentros','round_generate'=>'Nueva ronda','round_close'=>'Cierre de ronda','round_reopen'=>'Reapertura de ronda','round_discard'=>'Retiro de ronda','result_save'=>'Registro o corrección de resultado','result_clear'=>'Anulación de resultado','match_schedule'=>'Programación de encuentro'];
$entities = ['workspace' => 'Entorno', 'member' => 'Miembro', 'module' => 'Módulo', 'invitation' => 'Invitación', 'tournament' => 'Torneo'];
$fieldLabels = [
    'match'=>'Encuentro', 'round'=>'Ronda', 'matches'=>'Encuentros', 'participants'=>'Inscritos', 'format'=>'Formato', 'rival_1'=>'Primer rival', 'rival_2'=>'Segundo rival', 'score_1'=>'Primer marcador', 'score_2'=>'Segundo marcador', 'winner'=>'Ganador', 'notes'=>'Motivo o detalle', 'scheduled_at'=>'Fecha programada', 'status'=>'Estado', 'points_win'=>'Puntos por victoria','points_draw'=>'Puntos por empate','points_loss'=>'Puntos por derrota','allow_draws'=>'Admite empates','score_order'=>'Criterio del marcador','score_label'=>'Nombre del marcador',
    'name' => 'Nombre', 'description' => 'Descripción', 'role' => 'Rol',
    'is_active' => 'Estado del módulo', 'active' => 'Estado de la invitación',
    'organizer_id' => 'Organizador', 'detail' => 'Detalle',
];
// El registro conserva sus datos originales; aquí se presentan en lenguaje cotidiano.
$formatAuditValue = static function (string $field, $value) use (&$formatAuditValue, $fieldLabels): string {
    if (is_array($value)) {
        $parts = [];
        foreach ($value as $key => $item) {
            $label = is_string($key) ? ($fieldLabels[$key] ?? ucfirst(str_replace('_', ' ', $key))) . ': ' : '';
            $parts[] = $label . $formatAuditValue(is_string($key) ? $key : $field, $item);
        }
        return $parts ? implode(' · ', $parts) : 'Sin datos';
    }
    if ($field === 'score_order') return $value==='lower'?'Gana el valor menor':'Gana el valor mayor';
    if ($field === 'allow_draws') return $value?'Sí':'No';
    if ($field === 'organizer_id') {
        return $value === null ? 'Sin organizador asignado' : 'Usuario #' . $value;
    }
    if ($value === null || $value === '') {
        return $field === 'description' ? 'Sin descripción' : 'Sin especificar';
    }
    if (in_array($value, [true, false, 1, 0, '1', '0'], true)) {
        $enabled = in_array($value, [true, 1, '1'], true);
        if ($field === 'is_active') {
            return $enabled ? 'Habilitado' : 'Deshabilitado';
        }
        if ($field === 'active') {
            return $enabled ? 'Activa' : 'Revocada';
        }
    }
    if (is_bool($value)) {
        return $value ? 'Sí' : 'No';
    }
    return (string) $value;
};
$readAuditValues = static function ($raw): array {
    if ($raw === null) {
        return [];
    }
    $values = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['detail' => 'El detalle de este registro no está disponible.'];
    }
    return is_array($values) ? $values : ['detail' => $values];
};
?>
<h2 class="section-title">Auditoría del entorno</h2>
<p class="environment-muted">Últimas 100 acciones de este entorno. Horarios de Uruguay (UTC−3).</p>
<div class="environment-members">
    <?php foreach ($logs as $log): ?>
        <article class="nuva-card environment-panel">
            <div class="environment-heading"><h3><?= $escape($actions[$log['action']] ?? $log['action']) ?> · <?= $escape($entities[$log['entity_type']] ?? $log['entity_type']) ?> #<?= $escape($log['entity_id']) ?></h3><time class="environment-muted"><?= $escape($localDate($log['created_at'])) ?></time></div>
            <p class="environment-muted">Responsable: <?= $escape($log['actor_name']) ?> · Usuario #<?= (int) $log['user_id'] ?></p>
            <?php
            $before = $readAuditValues($log['old_value']);
            $after = $readAuditValues($log['new_value']);
            $fields = array_unique(array_merge(array_keys($before), array_keys($after)));
            $beforeEmpty = $log['action'] === 'create' ? 'No existía' : 'Sin información anterior';
            $afterEmpty = 'Sin información posterior';
            if ($log['entity_type'] === 'member') {
                $beforeEmpty = 'No pertenecía al entorno';
                $afterEmpty = 'Ya no pertenece al entorno';
            }
            ?>
            <details class="environment-audit-detail">
                <summary>Ver cambios</summary>
                <?php if ($fields): ?>
                    <div class="environment-audit-table-wrap">
                        <table class="environment-audit-table">
                            <caption class="visually-hidden">Información antes y después de esta acción</caption>
                            <thead><tr><th scope="col">Dato</th><th scope="col">Antes</th><th scope="col">Después</th></tr></thead>
                            <tbody>
                                <?php foreach ($fields as $field): ?>
                                    <tr>
                                        <th scope="row"><?= $escape($fieldLabels[$field] ?? ucfirst(str_replace('_', ' ', (string) $field))) ?></th>
                                        <td><?= $escape(array_key_exists($field, $before) ? $formatAuditValue((string) $field, $before[$field]) : $beforeEmpty) ?></td>
                                        <td><?= $escape(array_key_exists($field, $after) ? $formatAuditValue((string) $field, $after[$field]) : $afterEmpty) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="environment-muted">No hay detalles adicionales para esta acción.</p>
                <?php endif; ?>
            </details>
        </article>
    <?php endforeach; ?>
    <?php if (!$logs): ?><div class="collection__empty nuva-card"><h3>Todavía no hay registros</h3></div><?php endif; ?>
</div>
