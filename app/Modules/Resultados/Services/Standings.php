<?php

namespace App\Modules\Resultados\Services;

class Standings
{
    public static function build(array $entrants, array $games, array $tournament): array
    {
        $table = [];
        foreach ($entrants as $entrant) {
            $entrantId = (int) $entrant['entrant_id'];
            $table[$entrantId] = $entrant + [
                'played' => 0,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'byes' => 0,
                'points' => 0.0,
                'for' => 0.0,
                'against' => 0.0,
                'diff' => 0.0,
                'buchholz' => 0.0,
            ];
        }

        foreach ($games as $match) {
            $firstId = (int) $match['team_1_id'];
            $secondId = (int) $match['team_2_id'];
            if ($match['status'] === 'Libre' && isset($table[$firstId])) {
                $table[$firstId]['byes']++;
                if ($tournament['format_code'] === 'swiss') {
                    $table[$firstId]['points'] += (float) $tournament['points_win'];
                }
                continue;
            }

            if ($match['status'] !== 'Finalizado' || !isset($table[$firstId], $table[$secondId])) {
                continue;
            }

            // Registrar el mismo encuentro desde el punto de vista de cada rival.
            $table[$firstId] = self::addResult($table[$firstId], $firstId, $match['score_1'], $match['score_2'], $match['winner_id'], $tournament);
            $table[$secondId] = self::addResult($table[$secondId], $secondId, $match['score_2'], $match['score_1'], $match['winner_id'], $tournament);
        }

        // Buchholz suma los puntos de los rivales enfrentados: es un desempate del suizo.
        foreach ($games as $match) {
            if ($match['status'] !== 'Finalizado') {
                continue;
            }

            $firstId = (int) $match['team_1_id'];
            $secondId = (int) $match['team_2_id'];
            if (isset($table[$firstId], $table[$secondId])) {
                $table[$firstId]['buchholz'] += $table[$secondId]['points'];
                $table[$secondId]['buchholz'] += $table[$firstId]['points'];
            }
        }

        foreach ($table as $entrantId => $row) {
            $difference = $row['for'] - $row['against'];
            if ($tournament['score_order'] === 'lower') {
                $difference = -$difference;
            }

            $table[$entrantId]['diff'] = $difference;
        }

        $rows = array_values($table);
        if ($tournament['format_code'] === 'swiss') {
            usort($rows, [self::class, 'compareSwiss']);
        } else {
            usort($rows, [self::class, 'compareLeague']);
        }

        return $rows;
    }

    private static function addResult(array $row, int $entrantId, $ownScore, $opponentScore, $winnerId, array $tournament): array
    {
        $row['played']++;
        $row['for'] += (float) $ownScore;
        $row['against'] += (float) $opponentScore;

        if (!$winnerId) {
            $row['draws']++;
            $row['points'] += (float) $tournament['points_draw'];
        } elseif ((int) $winnerId === $entrantId) {
            $row['wins']++;
            $row['points'] += (float) $tournament['points_win'];
        } else {
            $row['losses']++;
            $row['points'] += (float) $tournament['points_loss'];
        }

        return $row;
    }

    public static function compareLeague(array $first, array $second): int
    {
        return self::compareRows($first, $second, ['points', 'diff', 'wins']);
    }

    public static function compareSwiss(array $first, array $second): int
    {
        return self::compareRows($first, $second, ['points', 'buchholz', 'wins', 'diff']);
    }

    private static function compareRows(array $first, array $second, array $criteria): int
    {
        foreach ($criteria as $criterion) {
            if (abs($first[$criterion] - $second[$criterion]) > 0.00001) {
                return $second[$criterion] <=> $first[$criterion];
            }
        }
        // Si todos los criterios coinciden, conservar el orden del sorteo inicial.
        return $first['seed'] <=> $second['seed'];
    }
}
