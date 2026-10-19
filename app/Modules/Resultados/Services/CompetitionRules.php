<?php

namespace App\Services;

// Fachada compatible para los consumidores del motor; cada formato vive en su módulo.
class CompetitionRules
{
    public static function league(array $ids, int $legs): array
    {
        return \App\Modules\Liga\Services\LeagueRules::league($ids, $legs);
    }
    public static function swiss(array $standings, array $games): array
    {
        return \App\Modules\SistemaSuizo\Services\SwissRules::swiss($standings, $games);
    }
    public static function standings(array $entrants, array $games, array $tournament): array
    {
        return \App\Modules\Resultados\Services\Standings::build($entrants, $games, $tournament);
    }
}
