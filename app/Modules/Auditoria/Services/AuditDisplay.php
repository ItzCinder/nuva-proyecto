<?php

namespace App\Modules\Auditoria\Services;

use App\Modules\Estadisticas\Services\Physics;

// La base conserva el registro completo. La pantalla muestra solo información útil.
class AuditDisplay
{
    private const ACTIONS = [
        'create' => 'Se creó un registro', 'update' => 'Se actualizaron los datos',
        'join' => 'Se incorporó una persona', 'leave' => 'Se retiró un miembro',
        'role_change' => 'Se cambió un rol', 'revoke' => 'Se desactivó una invitación',
        'organizer_removed' => 'Se retiró la asignación del organizador',
        'matches_generate' => 'Se generaron los encuentros', 'round_generate' => 'Se creó una ronda',
        'round_close' => 'Se cerró una ronda', 'round_reopen' => 'Se reabrió una ronda',
        'round_discard' => 'Se retiró una ronda', 'result_save' => 'Se guardó un resultado',
        'result_clear' => 'Se anuló un resultado', 'match_schedule' => 'Se cambió la fecha de un encuentro',
        'stat_create' => 'Se creó una estadística', 'stat_config' => 'Se cambiaron las estadísticas disponibles',
        'stat_record' => 'Se registró una medición', 'stat_correct' => 'Se corrigió una medición',
        'stat_invalidate' => 'Se anuló una medición',
    ];
    private const FIELDS = [
        'name' => 'Nombre', 'description' => 'Descripción', 'picture' => 'Foto',
        'role' => 'Rol', 'active' => 'Invitación', 'is_active' => 'Disponibilidad',
        'discipline' => 'Disciplina', 'discipline_category' => 'Categoría',
        'has_team' => 'Por equipos', 'max_participants' => 'Cupo', 'published' => 'Torneo público',
        'start_date' => 'Inicio', 'end_date' => 'Fin', 'status' => 'Estado',
        'organizer_id' => 'Organizador', 'module_id' => 'Formato', 'format' => 'Formato',
        'league_legs' => 'Vueltas de la liga', 'swiss_rounds' => 'Rondas del suizo',
        'third_place' => 'Encuentro por el tercer puesto', 'team_size' => 'Integrantes por equipo',
        'points_win' => 'Puntos por victoria', 'points_draw' => 'Puntos por empate',
        'points_loss' => 'Puntos por derrota', 'allow_draws' => 'Admite empates',
        'score_order' => 'Criterio del marcador', 'score_label' => 'Nombre del marcador',
        'round' => 'Ronda', 'matches' => 'Encuentros', 'participants' => 'Inscritos',
        'score_1' => 'Marcador del primer rival', 'score_2' => 'Marcador del segundo rival',
        'winner' => 'Ganador', 'notes' => 'Aclaración', 'scheduled_at' => 'Fecha del encuentro',
        'member_id' => 'Persona inscrita', 'team_id' => 'Equipo', 'team_name' => 'Equipo',
        'participant_name' => 'Participante', 'target_name' => 'Persona o equipo',
        'label' => 'Prueba', 'date' => 'Fecha de la medición', 'value' => 'Valor registrado',
        'demonstration' => 'Son datos de ejemplo', 'invalid' => 'Anulada', 'reason' => 'Motivo',
        'procedure' => 'Cómo se hizo', 'instrument' => 'Con qué se midió', 'conditions' => 'Lugar y condiciones',
        'unit' => 'Unidad', 'target' => 'Se aplica a', 'rule' => 'Cómo reunir los valores',
    ];

    public static function entry(array $log, array $names): array
    {
        $before = self::readValues($log['old_value']);
        $after = self::readValues($log['new_value']);
        $values = $after + $before;
        $entity = $log['entity_type'];
        $title = self::ACTIONS[$log['action']] ?? 'Se registró una actividad';
        if ($log['action'] === 'create') {
            $title = match ($entity) {
                'workspace' => 'Se creó el entorno', 'invitation' => 'Se creó una invitación',
                'tournament' => isset($values['team_name']) ? 'Se creó un equipo' : 'Se creó un torneo',
                default => $title,
            };
        }
        if ($entity === 'stat_demo') {
            $title = 'Se cargaron datos de ejemplo';
        } elseif ($entity === 'stat_trial') {
            $title = match ($log['action']) {
                'stat_record' => 'Se registró una prueba física',
                'stat_correct' => 'Se corrigió una prueba física',
                'stat_invalidate' => 'Se anuló una prueba física',
                default => $title,
            };
        } elseif ($entity === 'stat_manual' && !empty($values['definition']['name'])) {
            $title .= ' · ' . $values['definition']['name'];
        }
        $subject = self::subject($log, $values, $names);
        $changes = self::changes($before, $after, $names);
        if ($log['action'] === 'stat_config' && !$changes) {
            $title = 'Se guardó la selección de estadísticas';
        }
        // Los rivales son contexto del resultado, no cambios eliminados del registro.
        if (!empty($values['rival_1']) && !empty($values['rival_2'])) {
            $subject .= ' · ' . $values['rival_1'] . ' — ' . $values['rival_2'];
            $changes = self::scoreLabels($changes, $values);
        }
        $url = '';
        $tournamentId = $values['tournament_id'] ?? null;
        if (in_array($entity, ['stat_trial', 'stat_manual'], true) && isset($names['tournaments'][$tournamentId])) {
            $parameters = [
                'page' => 'tournament-statistics', 'id' => $tournamentId,
                'view' => $entity === 'stat_trial' ? 'trials' : 'manual',
                'record' => $log['entity_id'], 'origin' => !empty($values['demonstration']) ? 'demo' : 'real',
            ];
            if ($entity === 'stat_manual') {
                $parameters['stat'] = $values['stat_type_id'];
                $parameters['kind'] = 'manual';
            }
            $url = '?' . http_build_query($parameters) . '#statistics-detail';
        }
        return ['title' => $title, 'subject' => $subject, 'changes' => $changes, 'url' => $url];
    }

    private static function subject(array $log, array $values, array $names): string
    {
        $entity = $log['entity_type'];
        $entityId = $log['entity_id'];
        if ($entity === 'member') {
            return $names['users'][$entityId] ?? 'Miembro del entorno';
        }
        if ($entity === 'module') {
            return $names['modules'][$entityId] ?? 'Formato de competición';
        }
        if ($entity === 'tournament' || $entity === 'stat_configuration') {
            // Los registros antiguos de equipos también usaban la entidad tournament.
            if (isset($values['team_name'])) {
                return $values['team_name'];
            }
            return $values['name'] ?? $names['tournaments'][$entityId] ?? 'Torneo del entorno';
        }
        if (isset($values['tournament_id'])) {
            $tournamentName = $names['tournaments'][$values['tournament_id']] ?? 'Torneo del entorno';
            $personName = $values['participant_name'] ?? $values['target_name'] ?? '';
            $trialName = !empty($values['label']) ? ' · ' . $values['label'] : '';
            return $personName ? $personName . $trialName . ' · ' . $tournamentName : $tournamentName;
        }
        return $values['name'] ?? match ($entity) {
            'invitation' => 'Acceso al entorno', 'stat_environment' => 'Estadísticas del entorno',
            'stat_demo' => 'Datos de ejemplo', default => 'Actividad del entorno',
        };
    }

    private static function changes(array $before, array $after, array $names): array
    {
        $rows = [];
        foreach (self::FIELDS as $field => $label) {
            if (!array_key_exists($field, $before) && !array_key_exists($field, $after)) {
                continue;
            }
            if (!$before && in_array($field, ['invalid', 'demonstration'], true) && empty($after[$field])) {
                continue;
            }
            if (!$before && isset($after['inputs']) && in_array($field, ['procedure', 'instrument', 'conditions'], true)) {
                continue;
            }
            // Las notas físicas son explicaciones automáticas, disponibles en la prueba.
            if (is_array($before[$field] ?? null) || is_array($after[$field] ?? null)) {
                continue;
            }
            if ($field === 'value') {
                $oldUnit = trim((string)($before['definition']['unit'] ?? ''));
                $newUnit = trim((string)($after['definition']['unit'] ?? ''));
                $oldValue = self::value($field, $before[$field] ?? null, $names);
                $newValue = self::value($field, $after[$field] ?? null, $names);
                self::addChange($rows, $label, trim($oldValue . ' ' . $oldUnit), trim($newValue . ' ' . $newUnit));
            } elseif ($field === 'picture' && !empty($before[$field]) && !empty($after[$field]) && $before[$field] !== $after[$field]) {
                self::addChange($rows, $label, 'Foto anterior', 'Foto nueva');
            } else {
                self::addChange($rows, $label, self::value($field, $before[$field] ?? null, $names), self::value($field, $after[$field] ?? null, $names));
            }
        }
        foreach (['templates' => 'Pruebas físicas habilitadas', 'manual' => 'Estadísticas manuales habilitadas'] as $field => $label) {
            if (isset($before[$field]) || isset($after[$field])) {
                self::addChange($rows, $label, self::selection($before[$field] ?? [], $field, $names), self::selection($after[$field] ?? [], $field, $names));
            }
        }
        $oldOptions = $before['options'] ?? [];
        $newOptions = $after['options'] ?? [];
        foreach (array_unique(array_merge(array_keys($oldOptions), array_keys($newOptions))) as $code) {
            $label = self::optionName($code, $names);
            self::addChange($rows, $label, self::optionState($oldOptions[$code] ?? []), self::optionState($newOptions[$code] ?? []));
        }
        // En una corrección interesan las medidas que cambiaron, no las fórmulas internas.
        if ($before && isset($after['inputs'])) {
            $measurements = [
                'distance' => ['Distancia', 'm'], 'time' => ['Tiempo', 's'],
                'segment_distance' => ['Distancia del tramo inicial', 'm'], 'segment_time' => ['Tiempo del tramo inicial', 's'],
                'mass' => ['Kilos de la balanza', 'kg'], 'acceleration' => ['Aceleración medida', 'm/s²'],
                'speed' => ['Rapidez final medida', 'm/s'], 'initial_speed' => ['Rapidez inicial medida', 'm/s'],
                'force' => ['Fuerza medida', 'N'], 'work_distance' => ['Desplazamiento', 'm'], 'angle' => ['Ángulo', '°'],
            ];
            foreach ($measurements as $field => [$label, $unit]) {
                $oldValue = $before['inputs'][$field] ?? null;
                $newValue = $after['inputs'][$field] ?? null;
                self::addChange($rows, $label, $oldValue === null ? 'Sin registrar' : $oldValue . ' ' . $unit, $newValue === null ? 'Sin registrar' : $newValue . ' ' . $unit);
            }
        }
        return $rows;
    }

    private static function addChange(array &$rows, string $label, string $before, string $after): void
    {
        if ($before !== $after) {
            $rows[] = ['label' => $label, 'before' => $before, 'after' => $after];
        }
    }

    private static function scoreLabels(array $rows, array $values): array
    {
        foreach ($rows as &$row) {
            if ($row['label'] === 'Marcador del primer rival') {
                $row['label'] = 'Marcador de ' . $values['rival_1'];
            } elseif ($row['label'] === 'Marcador del segundo rival') {
                $row['label'] = 'Marcador de ' . $values['rival_2'];
            }
        }
        return $rows;
    }

    private static function value(string $field, $value, array $names): string
    {
        if ($field === 'picture') {
            return $value ? 'Con foto' : 'Sin foto';
        }
        $nameGroups = ['organizer_id' => 'users', 'member_id' => 'users', 'team_id' => 'teams', 'module_id' => 'modules'];
        if (isset($nameGroups[$field]) && $value !== null && $value !== '') {
            return $names[$nameGroups[$field]][$value] ?? 'Ya no disponible';
        }
        if ($value === null || $value === '') {
            return 'Sin registrar';
        }
        $booleanFields = ['has_team', 'published', 'third_place', 'allow_draws', 'demonstration', 'invalid', 'active', 'is_active'];
        if (in_array($field, $booleanFields, true)) {
            if ($field === 'active' || $field === 'is_active') {
                return $value ? 'Habilitada' : 'Desactivada';
            }
            return $value ? 'Sí' : 'No';
        }
        if (in_array($field, ['date', 'scheduled_at', 'start_date', 'end_date'], true)) {
            return nuva_local_date((string)$value);
        }
        $translations = [
            'score_order' => ['lower' => 'Gana el valor menor', 'higher' => 'Gana el valor mayor'],
            'target' => ['person' => 'Personas', 'team' => 'Equipos'],
            'rule' => ['latest' => 'Valor más reciente', 'sum' => 'Suma', 'average' => 'Promedio', 'min' => 'Menor valor', 'max' => 'Mayor valor'],
        ];
        return $translations[$field][$value] ?? (string)$value;
    }

    private static function selection(array $selected, string $field, array $names): string
    {
        $labels = [];
        foreach ($selected as $code) {
            $labels[] = $field === 'templates' ? (Physics::TEMPLATES[$code]['name'] ?? 'Prueba física') : ($names['statistics'][$code] ?? 'Estadística retirada');
        }
        return $labels ? implode(' · ', $labels) : 'Ninguna';
    }

    private static function optionName(string $code, array $names): string
    {
        if (str_starts_with($code, 'physics:')) {
            return Physics::TEMPLATES[substr($code, 8)]['name'] ?? 'Prueba física';
        }
        return $names['statistics'][substr($code, 7)] ?? 'Estadística manual';
    }

    private static function optionState(array $option): string
    {
        if (!empty($option['load']) && !empty($option['visible'])) {
            return 'Permite registrar y consultar';
        }
        if (!empty($option['load'])) {
            return 'Solo permite registrar';
        }
        return !empty($option['visible']) ? 'Solo permite consultar' : 'Desactivada';
    }

    public static function readValues($raw): array
    {
        if (!is_string($raw)) {
            return [];
        }
        $values = json_decode($raw, true);
        return is_array($values) ? $values : [];
    }
}
