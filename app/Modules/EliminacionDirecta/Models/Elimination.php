<?php

namespace App\Modules\EliminacionDirecta\Models;

use app\Models\Model;

class Elimination extends Model
{
    public function generate(array $tournament, array $entrantIds): void
    {
        $entrantCount = count($entrantIds);
        $fixtures = new \App\Modules\Calendarios\Models\Fixtures();
        $size = 2;
        while ($size < $entrantCount) {
            $size *= 2;
        }

        $byes = $size - $entrantCount;
        $pairs = [];
        for ($position = 0; $position < $byes; $position++) {
            $pairs[] = [array_shift($entrantIds), null];
        }
        while ($entrantIds) {
            $pairs[] = [array_shift($entrantIds), array_shift($entrantIds)];
        }
        // Reparte los pases libres entre las ramas de la llave.
        $ordered = [];
        while ($pairs) {
            $ordered[] = array_shift($pairs);
            if ($pairs) {
                $ordered[] = array_pop($pairs);
            }
        }

        $roundCount = (int)log($size, 2);
        $previous = [];
        for ($roundNumber = 1; $roundNumber <= $roundCount; $roundNumber++) {
            $name = 'Ronda ' . $roundNumber;
            if ($roundNumber === $roundCount) {
                $name = 'Final';
            } elseif ($roundNumber === $roundCount - 1) {
                $name = 'Semifinales';
            }
            $this->query('INSERT INTO tournament_rounds(tournament_id,number,name) VALUES(?,?,?)', [$tournament['id'], $roundNumber, $name]);
            $current = [];
            if ($roundNumber === 1) {
                foreach ($ordered as $position => $pair) {
                    $current[] = $fixtures->addGame($tournament, $roundNumber, $name, $position + 1, $pair);
                }
            } else {
                for ($position = 0; $position < count($previous); $position += 2) {
                    $current[] = $fixtures->addGame($tournament, $roundNumber, $name, count($current) + 1, [null, null], $previous[$position], $previous[$position + 1]);
                }
            }

            if ($roundNumber === $roundCount && $tournament['third_place']) {
                $fixtures->addGame($tournament, $roundNumber, 'Tercer puesto', 2, [null, null], $previous[0], $previous[1], 'loser');
            }

            $previous = $current;
        }

        $this->propagate($tournament);
    }
    public function propagate(array $tournament): void
    {
        $games = $this->query('SELECT * FROM tournament_match WHERE id_tournament=? ORDER BY round_no,position', [$tournament['id']])->fetchAll();
        $known = [];
        foreach ($games as $match) {
            if ($match['source_1_id']) {
                $pair = [];
                foreach (['source_1_id', 'source_2_id'] as $key) {
                    $sourceMatch = $known[$match[$key]];
                    $entrant = null;
                    if (in_array($sourceMatch['status'], ['Finalizado', 'Libre'], true)) {
                        if ($match['source_kind'] === 'winner') {
                            $entrant = $sourceMatch['winner_id'];
                        } elseif ($sourceMatch['winner_id'] == $sourceMatch['team_1_id']) {
                            $entrant = $sourceMatch['team_2_id'];
                        } else {
                            $entrant = $sourceMatch['team_1_id'];
                        }
                    }

                    $pair[] = $entrant;
                }

                if ($pair[0] != $match['team_1_id'] || $pair[1] != $match['team_2_id']) {
                    if ($match['status'] === 'Finalizado') {
                        throw new \InvalidArgumentException('La corrección cambia un rival de un encuentro posterior con resultado. Reabre y anula primero ese resultado posterior.');
                    }

                    $this->query('UPDATE tournament_match SET team_1_id=?,team_2_id=?,revision=revision+1 WHERE id=?', [$pair[0], $pair[1], $match['id']]);
                    $match['team_1_id'] = $pair[0];
                    $match['team_2_id'] = $pair[1];
                }
            }

            $known[$match['id']] = $match;
        }
    }
}
