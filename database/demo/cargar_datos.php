<?php
// Ejemplo: /opt/lampp/bin/php database/demo/cargar_datos.php --owner-id=1
// Agrega datos de prueba al usuario indicado. No borra datos existentes y se puede ejecutar de nuevo.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$ownerId = 0;
foreach ($argv as $arg) if (preg_match('/^--owner-id=([1-9][0-9]*)$/', $arg, $match)) $ownerId = (int)$match[1];
if (!$ownerId) { fwrite(STDERR, "Uso: php database/demo/cargar_datos.php --owner-id=ID_DE_TU_CUENTA\n"); exit(1); }
$env = parse_ini_file(dirname(__DIR__,2).'/.env', false, INI_SCANNER_RAW);
if (!$env) { fwrite(STDERR,"Falta el archivo .env\n"); exit(1); }
foreach ($env as $key=>$value) $_ENV[$key]=$value;
require dirname(__DIR__,2).'/app/Services/Database.php';
require dirname(__DIR__,2).'/app/Models/Model.php';
require dirname(__DIR__,2).'/app/Models/Role.php';
require dirname(__DIR__,2).'/app/Models/Workspace.php';
require dirname(__DIR__,2).'/app/Models/Tournament.php';
$db=Database::getConnection();
$db->exec("SET time_zone = '+00:00'");
$stmt=$db->prepare('SELECT id FROM users WHERE id=?'); $stmt->execute([$ownerId]);
if (!$stmt->fetchColumn()) { fwrite(STDERR,"Ese usuario no existe en la base.\n"); exit(1); }
$db->exec('CREATE TABLE IF NOT EXISTS demo_seed_registry (
  owner_id INT NOT NULL PRIMARY KEY, created_at DATETIME NOT NULL,
  CONSTRAINT fk_demo_seed_owner FOREIGN KEY (owner_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
$stmt=$db->prepare('SELECT owner_id FROM demo_seed_registry WHERE owner_id=?'); $stmt->execute([$ownerId]);
if ($stmt->fetchColumn()) { echo "Los datos de demostración ya están cargados para esta cuenta.\n"; exit; }
$people=['Sofía Méndez','Leo Fernández','Valentina Suárez','Tomás Pereira','Lucía Silva','Mateo González','Florencia Díaz','Nicolás Rodríguez'];
$spaces=[
  ['name'=>'Club del Sur · Demo','description'=>'Ligas y copas para la comunidad deportiva.','tournaments'=>[
    ['name'=>'Liga de futsal de primavera','discipline'=>'Futsal','category'=>'tradicional','format'=>'league','legs'=>2,'teams'=>1,'status'=>'En curso','start'=>'2026-10-01 18:00:00','end'=>'2026-12-15 22:00:00'],
    ['name'=>'Copa de pádel del Sur','discipline'=>'Pádel','category'=>'tradicional','format'=>'elimination','teams'=>1,'third'=>1,'status'=>'Planificado','start'=>'2026-11-01 10:00:00','end'=>'2026-11-29 19:00:00'],
    ['name'=>'Circuito de escalada','discipline'=>'Escalada','category'=>'otros','format'=>'league','legs'=>1,'teams'=>0,'status'=>'Planificado','start'=>'2026-11-08 14:00:00','end'=>'2026-12-20 19:00:00'],
  ]],
  ['name'=>'Club de Estrategia · Demo','description'=>'Encuentros de ajedrez y juegos de mesa.','tournaments'=>[
    ['name'=>'Abierto de ajedrez de octubre','discipline'=>'Ajedrez','category'=>'mental','format'=>'swiss','rounds'=>3,'teams'=>0,'status'=>'En curso','start'=>'2026-10-05 16:00:00','end'=>'2026-10-31 20:00:00'],
    ['name'=>'Copa de damas','discipline'=>'Damas','category'=>'mental','format'=>'elimination','teams'=>0,'status'=>'Planificado','start'=>'2026-11-04 17:00:00','end'=>'2026-11-18 20:00:00'],
    ['name'=>'Liga de Go','discipline'=>'Go','category'=>'mental','format'=>'league','teams'=>0,'status'=>'Finalizado','start'=>'2026-08-01 16:00:00','end'=>'2026-09-30 20:00:00'],
  ]],
  ['name'=>'Arena Digital · Demo','description'=>'Competiciones de eSports entre equipos y jugadores.','tournaments'=>[
    ['name'=>'Copa Valorant Arena','discipline'=>'Valorant','category'=>'esports','format'=>'elimination','teams'=>1,'status'=>'En curso','start'=>'2026-10-12 19:00:00','end'=>'2026-11-09 22:00:00'],
    ['name'=>'Suizo de Counter-Strike 2','discipline'=>'Counter-Strike 2','category'=>'esports','format'=>'swiss','rounds'=>3,'teams'=>1,'status'=>'Planificado','start'=>'2026-11-02 19:00:00','end'=>'2026-11-30 22:00:00'],
    ['name'=>'Liga EA Sports FC','discipline'=>'EA Sports FC','category'=>'esports','format'=>'league','teams'=>0,'status'=>'Planificado','start'=>'2026-10-25 18:00:00','end'=>'2026-12-01 22:00:00'],
  ]],
];
try {
  $db->beginTransaction();
  $ids=[];
  $insert=$db->prepare('INSERT INTO users (google_id,email,name,picture_url,created_at) VALUES (?,?,?,NULL,UTC_TIMESTAMP())');
  foreach ($people as $i=>$person) {
    $insert->execute(['nuva-demo:'.$ownerId.':'.$i,'nuva-demo-'.$ownerId.'-'.$i.'@example.invalid',$person]);
    $ids[]=(int)$db->lastInsertId();
  }
  $workspaceModel=new \app\Models\Workspace();
  $tournamentModel=new \app\Models\Tournament();
  $roleOrganizer=\app\Models\Role::findId(\app\Models\Role::ORGANIZER);
  $roleParticipant=\app\Models\Role::findId(\app\Models\Role::PARTICIPANT);
  $memberInsert=$db->prepare('INSERT INTO workspace_members (workspace_id,user_id,role_id,assigned_at) VALUES (?,?,?,UTC_TIMESTAMP())');
  $moduleIds=[];foreach ($db->query('SELECT id,code FROM modules') as $m) $moduleIds[$m['code']]=(int)$m['id'];
  $spaceCount=$tournamentCount=0;
  foreach ($spaces as $space) {
    $workspaceId=$workspaceModel->create($ownerId,$space['name'],$space['description']); $spaceCount++;
    foreach ($ids as $i=>$id) $memberInsert->execute([$workspaceId,$id,$i<2?$roleOrganizer:$roleParticipant]);
    foreach ($space['tournaments'] as $item) {
      $values=[
        'workspace_id'=>$workspaceId, 'organizer_id'=>$ids[$tournamentCount%2], 'name'=>$item['name'],
        'description'=>'Torneo de demostración para explorar Nuva.', 'discipline_category'=>$item['category'],
        'discipline'=>$item['discipline'], 'has_team'=>(int)$item['teams'], 'max_participants'=>16,
        'published'=>1, 'start_date'=>$item['start'], 'end_date'=>$item['end'], 'status'=>'Planificado',
        'module_id'=>$moduleIds[$item['format']], 'league_legs'=>$item['legs'] ?? 1,
        'swiss_rounds'=>$item['rounds'] ?? null, 'third_place'=>$item['third'] ?? 0,
        'team_size'=>$item['teams'] ? 5 : null,
        'points_win'=>$item['format']==='swiss'?1:3, 'points_draw'=>$item['format']==='swiss'?0.5:1, 'points_loss'=>0,
        'allow_draws'=>$item['format']==='elimination'?0:1, 'score_order'=>'higher', 'score_label'=>'Marcador',
      ];
      $tid=$tournamentModel->create($values,$ownerId); $tournamentCount++;
      $t=$tournamentModel->find($tid);
      if ($item['teams']) {
        foreach (['Naranja','Azul','Verde','Violeta'] as $i=>$color) {
          $teamId=$tournamentModel->createTeam($t,$ownerId,'Equipo '.$color);
          foreach (array_slice($ids,$i*2,2) as $personId) $tournamentModel->enroll($tournamentModel->find($tid),$ownerId,$personId,$teamId);
        }
      } else {
        foreach (array_slice($ids,2,4) as $id) $tournamentModel->enroll($tournamentModel->find($tid),$ownerId,$id,null);
      }
    }
  }
  $stmt=$db->prepare('INSERT INTO demo_seed_registry (owner_id,created_at) VALUES (?,UTC_TIMESTAMP())'); $stmt->execute([$ownerId]);
  $db->commit();
  echo "Cargados $spaceCount entornos, $tournamentCount torneos y personas ficticias para la cuenta #$ownerId.\n";
} catch (Throwable $e) {
  if ($db->inTransaction()) $db->rollBack();
  fwrite(STDERR,"No se cargaron los datos: ".$e->getMessage()."\n"); exit(1);
}
