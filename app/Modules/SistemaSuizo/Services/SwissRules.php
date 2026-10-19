<?php

namespace App\Modules\SistemaSuizo\Services;

class SwissRules
{
    public static function swiss(array $standings, array $games): array
    {
        // Recordar los rivales de cada inscrito para no repetir encuentros.
        $previousOpponents = [];
        foreach ($games as $match) {
            if (!$match['team_2_id']) {
                continue;
            }

            $firstId = (int) $match['team_1_id'];
            $secondId = (int) $match['team_2_id'];
            $previousOpponents[$firstId][$secondId] = true;
            $previousOpponents[$secondId][$firstId] = true;
        }

        $entrantIds = [];
        foreach ($standings as $entrant) {
            $entrantIds[] = (int) $entrant['entrant_id'];
        }

        // Si la cantidad es impar, descansa uno de los últimos clasificados que aún no descansó.
        $possibleByes = [null];
        if (count($entrantIds) % 2 !== 0) {
            $possibleByes = [];
            foreach (array_reverse($standings) as $entrant) {
                if (!$entrant['byes']) {
                    $possibleByes[] = (int) $entrant['entrant_id'];
                }
            }
        }

        $attemptCount = 0;
        foreach ($possibleByes as $restingId) {
            $remaining = [];
            foreach ($entrantIds as $entrantId) {
                if ($entrantId !== $restingId) {
                    $remaining[] = $entrantId;
                }
            }

            $pairs = self::findPairs($remaining, $previousOpponents, $attemptCount);
            if ($pairs !== null) {
                if ($restingId !== null) {
                    $pairs[] = [$restingId, null];
                }

                return $pairs;
            }
        }

        throw new \InvalidArgumentException('No se encontró un emparejamiento sin repetir rivales o pases libres. Revisa las rondas configuradas; no se generaron cruces repetidos.');
    }

    private static function findPairs(array $remaining, array $previousOpponents, int &$attemptCount): ?array
    {
        if (empty($remaining)) {
            return [];
        }

        $attemptCount++;
        if ($attemptCount > 200000) {
            throw new \InvalidArgumentException('La búsqueda de cruces sin repeticiones alcanzó su límite. Revisa la cantidad de rondas antes de continuar.');
        }

        $firstId = array_shift($remaining);
        foreach ($remaining as $position => $secondId) {
            if (isset($previousOpponents[$firstId][$secondId])) {
                continue;
            }

            $rest = $remaining;
            unset($rest[$position]);
            // Probar este cruce y resolver el resto. Si bloquea los siguientes, probar otro rival.
            $otherPairs = self::findPairs(array_values($rest), $previousOpponents, $attemptCount);
            if ($otherPairs !== null) {
                $pairs = [[$firstId, $secondId]];
                return array_merge($pairs, $otherPairs);
            }
        }

        return null;
    }
}
