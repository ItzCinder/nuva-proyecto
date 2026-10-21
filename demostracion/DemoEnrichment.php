<?php

use app\Models\Competition;
use app\Models\Role;
use app\Models\Tournament;
use App\Modules\Auditoria\Models\Audit;
use App\Modules\Estadisticas\Models\Statistics;

// Completa únicamente conjuntos reconocidos de demostración, nunca entornos personales.
class DemoEnrichment
{
    private PDO $database;
    private Tournament $tournaments;
    private Competition $competition;
    private Audit $audit;
    public array $counts = ['entornos' => 0, 'torneos' => 0, 'pruebas' => 0, 'valores' => 0];

    public function __construct()
    {
        $this->database = Database::getConnection();
        $this->tournaments = new Tournament();
        $this->competition = new Competition();
        $this->audit = new Audit();
    }

    public function load(int $ownerId): void
    {
        if (!$ownerId) {
            $statement = $this->database->prepare('SELECT id FROM users WHERE google_id=?');
            $statement->execute(['nuva-demo-completa-v1:0:0']);
            $ownerId = (int)$statement->fetchColumn();
        }
        $legacyNames = [
            'Club del Sur · Demo',
            'Club de Estrategia · Demo',
            'Arena Digital · Demo',
            'Competiciones en acción · Demo',
            'Comunidad del Río · Demo',
            'Centro de Estrategia · Demo'
        ];
        $workspaceNames = $legacyNames;
        foreach (require __DIR__ . '/entidades.php' as $entity) {
            $workspaceNames[] = $entity['name'] . ' · Demo Nuva';
        }
        $placeholders = implode(',', array_fill(0, count($workspaceNames), '?'));
        $statement = $this->database->prepare('SELECT * FROM workspaces WHERE owner_id=? AND name IN (' . $placeholders . ') ORDER BY id');
        $statement->execute(array_merge([$ownerId], $workspaceNames));
        foreach ($statement->fetchAll() as $workspace) {
            $workspaceId = (int)$workspace['id'];
            $statistics = new Statistics();
            if ($statistics->latest($workspaceId, 'demo', 'complete-v2:' . $workspaceId)) {
                continue;
            }
            if (!$this->hasDemoMembers($workspaceId)) {
                continue;
            }
            $description = DemoStories::workspaceDescription($workspace['name']);
            $this->query('UPDATE workspaces SET description=? WHERE id=?', [$description, $workspaceId]);
            $this->audit->record($workspaceId, $ownerId, 'workspace', (string)$workspaceId, 'update', ['description' => $workspace['description']], ['description' => $description]);
            $tournamentRows = $this->query('SELECT id FROM tournament WHERE workspace_id=? ORDER BY id', [$workspaceId])->fetchAll(PDO::FETCH_COLUMN);
            foreach ($tournamentRows as $tournamentId) {
                $tournament = $this->tournaments->find((int)$tournamentId);
                if (!$this->isDemoTournament($tournament)) {
                    continue;
                }
                $this->fillTeams($tournament, $ownerId);
                $this->prepareRules($tournament);
                $tournament = $this->tournaments->find((int)$tournamentId);
                $this->developTournament($tournament, $ownerId);
                $tournament = $this->tournaments->find((int)$tournamentId);
                $this->completeCalendar($tournament, $ownerId, str_contains($workspace['name'], 'Demo Nuva'));
                $tournament = $this->tournaments->find((int)$tournamentId);
                $details = new DemoStatistics();
                $result = $details->load($tournament, $ownerId);
                $this->counts['pruebas'] += $result['trials'];
                $this->counts['valores'] += $result['manual'];
                $this->counts['torneos']++;
            }
            $this->audit->record(
                $workspaceId,
                $ownerId,
                'stat_demo',
                'complete-v2:' . $workspaceId,
                'stat_record',
                null,
                ['complete' => true, 'detail' => 'La comisión cotejó inscripciones, actas y fichas de entrenamiento de la temporada.']
            );
            $this->counts['entornos']++;
            echo 'Preparado: ' . $workspace['name'] . ".\n";
        }
    }

    private function hasDemoMembers(int $workspaceId): bool
    {
        return (bool)$this->query("SELECT 1 FROM workspace_members members JOIN users ON users.id=members.user_id WHERE members.workspace_id=? AND (users.google_id LIKE 'nuva-demo:%' OR users.google_id LIKE 'nuva-competition-demo:%' OR users.google_id LIKE 'nuva-extra-demo:%' OR users.google_id LIKE 'nuva-demo-completa-v1:%') LIMIT 1", [$workspaceId])->fetchColumn();
    }

    private function isDemoTournament(array $tournament): bool
    {
        if (preg_match('/^(Liga|Abierto suizo|Copa) de (Fútbol|Fútbol sala|Básquetbol|Counter-Strike 2|Valorant|Ajedrez) · (Planificado|En curso|Finalizado) · Demo$/uD', $tournament['name'])) {
            return true;
        }
        // Nombres de las entregas anteriores; no se incluyen torneos nuevos del usuario.
        $names = [
            'Liga de futsal de primavera',
            'Copa de pádel del Sur',
            'Circuito de escalada',
            'Abierto de ajedrez de octubre',
            'Copa de damas',
            'Liga de Go',
            'Copa Valorant Arena',
            'Suizo de Counter-Strike 2',
            'Liga EA Sports FC',
            'Liga de vóleibol · En curso',
            'Liga de precisión · Finalizada',
            'Abierto de ajedrez · En curso',
            'Suizo de Rocket League · Finalizado',
            'Copa de pádel · En curso',
            'Copa de velocidad · Finalizada',
            'Liga de fútbol del Río · Finalizada',
            'Copa de tenis del Río · Finalizada',
            'Suizo de pádel del Río · En curso',
            'Liga de Go · Finalizada',
            'Abierto de estrategia · Finalizado',
            'Copa de videojuegos · En curso'
        ];
        return in_array($tournament['name'], $names, true);
    }

    private function fillTeams(array $tournament, int $actor): void
    {
        if (!$tournament['has_team']) {
            return;
        }
        $tournamentId = (int)$tournament['id'];
        $teams = $this->tournaments->teams($tournamentId);
        if (!$this->tournaments->hasMatches($tournamentId)) {
            while (count($teams) < 4) {
                $this->tournaments->createTeam($tournament, $actor, ['Aurora', 'Horizonte', 'Centella', 'Nexo'][count($teams)] . ' Juvenil');
                $teams = $this->tournaments->teams($tournamentId);
            }
        }
        $participants = $this->tournaments->participants($tournamentId);
        $registered = array_column($participants, 'user_id');
        $available = $this->query("SELECT id FROM users WHERE google_id LIKE ? ORDER BY id", ['nuva-demo-completa-v1:' . $actor . ':%'])->fetchAll(PDO::FETCH_COLUMN);
        if (!$available) {
            $available = $this->query("SELECT id FROM users WHERE google_id LIKE 'nuva-demo-completa-v1:0:%' ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
        }
        $roleId = Role::findId(Role::PARTICIPANT);
        foreach ($teams as $team) {
            $size = count(array_filter($participants, fn($participant) => (int)$participant['team_id'] === (int)$team['id']));
            foreach ($available as $personId) {
                if ($size >= (int)$tournament['team_size']) {
                    break;
                }
                if (in_array($personId, $registered)) {
                    continue;
                }
                // La carga puede completar un plantel antiguo sin alterar los cruces de equipos.
                if (!$this->query('SELECT 1 FROM workspace_members WHERE workspace_id=? AND user_id=?', [$tournament['workspace_id'], $personId])->fetchColumn()) {
                    $this->query('INSERT INTO workspace_members(workspace_id,user_id,role_id,assigned_at) VALUES(?,?,?,UTC_TIMESTAMP())', [$tournament['workspace_id'], $personId, $roleId]);
                }
                $this->query('INSERT INTO tournament_members(tournament_id,member_id,role_id,team_id) VALUES(?,?,?,?)', [$tournamentId, $personId, $roleId, $team['id']]);
                $registered[] = $personId;
                $size++;
            }
        }
    }

    private function prepareRules(array $tournament): void
    {
        if ($tournament['format_code'] !== 'swiss' || $this->tournaments->hasMatches((int)$tournament['id'])) {
            return;
        }
        $entrants = $tournament['has_team'] ? count($this->tournaments->teams((int)$tournament['id'])) : count($this->tournaments->participants((int)$tournament['id']));
        $rounds = min((int)$tournament['swiss_rounds'], max(1, $entrants - 1));
        $this->query('UPDATE tournament_config SET swiss_rounds=? WHERE id=?', [$rounds, $tournament['id']]);
    }

    private function developTournament(array $tournament, int $actor): void
    {
        if ($tournament['status'] === 'Planificado') {
            return;
        }
        $tournamentId = (int)$tournament['id'];
        $finishAll = $tournament['status'] === 'Finalizado';
        $snapshot = $this->competition->snapshot($tournament);
        if (!$snapshot['rounds']) {
            $this->query('UPDATE tournament SET status=? WHERE id=?', ['Planificado', $tournamentId]);
            $this->competition->operate($tournamentId, $actor, 'generate', []);
        }
        for ($iteration = 0; $iteration < 64; $iteration++) {
            $current = $this->tournaments->find($tournamentId);
            $snapshot = $this->competition->snapshot($current);
            $closed = array_filter($snapshot['rounds'], fn($round) => $round['status'] === 'Cerrada');
            if (!$finishAll && $closed) {
                if ($current['format_code'] === 'swiss' && count($closed) === count($snapshot['rounds']) && count($closed) < (int)$current['swiss_rounds']) {
                    $this->competition->operate($tournamentId, $actor, 'next_round', []);
                }
                return;
            }
            $openRound = null;
            foreach ($snapshot['rounds'] as $round) {
                if ($round['status'] !== 'Cerrada') {
                    $openRound = $round;
                    break;
                }
            }
            if (!$openRound) {
                if ($current['format_code'] === 'swiss' && count($snapshot['rounds']) < (int)$current['swiss_rounds']) {
                    $this->competition->operate($tournamentId, $actor, 'next_round', []);
                    continue;
                }
                return;
            }
            foreach ($snapshot['games'] as $match) {
                if ((int)$match['round_no'] !== (int)$openRound['number'] || in_array($match['status'], ['Finalizado', 'Libre'], true)) {
                    continue;
                }
                $scores = $this->scores($current, $match);
                $notes = DemoStories::matchNotes($current, array_merge($match, ['status' => 'Finalizado', 'score_1' => $scores[0], 'score_2' => $scores[1]]));
                $firstWins = ($current['score_order'] === 'lower') ? $scores[0] < $scores[1] : $scores[0] > $scores[1];
                $this->competition->operate($tournamentId, $actor, 'save_result', [
                    'match_id' => (string)$match['id'],
                    'revision' => (string)$match['revision'],
                    'score_1' => (string)$scores[0],
                    'score_2' => (string)$scores[1],
                    'outcome' => $scores[0] === $scores[1] ? 'draw' : ($firstWins ? '1' : '2'),
                    'notes' => $notes,
                ]);
            }
            $this->competition->operate($tournamentId, $actor, 'close_round', ['round_no' => (string)$openRound['number']]);
        }
        throw new RuntimeException('No se pudo completar el desarrollo del torneo ' . $tournamentId);
    }

    private function scores(array $tournament, array $match): array
    {
        $key = $tournament['id'] . ':' . $match['id'];
        if ($tournament['discipline_category'] === 'mental') {
            return DemoStories::number($key, 0, 1) ? [1, 0] : [0, 1];
        }
        if ($tournament['discipline_category'] === 'esports') {
            return DemoStories::number($key, 0, 1) ? [2, 0] : [1, 2];
        }
        if (str_contains($tournament['discipline'], 'Básquet')) {
            return [DemoStories::number($key . ':a', 65, 88), DemoStories::number($key . ':b', 89, 104)];
        }
        return DemoStories::number($key, 0, 1) ? [3, 1] : [0, 2];
    }

    private function completeCalendar(array $tournament, int $actor, bool $varyScores): void
    {
        $today = new DateTimeImmutable('today', new DateTimeZone('America/Montevideo'));
        $start = $today->modify('-14 days')->setTime(9, 0);
        $end = $today->modify('+14 days')->setTime(22, 0);
        if ($tournament['status'] === 'Finalizado') {
            $start = $today->modify('-45 days')->setTime(9, 0);
            $end = $today->modify('-2 days')->setTime(22, 0);
        } elseif ($tournament['status'] === 'Planificado') {
            $start = $today->modify('+7 days')->setTime(9, 0);
            $end = $today->modify('+35 days')->setTime(22, 0);
        }
        $description = DemoStories::tournamentDescription($tournament);
        $utc = new DateTimeZone('UTC');
        $this->query('UPDATE tournament SET description=?,start_date=?,end_date=? WHERE id=?', [$description, $start->setTimezone($utc)->format('Y-m-d H:i:s'), $end->setTimezone($utc)->format('Y-m-d H:i:s'), $tournament['id']]);
        $this->audit->record(
            (int)$tournament['workspace_id'],
            $actor,
            'tournament',
            (string)$tournament['id'],
            'update',
            ['description' => $tournament['description'], 'start_date' => $tournament['start_date'], 'end_date' => $tournament['end_date']],
            ['description' => $description, 'start_date' => $start->setTimezone($utc)->format('Y-m-d H:i:s'), 'end_date' => $end->setTimezone($utc)->format('Y-m-d H:i:s')]
        );
        $snapshot = $this->competition->snapshot($tournament);
        $rounds = max(1, count($snapshot['rounds']));
        foreach ($snapshot['games'] as $match) {
            if ($match['status'] === 'Libre') {
                continue;
            }
            if ($match['status'] === 'Finalizado') {
                $lastDay = $tournament['status'] === 'Finalizado' ? $end->modify('-1 day') : $today->modify('-2 days');
                $span = $lastDay->getTimestamp() - $start->getTimestamp();
                $offset = (int)($span * (int)$match['round_no'] / $rounds);
                $date = $start->modify('+' . $offset . ' seconds')->setTime(10 + (int)$match['position'], 0);
            } else {
                $offset = (int)(10 * 86400 * ((int)$match['round_no'] - 1) / $rounds);
                $date = $today->modify('+2 days')->modify('+' . $offset . ' seconds')->setTime(10 + (int)$match['position'], 0);
            }
            if ($varyScores && $match['status'] === 'Finalizado') {
                $scores = $this->scores($tournament, $match);
                $winnerScore = max($scores);
                $loserScore = min($scores);
                if ($match['score_1'] == $match['score_2']) {
                    $drawScore = $tournament['discipline_category'] === 'mental' ? 0.5 : $winnerScore;
                    $scores = [$drawScore, $drawScore];
                } else {
                    $scores = (int)$match['winner_id'] === (int)$match['team_1_id'] ? [$winnerScore, $loserScore] : [$loserScore, $winnerScore];
                }
                $match['score_1'] = $scores[0];
                $match['score_2'] = $scores[1];
            }
            $notes = DemoStories::matchNotes($tournament, $match);
            $played = $match['status'] === 'Finalizado' ? $date->modify('+75 minutes')->setTimezone($utc)->format('Y-m-d H:i:s') : null;
            $this->query(
                'UPDATE tournament_match SET scheduled_at=?,played_at=?,notes=?,score_1=?,score_2=? WHERE id=?',
                [$date->setTimezone($utc)->format('Y-m-d H:i:s'), $played, $notes, $match['score_1'], $match['score_2'], $match['id']]
            );
        }
    }

    private function query(string $sql, array $values = []): PDOStatement
    {
        $statement = $this->database->prepare($sql);
        $statement->execute($values);
        return $statement;
    }
}
