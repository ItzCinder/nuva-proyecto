<?php
namespace App\Services;

// Algoritmos sin consultas: se prueban con participantes ficticios.
class CompetitionRules
{
    public static function league(array $ids, int $legs): array
    {
        if (count($ids) % 2) $ids[] = null;
        $n = count($ids); $rounds = [];
        for ($r = 0; $r < $n - 1; $r++) {
            $pairs = [];
            for ($i = 0; $i < $n / 2; $i++) {
                $a = $ids[$i]; $b = $ids[$n - 1 - $i];
                if ($a === null) [$a, $b] = [$b, $a];
                elseif ($b !== null && ($r + $i) % 2) [$a, $b] = [$b, $a];
                $pairs[] = [$a, $b];
            }
            $rounds[] = $pairs;
            $last = array_pop($ids); array_splice($ids, 1, 0, [$last]);
        }
        if ($legs === 2) {
            foreach ($rounds as $pairs) $rounds[] = array_map(static fn ($p) => $p[1] === null ? $p : [$p[1], $p[0]], $pairs);
        }
        return $rounds;
    }

    public static function standings(array $entrants, array $games, array $t): array
    {
        $table = [];
        foreach ($entrants as $e) $table[(int)$e['entrant_id']] = $e + ['played'=>0,'wins'=>0,'draws'=>0,'losses'=>0,'byes'=>0,'points'=>0.0,'for'=>0.0,'against'=>0.0,'diff'=>0.0,'buchholz'=>0.0];
        foreach ($games as $g) {
            $a = (int)$g['team_1_id']; $b = (int)$g['team_2_id'];
            if ($g['status'] === 'Libre' && isset($table[$a])) {
                $table[$a]['byes']++;
                if ($t['format_code'] === 'swiss') $table[$a]['points'] += (float)$t['points_win'];
                continue;
            }
            if ($g['status'] !== 'Finalizado' || !isset($table[$a], $table[$b])) continue;
            foreach ([[$a,$b,'score_1','score_2'],[$b,$a,'score_2','score_1']] as [$id,$other,$own,$opponent]) {
                $row = &$table[$id]; $row['played']++;
                $row['for'] += (float)$g[$own]; $row['against'] += (float)$g[$opponent];
                $result = !$g['winner_id'] ? 'draws' : ((int)$g['winner_id'] === $id ? 'wins' : 'losses');
                $row[$result]++;
                $row['points'] += (float)$t[['wins'=>'points_win','draws'=>'points_draw','losses'=>'points_loss'][$result]];
                unset($row);
            }
        }
        foreach ($games as $g) {
            if ($g['status'] !== 'Finalizado') continue;
            $a=(int)$g['team_1_id']; $b=(int)$g['team_2_id'];
            if (isset($table[$a],$table[$b])) {
                $table[$a]['buchholz'] += $table[$b]['points']; $table[$b]['buchholz'] += $table[$a]['points'];
            }
        }
        foreach ($table as &$row) $row['diff'] = ($row['for'] - $row['against']) * ($t['score_order'] === 'lower' ? -1 : 1);
        unset($row);
        $rows = array_values($table);
        usort($rows, static function ($a,$b) use ($t) {
            $keys = $t['format_code'] === 'swiss' ? ['points','buchholz','wins','diff'] : ['points','diff','wins'];
            foreach ($keys as $key) if (abs($a[$key]-$b[$key]) > .00001) return $b[$key] <=> $a[$key];
            return $a['seed'] <=> $b['seed'];
        });
        return $rows;
    }

    public static function swiss(array $standings, array $games): array
    {
        $played = [];
        foreach ($games as $g) if ($g['team_2_id']) {
            $played[(int)$g['team_1_id']][(int)$g['team_2_id']] = true;
            $played[(int)$g['team_2_id']][(int)$g['team_1_id']] = true;
        }
        $ids = array_map('intval', array_column($standings, 'entrant_id'));
        $byes = [null];
        if (count($ids) % 2) $byes = array_map('intval', array_column(array_filter(array_reverse($standings), static fn ($s) => !$s['byes']), 'entrant_id'));
        $nodes = 0;
        foreach ($byes as $bye) {
            $remaining = array_values(array_filter($ids, static fn ($id) => $id !== $bye));
            $pairs = self::pairSwiss($remaining, $played, $nodes);
            if ($pairs !== null) {
                if ($bye !== null) $pairs[] = [$bye, null];
                return $pairs;
            }
        }
        throw new \InvalidArgumentException('No se encontró un emparejamiento sin repetir rivales o pases libres. Revisa las rondas configuradas; no se generaron cruces repetidos.');
    }

    private static function pairSwiss(array $ids, array $played, int &$nodes): ?array
    {
        if (!$ids) return [];
        if (++$nodes > 200000) throw new \InvalidArgumentException('La búsqueda de cruces sin repeticiones alcanzó su límite. Revisa la cantidad de rondas antes de continuar.');
        $a = array_shift($ids);
        // El orden de clasificación prioriza rivales con rendimiento próximo.
        foreach ($ids as $i=>$b) {
            if (isset($played[$a][$b])) continue;
            $rest=$ids; unset($rest[$i]);
            $pairs=self::pairSwiss(array_values($rest),$played,$nodes);
            if ($pairs !== null) return array_merge([[$a,$b]],$pairs);
        }
        return null;
    }
}
