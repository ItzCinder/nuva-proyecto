<?php

namespace App\Modules\Resultados\Models;

use app\Models\Model;

class Results extends Model
{
    public function operate(array $tournament, int $actor, string $action, array $input, array $state): void
    {
        $gameId = nuva_integer($input['match_id'] ?? '', 'Encuentro');
        $game = null;
        foreach ($state['games'] as $match) {
            if ((int)$match['id'] === $gameId) {
                $game = $match;
            }
        }

        if (!$game) {
            throw new \InvalidArgumentException('El encuentro no pertenece a este torneo.');
        }

        if (!isset($input['revision']) || (string)$game['revision'] !== (string)$input['revision']) {
            throw new \InvalidArgumentException('Este encuentro cambió mientras lo editabas. Recarga la página antes de guardar.');
        }

        foreach ($state['rounds'] as $round) {
            if ((int)$round['number'] === (int)$game['round_no'] && $round['status'] === 'Cerrada') {
                throw new \InvalidArgumentException('La ronda está cerrada. Reábrela antes de corregirla.');
            }
        }

        if ($game['status'] === 'Libre') {
            throw new \InvalidArgumentException('Los pases libres se resuelven automáticamente.');
        }

        if ($action === 'schedule') {
            (new \App\Modules\Calendarios\Models\Calendar())->schedule($tournament, $actor, $game, $input);
            return;
        }

        if (!in_array($action, ['save_result', 'clear_result'], true)) {
            throw new \InvalidArgumentException('Acción desconocida.');
        }

        if (!$game['team_1_id'] || !$game['team_2_id']) {
            throw new \InvalidArgumentException('Todavía faltan los rivales de este encuentro.');
        }

        if ($tournament['format_code'] === 'elimination' && $action === 'save_result') {
            foreach ($state['rounds'] as $round) {
                if ((int)$round['number'] < (int)$game['round_no'] && $round['status'] !== 'Cerrada') {
                    throw new \InvalidArgumentException('Cierra las rondas anteriores antes de cargar este resultado.');
                }
            }
        }

        if ($tournament['format_code'] === 'swiss' && (int)$game['round_no'] < count($state['rounds'])) {
            throw new \InvalidArgumentException('Retira las rondas posteriores antes de corregir un resultado que determinó sus cruces.');
        }

        $notes = trim((string)($input['notes'] ?? ''));
        if (mb_strlen($notes) > 500) {
            throw new \InvalidArgumentException('El detalle admite hasta 500 caracteres.');
        }

        if ($action === 'clear_result') {
            if ($game['status'] !== 'Finalizado' || $notes === '') {
                throw new \InvalidArgumentException('Para anular un resultado registrado debes indicar el motivo.');
            }

            $winner = null;
            $firstScore = null;
            $secondScore = null;
            $status = 'Pendiente';
        } else {
            if ($game['status'] === 'Suspendido') {
                throw new \InvalidArgumentException('Reanuda el encuentro antes de registrar su resultado.');
            }

            $firstScore = $this->score($input['score_1'] ?? '');
            $secondScore = $this->score($input['score_2'] ?? '');
            if (($firstScore === null) !== ($secondScore === null)) {
                throw new \InvalidArgumentException('Completa ambos marcadores o deja ambos vacíos.');
            }

            $outcome = (string)($input['outcome'] ?? '');
            if (!in_array($outcome, ['1', '2', 'draw'], true)) {
                throw new \InvalidArgumentException('Selecciona el resultado del encuentro.');
            }

            if ($outcome === 'draw' && (!$tournament['allow_draws'] || $tournament['format_code'] === 'elimination')) {
                throw new \InvalidArgumentException('Este torneo necesita un ganador; no admite empates.');
            }

            $winner = null;
            if ($outcome !== 'draw') {
                $winner = (int) $game['team_' . $outcome . '_id'];
            }
            $expected = 'draw';
            if ($firstScore !== $secondScore) {
                $higherWins = $tournament['score_order'] === 'higher';
                if (($firstScore > $secondScore) === $higherWins) {
                    $expected = '1';
                } else {
                    $expected = '2';
                }
            }
            if (($firstScore === null || $outcome !== $expected || $game['status'] === 'Finalizado') && $notes === '') {
                throw new \InvalidArgumentException('Explica el motivo de la corrección, del desempate o de la decisión especial.');
            }

            $status = 'Finalizado';
        }

        $this->query(
            'UPDATE tournament_match SET score_1=?,score_2=?,winner_id=?,notes=?,status=?,played_at=IF(? = ?,UTC_TIMESTAMP(),NULL),revision=revision+1 WHERE id=?',
            [$firstScore, $secondScore, $winner, $notes, $status, $status, 'Finalizado', $gameId]
        );
        if ($tournament['format_code'] === 'elimination') {
            (new \App\Modules\EliminacionDirecta\Models\Elimination())->propagate($tournament);
        }

        $this->log(
            $tournament,
            $actor,
            $action === 'clear_result' ? 'result_clear' : 'result_save',
            [
                'match' => $gameId,
                'rival_1' => $game['name_1'],
                'rival_2' => $game['name_2'],
                'score_1' => $game['score_1'],
                'score_2' => $game['score_2'],
                'winner' => $this->winnerName($game['winner_id'], $state),
                'notes' => $game['notes'],
                'status' => $game['status']
            ],
            [
                'match' => $gameId,
                'score_1' => $firstScore,
                'score_2' => $secondScore,
                'winner' => $winner ? $this->winnerName($winner, $state) : ($status === 'Finalizado' ? 'Empate' : 'Sin resultado'),
                'notes' => $notes,
                'status' => $status
            ]
        );
    }
    private function score($value): ?float
    {
        if (!is_string($value)) {
            throw new \InvalidArgumentException('El marcador debe ser un número.');
        }
        if (trim($value) === '') {
            return null;
        }

        $value = str_replace(',', '.', trim($value));
        if (!preg_match('/^-?\d{1,6}(?:\.\d{1,2})?$/D', $value)) {
            throw new \InvalidArgumentException('El marcador debe ser un número de −999999,99 a 999999,99, con hasta dos decimales.');
        }

        return (float)$value;
    }
    private function winnerName($id, array $state): string
    {
        foreach ($state['seeds'] as $seed) {
            if ((int)$seed['entrant_id'] === (int)$id) {
                return $seed['name'];
            }
        }

        return 'Sin ganador';
    }
    private function log(array $tournament, int $actor, string $action, ?array $before, ?array $after): void
    {
        (new \App\Modules\Auditoria\Models\Audit())->record((int)$tournament['workspace_id'], $actor, 'tournament', (string)$tournament['id'], $action, $before, $after);
    }
}
