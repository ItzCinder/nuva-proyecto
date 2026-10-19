<?php

namespace app\Models;

class Tournament extends Model
{
    public const DISCIPLINES = [
        'tradicional' => ['Fútbol', 'Futsal', 'Básquetbol', 'Vóleibol', 'Tenis', 'Pádel', 'Handball', 'Rugby', 'Atletismo', 'Natación', 'Ciclismo', 'Tenis de mesa'],

        'mental' => ['Ajedrez', 'Damas', 'Go'],

        'esports' => ['Counter-Strike 2', 'Valorant', 'League of Legends', 'Dota 2', 'EA Sports FC', 'Rocket League'],
    ];
    public const CATEGORIES = [
        'tradicional' => 'Deporte tradicional',
        'mental' => 'Deporte mental',
        'esports' => 'eSports',
        'otros' => 'Otros'
    ];
    public const STATUS = ['Planificado', 'En curso', 'Finalizado'];

    public function __construct()
    {
        parent::__construct();
        $this->database->exec("SET time_zone = '+00:00'");
    }

    private function baseQuery(): string
    {
        return 'SELECT t.*, w.name AS workspace_name, w.picture AS workspace_picture, w.owner_id, m.code AS format_code, m.name AS format_name,
                c.module_id, c.league_legs, c.swiss_rounds, c.third_place, c.team_size, c.points_win, c.points_draw, c.points_loss, c.allow_draws, c.score_order, c.score_label, u.name AS organizer_name,
                (SELECT COUNT(*) FROM tournament_members tm WHERE tm.tournament_id = t.id) AS member_count,
                (SELECT COUNT(*) FROM tournament_teams tt WHERE tt.tournament_id = t.id) AS team_count
                FROM tournament t JOIN workspaces w ON w.id = t.workspace_id
                JOIN tournament_config c ON c.id = t.id JOIN modules m ON m.id = c.module_id
                LEFT JOIN users u ON u.id = t.organizer_id';
    }

    public function publicList(): array
    {
        return $this->database->query($this->baseQuery() . ' WHERE t.published = 1 ORDER BY t.start_date DESC, t.id DESC')->fetchAll();
    }

    public function myList(int $userId): array
    {
        $sql = $this->baseQuery() . ' WHERE
            ((w.owner_id = ? OR EXISTS (SELECT 1 FROM workspace_members wm JOIN roles r ON r.id = wm.role_id
              WHERE wm.workspace_id = w.id AND wm.user_id = ? AND r.name = ?))
              OR (t.organizer_id = ? AND EXISTS (SELECT 1 FROM workspace_members wm JOIN roles r ON r.id = wm.role_id
              WHERE wm.workspace_id = w.id AND wm.user_id = ? AND r.name = ?))
              OR EXISTS (SELECT 1 FROM tournament_members tm JOIN workspace_members wm
              ON wm.workspace_id = w.id AND wm.user_id = tm.member_id
              WHERE tm.tournament_id = t.id AND tm.member_id = ?)) ORDER BY t.start_date DESC, t.id DESC';
        $statement = $this->database->prepare($sql);
        $statement->execute([$userId, $userId, Role::ADMIN, $userId, $userId, Role::ORGANIZER, $userId]);
        return $statement->fetchAll();
    }

    public function workspaceList(array $workspace, int $userId): array
    {
        $sql = $this->baseQuery() . ' WHERE t.workspace_id = ?';
        $params = [$workspace['id']];
        if ($workspace['role_name'] === Role::ORGANIZER) {
            $sql .= ' AND t.organizer_id = ?';
            $params[] = $userId;
        } elseif (!in_array($workspace['role_name'], [Role::OWNER, Role::ADMIN], true)) {
            $sql .= ' AND EXISTS (SELECT 1 FROM tournament_members tm WHERE tm.tournament_id = t.id AND tm.member_id = ?)';
            $params[] = $userId;
        }

        $statement = $this->database->prepare($sql . ' ORDER BY t.start_date DESC, t.id DESC');
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->database->prepare($this->baseQuery() . ' WHERE t.id = ?');
        $statement->execute([$id]);
        return $statement->fetch() ?: null;
    }

    public function canSee(array $tournament, ?int $userId, ?array $workspace): bool
    {
        if ($tournament['published']) {
            return true;
        }

        if (!$userId || !$workspace) {
            return false;
        }

        if (in_array($workspace['role_name'], [Role::OWNER, Role::ADMIN], true)) {
            return true;
        }

        if ($workspace['role_name'] === Role::ORGANIZER && (int)$tournament['organizer_id'] === $userId) {
            return true;
        }

        $statement = $this->database->prepare('SELECT 1 FROM tournament_members WHERE tournament_id = ? AND member_id = ? LIMIT 1');
        $statement->execute([$tournament['id'], $userId]);
        return (bool)$statement->fetchColumn();
    }

    public function canManage(array $tournament, ?int $userId, ?array $workspace): bool
    {
        return $userId && $workspace && (in_array($workspace['role_name'], [Role::OWNER, Role::ADMIN], true)
            || ($workspace['role_name'] === Role::ORGANIZER && (int)$tournament['organizer_id'] === $userId));
    }

    public function enabledModules(int $workspaceId): array
    {
        $statement = $this->database->prepare('SELECT m.id, m.code, m.name, m.description FROM workspace_modules wm
            JOIN modules m ON m.id = wm.module_id WHERE wm.workspace_id = ? AND wm.is_active = 1 ORDER BY m.id');
        $statement->execute([$workspaceId]);
        return $statement->fetchAll();
    }

    public function organizers(int $workspaceId): array
    {
        $statement = $this->database->prepare('SELECT u.id, u.name FROM workspace_members wm JOIN users u ON u.id = wm.user_id
            JOIN roles r ON r.id = wm.role_id WHERE wm.workspace_id = ? AND r.name = ? ORDER BY u.name');
        $statement->execute([$workspaceId, Role::ORGANIZER]);
        return $statement->fetchAll();
    }

    public function eligibleMembers(int $workspaceId): array
    {
        $statement = $this->database->prepare('SELECT u.id, u.name FROM workspace_members wm JOIN users u ON u.id = wm.user_id
            WHERE wm.workspace_id = ? ORDER BY u.name, u.id');
        $statement->execute([$workspaceId]);
        return $statement->fetchAll();
    }

    public function participants(int $id): array
    {
        $statement = $this->database->prepare('SELECT tm.id, tm.team_id, u.name, u.id AS user_id, tt.name AS team_name FROM tournament_members tm
            JOIN users u ON u.id = tm.member_id LEFT JOIN tournament_teams tt ON tt.id = tm.team_id
            WHERE tm.tournament_id = ? ORDER BY tt.name, u.name');
        $statement->execute([$id]);
        return $statement->fetchAll();
    }

    public function teams(int $id): array
    {
        $statement = $this->database->prepare('SELECT tt.id, tt.name, COUNT(tm.id) AS member_count FROM tournament_teams tt
            LEFT JOIN tournament_members tm ON tm.team_id = tt.id AND tm.tournament_id = tt.tournament_id
            WHERE tt.tournament_id = ? GROUP BY tt.id, tt.name ORDER BY tt.name');
        $statement->execute([$id]);
        return $statement->fetchAll();
    }

    public function create(array $values, int $actorId): int
    {
        $statement = $this->database->prepare('INSERT INTO tournament
            (workspace_id, organizer_id, name, description, discipline_category, discipline, has_team, max_participants, published,
             created_at, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), ?, ?, ?)');
        $statement->execute([$values['workspace_id'], $values['organizer_id'], $values['name'], $values['description'],
            $values['discipline_category'], $values['discipline'], $values['has_team'], $values['max_participants'],
            $values['published'], $values['start_date'], $values['end_date'], $values['status']]);
        $id = (int)$this->database->lastInsertId();
        $statement = $this->database->prepare('INSERT INTO tournament_config
            (id, module_id, league_legs, swiss_rounds, third_place, team_size, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())');
        $statement->execute([$id, $values['module_id'], $values['league_legs'], $values['swiss_rounds'], $values['third_place'], $values['team_size']]);
        $this->saveScoring($id, $values);
        $this->audit((int)$values['workspace_id'], $actorId, $id, 'create', null, $this->auditValues($values));
        (new \App\Modules\Estadisticas\Models\Statistics())->initializeTournament(['id' => $id,'workspace_id' => $values['workspace_id']], $actorId);
        return $id;
    }

    public function update(array $old, array $values, int $actorId): void
    {
        if ($this->hasMatches((int)$old['id'])) {
            foreach (['module_id','has_team','league_legs','swiss_rounds','third_place','team_size','points_win','points_draw','points_loss','allow_draws','score_order','score_label','status','start_date','end_date'] as $key) {
                if ((string)$old[$key] !== (string)$values[$key] && (!is_numeric($old[$key]) || !is_numeric($values[$key]) || (float)$old[$key] !== (float)$values[$key])) {
                    throw new \InvalidArgumentException('Las reglas, fechas y estado quedan fijos al generar los encuentros. El estado cambia al cerrar las rondas.');
                }
            }
        }

        $statement = $this->database->prepare('UPDATE tournament SET organizer_id=?, name=?, description=?, discipline_category=?, discipline=?,
            has_team=?, max_participants=?, published=?, start_date=?, end_date=?, status=? WHERE id=?');
        $statement->execute([$values['organizer_id'], $values['name'], $values['description'], $values['discipline_category'],
            $values['discipline'], $values['has_team'], $values['max_participants'], $values['published'],
            $values['start_date'], $values['end_date'], $values['status'], $old['id']]);
        $statement = $this->database->prepare('UPDATE tournament_config SET module_id=?, league_legs=?, swiss_rounds=?, third_place=?, team_size=?,
            updated_at=UTC_TIMESTAMP() WHERE id=?');
        $statement->execute([$values['module_id'], $values['league_legs'], $values['swiss_rounds'], $values['third_place'], $values['team_size'], $old['id']]);
        $this->saveScoring((int)$old['id'], $values);
        $this->audit((int)$old['workspace_id'], $actorId, (int)$old['id'], 'update', $this->auditValues($old), $this->auditValues($values));
    }

    public function enroll(array $tournament, int $actorId, int $memberId, ?int $teamId): void
    {
        (new \App\Modules\Participantes\Models\Enrollment())->enroll($tournament, $actorId, $memberId, $teamId);
    }

    public function createTeam(array $tournament, int $actorId, string $name): int
    {
        return (new \App\Modules\Participantes\Models\Enrollment())->createTeam($tournament, $actorId, $name);
    }

    public function hasMatches(int $id): bool
    {
        $statement = $this->database->prepare('SELECT 1 FROM tournament_match WHERE id_tournament=? LIMIT 1');
        $statement->execute([$id]);
        return (bool)$statement->fetchColumn();
    }

    private function saveScoring(int $id, array $values): void
    {
        $statement = $this->database->prepare('UPDATE tournament_config SET points_win=?,points_draw=?,points_loss=?,allow_draws=?,score_order=?,score_label=? WHERE id=?');
        $statement->execute([$values['points_win'] ?? 3,$values['points_draw'] ?? 1,$values['points_loss'] ?? 0,$values['allow_draws'] ?? 1,$values['score_order'] ?? 'higher',$values['score_label'] ?? 'Marcador',$id]);
    }

    private function auditValues(array $values): array
    {
        return array_intersect_key($values, array_flip(['name','description','discipline_category','discipline','has_team','max_participants',
            'published','start_date','end_date','status','organizer_id','module_id','league_legs','swiss_rounds','third_place','team_size','points_win','points_draw','points_loss','allow_draws','score_order','score_label']));
    }

    private function audit(int $workspaceId, int $actorId, int $entityId, string $action, ?array $before, ?array $after): void
    {
        (new \App\Modules\Auditoria\Models\Audit())->record($workspaceId, $actorId, 'tournament', (string)$entityId, $action, $before, $after);
    }
}
