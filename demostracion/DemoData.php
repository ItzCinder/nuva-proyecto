<?php

use app\Models\Competition;
use app\Models\Role;
use app\Models\Tournament;
use app\Models\Workspace;
use App\Modules\Estadisticas\Models\Statistics;

// Datos ficticios de la entrega. Utiliza los mismos modelos que los formularios.
// El llamador abre la transacción; esta clase no crea tablas ni borra registros.
class DemoData
{
    private PDO $database;
    private Tournament $tournaments;
    private Workspace $workspaces;
    private Competition $competition;
    private Statistics $statistics;
    private int $owner;
    private array $people = [];
    private array $modules = [];
    private array $entity = [];
    private string $state = '';
    private DateTimeImmutable $startDate;
    public array $invitations = [];

    public function __construct()
    {
        $this->database = Database::getConnection();
        $this->tournaments = new Tournament();
        $this->workspaces = new Workspace();
        $this->competition = new Competition();
        $this->statistics = new Statistics();
    }

    public function load(int $ownerId = 0): bool
    {
        // La marca vive en users, una tabla existente. Impide duplicar la carga al reiniciar.
        $marker = 'nuva-demo-completa-v1:' . $ownerId . ':';
        $statement = $this->database->prepare('SELECT id FROM users WHERE google_id=?');
        $statement->execute([$marker . '0']);
        if ($statement->fetchColumn()) {
            return false;
        }
        if ($ownerId !== 0) {
            $statement = $this->database->prepare('SELECT id FROM users WHERE id=?');
            $statement->execute([$ownerId]);
            if (!$statement->fetchColumn()) {
                throw new InvalidArgumentException('No existe la cuenta indicada.');
            }
        }

        $this->updateLegacyPictures();
        $this->createPeople($marker);
        $this->owner = $this->people[0];
        if ($ownerId !== 0) {
            $this->owner = $ownerId;
        }
        $this->modules = array_column($this->database->query('SELECT id, code FROM modules')->fetchAll(), 'id', 'code');

        $entities = require __DIR__ . '/entidades.php';
        foreach ($entities as $entity) {
            $this->entity = $entity;
            $workspaceId = $this->createWorkspace();
            $this->configureStatistics($workspaceId);
            foreach (['league', 'swiss', 'elimination'] as $format) {
                foreach (['Planificado', 'En curso', 'Finalizado'] as $state) {
                    $this->state = $state;
                    $this->createTournament($workspaceId, $format);
                }
            }
        }
        return true;
    }

    private function updateLegacyPictures(): void
    {
        $pictures = [
            'Club del Sur · Demo' => 'penarol.svg',
            'Club de Estrategia · Demo' => 'fide.png',
            'Arena Digital · Demo' => 'furia.svg',
            'Competiciones en acción · Demo' => 'celtics.svg',
            'Comunidad del Río · Demo' => 'nacional.webp',
            'Centro de Estrategia · Demo' => 'fide.png',
        ];
        $statement = $this->database->prepare("UPDATE workspaces SET picture=? WHERE name=? AND picture=''");
        foreach ($pictures as $name => $file) {
            $statement->execute(['/public/assets/demo/entities/' . $file, $name]);
        }
    }

    private function createPeople(string $marker): void
    {
        $firstNames = ['Ana', 'Bruno', 'Camila', 'Diego', 'Elena', 'Felipe', 'Gabriela', 'Hugo'];
        $lastNames = ['Silva', 'Torres', 'Ríos', 'Pereira', 'Costa', 'Méndez', 'Luna', 'Ramos'];
        $statement = $this->database->prepare(
            'INSERT INTO users(google_id, email, name, created_at) VALUES(?, ?, ?, UTC_TIMESTAMP())'
        );
        for ($index = 0; $index < 64; $index++) {
            $name = $firstNames[$index % 8] . ' ' . $lastNames[intdiv($index, 8)] . ' · Demo';
            $statement->execute([$marker . $index, 'nuva-' . str_replace(':', '-', $marker) . $index . '@example.invalid', $name]);
            $this->people[] = (int) $this->database->lastInsertId();
        }
    }

    private function createWorkspace(): int
    {
        $name = $this->entity['name'] . ' · Demo Nuva';
        $description = DemoStories::workspaceDescription($name);
        $picture = '/public/assets/demo/entities/' . $this->entity['picture'];
        if (!is_file(dirname(__DIR__) . $picture)) {
            throw new RuntimeException('Falta una imagen de demostración.');
        }
        $workspaceId = $this->workspaces->create($this->owner, $name, $description, $picture);
        $statement = $this->database->prepare(
            'INSERT INTO workspace_members(workspace_id, user_id, role_id, assigned_at) VALUES(?, ?, ?, UTC_TIMESTAMP())'
        );
        foreach ($this->people as $index => $personId) {
            if ($personId === $this->owner) {
                continue;
            }
            $role = Role::PARTICIPANT;
            if ($index === 1) {
                $role = Role::ADMIN;
            } elseif ($index === 2) {
                $role = Role::ORGANIZER;
            }
            $statement->execute([$workspaceId, $personId, Role::findId($role)]);
        }
        $this->invitations[] = ['name' => $name, 'code' => $this->workspaces->createInvitation($workspaceId, $this->owner)];
        return $workspaceId;
    }

    private function configureStatistics(int $workspaceId): void
    {
        $this->statistics->writeEnvironment($workspaceId, $this->owner, 'stat_environment', [
            'revision' => '0', 'templates' => $this->physicalTemplates(), 'manual' => [],
        ]);
    }

    private function physicalTemplates(): array
    {
        if ($this->entity['category'] === 'tradicional') {
            return ['motion', 'dynamics', 'energy'];
        }
        return [];
    }

    private function createTournament(int $workspaceId, string $format): void
    {
        $formatNames = ['league' => 'Liga', 'swiss' => 'Abierto suizo', 'elimination' => 'Copa'];
        $date = new DateTimeImmutable('today', new DateTimeZone('America/Montevideo'));
        $this->startDate = $date->modify('-10 days')->setTime(16, 0);
        $endDate = $date->modify('+25 days')->setTime(22, 0);
        if ($this->state === 'Finalizado') {
            $this->startDate = $date->modify('-35 days')->setTime(16, 0);
            $endDate = $date->modify('-1 day')->setTime(22, 0);
        } elseif ($this->state === 'Planificado') {
            $this->startDate = $date->modify('+7 days')->setTime(16, 0);
            $endDate = $date->modify('+40 days')->setTime(22, 0);
        }
        $hasTeams = $this->entity['team_size'] > 0;
        $utc = new DateTimeZone('UTC');
        $values = [
            'workspace_id' => $workspaceId,
            'organizer_id' => $this->people[2],
            'name' => $formatNames[$format] . ' de ' . $this->entity['discipline'] . ' · ' . $this->state . ' · Demo',
            'description' => 'Competición ficticia para recorrer Nuva. No es una actividad oficial de la entidad. Sus resultados y estadísticas son ejemplos.',
            'discipline_category' => $this->entity['category'],
            'discipline' => $this->entity['discipline'],
            'has_team' => (int) $hasTeams,
            'max_participants' => 16,
            'published' => 1,
            'start_date' => $this->startDate->setTimezone($utc)->format('Y-m-d H:i:s'),
            'end_date' => $endDate->setTimezone($utc)->format('Y-m-d H:i:s'),
            'status' => 'Planificado',
            'module_id' => $this->modules[$format],
            'league_legs' => 1,
            'swiss_rounds' => 3,
            'third_place' => 1,
            'team_size' => $hasTeams ? $this->entity['team_size'] : null,
            'points_win' => $format === 'swiss' ? 1 : 3,
            'points_draw' => $format === 'swiss' ? 0.5 : 1,
            'points_loss' => 0,
            'allow_draws' => (int) ($format !== 'elimination'),
            'score_order' => 'higher',
            'score_label' => $this->entity['score'],
        ];
        $id = $this->tournaments->create($values, $this->owner);
        $this->enroll($id, $hasTeams);
        if ($this->state !== 'Planificado') {
            $this->competition->operate($id, $this->owner, 'generate', []);
            $this->playTournament($id, $format, $this->state === 'Finalizado');
        }
    }

    private function enroll(int $id, bool $hasTeams): void
    {
        $tournament = $this->tournaments->find($id);
        for ($index = 0; $index < ($hasTeams ? 4 : 6); $index++) {
            if ($hasTeams) {
                $teamId = $this->tournaments->createTeam($tournament, $this->owner, ['Aurora', 'Horizonte', 'Centella', 'Nexo'][$index] . ' · Demo');
                for ($member = 0; $member < $this->entity['team_size']; $member++) {
                    $personId = $this->people[3 + $index * $this->entity['team_size'] + $member];
                    $this->tournaments->enroll($this->tournaments->find($id), $this->owner, $personId, $teamId);
                }
            } else {
                $this->tournaments->enroll($this->tournaments->find($id), $this->owner, $this->people[3 + $index], null);
            }
        }
    }

    private function playTournament(int $id, string $format, bool $finished): void
    {
        $state = $this->competition->snapshot($this->tournaments->find($id));
        $roundCount = count($state['rounds']);
        if ($format === 'swiss') {
            $roundCount = 3;
        }
        if (!$finished) {
            $roundCount = 1;
        }
        for ($roundNumber = 1; $roundNumber <= $roundCount; $roundNumber++) {
            if ($format === 'swiss' && $roundNumber > 1) {
                $this->competition->operate($id, $this->owner, 'next_round', []);
            }
            $this->playRound($id, $roundNumber);
        }
        if (!$finished && $format === 'swiss') {
            $this->competition->operate($id, $this->owner, 'next_round', []);
        }
        if (!$finished) {
            // También dejar encuentros pendientes en el calendario para probar la agenda.
            $state = $this->competition->snapshot($this->tournaments->find($id));
            foreach ($state['games'] as $match) {
                if ($match['status'] === 'Pendiente') {
                    $this->scheduleMatch($id, $match, true);
                }
            }
        }
    }

    private function playRound(int $id, int $roundNumber): void
    {
        $state = $this->competition->snapshot($this->tournaments->find($id));
        foreach ($state['games'] as $match) {
            if ((int) $match['round_no'] !== $roundNumber || $match['status'] === 'Libre') {
                continue;
            }
            $this->scheduleMatch($id, $match);
            $scores = $this->exampleScores($id, $match, $roundNumber);
            $this->competition->operate($id, $this->owner, 'save_result', [
                'match_id' => (string) $match['id'],
                'revision' => (string) ((int) $match['revision'] + 1),
                'score_1' => (string) $scores[0],
                'score_2' => (string) $scores[1],
                'outcome' => $scores[0] === $scores[1] ? 'draw' : ($scores[0] > $scores[1] ? '1' : '2'),
                'notes' => DemoStories::matchNotes($this->tournaments->find($id), array_merge($match, ['status' => 'Finalizado', 'score_1' => $scores[0], 'score_2' => $scores[1]])),
            ]);
        }
        $this->competition->operate($id, $this->owner, 'close_round', ['round_no' => $roundNumber]);
    }

    private function exampleScores(int $tournamentId, array $match, int $roundNumber): array
    {
        $first = 2;
        $second = 1;
        if ($this->entity['discipline'] === 'Básquetbol') {
            $first = 82;
            $second = 75;
        } elseif ($this->entity['category'] === 'mental') {
            $first = 1;
            $second = 0;
        }
        // Alternar ganadores; los empates solo aparecen donde el reglamento los admite.
        if ((int) $match['position'] % 2 === 0) {
            return [$second, $first];
        }
        $tournament = $this->tournaments->find($tournamentId);
        if ($tournament['format_code'] !== 'elimination' && $this->entity['category'] !== 'esports'
            && $roundNumber === 2) {
            $draw = $this->entity['category'] === 'mental' ? 0.5 : $first;
            return [$draw, $draw];
        }
        return [$first, $second];
    }

    private function scheduleMatch(int $id, array $match, bool $future = false): void
    {
        $date = $this->startDate;
        if ($future) {
            $date = new DateTimeImmutable('tomorrow 16:00', new DateTimeZone('America/Montevideo'));
        }
        $date = $date->modify('+' . ((int) $match['round_no'] - 1) . ' days');
        $date = $date->modify('+' . ((int) $match['position'] - 1) . ' hours');
        $this->competition->operate($id, $this->owner, 'schedule', [
            'match_id' => (string) $match['id'],
            'revision' => (string) $match['revision'],
            'scheduled_at' => $date->format('Y-m-d\TH:i'),
        ]);
    }

}
