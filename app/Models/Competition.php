<?php
namespace app\Models;

require_once __DIR__ . '/../Services/CompetitionRules.php';
use App\Services\CompetitionRules;

class Competition extends Model
{
    private function query(string $sql, array $values = []): \PDOStatement
    {
        $stmt = $this->db->prepare($sql); $stmt->execute($values); return $stmt;
    }

    public function snapshot(array $t): array
    {
        $id = (int)$t['id'];
        $seeds = $this->query('SELECT * FROM tournament_seeds WHERE tournament_id=? ORDER BY seed', [$id])->fetchAll();
        $rounds = $this->query('SELECT * FROM tournament_rounds WHERE tournament_id=? ORDER BY number', [$id])->fetchAll();
        $games = $this->query('SELECT * FROM tournament_match WHERE id_tournament=? ORDER BY round_no, position, id', [$id])->fetchAll();
        $names = array_column($seeds, 'name', 'entrant_id');
        foreach ($games as &$g) {
            $g['name_1'] = $names[$g['team_1_id']] ?? ($g['source_1_id'] ? ($g['source_kind']==='loser'?'Perdedor':'Ganador').' del encuentro #'.$g['source_1_id'] : 'Por definir');
            $g['name_2'] = $names[$g['team_2_id']] ?? ($g['source_2_id'] ? ($g['source_kind']==='loser'?'Perdedor':'Ganador').' del encuentro #'.$g['source_2_id'] : 'Pase libre');
        }
        unset($g);
        $standings = CompetitionRules::standings($seeds, $games, $t);
        $champion = null;
        if ($t['status'] === 'Finalizado') {
            if ($t['format_code'] === 'elimination') {
                foreach ($games as $g) if ($g['round_name'] === 'Final' && $g['winner_id']) $champion = $names[$g['winner_id']] ?? null;
            } else $champion = $standings[0]['name'] ?? null;
        }
        return compact('seeds','rounds','games','standings','champion');
    }

    // El controlador abre la transacción; este bloqueo serializa todas las operaciones del torneo.
    public function operate(int $id, int $actor, string $action, array $input): void
    {
        $this->query('SELECT id FROM tournament WHERE id=? FOR UPDATE', [$id]);
        $model = new Tournament(); $t = $model->find($id);
        $workspace = (new Workspace())->findForUser((int)$t['workspace_id'], $actor);
        if (!$model->canManage($t, $actor, $workspace)) throw new \DomainException('No tienes permisos para gestionar este torneo.');
        $state = $this->snapshot($t);
        if ($action === 'generate') {
            $this->generate($t, $actor, $state); return;
        }
        if (!$state['rounds']) throw new \InvalidArgumentException('Primero genera los enfrentamientos.');
        if ($action === 'next_round') {
            if ($t['format_code'] !== 'swiss') throw new \InvalidArgumentException('Este formato ya tiene sus rondas generadas.');
            $last = end($state['rounds']);
            if ($last['status'] !== 'Cerrada') throw new \InvalidArgumentException('Completa y cierra la ronda actual antes de generar la siguiente.');
            if ((int)$last['number'] >= (int)$t['swiss_rounds']) throw new \InvalidArgumentException('Ya se completaron todas las rondas.');
            $number = (int)$last['number'] + 1;
            $pairs = CompetitionRules::swiss($state['standings'], $state['games']);
            $this->addRound($t,$number,'Ronda '.$number,$pairs);
            $this->log($t,$actor,'round_generate',null,['round'=>$number,'matches'=>count($pairs)]); return;
        }
        if (in_array($action,['close_round','reopen_round','discard_round'],true)) {
            $this->roundAction($t,$actor,$action,(int)($input['round_no'] ?? 0),$state); return;
        }
        $gameId = (int)($input['match_id'] ?? 0); $game = null;
        foreach ($state['games'] as $g) if ((int)$g['id'] === $gameId) $game = $g;
        if (!$game) throw new \InvalidArgumentException('El encuentro no pertenece a este torneo.');
        if (!isset($input['revision']) || (string)$game['revision'] !== (string)$input['revision']) throw new \InvalidArgumentException('Este encuentro cambió mientras lo editabas. Recarga la página antes de guardar.');
        foreach ($state['rounds'] as $round) if ((int)$round['number']===(int)$game['round_no'] && $round['status']==='Cerrada')
            throw new \InvalidArgumentException('La ronda está cerrada. Reábrela antes de corregirla.');
        if ($game['status']==='Libre') throw new \InvalidArgumentException('Los pases libres se resuelven automáticamente.');
        if ($action === 'schedule') {
            if ($game['status']==='Finalizado') throw new \InvalidArgumentException('No puedes reprogramar un encuentro finalizado.');
            $date = trim((string)($input['scheduled_at'] ?? '')); $scheduled = null;
            if ($date !== '') {
                $d = \DateTimeImmutable::createFromFormat('!Y-m-d\\TH:i',$date,new \DateTimeZone('America/Montevideo'));
                if (!$d || $d->format('Y-m-d\\TH:i')!==$date) throw new \InvalidArgumentException('Introduce una fecha y hora válidas.');
                $scheduled = $d->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
                if ($scheduled < $t['start_date'] || $scheduled > $t['end_date']) throw new \InvalidArgumentException('El encuentro debe quedar dentro del período del torneo.');
            }
            $status = !empty($input['suspended']) ? 'Suspendido' : 'Pendiente';
            $this->query('UPDATE tournament_match SET scheduled_at=?,status=?,revision=revision+1 WHERE id=?',[$scheduled,$status,$gameId]);
            $this->log($t,$actor,'match_schedule',['match'=>$gameId,'scheduled_at'=>$game['scheduled_at'],'status'=>$game['status']],['match'=>$gameId,'scheduled_at'=>$scheduled,'status'=>$status]); return;
        }
        if (!in_array($action,['save_result','clear_result'],true)) throw new \InvalidArgumentException('Acción desconocida.');
        if (!$game['team_1_id'] || !$game['team_2_id']) throw new \InvalidArgumentException('Todavía faltan los rivales de este encuentro.');
        if ($t['format_code']==='elimination' && $action==='save_result') {
            foreach ($state['rounds'] as $r) if ((int)$r['number'] < (int)$game['round_no'] && $r['status']!=='Cerrada')
                throw new \InvalidArgumentException('Cierra las rondas anteriores antes de cargar este resultado.');
        }
        if ($t['format_code']==='swiss' && (int)$game['round_no'] < count($state['rounds']))
            throw new \InvalidArgumentException('Retira las rondas posteriores antes de corregir un resultado que determinó sus cruces.');
        $notes = trim((string)($input['notes'] ?? ''));
        if (mb_strlen($notes)>500) throw new \InvalidArgumentException('El detalle admite hasta 500 caracteres.');
        if ($action==='clear_result') {
            if ($game['status']!=='Finalizado' || $notes==='') throw new \InvalidArgumentException('Para anular un resultado registrado debes indicar el motivo.');
            $winner=null; $s1=null; $s2=null; $status='Pendiente';
        } else {
            if ($game['status']==='Suspendido') throw new \InvalidArgumentException('Reanuda el encuentro antes de registrar su resultado.');
            $s1=$this->score($input['score_1'] ?? ''); $s2=$this->score($input['score_2'] ?? '');
            if (($s1===null)!==($s2===null)) throw new \InvalidArgumentException('Completa ambos marcadores o deja ambos vacíos.');
            $outcome=(string)($input['outcome'] ?? '');
            if (!in_array($outcome,['1','2','draw'],true)) throw new \InvalidArgumentException('Selecciona el resultado del encuentro.');
            if ($outcome==='draw' && (!$t['allow_draws'] || $t['format_code']==='elimination')) throw new \InvalidArgumentException('Este torneo necesita un ganador; no admite empates.');
            $winner=$outcome==='draw'?null:(int)$game['team_'.$outcome.'_id'];
            $expected = $s1===$s2 ? 'draw' : (($s1 > $s2) === ($t['score_order']==='higher') ? '1' : '2');
            if (($s1===null || $outcome!==$expected || $game['status']==='Finalizado') && $notes==='')
                throw new \InvalidArgumentException('Explica el motivo de la corrección, del desempate o de la decisión especial.');
            $status='Finalizado';
        }
        $this->query('UPDATE tournament_match SET score_1=?,score_2=?,winner_id=?,notes=?,status=?,played_at=IF(? = ?,UTC_TIMESTAMP(),NULL),revision=revision+1 WHERE id=?',
            [$s1,$s2,$winner,$notes,$status,$status,'Finalizado',$gameId]);
        if ($t['format_code']==='elimination') $this->propagate($t);
        $this->log($t,$actor,$action==='clear_result'?'result_clear':'result_save',
            ['match'=>$gameId,'rival_1'=>$game['name_1'],'rival_2'=>$game['name_2'],'score_1'=>$game['score_1'],'score_2'=>$game['score_2'],'winner'=>$this->winnerName($game['winner_id'],$state),'notes'=>$game['notes'],'status'=>$game['status']],
            ['match'=>$gameId,'score_1'=>$s1,'score_2'=>$s2,'winner'=>$winner?$this->winnerName($winner,$state):($status==='Finalizado'?'Empate':'Sin resultado'),'notes'=>$notes,'status'=>$status]);
    }

    private function score($value): ?float
    {
        if (!is_string($value) || trim($value)==='') return null;
        if (!preg_match('/^-?\d{1,6}(?:\.\d{1,2})?$/',trim($value))) throw new \InvalidArgumentException('El marcador debe ser un número de −999999,99 a 999999,99, con hasta dos decimales.');
        return (float)$value;
    }

    private function winnerName($id,array $state): string
    {
        foreach ($state['seeds'] as $s) if ((int)$s['entrant_id']===(int)$id) return $s['name'];
        return 'Sin ganador';
    }

    private function generate(array $t,int $actor,array $state): void
    {
        if ($state['games'] || $state['rounds']) throw new \InvalidArgumentException('Este torneo ya tiene enfrentamientos generados.');
        if ($t['status']==='Finalizado') throw new \InvalidArgumentException('Configura el torneo como Planificado antes de generar sus enfrentamientos.');
        if ($t['has_team']) {
            $entrants=$this->query('SELECT tt.id AS entrant_id,tt.name,COUNT(tm.id) AS members FROM tournament_teams tt LEFT JOIN tournament_members tm ON tm.team_id=tt.id AND tm.tournament_id=tt.tournament_id WHERE tt.tournament_id=? GROUP BY tt.id,tt.name ORDER BY tt.id',[$t['id']])->fetchAll();
            foreach ($entrants as $e) if (!$e['members']) throw new \InvalidArgumentException('Todos los equipos deben tener al menos un integrante.');
        } else $entrants=$this->query('SELECT tm.id AS entrant_id,u.name FROM tournament_members tm JOIN users u ON u.id=tm.member_id WHERE tm.tournament_id=? ORDER BY tm.id',[$t['id']])->fetchAll();
        $n=count($entrants);
        if ($n<2 || $n>256) throw new \InvalidArgumentException('Se necesitan entre 2 y 256 participantes o equipos inscritos.');
        if ($t['format_code']==='swiss' && ($n<4 || (int)$t['swiss_rounds']>($n%2 ? $n : $n-1)))
            throw new \InvalidArgumentException('El suizo necesita al menos 4 inscritos y como máximo '.($n%2?$n:$n-1).' rondas para evitar rivales repetidos. Ajusta la configuración.');
        if ($t['format_code']==='elimination' && $t['third_place'] && $n<4) throw new \InvalidArgumentException('El partido por tercer puesto requiere al menos 4 inscritos.');
        shuffle($entrants); $ids=[];
        foreach ($entrants as $i=>$e) {
            $ids[]=(int)$e['entrant_id'];
            $this->query('INSERT INTO tournament_seeds(tournament_id,entrant_id,seed,name) VALUES(?,?,?,?)',[$t['id'],$e['entrant_id'],$i+1,$e['name']]);
        }
        if ($t['format_code']==='league') {
            foreach (CompetitionRules::league($ids,(int)$t['league_legs']) as $i=>$pairs) $this->addRound($t,$i+1,'Jornada '.($i+1),$pairs);
        } elseif ($t['format_code']==='swiss') {
            $state=$this->snapshot($t); $this->addRound($t,1,'Ronda 1',CompetitionRules::swiss($state['standings'],[]));
        } else {
            $size=2; while ($size<$n) $size*=2;
            $byes=$size-$n; $pairs=[];
            for ($i=0;$i<$byes;$i++) $pairs[]=[array_shift($ids),null];
            while ($ids) $pairs[]=[array_shift($ids),array_shift($ids)];
            // Reparte los pases libres entre las ramas de la llave.
            $ordered=[]; while ($pairs) { $ordered[]=array_shift($pairs); if ($pairs) $ordered[]=array_pop($pairs); }
            $roundCount=(int)log($size,2); $previous=[];
            for ($r=1;$r<=$roundCount;$r++) {
                $name=$r===$roundCount?'Final':($r===$roundCount-1?'Semifinales':'Ronda '.$r);
                $this->query('INSERT INTO tournament_rounds(tournament_id,number,name) VALUES(?,?,?)',[$t['id'],$r,$name]);
                $current=[];
                if ($r===1) foreach ($ordered as $i=>$pair) $current[]=$this->addGame($t,$r,$name,$i+1,$pair);
                else for ($i=0;$i<count($previous);$i+=2) $current[]=$this->addGame($t,$r,$name,count($current)+1,[null,null],$previous[$i],$previous[$i+1]);
                if ($r===$roundCount && $t['third_place']) $this->addGame($t,$r,'Tercer puesto',2,[null,null],$previous[0],$previous[1],'loser');
                $previous=$current;
            }
            $this->propagate($t);
        }
        $this->query('UPDATE tournament SET status=? WHERE id=?',['En curso',$t['id']]);
        $this->log($t,$actor,'matches_generate',null,['format'=>$t['format_name'],'participants'=>$n,'detail'=>'Sorteo inicial e inscripciones cerradas.']);
    }

    private function addRound(array $t,int $number,string $name,array $pairs): void
    {
        $this->query('INSERT INTO tournament_rounds(tournament_id,number,name) VALUES(?,?,?)',[$t['id'],$number,$name]);
        foreach ($pairs as $i=>$pair) $this->addGame($t,$number,$name,$i+1,$pair);
    }

    private function addGame(array $t,int $round,string $name,int $position,array $pair,?int $source1=null,?int $source2=null,string $kind='winner'): int
    {
        $bye=$pair[0]!==null && $pair[1]===null && $source1===null;
        $winner=$bye && $t['format_code']!=='league' ? $pair[0] : null;
        $this->query('INSERT INTO tournament_match(id_tournament,has_team,team_1_id,team_2_id,round_name,round_no,position,status,winner_id,source_1_id,source_2_id,source_kind) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)',
            [$t['id'],$t['has_team'],$pair[0],$pair[1],$name,$round,$position,$bye?'Libre':'Pendiente',$winner,$source1,$source2,$kind]);
        return (int)$this->db->lastInsertId();
    }

    private function propagate(array $t): void
    {
        $games=$this->query('SELECT * FROM tournament_match WHERE id_tournament=? ORDER BY round_no,position',[$t['id']])->fetchAll(); $known=[];
        foreach ($games as $g) {
            if ($g['source_1_id']) {
                $pair=[];
                foreach (['source_1_id','source_2_id'] as $key) {
                    $s=$known[$g[$key]]; $entrant=null;
                    if (in_array($s['status'],['Finalizado','Libre'],true)) {
                        $entrant=$g['source_kind']==='winner'?$s['winner_id']:($s['winner_id']==$s['team_1_id']?$s['team_2_id']:$s['team_1_id']);
                    }
                    $pair[]=$entrant;
                }
                if ($pair[0]!=$g['team_1_id'] || $pair[1]!=$g['team_2_id']) {
                    if ($g['status']==='Finalizado') throw new \InvalidArgumentException('La corrección cambia un rival de un encuentro posterior con resultado. Reabre y anula primero ese resultado posterior.');
                    $this->query('UPDATE tournament_match SET team_1_id=?,team_2_id=?,revision=revision+1 WHERE id=?',[$pair[0],$pair[1],$g['id']]);
                    $g['team_1_id']=$pair[0];$g['team_2_id']=$pair[1];
                }
            }
            $known[$g['id']]=$g;
        }
    }

    private function roundAction(array $t,int $actor,string $action,int $number,array $state): void
    {
        $round=null; foreach ($state['rounds'] as $r) if ((int)$r['number']===$number) $round=$r;
        if (!$round) throw new \InvalidArgumentException('Ronda inexistente.');
        $games=array_values(array_filter($state['games'],static fn($g)=>(int)$g['round_no']===$number));
        if ($action==='discard_round') {
            if ($t['format_code']!=='swiss' || $number!==count($state['rounds']) || $number===1) throw new \InvalidArgumentException('Solo puedes retirar la última ronda del suizo, a partir de la segunda.');
            foreach ($games as $g) if ($g['status']==='Finalizado') throw new \InvalidArgumentException('Reabre la ronda y anula sus resultados antes de retirarla.');
            $this->query('DELETE FROM tournament_match WHERE id_tournament=? AND round_no=?',[$t['id'],$number]);
            $this->query('DELETE FROM tournament_rounds WHERE tournament_id=? AND number=?',[$t['id'],$number]);
            $this->query('UPDATE tournament SET status=? WHERE id=?',['En curso',$t['id']]);
            $this->log($t,$actor,'round_discard',['round'=>$number,'matches'=>count($games),'detail'=>'Ronda sin resultados retirada para revisar cruces anteriores.'],null); return;
        }
        $status=$action==='close_round'?'Cerrada':'Abierta';
        if ($round['status']===$status) throw new \InvalidArgumentException('La ronda ya está '.strtolower($status).'.');
        if ($status==='Cerrada') {
            foreach ($games as $g) if (!in_array($g['status'],['Finalizado','Libre'],true)) throw new \InvalidArgumentException('Completa todos los resultados antes de cerrar la ronda.');
            foreach ($state['rounds'] as $r) if ((int)$r['number']<$number && $r['status']!=='Cerrada') throw new \InvalidArgumentException('Cierra primero las rondas anteriores.');
        } elseif ($t['format_code']==='swiss' && $number<count($state['rounds'])) throw new \InvalidArgumentException('Retira las rondas posteriores del suizo antes de reabrir esta ronda.');
        $this->query('UPDATE tournament_rounds SET status=? WHERE tournament_id=? AND number=?',[$status,$t['id'],$number]);
        $open=(int)$this->query('SELECT COUNT(*) FROM tournament_rounds WHERE tournament_id=? AND status<>?',[$t['id'],'Cerrada'])->fetchColumn();
        $finished=!$open && ($t['format_code']!=='swiss' || count($state['rounds'])===(int)$t['swiss_rounds']);
        $this->query('UPDATE tournament SET status=? WHERE id=?',[$finished?'Finalizado':'En curso',$t['id']]);
        $this->log($t,$actor,$status==='Cerrada'?'round_close':'round_reopen',['round'=>$number,'status'=>$round['status']],['round'=>$number,'status'=>$status]);
    }

    private function log(array $t,int $actor,string $action,?array $before,?array $after): void
    {
        $this->query('INSERT INTO workspace_audit_logs(workspace_id,user_id,entity_type,entity_id,action,old_value,new_value,created_at) VALUES(?,?,?,?,?,?,?,UTC_TIMESTAMP())',
            [$t['workspace_id'],$actor,'tournament',(string)$t['id'],$action,$before===null?null:json_encode($before,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),$after===null?null:json_encode($after,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
    }
}
