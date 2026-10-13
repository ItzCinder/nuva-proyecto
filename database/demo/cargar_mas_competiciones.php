<?php
// Ejemplo: /opt/lampp/bin/php database/demo/cargar_mas_competiciones.php --owner-id=1
// Agrega seis torneos operativos en dos entornos separados. No modifica torneos existentes.
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
$owner=0;
foreach($argv as $arg) if(preg_match('/^--owner-id=([1-9][0-9]*)$/',$arg,$m)) $owner=(int)$m[1];
if(!$owner) {fwrite(STDERR,"Uso: php database/demo/cargar_mas_competiciones.php --owner-id=ID\n");exit(1);}
$root=dirname(__DIR__,2);
$_ENV=parse_ini_file($root.'/.env',false,INI_SCANNER_RAW);
foreach(['Services/Database','Models/Model','Models/Role','Models/Workspace','Models/Tournament','Models/Competition'] as $file) require_once $root.'/app/'.$file.'.php';
$db=Database::getConnection();$db->exec("SET time_zone='+00:00'");
$q=$db->prepare('SELECT id FROM users WHERE id=?');$q->execute([$owner]);
if(!$q->fetchColumn()) {fwrite(STDERR,"No existe esa cuenta.\n");exit(1);}
$db->exec('CREATE TABLE IF NOT EXISTS extra_competition_demo_registry(owner_id INT NOT NULL PRIMARY KEY,workspace_id INT NOT NULL,created_at DATETIME NOT NULL,FOREIGN KEY(owner_id) REFERENCES users(id),FOREIGN KEY(workspace_id) REFERENCES workspaces(id)) ENGINE=InnoDB');
$model=new app\Models\Tournament();$engine=new app\Models\Competition();
try {
 $db->beginTransaction();
 $q=$db->prepare('SELECT id FROM users WHERE id=? FOR UPDATE');$q->execute([$owner]);
 $q=$db->prepare('SELECT workspace_id FROM extra_competition_demo_registry WHERE owner_id=?');$q->execute([$owner]);
 if($q->fetchColumn()) {$db->rollBack();echo "Los ejemplos de competiciones ya están cargados para esta cuenta.\n";exit;}
 $workspace=(new app\Models\Workspace())->create($owner,'Comunidad del Río · Demo','Ejemplos de liga, suizo y eliminación, con resultados ficticios y calendarios.');
 $people=[];
 foreach(['Ana Torres','Bruno Silva','Camila Ríos','Diego Méndez','Elena Costa','Felipe Ramos','Gabriela Luna','Hugo Pereira'] as $i=>$name) {
  $q=$db->prepare('INSERT INTO users(google_id,email,name,created_at) VALUES(?,?,?,UTC_TIMESTAMP())');
  $q->execute(['nuva-extra-demo:'.$owner.':'.$i,'extra-demo-'.$owner.'-'.$i.'@example.invalid',$name]);
  $people[]=(int)$db->lastInsertId();
  $q=$db->prepare('INSERT INTO workspace_members(workspace_id,user_id,role_id,assigned_at) VALUES(?,?,?,UTC_TIMESTAMP())');
  $q->execute([$workspace,end($people),app\Models\Role::findId(app\Models\Role::PARTICIPANT)]);
 }
 $modules=array_column($db->query('SELECT code,id FROM modules')->fetchAll(),'id','code');
 $examples=[
  ['Liga de fútbol del Río · Finalizada','league','Fútbol','tradicional',1,4,true,'Goles','higher'],
  ['Copa de tenis del Río · Finalizada','elimination','Tenis','tradicional',0,5,true,'Sets','higher'],
  ['Suizo de pádel del Río · En curso','swiss','Pádel','tradicional',1,4,false,'Sets','higher'],
  ['Liga de Go · Finalizada','league','Go','mental',0,5,true,'Puntos','higher'],
  ['Abierto de estrategia · Finalizado','swiss','Juego de estrategia','otros',0,7,true,'Puntos','higher'],
  ['Copa de videojuegos · En curso','elimination','Valorant','esports',1,4,false,'Mapas','higher'],
 ];
 $now=new DateTimeImmutable('today',new DateTimeZone('America/Montevideo'));$links=[];
 $firstWorkspace=$workspace;
 foreach($examples as $exampleIndex => [$name,$format,$discipline,$category,$teams,$n,$finished,$label,$order]) {
  if ($exampleIndex===3) {
   $workspace=(new app\Models\Workspace())->create($owner,'Centro de Estrategia · Demo','Ligas, suizos y copas de juegos mentales y videojuegos, con resultados ficticios.');
   foreach ($people as $person) {
    $q=$db->prepare('INSERT INTO workspace_members(workspace_id,user_id,role_id,assigned_at) VALUES(?,?,?,UTC_TIMESTAMP())');
    $q->execute([$workspace,$person,app\Models\Role::findId(app\Models\Role::PARTICIPANT)]);
   }
  }
  $start=$now->modify($finished?'-14 days':'-2 days')->setTime(16,0);
  $end=$now->modify($finished?'-1 day':'+30 days')->setTime(22,0);
  $utc=static fn($d)=>$d->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
  $id=$model->create(['workspace_id'=>$workspace,'organizer_id'=>null,'name'=>$name,'description'=>'Demostración: nombres, marcadores y resultados ficticios. Puedes gestionar este torneo para probar Nuva.','discipline_category'=>$category,'discipline'=>$discipline,'has_team'=>$teams,'max_participants'=>16,'published'=>1,'start_date'=>$utc($start),'end_date'=>$utc($end),'status'=>'Planificado','module_id'=>$modules[$format],'league_legs'=>1,'swiss_rounds'=>$format==='swiss'?3:null,'third_place'=>$format==='elimination'?1:0,'team_size'=>$teams?2:null,'points_win'=>$format==='swiss'?1:3,'points_draw'=>$format==='swiss'?0.5:1,'points_loss'=>0,'allow_draws'=>$format==='swiss'?1:0,'score_order'=>$order,'score_label'=>$label],$owner);
  $t=$model->find($id);
  for($i=0;$i<$n;$i++) {
   if($teams) {
    $teamId=$model->createTeam($t,$owner,['Naranjas','Azules','Verdes','Violetas'][$i]);
    foreach(array_slice($people,$i*2,2) as $person) $model->enroll($model->find($id),$owner,$person,$teamId);
   } else $model->enroll($model->find($id),$owner,$people[$i],null);
  }
  $engine->operate($id,$owner,'generate',[]);
  $roundCount=$format==='swiss'?3:count($engine->snapshot($model->find($id))['rounds']);
  for($r=1;$r<=($finished?$roundCount:1);$r++) {
   if($format==='swiss' && $r>1) $engine->operate($id,$owner,'next_round',[]);
   foreach($engine->snapshot($model->find($id))['games'] as $g) if((int)$g['round_no']===$r && $g['status']!=='Libre') {
    $date=$start->modify('+'.($r-1).' days')->modify('+'.((int)$g['position']-1).' hours');
    $engine->operate($id,$owner,'schedule',['match_id'=>$g['id'],'revision'=>(string)$g['revision'],'scheduled_at'=>$date->format('Y-m-d\TH:i')]);
    $draw=$format==='swiss' && ((int)$g['position']+$r)%3===0;
    $side=((int)$g['position']+$r)%2?1:2;
    if($format==='swiss' && !$teams) {$a=$draw?'0.5':($side===1?'1':'0');$b=$draw?'0.5':($side===2?'1':'0');}
    elseif($order==='lower') {$a=$side===1?'11.24':'12.48';$b=$side===2?'11.16':'12.30';}
    else {$a=$draw?'2':($side===1?'3':'1');$b=$draw?'2':($side===2?'3':'1');}
    $engine->operate($id,$owner,'save_result',['match_id'=>$g['id'],'revision'=>(string)((int)$g['revision']+1),'score_1'=>$a,'score_2'=>$b,'outcome'=>$draw?'draw':(string)$side,'notes'=>'Resultado ficticio de demostración.']);
   }
   $engine->operate($id,$owner,'close_round',['round_no'=>$r]);
  }
  if(!$finished) {
   if($format==='swiss') $engine->operate($id,$owner,'next_round',[]);
   foreach($engine->snapshot($model->find($id))['games'] as $g) if($g['status']==='Pendiente') {
    $date=$now->modify('+'.((int)$g['round_no']-1).' days')->setTime(17+(int)$g['position']%4,0);
    $engine->operate($id,$owner,'schedule',['match_id'=>$g['id'],'revision'=>(string)$g['revision'],'scheduled_at'=>$date->format('Y-m-d\TH:i')]);
   }
  }
  $links[]='- '.$name.': http://localhost/public/index.php?page=tournament&id='.$id;
 }
 $q=$db->prepare('INSERT INTO extra_competition_demo_registry(owner_id,workspace_id,created_at) VALUES(?,?,UTC_TIMESTAMP())');$q->execute([$owner,$firstWorkspace]);
 $db->commit();echo "Dos entornos de demostración creados (#$firstWorkspace y #$workspace).\n".implode("\n",$links)."\n";
} catch(Throwable $e) {if($db->inTransaction())$db->rollBack();fwrite(STDERR,'No se cargaron ejemplos: '.$e->getMessage()."\n");exit(1);}
