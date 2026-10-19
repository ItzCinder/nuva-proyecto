<?php

namespace App\Modules\Calendarios\Models;

use app\Models\Model;

class Fixtures extends Model
{
    public function addRound(array $tournament, int $number, string $name, array $pairs): void
    {
        $this->query('INSERT INTO tournament_rounds(tournament_id,number,name) VALUES(?,?,?)', [$tournament['id'], $number, $name]);
        foreach ($pairs as $index => $pair) {
            $this->addGame($tournament, $number, $name, $index + 1, $pair);
        }
    }
    public function addGame(array $tournament, int $round, string $name, int $position, array $pair, ?int $source1 = null, ?int $source2 = null, string $kind = 'winner'): int
    {
        $bye = $pair[0] !== null && $pair[1] === null && $source1 === null;
        $winner = $bye && $tournament['format_code'] !== 'league' ? $pair[0] : null;
        $this->query(
            'INSERT INTO tournament_match(id_tournament,has_team,team_1_id,team_2_id,round_name,round_no,position,status,winner_id,source_1_id,source_2_id,source_kind) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)',
            [$tournament['id'], $tournament['has_team'], $pair[0], $pair[1], $name, $round, $position, $bye ? 'Libre' : 'Pendiente', $winner, $source1, $source2, $kind]
        );
        return (int)$this->database->lastInsertId();
    }
}
