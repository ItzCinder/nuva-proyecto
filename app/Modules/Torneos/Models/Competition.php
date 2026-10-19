<?php

namespace app\Models;

use App\Modules\Auditoria\Models\Audit;
use App\Modules\Calendarios\Models\Fixtures;
use App\Modules\EliminacionDirecta\Models\Elimination;
use App\Modules\Liga\Models\League;
use App\Modules\Resultados\Models\Results;
use App\Modules\SistemaSuizo\Models\Swiss;
use App\Services\CompetitionRules;
use DomainException;
use InvalidArgumentException;

class Competition extends Model
{
    // Reunir los datos que necesitan las pestañas del torneo.
    public function snapshot(array $tournament): array
    {
        $tournamentId = (int) $tournament['id'];

        $statement = $this->query(
            'SELECT * FROM tournament_seeds WHERE tournament_id=? ORDER BY seed',
            [$tournamentId]
        );
        $seeds = $statement->fetchAll();

        $statement = $this->query(
            'SELECT * FROM tournament_rounds WHERE tournament_id=? ORDER BY number',
            [$tournamentId]
        );
        $rounds = $statement->fetchAll();

        $statement = $this->query(
            'SELECT * FROM tournament_match WHERE id_tournament=? ORDER BY round_no, position, id',
            [$tournamentId]
        );
        $games = $statement->fetchAll();

        $names = [];
        foreach ($seeds as $seed) {
            $names[$seed['entrant_id']] = $seed['name'];
        }

        foreach ($games as $index => $match) {
            $games[$index]['name_1'] = $this->participantName(
                $match['team_1_id'], $match['source_1_id'], $match['source_kind'], $names, 'Por definir'
            );
            $games[$index]['name_2'] = $this->participantName(
                $match['team_2_id'], $match['source_2_id'], $match['source_kind'], $names, 'Pase libre'
            );
        }

        $standings = CompetitionRules::standings($seeds, $games, $tournament);
        $champion = $this->championName($tournament, $games, $standings, $names);

        return [
            'seeds' => $seeds,
            'rounds' => $rounds,
            'games' => $games,
            'standings' => $standings,
            'champion' => $champion,
        ];
    }

    private function participantName($participantId, $sourceId, string $sourceKind, array $names, string $fallback): string
    {
        if (isset($names[$participantId])) {
            return $names[$participantId];
        }

        // En eliminación, el rival puede depender de otro encuentro todavía sin resolver.
        if ($sourceId) {
            if ($sourceKind === 'loser') {
                return 'Perdedor del encuentro #' . $sourceId;
            }
            return 'Ganador del encuentro #' . $sourceId;
        }

        return $fallback;
    }

    private function championName(array $tournament, array $games, array $standings, array $names): ?string
    {
        if ($tournament['status'] !== 'Finalizado') {
            return null;
        }

        if ($tournament['format_code'] !== 'elimination') {
            return $standings[0]['name'] ?? null;
        }

        foreach ($games as $match) {
            if ($match['round_name'] === 'Final' && $match['winner_id']) {
                return $names[$match['winner_id']] ?? null;
            }
        }

        return null;
    }

    // El controlador abre la transacción. El bloqueo evita cambios simultáneos en el mismo torneo.
    public function operate(int $tournamentId, int $actor, string $action, array $input): void
    {
        $this->query('SELECT id FROM tournament WHERE id=? FOR UPDATE', [$tournamentId]);

        $tournaments = new Tournament();
        $tournament = $tournaments->find($tournamentId);
        if (!$tournament) {
            throw new InvalidArgumentException('No encontramos ese torneo.');
        }

        $workspaces = new Workspace();
        $workspace = $workspaces->findForUser((int) $tournament['workspace_id'], $actor);
        if (!$tournaments->canManage($tournament, $actor, $workspace)) {
            throw new DomainException('No tienes permisos para gestionar este torneo.');
        }

        $state = $this->snapshot($tournament);
        if ($action === 'generate') {
            $this->generate($tournament, $actor, $state);
            return;
        }

        if (!$state['rounds']) {
            throw new InvalidArgumentException('Primero genera los enfrentamientos.');
        }

        $roundNumber = 0;
        if (in_array($action, ['close_round', 'reopen_round', 'discard_round'], true)) {
            $roundNumber = nuva_integer($input['round_no'] ?? '', 'Ronda');
        }
        switch ($action) {
            case 'next_round':
                $this->generateNextRound($tournament, $actor, $state);
                break;
            case 'close_round':
                $this->closeRound($tournament, $actor, $roundNumber, $state);
                break;
            case 'reopen_round':
                $this->reopenRound($tournament, $actor, $roundNumber, $state);
                break;
            case 'discard_round':
                $this->discardRound($tournament, $actor, $roundNumber, $state);
                break;
            default:
                // Los marcadores y las fechas los atiende el módulo de Resultados.
                $results = new Results();
                $results->operate($tournament, $actor, $action, $input, $state);
        }
    }

    private function generate(array $tournament, int $actor, array $state): void
    {
        if ($state['games'] || $state['rounds']) {
            throw new InvalidArgumentException('Este torneo ya tiene enfrentamientos generados.');
        }
        if ($tournament['status'] === 'Finalizado') {
            throw new InvalidArgumentException('Configura el torneo como Planificado antes de generar sus enfrentamientos.');
        }

        // 1. Leer los inscritos y comprobar que el formato permita esa cantidad.
        $entrants = $this->entrants($tournament);
        $this->validateEntrants($tournament, count($entrants));

        // 2. Sortear el orden y conservarlo para los cruces y desempates.
        shuffle($entrants);
        $entrantIds = [];
        foreach ($entrants as $index => $entrant) {
            $entrantIds[] = (int) $entrant['entrant_id'];
            $this->query(
                'INSERT INTO tournament_seeds(tournament_id, entrant_id, seed, name) VALUES(?, ?, ?, ?)',
                [$tournament['id'], $entrant['entrant_id'], $index + 1, $entrant['name']]
            );
        }

        // 3. Cada módulo genera sus propios enfrentamientos.
        switch ($tournament['format_code']) {
            case 'league':
                $league = new League();
                $league->generate($tournament, $entrantIds);
                break;
            case 'swiss':
                $swiss = new Swiss();
                $stateWithSeeds = $this->snapshot($tournament);
                $swiss->generateRound($tournament, $stateWithSeeds, 1);
                break;
            case 'elimination':
                $elimination = new Elimination();
                $elimination->generate($tournament, $entrantIds);
                break;
        }

        $this->query('UPDATE tournament SET status=? WHERE id=?', ['En curso', $tournament['id']]);
        $this->log($tournament, $actor, 'matches_generate', null, [
            'format' => $tournament['format_name'],
            'participants' => count($entrants),
            'detail' => 'Sorteo inicial e inscripciones cerradas.',
        ]);
    }

    private function entrants(array $tournament): array
    {
        if (!$tournament['has_team']) {
            $statement = $this->query(
                'SELECT tm.id AS entrant_id, u.name
                 FROM tournament_members tm
                 JOIN users u ON u.id=tm.member_id
                 WHERE tm.tournament_id=?
                 ORDER BY tm.id',
                [$tournament['id']]
            );
            return $statement->fetchAll();
        }

        $statement = $this->query(
            'SELECT tt.id AS entrant_id, tt.name, COUNT(tm.id) AS members
             FROM tournament_teams tt
             LEFT JOIN tournament_members tm
                 ON tm.team_id=tt.id AND tm.tournament_id=tt.tournament_id
             WHERE tt.tournament_id=?
             GROUP BY tt.id, tt.name
             ORDER BY tt.id',
            [$tournament['id']]
        );
        $teams = $statement->fetchAll();
        foreach ($teams as $team) {
            if ((int) $team['members'] === 0) {
                throw new InvalidArgumentException('Todos los equipos deben tener al menos un integrante.');
            }
        }
        return $teams;
    }

    private function validateEntrants(array $tournament, int $entrantCount): void
    {
        if ($entrantCount < 2 || $entrantCount > 256) {
            throw new InvalidArgumentException('Se necesitan entre 2 y 256 participantes o equipos inscritos.');
        }

        if (!in_array($tournament['format_code'], ['league', 'swiss', 'elimination'], true)) {
            throw new InvalidArgumentException('El formato del torneo no es válido.');
        }

        if ($tournament['format_code'] === 'swiss') {
            // Con cantidad impar hay una ronda extra porque cada inscrito tiene un descanso.
            $maximumRounds = $entrantCount - 1;
            if ($entrantCount % 2 === 1) {
                $maximumRounds = $entrantCount;
            }
            if ($entrantCount < 4 || (int) $tournament['swiss_rounds'] > $maximumRounds) {
                throw new InvalidArgumentException(
                    'El suizo necesita al menos 4 inscritos y como máximo ' . $maximumRounds .
                    ' rondas para evitar rivales repetidos. Ajusta la configuración.'
                );
            }
        }

        if ($tournament['format_code'] === 'elimination' && $tournament['third_place'] && $entrantCount < 4) {
            throw new InvalidArgumentException('El partido por tercer puesto requiere al menos 4 inscritos.');
        }
    }

    private function generateNextRound(array $tournament, int $actor, array $state): void
    {
        if ($tournament['format_code'] !== 'swiss') {
            throw new InvalidArgumentException('Este formato ya tiene sus rondas generadas.');
        }

        $lastRound = $state['rounds'][count($state['rounds']) - 1];
        if ($lastRound['status'] !== 'Cerrada') {
            throw new InvalidArgumentException('Completa y cierra la ronda actual antes de generar la siguiente.');
        }
        if ((int) $lastRound['number'] >= (int) $tournament['swiss_rounds']) {
            throw new InvalidArgumentException('Ya se completaron todas las rondas.');
        }

        $number = (int) $lastRound['number'] + 1;
        $pairs = CompetitionRules::swiss($state['standings'], $state['games']);
        $fixtures = new Fixtures();
        $fixtures->addRound($tournament, $number, 'Ronda ' . $number, $pairs);
        $this->log($tournament, $actor, 'round_generate', null, [
            'round' => $number,
            'matches' => count($pairs),
        ]);
    }

    private function findRound(array $rounds, int $number): array
    {
        foreach ($rounds as $round) {
            if ((int) $round['number'] === $number) {
                return $round;
            }
        }
        throw new InvalidArgumentException('Ronda inexistente.');
    }

    private function roundMatches(array $games, int $number): array
    {
        $matches = [];
        foreach ($games as $match) {
            if ((int) $match['round_no'] === $number) {
                $matches[] = $match;
            }
        }
        return $matches;
    }

    private function closeRound(array $tournament, int $actor, int $number, array $state): void
    {
        $round = $this->findRound($state['rounds'], $number);
        if ($round['status'] === 'Cerrada') {
            throw new InvalidArgumentException('La ronda ya está cerrada.');
        }

        $matches = $this->roundMatches($state['games'], $number);
        foreach ($matches as $match) {
            if ($match['status'] !== 'Finalizado' && $match['status'] !== 'Libre') {
                throw new InvalidArgumentException('Completa todos los resultados antes de cerrar la ronda.');
            }
        }
        foreach ($state['rounds'] as $previousRound) {
            if ((int) $previousRound['number'] < $number && $previousRound['status'] !== 'Cerrada') {
                throw new InvalidArgumentException('Cierra primero las rondas anteriores.');
            }
        }

        $this->query(
            'UPDATE tournament_rounds SET status=? WHERE tournament_id=? AND number=?',
            ['Cerrada', $tournament['id'], $number]
        );
        $this->updateTournamentStatus($tournament);
        $this->log($tournament, $actor, 'round_close',
            ['round' => $number, 'status' => $round['status']],
            ['round' => $number, 'status' => 'Cerrada']
        );
    }

    private function reopenRound(array $tournament, int $actor, int $number, array $state): void
    {
        $round = $this->findRound($state['rounds'], $number);
        if ($round['status'] === 'Abierta') {
            throw new InvalidArgumentException('La ronda ya está abierta.');
        }
        if ($tournament['format_code'] === 'swiss' && $number < count($state['rounds'])) {
            throw new InvalidArgumentException('Retira las rondas posteriores del suizo antes de reabrir esta ronda.');
        }

        $this->query(
            'UPDATE tournament_rounds SET status=? WHERE tournament_id=? AND number=?',
            ['Abierta', $tournament['id'], $number]
        );
        $this->query('UPDATE tournament SET status=? WHERE id=?', ['En curso', $tournament['id']]);
        $this->log($tournament, $actor, 'round_reopen',
            ['round' => $number, 'status' => $round['status']],
            ['round' => $number, 'status' => 'Abierta']
        );
    }

    private function discardRound(array $tournament, int $actor, int $number, array $state): void
    {
        $this->findRound($state['rounds'], $number);
        if ($tournament['format_code'] !== 'swiss' || $number !== count($state['rounds']) || $number === 1) {
            throw new InvalidArgumentException('Solo puedes retirar la última ronda del suizo, a partir de la segunda.');
        }

        $matches = $this->roundMatches($state['games'], $number);
        foreach ($matches as $match) {
            if ($match['status'] === 'Finalizado') {
                throw new InvalidArgumentException('Reabre la ronda y anula sus resultados antes de retirarla.');
            }
        }

        $this->query('DELETE FROM tournament_match WHERE id_tournament=? AND round_no=?', [$tournament['id'], $number]);
        $this->query('DELETE FROM tournament_rounds WHERE tournament_id=? AND number=?', [$tournament['id'], $number]);
        $this->query('UPDATE tournament SET status=? WHERE id=?', ['En curso', $tournament['id']]);
        $this->log($tournament, $actor, 'round_discard', [
            'round' => $number,
            'matches' => count($matches),
            'detail' => 'Ronda sin resultados retirada para revisar cruces anteriores.',
        ], null);
    }

    private function updateTournamentStatus(array $tournament): void
    {
        $statement = $this->query(
            'SELECT * FROM tournament_rounds WHERE tournament_id=?',
            [$tournament['id']]
        );
        $rounds = $statement->fetchAll();

        $allClosed = true;
        foreach ($rounds as $round) {
            if ($round['status'] !== 'Cerrada') {
                $allClosed = false;
                break;
            }
        }

        $allRoundsGenerated = true;
        if ($tournament['format_code'] === 'swiss') {
            $allRoundsGenerated = count($rounds) === (int) $tournament['swiss_rounds'];
        }

        $status = 'En curso';
        if ($allClosed && $allRoundsGenerated) {
            $status = 'Finalizado';
        }
        $this->query('UPDATE tournament SET status=? WHERE id=?', [$status, $tournament['id']]);
    }

    private function log(array $tournament, int $actor, string $action, ?array $before, ?array $after): void
    {
        $audit = new Audit();
        $audit->record(
            (int) $tournament['workspace_id'], $actor, 'tournament',
            (string) $tournament['id'], $action, $before, $after
        );
    }
}
