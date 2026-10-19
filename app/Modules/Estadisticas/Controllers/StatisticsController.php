<?php

namespace App\Modules\Estadisticas\Controllers;

use app\Models\Role;
use app\Models\Tournament;
use app\Models\Workspace;
use App\Modules\Estadisticas\Models\Statistics;
use App\Modules\Estadisticas\Services\StatisticsBrowser;

class StatisticsController
{
    public function handle(string $page, callable $render): void
    {
        $userId = 0;
        if (!empty($_SESSION['authenticated'])) {
            $userId = (int) ($_SESSION['user_id'] ?? 0);
        }

        $environmentMode = $page === 'environment-statistics';
        $id = filter_var($this->queryText('id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            $id = 0;
        }
        $data = [
            'error' => '',
            'flash' => $_SESSION['statistics_flash'] ?? '',
            'environmentMode' => $environmentMode,
            'manager' => false,
            'workspace' => null,
            'tournament' => null,
            'edit' => null,
            'manualEdit' => null,
            'history' => [],
        ];
        unset($_SESSION['statistics_flash']);

        try {
            $statistics = new Statistics();
            $workspaces = new Workspace();
            $tournaments = new Tournament();

            // 1. Comprobar el acceso antes de consultar datos o aceptar cambios.
            if ($environmentMode) {
                $workspace = null;
                if ($userId !== 0) {
                    $workspace = $workspaces->findForUser($id, $userId);
                }

                if (!$workspace || !in_array($workspace['role_name'], [Role::OWNER, Role::ADMIN], true)) {
                    throw new \DomainException('Solo el dueño y el administrador pueden configurar estadísticas del entorno.');
                }

                $manager = true;
                $workspaceId = $id;
            } else {
                $tournament = $tournaments->find($id);
                if (!$tournament) {
                    throw new \DomainException('No encontramos ese torneo.');
                }

                $workspaceId = (int) $tournament['workspace_id'];
                $workspace = null;
                if ($userId !== 0) {
                    $workspace = $workspaces->findForUser($workspaceId, $userId);
                }

                if (!$tournaments->canSee($tournament, $userId ?: null, $workspace)) {
                    throw new \DomainException('Las estadísticas de este torneo no están disponibles para tu cuenta.');
                }

                $manager = $tournaments->canManage($tournament, $userId ?: null, $workspace);
                $data['tournament'] = $tournament;
            }

            $data['workspace'] = $workspace;
            $data['manager'] = $manager;

            if (empty($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }

            // 2. Guardar el formulario. Una entrada inválida vuelve a la misma pantalla.
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
                if (!$manager || $userId === 0) {
                    throw new \DomainException('No tienes permisos para modificar estadísticas.');
                }

                $token = $_POST['csrf_token'] ?? '';
                if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
                    nuva_security_event('csrf_rejected');
                    throw new \DomainException('El formulario venció. Recarga la página.');
                }

                try {
                    $this->save($statistics, $id, $userId, $environmentMode);
                    $_SESSION['statistics_flash'] = 'Los cambios de estadísticas se guardaron.';
                    header('Location: ?page=' . $page . '&id=' . $id, true, 303);
                    return;
                } catch (\InvalidArgumentException $error) {
                    nuva_security_event('invalid_form');
                    http_response_code(422);
                    $data['error'] = $error->getMessage();
                }
            }

            // 3. Preparar los datos de la vista.
            $data['catalogue'] = $statistics->environment($workspaceId);
            $data['definitions'] = $statistics->definitions($workspaceId);
            $data['options'] = $statistics->options($workspaceId);
            if (!$environmentMode) {
                $tournamentData = $this->tournamentData($statistics, $tournaments, $tournament, $userId, $manager);
                $data = array_merge($data, $tournamentData);
            }

            $render('statistics/page', $data);
        } catch (\DomainException $error) {
            nuva_security_event('permission_denied');
            http_response_code(403);
            $data['error'] = $error->getMessage();
            $render('statistics/error', $data);
        } catch (\Throwable $error) {
            nuva_log_error('Estadísticas', $error);
            http_response_code(503);
            $data['error'] = 'No se pudieron cargar las estadísticas. Inténtalo de nuevo.';
            $render('statistics/error', $data);
        }
    }

    private function save(Statistics $statistics, int $id, int $userId, bool $environmentMode): void
    {
        $database = \Database::getConnection();
        try {
            // Esperar un bloqueo debe permitir leer los últimos cambios confirmados.
            $database->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
            $database->beginTransaction();
            $action = '';
            if (is_string($_POST['action'] ?? null)) {
                $action = $_POST['action'];
            }

            if ($environmentMode) {
                $statistics->writeEnvironment($id, $userId, $action, $_POST);
            } else {
                $statistics->writeTournament($id, $userId, $action, $_POST);
            }

            $database->commit();
        } catch (\Throwable $error) {
            if ($database->inTransaction()) {
                $database->rollBack();
            }

            throw $error;
        }
    }

    private function tournamentData(Statistics $statistics, Tournament $tournaments, array $tournament, int $userId, bool $manager): array
    {
        $id = (int) $tournament['id'];
        $participants = $tournaments->participants($id);
        $teams = $tournaments->teams($id);
        $memberIds = [];
        $teamIds = [];
        foreach ($participants as $participant) {
            if ((int) $participant['user_id'] === $userId) {
                $memberIds[] = (int) $participant['id'];
                $teamIds[] = (int) $participant['team_id'];
            }
        }

        $trials = $statistics->records($tournament, 'trial');
        $manual = $statistics->records($tournament, 'manual');
        $onlyOwnRecords = !$manager && !$tournament['published'];
        if ($onlyOwnRecords) {
            $trials = $this->ownRecords($trials, 'trial', $memberIds, $teamIds);
            $manual = $this->ownRecords($manual, 'manual', $memberIds, $teamIds);
        }

        $data = [
            'configuration' => $statistics->configuration($tournament),
            'participants' => $participants,
            'teams' => $teams,
            'trials' => $statistics->visible($tournament, $trials, 'trial', $manager),
            'manualRecords' => $statistics->visible($tournament, $manual, 'manual', $manager),
            'edit' => null,
            'manualEdit' => null,
            'history' => [],
        ];
        if ($manager) {
            foreach ($trials as $trial) {
                if ($trial['record_id'] === $this->queryText('edit') && !$trial['invalid']) {
                    $data['edit'] = $trial;
                }
            }

            foreach ($manual as $record) {
                if ($record['record_id'] === $this->queryText('manual_edit') && !$record['invalid']) {
                    $data['manualEdit'] = $record;
                }
            }
        }

        // El historial solo se consulta si su registro ya es visible para el usuario.
        $historyType = 'trial';
        $visibleRecords = $data['trials'];
        if ($this->queryText('kind') === 'manual') {
            $historyType = 'manual';
            $visibleRecords = $data['manualRecords'];
        }

        $historyId = $this->queryText('history');
        foreach ($visibleRecords as $record) {
            if ($record['record_id'] !== $historyId) {
                continue;
            }

            $history = $statistics->history($tournament, $historyType, $historyId);
            if ($onlyOwnRecords) {
                $history = $this->ownRecords($history, $historyType, $memberIds, $teamIds);
            }

            $data['history'] = $statistics->visible($tournament, $history, $historyType, $manager);
            break;
        }

        $data['summaries'] = $statistics->manualSummaries($data['manualRecords']);
        $data['browse'] = StatisticsBrowser::build($data['trials'], $data['manualRecords'], $_GET);
        return $data;
    }

    private function ownRecords(array $records, string $type, array $memberIds, array $teamIds): array
    {
        $ownRecords = [];
        foreach ($records as $record) {
            if ($type === 'trial') {
                $belongsToUser = in_array((int) $record['participant_id'], $memberIds, true);
            } elseif ($record['definition']['target'] === 'team') {
                $belongsToUser = in_array((int) $record['target_id'], $teamIds, true);
            } else {
                $belongsToUser = in_array((int) $record['target_id'], $memberIds, true);
            }

            if ($belongsToUser) {
                $ownRecords[] = $record;
            }
        }

        return $ownRecords;
    }

    private function queryText(string $key): string
    {
        if (!is_string($_GET[$key] ?? null)) {
            return '';
        }

        return $_GET[$key];
    }
}
