<?php
// Ejecutar: /opt/lampp/bin/php tests/competition.php
// Los datos de integración se crean dentro de una transacción que siempre se revierte.
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
chdir(dirname(__DIR__));
$_ENV=parse_ini_file('.env',false,INI_SCANNER_RAW);
foreach (['Services/Database','Models/Model','Models/Role','Models/Workspace','Models/Tournament','Models/Competition'] as $file) require_once 'app/'.$file.'.php';
use App\Services\CompetitionRules as Rules;
use app\Models\Tournament;
use app\Models\Competition;
function check($condition,$message) { if (!$condition) throw new RuntimeException($message); }
function rejects(callable $call,string $message) { try { $call(); } catch (InvalidArgumentException|DomainException $e) { return; } throw new RuntimeException($message); }
foreach ([2,3,4,5,16,31,256] as $n) foreach ([1,2] as $legs) {
 $seen=[];
 foreach(Rules::league(range(1,$n),$legs) as $pairs) {
  $round=[];
  foreach($pairs as [$a,$b]) {
   foreach([$a,$b] as $id) if($id!==null) { check(!isset($round[$id]),'Doble encuentro en jornada'); $round[$id]=1; }
   if($b!==null) { $key=min($a,$b).':'.max($a,$b);$seen[$key]=($seen[$key]??0)+1; }
  }
  check(count($round)===$n,'Faltan inscritos en jornada');
 }
 check(count($seen)===$n*($n-1)/2,'Liga incompleta');
 foreach($seen as $count) check($count===$legs,'Repetición incorrecta');
}
$db=Database::getConnection();$db->beginTransaction();
try {
 $db->exec("INSERT INTO users(google_id,email,name,created_at) VALUES('nuva-engine-test','engine-test@example.invalid','Prueba del motor',UTC_TIMESTAMP())");
 $owner=(int)$db->lastInsertId();$workspace=(new app\Models\Workspace())->create($owner,'Pruebas temporales','Se revierte al terminar');
 $people=[];
 for($i=0;$i<9;$i++) {
  $q=$db->prepare('INSERT INTO users(google_id,email,name,created_at) VALUES(?,?,?,UTC_TIMESTAMP())');$q->execute(['nuva-engine-'.$i,'engine-'.$i.'@example.invalid','Participante '.$i]);$people[]=(int)$db->lastInsertId();
  $q=$db->prepare('INSERT INTO workspace_members(workspace_id,user_id,role_id,assigned_at) VALUES(?,?,?,UTC_TIMESTAMP())');$q->execute([$workspace,end($people),app\Models\Role::findId(app\Models\Role::PARTICIPANT)]);
 }
 $model=new Tournament();$engine=new Competition();$modules=array_column($db->query('SELECT code,id FROM modules')->fetchAll(),'id','code');
 $make=function($format,$n,$third=0,$team=0) use($model,$workspace,$owner,$people,$modules) {
  $id=$model->create(['workspace_id'=>$workspace,'organizer_id'=>null,'name'=>'Prueba '.$format,'description'=>'','discipline_category'=>'otros','discipline'=>'Disciplina genérica','has_team'=>$team,'max_participants'=>16,'published'=>1,'start_date'=>'2026-01-01 00:00:00','end_date'=>'2027-12-31 23:00:00','status'=>'Planificado','module_id'=>$modules[$format],'league_legs'=>2,'swiss_rounds'=>3,'third_place'=>$third,'team_size'=>$team?2:null,'points_win'=>3,'points_draw'=>1,'points_loss'=>0,'allow_draws'=>1,'score_order'=>'lower','score_label'=>'Tiempo'],$owner);
  $t=$model->find($id);
  for($i=0;$i<$n;$i++) { $teamId=$team?$model->createTeam($t,$owner,'Equipo '.$i):null;$model->enroll($t,$owner,$people[$i],$teamId); }
  return $id;
 };
 $act=function($id,$action,$data=[]) use($engine,$owner,$db) {
  $db->exec('SAVEPOINT operation');
  try {$engine->operate($id,$owner,$action,$data);} catch(Throwable $e) {$db->exec('ROLLBACK TO SAVEPOINT operation');throw $e;}
 };
 foreach([['league',5,0,0],['swiss',5,0,0],['swiss',4,0,1],['elimination',2,0,0],['elimination',3,0,0],['elimination',5,1,0],['elimination',8,1,1]] as [$format,$n,$third,$team]) {
  $id=$make($format,$n,$third,$team);$act($id,'generate');
  rejects(fn()=>$act($id,'generate'),'Generación duplicada aceptada');
  rejects(fn()=>$model->enroll($model->find($id),$owner,$people[8],null),'Inscripción tardía aceptada');
  rejects(fn()=>$engine->operate($id,$people[0],'close_round',['round_no'=>1]),'Participante pudo gestionar');
  for($r=1;$r<=($format==='swiss'?3:count($engine->snapshot($model->find($id))['rounds']));$r++) {
   if($format==='swiss' && $r>1) $act($id,'next_round');
   $state=$engine->snapshot($model->find($id));
   rejects(fn()=>$act($id,'close_round',['round_no'=>$r]),'Cerró ronda incompleta');
   foreach($state['games'] as $g) if((int)$g['round_no']===$r && $g['status']!=='Libre') {
    check($g['team_1_id'] && $g['team_2_id'],'Cruce sin participantes');
    $input=['match_id'=>$g['id'],'revision'=>(string)$g['revision'],'score_1'=>'1.25','score_2'=>'2.50','outcome'=>'1','notes'=>''];
    $act($id,'save_result',$input);
    rejects(fn()=>$act($id,'save_result',$input),'Aceptó revisión vieja');
   }
   $act($id,'close_round',['round_no'=>$r]);
  }
  $t=$model->find($id);$state=$engine->snapshot($t);
  check($t['status']==='Finalizado' && $state['champion'],'No finalizó con campeón');
  if($format==='swiss') {
   $pairs=[];$byes=[];foreach($state['games'] as $g) {
    if(!$g['team_2_id']) {check(!isset($byes[$g['team_1_id']]),'Pase libre repetido');$byes[$g['team_1_id']]=1;continue;}
    $key=min($g['team_1_id'],$g['team_2_id']).':'.max($g['team_1_id'],$g['team_2_id']);check(!isset($pairs[$key]),'Rival repetido');$pairs[$key]=1;
   }
   rejects(fn()=>$act($id,'reopen_round',['round_no'=>1]),'Reabrió suizo con ronda posterior');
  }
  if($format==='elimination' && $n===8) {
   $act($id,'reopen_round',['round_no'=>1]);$g=$state['games'][0];
   rejects(fn()=>$act($id,'save_result',['match_id'=>$g['id'],'revision'=>(string)$g['revision'],'score_1'=>'3','score_2'=>'1','outcome'=>'2','notes'=>'Corrección']),'Cambió rivales posteriores con resultados');
  }
  echo "OK $format · $n inscritos · equipos=$team\n";
 }
 // Empates, decisiones sin marcador, suspensión, anulación y recálculo.
 $id=$make('league',2);$act($id,'generate');$g=$engine->snapshot($model->find($id))['games'][0];
 $act($id,'schedule',['match_id'=>$g['id'],'revision'=>'0','scheduled_at'=>'2026-10-15T18:00','suspended'=>'1']);
 rejects(fn()=>$act($id,'save_result',['match_id'=>$g['id'],'revision'=>'1','score_1'=>'1','score_2'=>'1','outcome'=>'draw']),'Resultado en suspendido');
 $act($id,'schedule',['match_id'=>$g['id'],'revision'=>'1','scheduled_at'=>'2026-10-15T18:00']);
 $act($id,'save_result',['match_id'=>$g['id'],'revision'=>'2','score_1'=>'1','score_2'=>'1','outcome'=>'draw']);
 $state=$engine->snapshot($model->find($id));check($state['standings'][0]['points']==1 && $state['standings'][1]['points']==1,'Puntos empate incorrectos');
 $act($id,'clear_result',['match_id'=>$g['id'],'revision'=>'3','notes'=>'Prueba de anulación']);
 check($engine->snapshot($model->find($id))['standings'][0]['points']==0,'No recalculó anulación');
 $act($id,'save_result',['match_id'=>$g['id'],'revision'=>'4','outcome'=>'2','notes'=>'Victoria por decisión arbitral']);
 check($engine->snapshot($model->find($id))['standings'][0]['points']==3,'Decisión sin marcador incorrecta');
 // Corrección de una llave después de retirar el resultado dependiente.
 $id=$make('elimination',4);$act($id,'generate');
 foreach($engine->snapshot($model->find($id))['games'] as $g) if((int)$g['round_no']===1) {
  rejects(fn()=>$act($id,'save_result',['match_id'=>$g['id'],'revision'=>(string)$g['revision'],'score_1'=>'2','score_2'=>'2','outcome'=>'1']),'Aceptó desempate sin detalle');
  $act($id,'save_result',['match_id'=>$g['id'],'revision'=>(string)$g['revision'],'score_1'=>'2','score_2'=>'2','outcome'=>'1','notes'=>'Ganó el desempate']);
 }
 $act($id,'close_round',['round_no'=>1]);
 $state=$engine->snapshot($model->find($id));$final=end($state['games']);$first=$state['games'][0];
 $act($id,'save_result',['match_id'=>$final['id'],'revision'=>(string)$final['revision'],'score_1'=>'-2.5','score_2'=>'-1','outcome'=>'1']);
 $act($id,'close_round',['round_no'=>2]);$act($id,'reopen_round',['round_no'=>2]);
 $act($id,'clear_result',['match_id'=>$final['id'],'revision'=>(string)((int)$final['revision']+1),'notes'=>'Se revisa la semifinal']);
 $act($id,'reopen_round',['round_no'=>1]);
 $act($id,'save_result',['match_id'=>$first['id'],'revision'=>(string)$first['revision'],'score_1'=>'5','score_2'=>'1','outcome'=>'2','notes'=>'Marcador corregido']);
 $state=$engine->snapshot($model->find($id));$changed=end($state['games']);
 check($changed['team_1_id']==$first['team_2_id'] && $changed['status']==='Pendiente','No actualizó la final tras corrección');
 $frozen=$model->find($id);$modified=$frozen;$modified['points_win']=10;
 rejects(fn()=>$model->update($frozen,$modified,$owner),'Cambió reglas de torneo iniciado');
 // Regenerar una ronda suiza sin duplicarla y conservar la auditoría.
 $id=$make('swiss',5);$act($id,'generate');
 foreach($engine->snapshot($model->find($id))['games'] as $g) if($g['status']!=='Libre')
  $act($id,'save_result',['match_id'=>$g['id'],'revision'=>(string)$g['revision'],'score_1'=>'1','score_2'=>'2','outcome'=>'1']);
 $act($id,'close_round',['round_no'=>1]);$act($id,'next_round');
 $act($id,'discard_round',['round_no'=>2]);$act($id,'reopen_round',['round_no'=>1]);
 $act($id,'close_round',['round_no'=>1]);$act($id,'next_round');
 check(count($engine->snapshot($model->find($id))['rounds'])===2,'Duplicó ronda al regenerar');
 echo "OK desempates, marcadores negativos, propagación de correcciones y regeneración suiza\n";
 echo "OK decisiones, empates, revisión concurrente, permisos, suspensión y correcciones\n";
} finally {if($db->inTransaction())$db->rollBack();}
echo "Todas las pruebas pasaron; datos temporales revertidos.\n";
