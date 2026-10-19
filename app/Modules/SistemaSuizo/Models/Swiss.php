<?php

namespace App\Modules\SistemaSuizo\Models;

use app\Models\Model;
use App\Modules\Calendarios\Models\Fixtures;
use App\Modules\SistemaSuizo\Services\SwissRules;

class Swiss extends Model
{
    public function generateRound(array $tournament, array $state, int $number): void
    {
        $pairs = SwissRules::swiss($state['standings'], $state['games']);
        $fixtures = new Fixtures();
        $fixtures->addRound($tournament, $number, 'Ronda ' . $number, $pairs);
    }
}
