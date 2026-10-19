<?php

namespace App\Modules\Estadisticas\Models;

use app\Models\Model;
use app\Models\Role;
use app\Models\Workspace;
use app\Models\Tournament;
use App\Modules\Estadisticas\Services\Physics;

class Statistics extends Model
{
    public function latest(int $workspace, string $type, string $id): ?array
    {
        $record = $this->query('SELECT id,new_value FROM workspace_audit_logs WHERE workspace_id=? AND entity_type=? AND entity_id=? ORDER BY id DESC LIMIT 1', [$workspace,'stat_' . $type,$id])->fetch();
        if (!$record) {
            return null;
        }

        $data = json_decode($record['new_value'], true, 64, JSON_THROW_ON_ERROR);
        $data['_revision'] = (int)$record['id'];
        return $data;
    }
    private function append(int $workspace, int $actor, string $type, string $id, ?array $before, array $after, string $action): void
    {
        unset($before['_revision'],$after['_revision']);
        $old = $before === null ? null : json_encode($before, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $new = json_encode($after, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if (strlen($new) > 50000 || ($old !== null && strlen($old) > 50000)) {
            throw new \InvalidArgumentException('El registro es demasiado extenso. Reduce los detalles.');
        }
        (new \App\Modules\Auditoria\Models\Audit())->record($workspace, $actor, 'stat_' . $type, $id, $action, $before, $after);
    }
    private function revision(?array $old, array $input): void
    {
        if ((string)($old['_revision'] ?? 0) !== (string)($input['revision'] ?? '')) {
            throw new \InvalidArgumentException('Esta información cambió mientras la editabas. Recarga la página.');
        }
    }
    private function text(array $input, string $key, int $max = 500, bool $required = false): string
    {
        $value = $input[$key] ?? '';
        if (!is_string($value)) {
            throw new \InvalidArgumentException('Formulario inválido.');
        }
        $value = trim($value);
        if (mb_strlen($value) > $max || ($required && $value === '') || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $value)) {
            throw new \InvalidArgumentException('Revisa el campo ' . str_replace('_', ' ', $key) . '.');
        }
        return $value;
    }
    private function selected(array $input, string $key): array
    {
        $list = $input[$key] ?? [];
        if (!is_array($list) || count($list) > 100) {
            throw new \InvalidArgumentException('Selección inválida.');
        }

        foreach ($list as $value) {
            if (!is_string($value)) {
                throw new \InvalidArgumentException('Selección inválida.');
            }
        }
        return array_values(array_unique($list));
    }
    public function definitions(int $workspace): array
    {
        $rows = $this->query('SELECT * FROM member_stat_types WHERE workspace_id=? AND is_system_default=0 ORDER BY name,id', [$workspace])->fetchAll();
        foreach ($rows as $index => $row) {
            $meta = $this->latest($workspace, 'definition', (string)$row['id']);
            $row['rule'] = $meta['rule'] ?? 'latest';
            $row['target'] = $meta['target'] ?? 'person';
            $row['version'] = $meta['version'] ?? 1;
            $rows[$index] = $row;
        }

        return $rows;
    }
    public function environment(int $workspace): array
    {
        return $this->latest($workspace, 'environment', (string)$workspace) ?? ['templates' => [],'manual' => [],'_revision' => 0];
    }
    public function configuration(array $tournament): array
    {
        return $this->latest((int)$tournament['workspace_id'], 'configuration', (string)$tournament['id']) ?? ['options' => [],'_revision' => 0];
    }
    public function options(int $workspace): array
    {
        $environmentConfiguration = $this->environment($workspace);
        $options = [];
        foreach (Physics::TEMPLATES as $code => $info) {
            $options['physics:' . $code] = ['name' => $info['name'],'enabled' => in_array($code, $environmentConfiguration['templates'], true)];
        }

        foreach ($this->definitions($workspace) as $definition) {
            $options['manual:' . $definition['id']] = ['name' => $definition['name'],'enabled' => in_array((string)$definition['id'], array_map('strval', $environmentConfiguration['manual']), true)];
        }

        return $options;
    }
    private function ensurePhysicalTypes(int $workspace, int $actor): array
    {
        $ids = [];
        foreach (Physics::METRICS as $code => [$name,$unit,$template]) {
            $full = 'nuva.physics.v1.' . $code;
            $id = $this->query('SELECT id FROM member_stat_types WHERE workspace_id=? AND code=?', [$workspace,$full])->fetchColumn();
            if (!$id) {
                $this->query('INSERT INTO member_stat_types(workspace_id,code,name,description,is_system_default,unit,data_type,created_by,created_at) VALUES(?,?,?,?,1,?,?,?,UTC_TIMESTAMP())', [$workspace,$full,$name,'Indicador de la plantilla ' . Physics::TEMPLATES[$template]['name'] . '. Versión 1. Resumen: último intento válido.',$unit,'decimal',$actor]);
                $id = $this->database->lastInsertId();
            }

            $ids[$code] = (int)$id;
        }

        return $ids;
    }
    public function writeEnvironment(int $workspace, int $actor, string $action, array $input): void
    {
        $workspaceMembership = (new Workspace())->findForUser($workspace, $actor, true);
        if (!$workspaceMembership || !in_array($workspaceMembership['role_name'], [Role::OWNER,Role::ADMIN], true)) {
            throw new \DomainException('Solo el dueño o administrador puede configurar estadísticas del entorno.');
        }

        if ($action === 'stat_definition') {
            $name = $this->text($input, 'name', 80, true);
            $description = $this->text($input, 'description', 500, true);
            $unit = $this->text($input, 'unit', 50);
            $type = $this->text($input, 'data_type', 10);
            $target = $this->text($input, 'target', 10);
            $rule = $this->text($input, 'rule', 10);
            if (!in_array($type, ['integer','decimal'], true) || !in_array($target, ['person','team'], true) || !in_array($rule, ['latest','sum','average','min','max'], true)) {
                throw new \InvalidArgumentException('Revisa el tipo, aplicación y resumen de la estadística.');
            }

            $this->query('INSERT INTO member_stat_types(workspace_id,code,name,description,is_system_default,unit,data_type,created_by,created_at) VALUES(?,?,?,?,0,?,?,?,UTC_TIMESTAMP())', [$workspace,'manual.' . bin2hex(random_bytes(12)),$name,$description,$unit ?: null,$type,$actor]);
            $id = (string)$this->database->lastInsertId();
            $this->append($workspace, $actor, 'definition', $id, null, ['name' => $name, 'description' => $description, 'unit' => $unit, 'type' => $type, 'target' => $target, 'rule' => $rule, 'version' => 1], 'stat_create');
            return;
        }

        if ($action !== 'stat_environment') {
            throw new \InvalidArgumentException('Acción desconocida.');
        }

        $old = $this->environment($workspace);
        $this->revision($old, $input);
        $templates = $this->selected($input, 'templates');
        $manual = $this->selected($input, 'manual');
        if (array_diff($templates, array_keys(Physics::TEMPLATES)) || array_diff($manual, array_map('strval', array_column($this->definitions($workspace), 'id')))) {
            throw new \InvalidArgumentException('Selecciona estadísticas del entorno.');
        }

        $ids = $this->ensurePhysicalTypes($workspace, $actor);
        foreach ($this->definitions($workspace) as $definition) {
            $this->query('INSERT INTO workspace_member_stat_config(workspace_id,stat_type_id,is_visible,updated_by,updated_at) VALUES(?,?,?,?,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE is_visible=VALUES(is_visible),updated_by=VALUES(updated_by),updated_at=VALUES(updated_at)', [$workspace,$definition['id'],in_array((string)$definition['id'], $manual, true) ? 1 : 0,$actor]);
        }

        foreach (Physics::METRICS as $code => [$label,$unit,$template]) {
            $this->query('INSERT INTO workspace_member_stat_config(workspace_id,stat_type_id,is_visible,updated_by,updated_at) VALUES(?,?,?,?,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE is_visible=VALUES(is_visible),updated_by=VALUES(updated_by),updated_at=VALUES(updated_at)', [$workspace,$ids[$code],in_array($template, $templates, true) ? 1 : 0,$actor]);
        }

        $this->append($workspace, $actor, 'environment', (string)$workspace, $old, ['templates' => $templates, 'manual' => $manual], 'stat_config');
    }
    public function initializeTournament(array $tournament, int $actor): void
    {
        if ($this->latest((int)$tournament['workspace_id'], 'configuration', (string)$tournament['id'])) {
            return;
        }

        $config = ['options' => []];
        foreach ($this->options((int)$tournament['workspace_id']) as $key => $info) {
            $config['options'][$key] = ['load' => $info['enabled'],'visible' => $info['enabled']];
        }

        $this->append((int)$tournament['workspace_id'], $actor, 'configuration', (string)$tournament['id'], null, $config, 'stat_config');
    }
    private function managed(int $id, int $actor): array
    {
        $model = new Tournament();
        $tournament = $model->find($id);
        if (!$tournament) {
            throw new \DomainException('Torneo inexistente.');
        }

        $workspaceMembership = (new Workspace())->findForUser((int)$tournament['workspace_id'], $actor, true);
        $this->query('SELECT id FROM tournament WHERE id=? FOR UPDATE', [$id]);
        $tournament = $model->find($id);
        if (!$model->canManage($tournament, $actor, $workspaceMembership)) {
            throw new \DomainException('No tienes permiso para gestionar estadísticas de este torneo.');
        }
        return $tournament;
    }
    private function available(array $tournament, string $option): void
    {
        $config = $this->configuration($tournament);
        $options = $this->options((int)$tournament['workspace_id']);
        if (empty($config['options'][$option]['load']) || empty($options[$option]['enabled'])) {
            throw new \InvalidArgumentException('La estadística no admite nuevas cargas. Revisa su activación en el entorno y en el torneo.');
        }
    }
    public function writeTournament(int $id, int $actor, string $action, array $input): void
    {
        $tournament = $this->managed($id, $actor);
        $workspace = (int)$tournament['workspace_id'];
        if ($action === 'stat_configuration') {
            $old = $this->configuration($tournament);
            $this->revision($old, $input);
            $options = $this->options($workspace);
            $load = $this->selected($input, 'load');
            $visible = $this->selected($input, 'visible');
            if (array_diff(array_merge($load, $visible), array_keys($options))) {
                throw new \InvalidArgumentException('Selección fuera del catálogo del entorno.');
            }

            $new = ['options' => []];
            foreach ($options as $code => $info) {
                if (in_array($code, $load, true) && !$info['enabled']) {
                    throw new \InvalidArgumentException('La opción ' . $info['name'] . ' está desactivada en el entorno.');
                }
                $new['options'][$code] = ['load' => in_array($code, $load, true),'visible' => in_array($code, $visible, true)];
            }

            $this->append($workspace, $actor, 'configuration', (string)$id, $old, $new, 'stat_config');
            return;
        }

        $type = in_array($action, ['stat_trial','stat_invalidate'], true) ? 'trial' : 'manual';
        if (!in_array($action, ['stat_trial','stat_invalidate','stat_manual','stat_manual_invalidate'], true)) {
            throw new \InvalidArgumentException('Acción desconocida.');
        }

        $recordId = $this->text($input, 'record_id', 30);
        $old = null;
        if ($recordId !== '') {
            $old = $this->latest($workspace, $type, $recordId);
            if (!$old || (int)$old['tournament_id'] !== $id || !empty($old['invalid'])) {
                throw new \InvalidArgumentException('El registro no está disponible en este torneo.');
            }

            $this->revision($old, $input);
        } else {
            $recordId = bin2hex(random_bytes(12));
            $this->revision(null, $input);
        }

        $reason = $this->text($input, 'reason', 500, $old !== null);
        if (in_array($action, ['stat_invalidate','stat_manual_invalidate'], true)) {
            if (!$old) {
                throw new \InvalidArgumentException('Selecciona un registro existente.');
            }

            $new = $old;
            $new['invalid'] = true;
            $new['reason'] = $reason;
        } elseif ($type === 'trial') {
            $templates = $this->selected($input, 'templates');
            if (!$templates || array_diff($templates, array_keys(Physics::TEMPLATES))) {
                throw new \InvalidArgumentException('Selecciona al menos una plantilla física.');
            }

            foreach ($templates as $code) {
                $this->available($tournament, 'physics:' . $code);
            }

            $person = nuva_integer($this->text($input, 'participant_id', 12, true), 'Participante');
            $targetRecord = $this->query('SELECT tm.id,u.name FROM tournament_members tm JOIN users u ON u.id=tm.member_id WHERE tm.id=? AND tm.tournament_id=?', [$person,$id])->fetch();
            if (!$targetRecord) {
                throw new \InvalidArgumentException('Selecciona un participante inscrito en este torneo.');
            }

            $new = Physics::calculate($input, $templates) + [
                'participant_id' => $person,
                'participant_name' => $targetRecord['name'],
                'templates' => $templates,
                'label' => $this->text($input, 'label', 80, true),
                'date' => $this->date($input),
                'procedure' => $this->text($input, 'procedure', 200, true),
                'instrument' => $this->text($input, 'instrument', 200, true),
                'conditions' => $this->text($input, 'conditions', 200, true),
                'evidence' => $this->text($input, 'evidence', 1000),
                'demonstration' => isset($input['demonstration']) ? $input['demonstration'] === '1' : !empty($old['demonstration']),
                'invalid' => false,
                'reason' => $reason,
            ];
            if ($new['evidence'] !== '' && (!filter_var($new['evidence'], FILTER_VALIDATE_URL) || !in_array(strtolower(parse_url($new['evidence'], PHP_URL_SCHEME) ?? ''), ['http','https'], true))) {
                throw new \InvalidArgumentException('La referencia de evidencia debe ser un enlace http o https.');
            }
        } else {
            $stat = nuva_integer($this->text($input, 'stat_type_id', 12, true), 'Estadística');
            $definition = null;
            foreach ($this->definitions($workspace) as $candidate) {
                if ((int)$candidate['id'] === $stat) {
                    $definition = $candidate;
                    break;
                }
            }

            if (!$definition) {
                throw new \InvalidArgumentException('Selecciona una estadística manual del entorno.');
            }
            $this->available($tournament, 'manual:' . $stat);
            $isTeam = $definition['target'] === 'team';
            $targetField = 'person_id';
            if ($isTeam) {
                $targetField = 'team_id';
            }
            $target = nuva_integer($this->text($input, $targetField, 12, true), 'Persona o equipo');
            if ($isTeam) {
                $targetRecord = $this->query('SELECT id,name FROM tournament_teams WHERE id=? AND tournament_id=?', [$target,$id])->fetch();
            } else {
                $targetRecord = $this->query('SELECT tm.id,u.name FROM tournament_members tm JOIN users u ON u.id=tm.member_id WHERE tm.id=? AND tm.tournament_id=?', [$target,$id])->fetch();
            }

            if (!$targetRecord) {
                throw new \InvalidArgumentException('La persona o el equipo debe estar inscrito en este torneo.');
            }

            $value = Physics::number($input, 'value', true);
            if ($definition['data_type'] === 'integer' && floor($value) !== $value) {
                throw new \InvalidArgumentException('Esta estadística admite números enteros.');
            }

            $new = [
                'stat_type_id' => $stat,
                'definition' => $definition,
                'target_id' => $target,
                'target_name' => $targetRecord['name'],
                'value' => $value,
                'date' => $this->date($input),
                'demonstration' => isset($input['demonstration']) ? $input['demonstration'] === '1' : !empty($old['demonstration']),
                'reason' => $reason,
                'invalid' => false
            ];
        }

        $new['tournament_id'] = $id;
        $new['actor_id'] = $actor;
        $new['record_id'] = $recordId;
        $new['edition'] = (int)($old['edition'] ?? 0) + 1;
        $this->append($workspace, $actor, $type, $recordId, $old, $new, !empty($new['invalid']) ? 'stat_invalidate' : ($old ? 'stat_correct' : 'stat_record'));
        $this->project($tournament, $actor);
    }
    private function date(array $input): string
    {
        $text = $this->text($input, 'date', 30, true);
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $text, new \DateTimeZone('America/Montevideo'));
        if (!$date || $date->format('Y-m-d\TH:i') !== $text) {
            throw new \InvalidArgumentException('Revisa la fecha y hora de la prueba.');
        }
        return $date->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }
    // El diario mantiene versiones completas; las tablas numéricas contienen proyecciones reconstruibles.
    public function records(array $tournament, string $type): array
    {
        if (!in_array($type, ['trial','manual'], true)) {
            throw new \InvalidArgumentException('Tipo de registro inválido.');
        }

        $rows = $this->query('SELECT l.id,l.new_value FROM workspace_audit_logs l JOIN (SELECT entity_id,MAX(id) AS last_id FROM workspace_audit_logs WHERE workspace_id=? AND entity_type=? GROUP BY entity_id) latest ON latest.last_id=l.id ORDER BY l.id DESC', [$tournament['workspace_id'],'stat_' . $type])->fetchAll();
        $records = [];
        foreach ($rows as $row) {
            $record = json_decode($row['new_value'], true, 64, JSON_THROW_ON_ERROR);
            if ((int)$record['tournament_id'] === (int)$tournament['id']) {
                $record['_revision'] = (int)$row['id'];
                $records[] = $record;
            }
        }

        return $records;
    }
    public function history(array $tournament, string $type, string $record): array
    {
        if (!in_array($type, ['trial','manual'], true)) {
            return [];
        }

        $rows = $this->query('SELECT l.new_value,l.created_at,u.name AS actor_name FROM workspace_audit_logs l JOIN users u ON u.id=l.user_id WHERE l.workspace_id=? AND l.entity_type=? AND l.entity_id=? ORDER BY l.id DESC', [$tournament['workspace_id'],'stat_' . $type,$record])->fetchAll();
        $out = [];
        foreach ($rows as $row) {
            $record = json_decode($row['new_value'], true, 64, JSON_THROW_ON_ERROR);
            if ((int)$record['tournament_id'] === (int)$tournament['id']) {
                $out[] = $record + ['changed_at' => $row['created_at'],'actor_name' => $row['actor_name']];
            }
        }
        return $out;
    }
    public function visible(array $tournament, array $records, string $type, bool $manager): array
    {
        if ($manager) {
            return $records;
        }

        $options = $this->configuration($tournament)['options'];
        $visibleRecords = [];
        foreach ($records as $record) {
            if ($record['invalid']) {
                continue;
            }

            if ($type === 'manual') {
                $optionCode = 'manual:' . $record['stat_type_id'];
                if (!empty($options[$optionCode]['visible'])) {
                    $visibleRecords[] = $record;
                }
                continue;
            }

            // Primero se decide qué plantillas puede consultar el visitante.
            $visibleTemplates = [];
            foreach ($record['templates'] as $templateCode) {
                if (!empty($options['physics:' . $templateCode]['visible'])) {
                    $visibleTemplates[] = $templateCode;
                }
            }

            if (empty($visibleTemplates)) {
                continue;
            }

            $record['templates'] = $visibleTemplates;

            $visibleResults = [];
            foreach ($record['outputs'] as $metricCode => $result) {
                $templateCode = Physics::METRICS[$metricCode][2];
                if (in_array($templateCode, $visibleTemplates, true)) {
                    $visibleResults[$metricCode] = $result;
                }
            }

            $record['outputs'] = $visibleResults;

            // También se retiran las mediciones de plantillas ocultas. No basta con ocultar sus tarjetas.
            $allowedFields = ['mrua', 'from_rest', 'constant_acceleration', 'independent_inputs'];
            $fieldsByTemplate = [
                'motion' => ['distance', 'time', 'segment_distance', 'segment_time', 'u_distance', 'u_time', 'u_segment_distance', 'u_segment_time'],

                'dynamics' => ['mass', 'u_mass', 'acceleration_source', 'acceleration', 'u_acceleration', 'segment_distance', 'segment_time', 'u_segment_distance', 'u_segment_time'],

                'energy' => ['mass', 'u_mass', 'speed_source', 'speed', 'u_speed', 'initial_speed', 'u_initial_speed', 'work_mode', 'force', 'u_force', 'work_distance', 'u_work_distance', 'angle', 'u_angle', 'constant_force', 'segment_distance', 'segment_time', 'u_segment_distance', 'u_segment_time'],
            ];
            foreach ($visibleTemplates as $templateCode) {
                $allowedFields = array_merge($allowedFields, $fieldsByTemplate[$templateCode]);
            }

            $visibleInputs = [];
            foreach ($record['inputs'] as $field => $value) {
                if (in_array($field, $allowedFields, true)) {
                    $visibleInputs[$field] = $value;
                }
            }

            $record['inputs'] = $visibleInputs;

            $visibleNotes = [];
            foreach ($record['notes'] as $note) {
                if (str_contains($note, 'Trabajo y cambio') && !in_array('energy', $visibleTemplates, true)) {
                    continue;
                }

                $visibleNotes[] = $note;
            }

            $record['notes'] = $visibleNotes;
            $visibleRecords[] = $record;
        }

        return $visibleRecords;
    }

    public function manualSummaries(array $records): array
    {
        // Cada estadística, persona/equipo y origen de datos tiene su propio resumen.
        $groups = [];
        foreach ($records as $record) {
            if ($record['invalid']) {
                continue;
            }

            $origin = $record['demonstration'] ? 1 : 0;
            $groupKey = $record['stat_type_id'] . ':' . $record['target_id'] . ':' . $origin;
            $groups[$groupKey][] = $record;
        }

        $summaries = [];
        foreach ($groups as $rows) {
            usort($rows, [self::class, 'newestFirst']);
            $latestRecord = $rows[0];
            $values = array_column($rows, 'value');
            $rule = $latestRecord['definition']['rule'];
            $summary = $latestRecord['value'];

            if ($rule === 'sum') {
                $summary = array_sum($values);
            } elseif ($rule === 'average') {
                $summary = array_sum($values) / count($values);
            } elseif ($rule === 'min') {
                $summary = min($values);
            } elseif ($rule === 'max') {
                $summary = max($values);
            }

            $latestRecord['summary'] = $summary;
            $latestRecord['count'] = count($values);
            $summaries[] = $latestRecord;
        }

        return $summaries;
    }

    private function projection(int $id, int $stat, ?int $member, ?int $team, float $value): void
    {
        $row = $this->query('SELECT id FROM tournament_participant_stat_values WHERE id_tournament=? AND stat_type_id=? AND member_id <=> ? AND team_id <=> ? ORDER BY id LIMIT 1', [$id,$stat,$member,$team])->fetchColumn();
        if ($row) {
            $this->query('UPDATE tournament_participant_stat_values SET value=?,updated_at=UTC_TIMESTAMP() WHERE id=?', [$value,$row]);
        } else {
            $this->query('INSERT INTO tournament_participant_stat_values(id_tournament,stat_type_id,member_id,team_id,value,updated_at) VALUES(?,?,?,?,?,UTC_TIMESTAMP())', [$id,$stat,$member,$team,$value]);
        }
    }
    private function project(array $tournament, int $actor): void
    {
        $statisticIds = $this->ensurePhysicalTypes((int) $tournament['workspace_id'], $actor);
        $trials = $this->records($tournament, 'trial');
        $manual = $this->records($tournament, 'manual');
        $managedIds = array_values($statisticIds);
        foreach ($manual as $record) {
            $managedIds[] = (int) $record['stat_type_id'];
        }

        foreach (array_unique($managedIds) as $statisticId) {
            $this->query(
                'DELETE FROM tournament_participant_stat_values WHERE id_tournament=? AND stat_type_id=?',
                [$tournament['id'], $statisticId]
            );
        }

        // Las tablas numéricas se reconstruyen con los registros reales vigentes.
        $realTrials = [];
        $realManual = [];
        foreach ($trials as $trial) {
            if (!$trial['invalid'] && !$trial['demonstration']) {
                $realTrials[] = $trial;
            }
        }

        foreach ($manual as $record) {
            if (!$record['demonstration']) {
                $realManual[] = $record;
            }
        }

        usort($realTrials, [self::class, 'newestFirst']);

        $alreadySaved = [];
        foreach ($realTrials as $trial) {
            foreach ($trial['outputs'] as $metricCode => $result) {
                $key = $trial['participant_id'] . ':' . $metricCode;
                if (isset($alreadySaved[$key])) {
                    continue;
                }

                $alreadySaved[$key] = true;
                $this->projection((int) $tournament['id'], $statisticIds[$metricCode], (int) $trial['participant_id'], null, $result['value']);
            }
        }

        foreach ($this->manualSummaries($realManual) as $summary) {
            $memberId = null;
            $teamId = null;
            if ($summary['definition']['target'] === 'person') {
                $memberId = (int) $summary['target_id'];
            } else {
                $teamId = (int) $summary['target_id'];
            }

            $this->projection((int) $tournament['id'], (int) $summary['stat_type_id'], $memberId, $teamId, $summary['summary']);
        }
    }

    public static function newestFirst(array $first, array $second): int
    {
        $dateOrder = strcmp($second['date'], $first['date']);
        if ($dateOrder !== 0) {
            return $dateOrder;
        }

        return strcmp($second['record_id'], $first['record_id']);
    }

}
