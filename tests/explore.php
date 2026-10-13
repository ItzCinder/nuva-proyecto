<?php
// Ejecutar: /opt/lampp/bin/php tests/explore.php. Todos los cambios se revierten.
if(PHP_SAPI!=='cli') {http_response_code(404);exit;}
chdir(dirname(__DIR__));$_ENV=parse_ini_file('.env',false,INI_SCANNER_RAW);
foreach(['Services/Database','Models/Model','Models/Role','Models/Workspace','Models/Tournament'] as $file) require_once 'app/'.$file.'.php';
$db=Database::getConnection();$spaces=new app\Models\Workspace();$torneos=new app\Models\Tournament();
function verify($ok,$message){if(!$ok)throw new RuntimeException($message);}
$db->beginTransaction();
try {
 $owner=(int)$db->query('SELECT MIN(id) FROM users')->fetchColumn();
 verify($owner>0,'Se necesita una cuenta de prueba o demostración.');
 $id=$spaces->create($owner,'Entorno privado de prueba','No debe exponerse.');
 verify($spaces->publicInfo($id)===null,'Se expuso un entorno sin torneos publicados.');
 $module=(int)$db->query("SELECT id FROM modules WHERE code='league'")->fetchColumn();
 $tid=$torneos->create(['workspace_id'=>$id,'organizer_id'=>null,'name'=>'Torneo de prueba privado','description'=>'','discipline_category'=>'otros','discipline'=>'Genérica','has_team'=>0,'max_participants'=>4,'published'=>0,'start_date'=>'2026-01-01 00:00:00','end_date'=>'2027-01-01 00:00:00','status'=>'Planificado','module_id'=>$module,'league_legs'=>1,'swiss_rounds'=>null,'third_place'=>0,'team_size'=>null],$owner);
 verify($spaces->publicInfo($id)===null,'Se expuso un entorno con torneo privado.');
 $q=$db->prepare('UPDATE tournament SET published=1 WHERE id=?');$q->execute([$tid]);
 $public=$spaces->publicInfo($id);verify($public!==null,'No aparece el entorno con torneo publicado.');
 verify(array_keys($public)===['id','name','description'],'La ficha pública expone campos privados.');
 $found=array_values(array_filter($spaces->publicList(),static fn($w)=>(int)$w['id']===$id));
 verify(count($found)===1 && (int)$found[0]['public_count']===1,'El listado público cuenta incorrectamente.');
 $q=$db->prepare('UPDATE tournament SET published=0 WHERE id=?');$q->execute([$tid]);
 verify($spaces->publicInfo($id)===null,'El entorno sigue público tras retirar su único torneo publicado.');
 echo "OK visibilidad de entornos y campos públicos; cambios revertidos.\n";
} finally {if($db->inTransaction())$db->rollBack();}
