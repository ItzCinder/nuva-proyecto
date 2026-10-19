<?php

namespace App\Modules\Auditoria\Models;

use app\Models\Model;

class Audit extends Model
{
    public function recent(int $workspace): array
    {
        $statement = $this->database->prepare('SELECT a.*,u.name AS actor_name FROM workspace_audit_logs a JOIN users u ON u.id=a.user_id WHERE a.workspace_id=? ORDER BY a.id DESC LIMIT 100');
        $statement->execute([$workspace]);
        $logs = $statement->fetchAll();
        $names = $this->displayNames($workspace, $logs);
        foreach ($logs as &$log) {
            $log['display'] = \App\Modules\Auditoria\Services\AuditDisplay::entry($log, $names);
        }
        unset($log);
        return $logs;
    }
    // Resolver los nombres de una sola vez, incluso de miembros que ya se retiraron.
    private function displayNames(int $workspace, array $logs): array
    {
        $userIds = [];
        foreach ($logs as $log) {
            if ($log['entity_type'] === 'member') {
                $userIds[] = (int)$log['entity_id'];
            }
            foreach (['old_value', 'new_value'] as $column) {
                $values = \App\Modules\Auditoria\Services\AuditDisplay::readValues($log[$column]);
                foreach (['organizer_id', 'member_id'] as $field) {
                    if (!empty($values[$field])) {
                        $userIds[] = (int)$values[$field];
                    }
                }
            }
        }
        $names = ['users' => []];
        $userIds = array_unique($userIds);
        if ($userIds) {
            $placeholders = implode(',', array_fill(0, count($userIds), '?'));
            $statement = $this->database->prepare('SELECT id,name FROM users WHERE id IN (' . $placeholders . ')');
            $statement->execute(array_values($userIds));
            $names['users'] = $statement->fetchAll(\PDO::FETCH_KEY_PAIR);
        }
        $queries = [
            'tournaments' => 'SELECT id,name FROM tournament WHERE workspace_id=?',
            'statistics' => 'SELECT id,name FROM member_stat_types WHERE workspace_id=?',
            'teams' => 'SELECT teams.id,teams.name FROM tournament_teams teams JOIN tournament ON tournament.id=teams.tournament_id WHERE tournament.workspace_id=?',
        ];
        foreach ($queries as $group => $sql) {
            $statement = $this->database->prepare($sql);
            $statement->execute([$workspace]);
            $names[$group] = $statement->fetchAll(\PDO::FETCH_KEY_PAIR);
        }
        $names['modules'] = $this->database->query('SELECT id,name FROM modules')->fetchAll(\PDO::FETCH_KEY_PAIR);
        return $names;
    }

    public function record(int $workspace, int $actor, string $entity, string $id, string $action, ?array $before, ?array $after): void
    {
        $statement = $this->database->prepare('INSERT INTO workspace_audit_logs(workspace_id,user_id,entity_type,entity_id,action,old_value,new_value,created_at) VALUES(?,?,?,?,?,?,?,UTC_TIMESTAMP())');
        $statement->execute([$workspace,$actor,$entity,$id,$action,$before === null ? null : json_encode($before, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),$after === null ? null : json_encode($after, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)]);
    }
}
