<?php

namespace App\Modules\Liga\Models;

use app\Models\Model;
use App\Modules\Calendarios\Models\Fixtures;
use App\Modules\Liga\Services\LeagueRules;

class League extends Model
{
    public function generate(array $tournament, array $entrantIds): void
    {
        $fixtures = new Fixtures();
        $rounds = LeagueRules::league($entrantIds, (int) $tournament['league_legs']);
        foreach ($rounds as $index => $pairs) {
            $roundNumber = $index + 1;
            $fixtures->addRound($tournament, $roundNumber, 'Jornada ' . $roundNumber, $pairs);
        }
    }
}
