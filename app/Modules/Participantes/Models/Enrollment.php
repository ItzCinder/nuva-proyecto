<?php

namespace App\Modules\Participantes\Models;

use app\Models\Model;
use app\Models\Role;

class Enrollment extends Model
{
    public function enroll(array $tournament, int $actorId, int $memberId, ?int $teamId): void
    {
        $statement = $this->database->prepare('SELECT id FROM tournament WHERE id = ? FOR UPDATE');
        $statement->execute([$tournament['id']]);
        if ((new \app\Models\Tournament())->hasMatches((int)$tournament['id'])) {
            throw new \InvalidArgumentException('Las inscripciones están cerradas: ya se generaron los encuentros.');
        }

        $statement = $this->database->prepare('SELECT 1 FROM workspace_members WHERE workspace_id = ? AND user_id = ?');
        $statement->execute([$tournament['workspace_id'], $memberId]);
        if (!$statement->fetchColumn()) {
            throw new \InvalidArgumentException('La persona debe pertenecer al entorno.');
        }

        $statement = $this->database->prepare('SELECT 1 FROM tournament_members WHERE tournament_id = ? AND member_id = ?');
        $statement->execute([$tournament['id'], $memberId]);
        if ($statement->fetchColumn()) {
            throw new \InvalidArgumentException('Esta persona ya está inscrita en el torneo.');
        }

        if ($teamId === null && $tournament['has_team']) {
            throw new \InvalidArgumentException('Selecciona un equipo.');
        }

        if ($teamId !== null) {
            if (!$tournament['has_team']) {
                throw new \InvalidArgumentException('Este torneo es individual.');
            }

            $statement = $this->database->prepare('SELECT COUNT(tm.id) FROM tournament_teams tt LEFT JOIN tournament_members tm
                ON tm.team_id = tt.id WHERE tt.id = ? AND tt.tournament_id = ?');
            $statement->execute([$teamId, $tournament['id']]);
            $count = (int)$statement->fetchColumn();
            if (!$this->teamExists($teamId, (int)$tournament['id'])) {
                throw new \InvalidArgumentException('Selecciona un equipo del torneo.');
            }

            if ($count >= (int)$tournament['team_size']) {
                throw new \InvalidArgumentException('El equipo alcanzó su límite de integrantes.');
            }
        }

        if (!$tournament['has_team']) {
            $statement = $this->database->prepare('SELECT COUNT(*) FROM tournament_members WHERE tournament_id = ?');
            $statement->execute([$tournament['id']]);
            if ((int)$statement->fetchColumn() >= (int)$tournament['max_participants']) {
                throw new \InvalidArgumentException('El torneo alcanzó su capacidad.');
            }
        }

        $statement = $this->database->prepare('INSERT INTO tournament_members (tournament_id, member_id, role_id, team_id) VALUES (?, ?, ?, ?)');
        $statement->execute([$tournament['id'], $memberId, Role::findId(Role::PARTICIPANT), $teamId]);
        $this->audit((int)$tournament['workspace_id'], $actorId, (int)$tournament['id'], 'join', null, ['member_id' => $memberId, 'team_id' => $teamId]);
    }
    public function createTeam(array $tournament, int $actorId, string $name): int
    {
        if (!$tournament['has_team']) {
            throw new \InvalidArgumentException('Este torneo es individual.');
        }

        $statement = $this->database->prepare('SELECT id FROM tournament WHERE id = ? FOR UPDATE');
        $statement->execute([$tournament['id']]);
        if ((new \app\Models\Tournament())->hasMatches((int)$tournament['id'])) {
            throw new \InvalidArgumentException('Las inscripciones están cerradas.');
        }

        $statement = $this->database->prepare('SELECT COUNT(*) FROM tournament_teams WHERE tournament_id = ?');
        $statement->execute([$tournament['id']]);
        if ((int)$statement->fetchColumn() >= (int)$tournament['max_participants']) {
            throw new \InvalidArgumentException('Se alcanzó el límite de equipos.');
        }

        $statement = $this->database->prepare('SELECT 1 FROM tournament_teams WHERE tournament_id = ? AND name = ?');
        $statement->execute([$tournament['id'], $name]);
        if ($statement->fetchColumn()) {
            throw new \InvalidArgumentException('Ya existe un equipo con ese nombre.');
        }

        $statement = $this->database->prepare('INSERT INTO tournament_teams (tournament_id, name, created_at) VALUES (?, ?, UTC_TIMESTAMP())');
        $statement->execute([$tournament['id'], $name]);
        $id = (int)$this->database->lastInsertId();
        $this->audit((int)$tournament['workspace_id'], $actorId, $id, 'create', null, ['team_name' => $name]);
        return $id;
    }
    private function teamExists(int $teamId, int $tournamentId): bool
    {
        $statement = $this->database->prepare('SELECT 1 FROM tournament_teams WHERE id=? AND tournament_id=?');
        $statement->execute([$teamId,$tournamentId]);
        return (bool)$statement->fetchColumn();
    }
    private function audit(int $workspaceId, int $actorId, int $entityId, string $action, ?array $before, ?array $after): void
    {
        (new \App\Modules\Auditoria\Models\Audit())->record($workspaceId, $actorId, 'tournament', (string)$entityId, $action, $before, $after);
    }
}
