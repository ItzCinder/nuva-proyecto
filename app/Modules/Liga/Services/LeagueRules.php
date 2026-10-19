<?php

namespace App\Modules\Liga\Services;

class LeagueRules
{
    public static function league(array $entrantIds, int $legs): array
    {
        // Un lugar vacío representa al participante que descansa en cada jornada.
        if (count($entrantIds) % 2 !== 0) {
            $entrantIds[] = null;
        }

        $entrantCount = count($entrantIds);
        $rounds = [];

        // Método circular: el primer inscrito queda fijo y los demás rotan.
        for ($roundIndex = 0; $roundIndex < $entrantCount - 1; $roundIndex++) {
            $pairs = [];
            for ($position = 0; $position < $entrantCount / 2; $position++) {
                $firstId = $entrantIds[$position];
                $secondId = $entrantIds[$entrantCount - 1 - $position];
                $swapSides = $firstId === null;
                if ($secondId !== null && ($roundIndex + $position) % 2 !== 0) {
                    $swapSides = true;
                }

                if ($swapSides) {
                    $temporaryId = $firstId;
                    $firstId = $secondId;
                    $secondId = $temporaryId;
                }

                $pairs[] = [$firstId, $secondId];
            }

            $rounds[] = $pairs;
            $lastId = array_pop($entrantIds);
            array_splice($entrantIds, 1, 0, [$lastId]);
        }

        // En ida y vuelta se repite el calendario invirtiendo los lados.
        if ($legs === 2) {
            $firstLeg = $rounds;
            foreach ($firstLeg as $pairs) {
                $returnPairs = [];
                foreach ($pairs as $pair) {
                    if ($pair[1] === null) {
                        $returnPairs[] = $pair;
                    } else {
                        $returnPairs[] = [$pair[1], $pair[0]];
                    }
                }

                $rounds[] = $returnPairs;
            }
        }

        return $rounds;
    }
}
