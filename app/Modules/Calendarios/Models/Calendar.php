<?php

namespace App\Modules\Calendarios\Models;

use app\Models\Model;

class Calendar extends Model
{
    public function schedule(array $tournament, int $actor, array $game, array $input): void
    {
        if ($game['status'] === 'Finalizado') {
            throw new \InvalidArgumentException('No puedes reprogramar un encuentro finalizado.');
        }

        $date = trim((string)($input['scheduled_at'] ?? ''));
        $scheduled = null;
        if ($date !== '') {
            $dateTime = \DateTimeImmutable::createFromFormat('!Y-m-d\\TH:i', $date, new \DateTimeZone('America/Montevideo'));
            if (!$dateTime || $dateTime->format('Y-m-d\\TH:i') !== $date) {
                throw new \InvalidArgumentException('Introduce una fecha y hora válidas.');
            }

            $scheduled = $dateTime->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
            if ($scheduled < $tournament['start_date'] || $scheduled > $tournament['end_date']) {
                throw new \InvalidArgumentException('El encuentro debe quedar dentro del período del torneo.');
            }
        }

        $status = !empty($input['suspended']) ? 'Suspendido' : 'Pendiente';
        $this->query('UPDATE tournament_match SET scheduled_at=?,status=?,revision=revision+1 WHERE id=?', [$scheduled, $status, $game['id']]);
        $this->log($tournament, $actor, 'match_schedule', ['match' => $game['id'], 'scheduled_at' => $game['scheduled_at'], 'status' => $game['status']], ['match' => $game['id'], 'scheduled_at' => $scheduled, 'status' => $status]);
        return;
    }
    private function log(array $tournament, int $actor, string $action, ?array $before, ?array $after): void
    {
        (new \App\Modules\Auditoria\Models\Audit())->record((int)$tournament['workspace_id'], $actor, 'tournament', (string)$tournament['id'], $action, $before, $after);
    }
}
