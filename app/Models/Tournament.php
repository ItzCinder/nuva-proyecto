<?php
namespace app\Models;

class Tournament extends Model
{
    public const DISCIPLINES = [
        'tradicional' => ['Fútbol', 'Futsal', 'Básquetbol', 'Vóleibol', 'Tenis', 'Pádel', 'Handball', 'Rugby', 'Atletismo', 'Natación', 'Ciclismo', 'Tenis de mesa'],
        'mental' => ['Ajedrez', 'Damas', 'Go'],
        'esports' => ['Counter-Strike 2', 'Valorant', 'League of Legends', 'Dota 2', 'EA Sports FC', 'Rocket League'],
    ];
    public const CATEGORIES = ['tradicional' => 'Deporte tradicional', 'mental' => 'Deporte mental', 'esports' => 'eSports', 'otros' => 'Otros'];
    public const STATUS = ['Planificado', 'En curso', 'Finalizado'];

    public function __construct()
    {
        parent::__construct();
        $this->db->exec("SET time_zone = '+00:00'");
    }

    private function baseQuery(): string
    {
        return 'SELECT t.*, w.name AS workspace_name, w.owner_id, m.code AS format_code, m.name AS format_name,
                c.module_id, c.league_legs, c.swiss_rounds, c.third_place, c.team_size, c.points_win, c.points_draw, c.points_loss, c.allow_draws, c.score_order, c.score_label, u.name AS organizer_name,
                (SELECT COUNT(*) FROM tournament_members tm WHERE tm.tournament_id = t.id) AS member_count,
                (SELECT COUNT(*) FROM tournament_teams tt WHERE tt.tournament_id = t.id) AS team_count
                FROM tournament t JOIN workspaces w ON w.id = t.workspace_id
                JOIN tournament_config c ON c.id = t.id JOIN modules m ON m.id = c.module_id
                LEFT JOIN users u ON u.id = t.organizer_id';
    }

    public function publicList(): array
    {
        return $this->db->query($this->baseQuery() . ' WHERE t.published = 1 ORDER BY t.start_date DESC, t.id DESC')->fetchAll();
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
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $userId, Role::ADMIN, $userId, $userId, Role::ORGANIZER, $userId]);
        return $stmt->fetchAll();
    }

    public function workspaceList(array $workspace, int $userId): array
    {
        $sql = $this->baseQuery() . ' WHERE t.workspace_id = ?';
        $params = [$workspace['id']];
        if ($workspace['role_name'] === Role::ORGANIZER) {
            $sql .= ' AND t.organizer_id = ?'; $params[] = $userId;
        } elseif (!in_array($workspace['role_name'], [Role::OWNER, Role::ADMIN], true)) {
            $sql .= ' AND EXISTS (SELECT 1 FROM tournament_members tm WHERE tm.tournament_id = t.id AND tm.member_id = ?)';
            $params[] = $userId;
        }
        $stmt = $this->db->prepare($sql . ' ORDER BY t.start_date DESC, t.id DESC');
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare($this->baseQuery() . ' WHERE t.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function canSee(array $t, ?int $userId, ?array $workspace): bool
    {
        if ($t['published']) return true;
        if (!$userId || !$workspace) return false;
        if (in_array($workspace['role_name'], [Role::OWNER, Role::ADMIN], true)) return true;
        if ($workspace['role_name'] === Role::ORGANIZER && (int)$t['organizer_id'] === $userId) return true;
        $stmt = $this->db->prepare('SELECT 1 FROM tournament_members WHERE tournament_id = ? AND member_id = ? LIMIT 1');
        $stmt->execute([$t['id'], $userId]);
        return (bool)$stmt->fetchColumn();
    }

    public function canManage(array $t, ?int $userId, ?array $workspace): bool
    {
        return $userId && $workspace && (in_array($workspace['role_name'], [Role::OWNER, Role::ADMIN], true)
            || ($workspace['role_name'] === Role::ORGANIZER && (int)$t['organizer_id'] === $userId));
    }

    public function enabledModules(int $workspaceId): array
    {
        $stmt = $this->db->prepare('SELECT m.id, m.code, m.name, m.description FROM workspace_modules wm
            JOIN modules m ON m.id = wm.module_id WHERE wm.workspace_id = ? AND wm.is_active = 1 ORDER BY m.id');
        $stmt->execute([$workspaceId]);
        return $stmt->fetchAll();
    }

    public function organizers(int $workspaceId): array
    {
        $stmt = $this->db->prepare('SELECT u.id, u.name FROM workspace_members wm JOIN users u ON u.id = wm.user_id
            JOIN roles r ON r.id = wm.role_id WHERE wm.workspace_id = ? AND r.name = ? ORDER BY u.name');
        $stmt->execute([$workspaceId, Role::ORGANIZER]);
        return $stmt->fetchAll();
    }

    public function eligibleMembers(int $workspaceId): array
    {
        $stmt = $this->db->prepare('SELECT u.id, u.name FROM workspace_members wm JOIN users u ON u.id = wm.user_id
            WHERE wm.workspace_id = ? ORDER BY u.name, u.id');
        $stmt->execute([$workspaceId]);
        return $stmt->fetchAll();
    }

    public function participants(int $id): array
    {
        $stmt = $this->db->prepare('SELECT tm.id, tm.team_id, u.name, u.id AS user_id, tt.name AS team_name FROM tournament_members tm
            JOIN users u ON u.id = tm.member_id LEFT JOIN tournament_teams tt ON tt.id = tm.team_id
            WHERE tm.tournament_id = ? ORDER BY tt.name, u.name');
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }

    public function teams(int $id): array
    {
        $stmt = $this->db->prepare('SELECT tt.id, tt.name, COUNT(tm.id) AS member_count FROM tournament_teams tt
            LEFT JOIN tournament_members tm ON tm.team_id = tt.id AND tm.tournament_id = tt.tournament_id
            WHERE tt.tournament_id = ? GROUP BY tt.id, tt.name ORDER BY tt.name');
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }

    public function create(array $values, int $actorId): int
    {
        $stmt = $this->db->prepare('INSERT INTO tournament
            (workspace_id, organizer_id, name, description, discipline_category, discipline, has_team, max_participants, published,
             created_at, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), ?, ?, ?)');
        $stmt->execute([$values['workspace_id'], $values['organizer_id'], $values['name'], $values['description'],
            $values['discipline_category'], $values['discipline'], $values['has_team'], $values['max_participants'],
            $values['published'], $values['start_date'], $values['end_date'], $values['status']]);
        $id = (int)$this->db->lastInsertId();
        $stmt = $this->db->prepare('INSERT INTO tournament_config
            (id, module_id, league_legs, swiss_rounds, third_place, team_size, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())');
        $stmt->execute([$id, $values['module_id'], $values['league_legs'], $values['swiss_rounds'], $values['third_place'], $values['team_size']]);
        $this->saveScoring($id, $values);
        $this->audit((int)$values['workspace_id'], $actorId, $id, 'create', null, $this->auditValues($values));
        return $id;
    }

    public function update(array $old, array $values, int $actorId): void
    {
        if ($this->hasMatches((int)$old['id'])) {
            foreach (['module_id','has_team','league_legs','swiss_rounds','third_place','team_size','points_win','points_draw','points_loss','allow_draws','score_order','score_label','status','start_date','end_date'] as $key) {
                if ((string)$old[$key] !== (string)$values[$key] && (!is_numeric($old[$key]) || !is_numeric($values[$key]) || (float)$old[$key] !== (float)$values[$key])) throw new \InvalidArgumentException('Las reglas, fechas y estado quedan fijos al generar los encuentros. El estado cambia al cerrar las rondas.');
            }
        }
        $stmt = $this->db->prepare('UPDATE tournament SET organizer_id=?, name=?, description=?, discipline_category=?, discipline=?,
            has_team=?, max_participants=?, published=?, start_date=?, end_date=?, status=? WHERE id=?');
        $stmt->execute([$values['organizer_id'], $values['name'], $values['description'], $values['discipline_category'],
            $values['discipline'], $values['has_team'], $values['max_participants'], $values['published'],
            $values['start_date'], $values['end_date'], $values['status'], $old['id']]);
        $stmt = $this->db->prepare('UPDATE tournament_config SET module_id=?, league_legs=?, swiss_rounds=?, third_place=?, team_size=?,
            updated_at=UTC_TIMESTAMP() WHERE id=?');
        $stmt->execute([$values['module_id'], $values['league_legs'], $values['swiss_rounds'], $values['third_place'], $values['team_size'], $old['id']]);
        $this->saveScoring((int)$old['id'], $values);
        $this->audit((int)$old['workspace_id'], $actorId, (int)$old['id'], 'update', $this->auditValues($old), $this->auditValues($values));
    }

    public function enroll(array $t, int $actorId, int $memberId, ?int $teamId): void
    {
        $stmt = $this->db->prepare('SELECT id FROM tournament WHERE id = ? FOR UPDATE');
        $stmt->execute([$t['id']]);
        if ($this->hasMatches((int)$t['id'])) throw new \InvalidArgumentException('Las inscripciones están cerradas: ya se generaron los encuentros.');
        $stmt = $this->db->prepare('SELECT 1 FROM workspace_members WHERE workspace_id = ? AND user_id = ?');
        $stmt->execute([$t['workspace_id'], $memberId]);
        if (!$stmt->fetchColumn()) throw new \InvalidArgumentException('La persona debe pertenecer al entorno.');
        $stmt = $this->db->prepare('SELECT 1 FROM tournament_members WHERE tournament_id = ? AND member_id = ?');
        $stmt->execute([$t['id'], $memberId]);
        if ($stmt->fetchColumn()) throw new \InvalidArgumentException('Esta persona ya está inscrita en el torneo.');
        if ($teamId === null && $t['has_team']) throw new \InvalidArgumentException('Selecciona un equipo.');
        if ($teamId !== null) {
            if (!$t['has_team']) throw new \InvalidArgumentException('Este torneo es individual.');
            $stmt = $this->db->prepare('SELECT COUNT(tm.id) FROM tournament_teams tt LEFT JOIN tournament_members tm
                ON tm.team_id = tt.id WHERE tt.id = ? AND tt.tournament_id = ?');
            $stmt->execute([$teamId, $t['id']]);
            $count = (int)$stmt->fetchColumn();
            if (!$this->teamExists($teamId, (int)$t['id'])) throw new \InvalidArgumentException('Selecciona un equipo del torneo.');
            if ($count >= (int)$t['team_size']) throw new \InvalidArgumentException('El equipo alcanzó su límite de integrantes.');
        }
        if (!$t['has_team']) {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM tournament_members WHERE tournament_id = ?');
            $stmt->execute([$t['id']]);
            if ((int)$stmt->fetchColumn() >= (int)$t['max_participants'])
                throw new \InvalidArgumentException('El torneo alcanzó su capacidad.');
        }
        $stmt = $this->db->prepare('INSERT INTO tournament_members (tournament_id, member_id, role_id, team_id) VALUES (?, ?, ?, ?)');
        $stmt->execute([$t['id'], $memberId, Role::findId(Role::PARTICIPANT), $teamId]);
        $this->audit((int)$t['workspace_id'], $actorId, (int)$t['id'], 'join', null, ['member_id'=>$memberId, 'team_id'=>$teamId]);
    }

    public function createTeam(array $t, int $actorId, string $name): int
    {
        if (!$t['has_team']) throw new \InvalidArgumentException('Este torneo es individual.');
        $stmt = $this->db->prepare('SELECT id FROM tournament WHERE id = ? FOR UPDATE'); $stmt->execute([$t['id']]);
        if ($this->hasMatches((int)$t['id'])) throw new \InvalidArgumentException('Las inscripciones están cerradas.');
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM tournament_teams WHERE tournament_id = ?'); $stmt->execute([$t['id']]);
        if ((int)$stmt->fetchColumn() >= (int)$t['max_participants']) throw new \InvalidArgumentException('Se alcanzó el límite de equipos.');
        $stmt = $this->db->prepare('SELECT 1 FROM tournament_teams WHERE tournament_id = ? AND name = ?'); $stmt->execute([$t['id'], $name]);
        if ($stmt->fetchColumn()) throw new \InvalidArgumentException('Ya existe un equipo con ese nombre.');
        $stmt = $this->db->prepare('INSERT INTO tournament_teams (tournament_id, name, created_at) VALUES (?, ?, UTC_TIMESTAMP())');
        $stmt->execute([$t['id'], $name]);
        $id=(int)$this->db->lastInsertId();
        $this->audit((int)$t['workspace_id'], $actorId, $id, 'create', null, ['team_name'=>$name]);
        return $id;
    }

    public function hasMatches(int $id): bool
    {
        $stmt=$this->db->prepare('SELECT 1 FROM tournament_match WHERE id_tournament=? LIMIT 1');
        $stmt->execute([$id]); return (bool)$stmt->fetchColumn();
    }

    private function saveScoring(int $id, array $v): void
    {
        $stmt=$this->db->prepare('UPDATE tournament_config SET points_win=?,points_draw=?,points_loss=?,allow_draws=?,score_order=?,score_label=? WHERE id=?');
        $stmt->execute([$v['points_win'] ?? 3,$v['points_draw'] ?? 1,$v['points_loss'] ?? 0,$v['allow_draws'] ?? 1,$v['score_order'] ?? 'higher',$v['score_label'] ?? 'Marcador',$id]);
    }

    private function teamExists(int $teamId, int $tournamentId): bool
    {
        $stmt=$this->db->prepare('SELECT 1 FROM tournament_teams WHERE id=? AND tournament_id=?');
        $stmt->execute([$teamId,$tournamentId]);
        return (bool)$stmt->fetchColumn();
    }

    private function auditValues(array $v): array
    {
        return array_intersect_key($v, array_flip(['name','description','discipline_category','discipline','has_team','max_participants',
            'published','start_date','end_date','status','organizer_id','module_id','league_legs','swiss_rounds','third_place','team_size','points_win','points_draw','points_loss','allow_draws','score_order','score_label']));
    }

    private function audit(int $workspaceId, int $actorId, int $entityId, string $action, ?array $before, ?array $after): void
    {
        $stmt=$this->db->prepare('INSERT INTO workspace_audit_logs
            (workspace_id,user_id,entity_type,entity_id,action,old_value,new_value,created_at)
            VALUES (?,?,?,?,?,?,?,UTC_TIMESTAMP())');
        $stmt->execute([$workspaceId,$actorId,'tournament',(string)$entityId,$action,
            $before === null ? null : json_encode($before, JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),
            $after === null ? null : json_encode($after, JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
    }
}
