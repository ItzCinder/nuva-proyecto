<?php

use app\Models\Competition;
use app\Models\Tournament;
use App\Modules\Auditoria\Models\Audit;
use App\Modules\Estadisticas\Models\Statistics;

class DemoStatistics
{
    private Statistics $statistics;
    private Tournament $tournaments;
    private array $tournament;
    private int $actor;
    private array $profile;
    private array $templates = [];
    private array $metrics = [];
    private array $protectedOptions = [];
    private array $counts = ['trials' => 0, 'manual' => 0];

    public function load(array $tournament, int $actor): array
    {
        $this->statistics = new Statistics();
        $this->tournaments = new Tournament();
        $this->tournament = $tournament;
        $this->actor = $actor;
        $this->profile = DemoStories::profile($tournament);
        $this->configure();
        $this->completeOldManualRecords();
        $this->addManualSessions();
        if ($this->templates) {
            $this->addPhysicalSessions();
        }
        return $this->counts;
    }

    private function configure(): void
    {
        $workspaceId = (int)$this->tournament['workspace_id'];
        $catalogue = $this->statistics->environment($workspaceId);
        $definitions = $this->statistics->definitions($workspaceId);
        $metricRows = $this->profile['metrics'];
        if ($this->tournament['has_team']) {
            $metricRows[] = ['Asistencia al entrenamiento', '%', 'decimal', 'team', 'average', 0, 100,
                'Porcentaje de integrantes del plantel presentes en cada entrenamiento. Se coteja la lista de asistencia; el resumen muestra el promedio de sesiones.'];
        }
        foreach ($metricRows as $metric) {
            [$name, $unit, $type, $target, $rule] = $metric;
            if ($target === 'team' && !$this->tournament['has_team']) {
                continue;
            }
            $definitionId = null;
            foreach ($definitions as $definition) {
                if ($definition['name'] === $name && $definition['unit'] === $unit && $definition['target'] === $target) {
                    $definitionId = (int)$definition['id'];
                    break;
                }
            }
            if (!$definitionId) {
                $this->statistics->writeEnvironment($workspaceId, $this->actor, 'stat_definition', [
                    'name' => $name, 'unit' => $unit, 'data_type' => $type, 'target' => $target, 'rule' => $rule, 'description' => $metric[7],
                ]);
                $definitionId = null;
                // El último INSERT es de auditoría: obtener la definición por su nombre y unidad.
                foreach ($this->statistics->definitions($workspaceId) as $definition) {
                    if ($definition['name'] === $name && $definition['unit'] === $unit && $definition['target'] === $target) {
                        $definitionId = (int)$definition['id'];
                        break;
                    }
                }
            }
            $metric['id'] = $definitionId;
            $this->metrics[] = $metric;
        }
        if ($this->tournament['discipline_category'] === 'tradicional') {
            $this->templates = match ($this->tournament['format_code']) {
                'swiss' => ['motion'], 'league' => ['motion', 'dynamics'], default => ['motion', 'dynamics', 'energy'],
            };
            if ($this->tournament['status'] === 'Planificado') {
                $this->templates = ['motion'];
            }
        }
        $manualIds = array_map('strval', array_column($this->metrics, 'id'));
        $this->statistics->writeEnvironment($workspaceId, $this->actor, 'stat_environment', [
            'revision' => (string)$catalogue['_revision'],
            'templates' => array_values(array_unique(array_merge($catalogue['templates'], $this->templates))),
            'manual' => array_values(array_unique(array_merge(array_map('strval', $catalogue['manual']), $manualIds))),
        ]);
        $configuration = $this->statistics->configuration($this->tournament);
        $selected = [];
        foreach ($this->templates as $template) {
            $selected[] = 'physics:' . $template;
        }
        foreach ($this->metrics as $metric) {
            if ($metric[3] !== 'team' || $metric[0] === 'Asistencia al entrenamiento' || $this->tournament['status'] !== 'Planificado') {
                $selected[] = 'manual:' . $metric['id'];
            }
        }
        $load = $selected;
        $visible = $selected;
        // Conservar la selección de cualquier medición real que ya existiera.
        foreach (['trial', 'manual'] as $kind) {
            foreach ($this->statistics->records($this->tournament, $kind) as $record) {
                if ($record['demonstration']) {
                    continue;
                }
                $codes = $kind === 'manual' ? ['manual:' . $record['stat_type_id']] : array_map(fn($code) => 'physics:' . $code, $record['templates']);
                foreach ($codes as $code) {
                    $this->protectedOptions[$code] = $configuration['options'][$code] ?? ['load' => false, 'visible' => false];
                    $load = array_values(array_diff($load, [$code]));
                    $visible = array_values(array_diff($visible, [$code]));
                    if (!empty($configuration['options'][$code]['load'])) {
                        $load[] = $code;
                    }
                    if (!empty($configuration['options'][$code]['visible'])) {
                        $visible[] = $code;
                    }
                }
            }
        }
        $this->templates = array_values(array_filter($this->templates, fn($template) => in_array('physics:' . $template, $load, true)));
        $this->statistics->writeTournament((int)$this->tournament['id'], $this->actor, 'stat_configuration', [
            'revision' => (string)$configuration['_revision'], 'load' => array_values(array_unique($load)), 'visible' => array_values(array_unique($visible)),
        ]);
    }

    private function date(int $session): string
    {
        $zone = new DateTimeZone('America/Montevideo');
        $today = new DateTimeImmutable('today', $zone);
        if ($this->tournament['status'] === 'Planificado') {
            $date = $today->modify('-3 days')->modify('+' . min($session, 2) . ' days');
        } elseif ($this->tournament['status'] === 'Finalizado') {
            $date = new DateTimeImmutable($this->tournament['end_date'], new DateTimeZone('UTC'));
            $date = $date->setTimezone($zone)->modify('-' . max(1, 7 - $session * 3) . ' days');
        } else {
            $date = new DateTimeImmutable($this->tournament['start_date'], new DateTimeZone('UTC'));
            $date = $date->setTimezone($zone)->modify('+' . (2 + min($session, 2) * 3) . ' days');
        }
        return $date->setTime(10 + min($session, 2), 15)->format('Y-m-d\TH:i');
    }

    private function completeOldManualRecords(): void
    {
        $database = Database::getConnection();
        $definitions = $this->statistics->definitions((int)$this->tournament['workspace_id']);
        foreach ($definitions as $definition) {
            if (isset($this->protectedOptions['manual:' . $definition['id']])) { continue; }
            if (!str_contains((string)$definition['description'], 'ficticio') && !str_contains((string)$definition['description'], 'demostración')) {
                continue;
            }
            $description = 'Ficha del coordinador para ' . $this->profile['session'] . '. Se registran ' . mb_strtolower($definition['name']) . ' al terminar cada bloque. ';
            $description .= $definition['unit'] === '/10' ? 'La valoración usa una escala de 0 a 10 sobre control y coordinación.' : 'Se mantiene la misma unidad y el mismo criterio de resumen en todas las sesiones.';
            $statement = $database->prepare('UPDATE member_stat_types SET description=? WHERE id=?');
            $statement->execute([$description, $definition['id']]);
            $before = $this->statistics->latest((int)$this->tournament['workspace_id'], 'definition', (string)$definition['id']);
            if ($before) {
                $after = $before;
                unset($after['_revision']);
                $after['description'] = $description;
                (new Audit())->record((int)$this->tournament['workspace_id'], $this->actor, 'stat_definition', (string)$definition['id'], 'update', $before, $after);
            }
        }
        // Corregir las planillas antiguas identificadas como ejemplos; conservar sus ediciones.
        foreach ($this->statistics->records($this->tournament, 'manual') as $index => $record) {
            if (!$record['demonstration'] || $record['invalid']) {
                continue;
            }
            if (isset($this->protectedOptions['manual:' . $record['stat_type_id']]) && !$this->protectedOptions['manual:' . $record['stat_type_id']]['load']) { continue; }
            $definition = $record['definition'];
            $key = $this->tournament['id'] . ':old:' . $record['record_id'];
            $value = DemoStories::number($key, 2, 16);
            if ($definition['unit'] === '/10') {
                $value = DemoStories::number($key, 55, 95) / 10;
            } elseif ($definition['unit'] === 's') {
                $value = DemoStories::number($key, 140, 250) / 10;
            }
            if ($definition['data_type'] === 'integer') { $value = round($value); }
            $this->saveManual((int)$record['stat_type_id'], $definition['target'], (int)$record['target_id'], $value, $index % 2,
                ['record_id' => $record['record_id'], 'revision' => (string)$record['_revision'], 'reason' => 'Se cotejó el apunte del turno con la ficha firmada por el coordinador.']);
        }
    }

    private function addManualSessions(): void
    {
        $participants = $this->tournaments->participants((int)$this->tournament['id']);
        foreach ($participants as $index => $participant) {
            $sessions = $this->tournament['status'] === 'Planificado' || $index >= 6 ? 1 : 2;
            for ($session = 0; $session < $sessions; $session++) {
                $key = $this->tournament['id'] . ':' . $participant['id'] . ':' . $session;
                foreach ($this->metrics as $metric) {
                    if ($metric[3] !== 'person') {
                        continue;
                    }
                    $value = DemoStories::number($key . ':' . $metric[0], $metric[5] * 10, $metric[6] * 10) / 10;
                    if ($metric[2] === 'integer') {
                        $value = DemoStories::number($key, $metric[5], $metric[6]);
                    }
                    if ($metric[0] === 'Precisión en ejercicios') {
                        $value = DemoStories::number($key, 3, 10) * 10;
                    } elseif ($metric[0] === 'Precisión del ejercicio técnico') {
                        $football = str_contains(mb_strtolower($this->tournament['discipline']), 'fút') || str_contains(mb_strtolower($this->tournament['discipline']), 'fut');
                        $value = DemoStories::number($key, $football ? 11 : 8, 20) * 5;
                    } elseif ($metric[0] === 'Precisión en práctica de puntería') {
                        $value = DemoStories::number($key . ':accuracy', 28, 76);
                    } elseif ($metric[0] === 'Precisión de lanzamiento') {
                        $value = DemoStories::number($key, 8, 19) * 5;
                    }
                    $this->saveManual($metric['id'], 'person', (int)$participant['id'], $value, $session);
                }
            }
        }
        $snapshot = (new Competition())->snapshot($this->tournament);
        foreach ($this->metrics as $metric) {
            if ($metric[3] !== 'team') {
                continue;
            }
            foreach ($this->tournaments->teams((int)$this->tournament['id']) as $team) {
                if ($metric[0] === 'Asistencia al entrenamiento') {
                    $roster = array_filter($participants, fn($participant) => (int)$participant['team_id'] === (int)$team['id']);
                    $size = count($roster);
                    if ($size) {
                        $present = DemoStories::number($this->tournament['id'] . ':' . $team['id'], max(1, $size - 2), $size);
                        $this->saveManual($metric['id'], 'team', (int)$team['id'], round(100 * $present / $size, 2), 0);
                    }
                    continue;
                }
                $roundTotals = [];
                foreach ($snapshot['games'] as $match) {
                    if ($match['status'] !== 'Finalizado') {
                        continue;
                    }
                    foreach ([1, 2] as $side) {
                        if ((int)$match['team_' . $side . '_id'] === (int)$team['id']) {
                            $roundTotals[$match['round_no']] = ($roundTotals[$match['round_no']] ?? 0) + (float)$match['score_' . $side];
                        }
                    }
                }
                foreach ($roundTotals as $round => $value) {
                    $this->saveManual($metric['id'], 'team', (int)$team['id'], $value, 0, ['date' => $this->roundDate($snapshot['games'], (int)$round)]);
                }
            }
        }
    }

    private function roundDate(array $games, int $round): string
    {
        $date = null;
        foreach ($games as $match) {
            if ((int)$match['round_no'] === $round && $match['status'] === 'Finalizado' && ($date === null || $match['played_at'] > $date)) {
                $date = $match['played_at'];
            }
        }
        return (new DateTimeImmutable($date, new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('America/Montevideo'))->format('Y-m-d\TH:i');
    }

    private function saveManual(int $definition, string $target, int $targetId, float $value, int $session, array $extra = []): void
    {
        $configuration = $this->statistics->configuration($this->tournament);
        if (empty($configuration['options']['manual:' . $definition]['load'])) {
            // Una definición antigua se abre para completar su ficha y conserva su visibilidad.
            $load = [];
            $visible = [];
            foreach ($configuration['options'] as $code => $option) {
                if ($option['load']) { $load[] = $code; }
                if ($option['visible']) { $visible[] = $code; }
            }
            $load[] = 'manual:' . $definition;
            $this->statistics->writeTournament((int)$this->tournament['id'], $this->actor, 'stat_configuration', ['revision' => (string)$configuration['_revision'], 'load' => $load, 'visible' => $visible]);
        }
        $this->statistics->writeTournament((int)$this->tournament['id'], $this->actor, 'stat_manual', array_merge([
            'revision' => '0', 'stat_type_id' => (string)$definition,
            $target === 'team' ? 'team_id' : 'person_id' => (string)$targetId,
            'value' => (string)$value, 'date' => $this->date($session), 'demonstration' => '1',
        ], $extra));
        $this->counts['manual']++;
    }

    private function addPhysicalSessions(): void
    {
        $participants = $this->tournaments->participants((int)$this->tournament['id']);
        $oldByPerson = [];
        foreach ($this->statistics->records($this->tournament, 'trial') as $record) {
            if ($record['demonstration'] && !$record['invalid']) {
                $oldByPerson[$record['participant_id']][] = $record;
            }
        }
        foreach ($participants as $index => $participant) {
            if ($index >= 8 && empty($oldByPerson[$participant['id']])) {
                continue;
            }
            $sessions = $this->tournament['status'] === 'Planificado' ? 1 : max(2, count($oldByPerson[$participant['id']] ?? []));
            for ($session = 0; $session < $sessions; $session++) {
                $old = $oldByPerson[$participant['id']][$session] ?? null;
                $input = $this->physicalInput($participant, $session);
                if ($old) {
                    $input['record_id'] = $old['record_id'];
                    $input['revision'] = (string)$old['_revision'];
                    $input['reason'] = 'El entrenador separó los turnos y cotejó las marcas con la ficha del recorrido.';
                }
                $this->statistics->writeTournament((int)$this->tournament['id'], $this->actor, 'stat_trial', $input);
                $this->counts['trials']++;
            }
        }
    }

    private function physicalInput(array $participant, int $session): array
    {
        $key = $this->tournament['id'] . ':' . $participant['id'] . ':' . $session;
        $distance = str_contains($this->tournament['discipline'], 'Básquet') ? 15 : 20;
        $time = DemoStories::number($key . ':time', 42, 65) / 10;
        $segmentTime = DemoStories::number($key . ':start', 19, 25) / 10;
        $mass = DemoStories::number('mass:' . $participant['user_id'], 550, 880) / 10;
        $withMargin = DemoStories::number($key . ':margin', 0, 4) !== 0;
        $sensor = $session === 1 && $this->tournament['format_code'] === 'elimination';
        $input = [
            'revision' => '0', 'templates' => $this->templates, 'participant_id' => (string)$participant['id'],
            'label' => ($this->tournament['status'] === 'Planificado' ? 'Control de ingreso' : 'Control de desplazamiento') . ' · Turno ' . ($session + 1),
            'date' => $this->date($session), 'procedure' => 'Salida quieta en línea recta; un asistente marca el paso por 5 m y otro cronometra el recorrido completo de ' . $distance . ' m.',
            'instrument' => $sensor ? 'Cinta métrica, cronómetro, balanza y sensor de movimiento calibrado' : 'Cinta métrica de 30 m, cronómetro digital y balanza',
            'conditions' => 'Pista plana y seca del polideportivo. Calentamiento previo; marcas intermedias para aproximar un aumento uniforme de rapidez en los primeros 5 m.',
            'demonstration' => '1', 'distance' => (string)$distance, 'time' => (string)$time,
            'segment_distance' => '5', 'segment_time' => (string)$segmentTime, 'mass' => (string)$mass,
            'mrua' => count($this->templates) > 1 ? '1' : '', 'from_rest' => '1', 'constant_acceleration' => '1',
            'acceleration_source' => $sensor ? 'measured' : 'model', 'speed_source' => $sensor ? 'measured' : 'model',
            'acceleration' => (string)(DemoStories::number($key, 12, 24) / 10), 'speed' => '4', 'initial_speed' => '0',
            'work_mode' => !$sensor && in_array('energy', $this->templates, true) ? 'model' : 'none',
            'independent_inputs' => $withMargin ? '1' : '',
        ];
        if ($sensor) {
            $input['procedure'] = 'Recorrido recto con sensor al inicio y final del tramo de estudio; el asistente repite el control de distancia y tiempo.';
        }
        if ($withMargin) {
            $input += ['u_distance' => '0.05', 'u_time' => '0.1', 'u_segment_distance' => '0.02', 'u_segment_time' => '0.1',
                'u_mass' => '0.1', 'u_acceleration' => '0.05', 'u_speed' => '0.1', 'u_initial_speed' => '0.1'];
        }
        return $input;
    }
}
