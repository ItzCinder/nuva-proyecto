<?php
namespace App\Controllers;

require_once __DIR__ . '/../Models/Competition.php';
use app\Models\Competition;
use app\Models\Role;
use app\Models\Tournament;
use app\Models\Workspace;

class TournamentController
{
    private Tournament $model;
    private Workspace $workspaces;
    private ?int $userId;

    public function handle(string $page, callable $render): void
    {
        $this->userId = !empty($_SESSION['authenticated']) && ctype_digit((string)($_SESSION['user_id'] ?? ''))
            ? (int)$_SESSION['user_id'] : null;
        try {
            $this->model = new Tournament();
            $this->workspaces = new Workspace();
            if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            if (in_array($page, ['competitions','competitions-all','my-tournaments'], true)) {
                if ($page === 'my-tournaments' && !$this->userId) { $render('login'); return; }
                $items = $page === 'my-tournaments' ? $this->model->myList($this->userId) : $this->model->publicList();
                $render('public/competitions', [
                    'collectionType' => $page === 'my-tournaments' ? 'tournaments' : 'competitions',
                    'collectionPage' => $page, 'collectionAll' => $page !== 'competitions',
                    'collectionTitle' => $page === 'my-tournaments' ? 'Mis torneos' : 'Competiciones',
                    'tournamentItems' => $items,
                ]);
                return;
            }
            $data = ['error'=>'', 'flash'=>$_SESSION['tournament_flash'] ?? '', 'title'=>'Torneo'];
            unset($_SESSION['tournament_flash']);
            if ($page === 'tournament-create') {
                if (!$this->userId) { $render('login'); return; }
                $workspaceId = $this->id($_GET, 'workspace_id');
                $workspace = $this->workspaces->findForUser($workspaceId, $this->userId);
                if (!$workspace || !in_array($workspace['role_name'], [Role::OWNER, Role::ADMIN], true)) {
                    $this->error($render, 403, 'No puedes crear torneos en este entorno.'); return;
                }
                $data += ['mode'=>'create','workspace'=>$workspace,'tournament'=>null];
            } else {
                $id = $this->id($_GET, 'id');
                $t = $this->model->find($id);
                if (!$t) { $this->error($render, 404, 'No encontramos ese torneo.'); return; }
                $workspace = $this->userId ? $this->workspaces->findForUser((int)$t['workspace_id'], $this->userId) : null;
                $canManage = $this->model->canManage($t, $this->userId, $workspace);
                if ($page === 'tournament-edit' ? !$canManage : !$this->model->canSee($t, $this->userId, $workspace)) {
                    $this->error($render, 403, 'Este torneo no está disponible para tu cuenta.'); return;
                }
                $data += ['mode'=>$page === 'tournament-edit' ? 'edit' : 'detail', 'workspace'=>$workspace,
                    'tournament'=>$t,'canManage'=>$canManage, 'participants'=>$this->model->participants($id),
                    'teams'=>$this->model->teams($id)];
            }
            if ($data['mode'] !== 'detail') {
                $data['modules'] = $this->model->enabledModules((int)$workspace['id']);
                $data['organizers'] = $this->model->organizers((int)$workspace['id']);
            } elseif ($data['canManage']) {
                $data['eligibleMembers'] = $this->model->eligibleMembers((int)$t['workspace_id']);
            }
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (!$this->userId || !hash_equals($_SESSION['csrf_token'], $this->text($_POST,'csrf_token'))) {
                    $this->error($render, 403, 'El formulario venció. Recarga la página.'); return;
                }
                $db = \Database::getConnection();
                try {
                    $db->beginTransaction();
                    if ($data['mode'] === 'detail') {
                        if (!$data['canManage']) throw new \DomainException('No puedes inscribir miembros.');
                        $action=$this->text($_POST,'action');
                        foreach ($_POST as $value) if (!is_string($value)) throw new \InvalidArgumentException('Formulario inválido.');
                        if (in_array($action,['team','enroll'],true)) {
                            $lock=$db->prepare('SELECT id FROM tournament WHERE id=? FOR UPDATE'); $lock->execute([$t['id']]);
                            $t=$this->model->find((int)$t['id']);
                            $role=$this->workspaces->findForUser((int)$t['workspace_id'],$this->userId);
                            if (!$this->model->canManage($t,$this->userId,$role)) throw new \DomainException('No tienes permisos para inscribir.');
                            $this->manageMembers($t);
                        }
                        else (new Competition())->operate((int)$t['id'],$this->userId,$action,$_POST);
                        $target = '?page=tournament&id=' . $t['id'] . '&section=' . (in_array($action,['team','enroll'],true)?'participants':'matches');
                    } else {
                        if ($data['mode']==='edit') {
                            $lock=$db->prepare('SELECT id FROM tournament WHERE id=? FOR UPDATE'); $lock->execute([$t['id']]);
                            $t=$this->model->find((int)$t['id']);
                            $workspace=$this->workspaces->findForUser((int)$t['workspace_id'],$this->userId);
                            if (!$this->model->canManage($t,$this->userId,$workspace)) throw new \DomainException('No tienes permisos para editar este torneo.');
                        }
                        $values = $this->values($workspace, $data['modules'], $data['organizers'], $t ?? null);
                        if ($data['mode'] === 'create') {
                            $id = $this->model->create($values, $this->userId);
                        } else {
                            $this->model->update($t, $values, $this->userId);
                            $id = (int)$t['id'];
                        }
                        $target = '?page=tournament&id=' . $id;
                    }
                    $db->commit();
                    $_SESSION['tournament_flash'] = 'Los cambios del torneo se guardaron correctamente.';
                    header('Location: ' . $target, true, 303);
                    return;
                } catch (\InvalidArgumentException $e) {
                    if ($db->inTransaction()) $db->rollBack();
                    http_response_code(422); $data['error'] = $e->getMessage();
                } catch (\DomainException $e) {
                    if ($db->inTransaction()) $db->rollBack();
                    $this->error($render, 403, $e->getMessage()); return;
                } catch (\Throwable $e) {
                    if ($db->inTransaction()) $db->rollBack();
                    throw $e;
                }
            }
            if (!empty($data['tournament'])) {
                $data['competition']=(new Competition())->snapshot($data['tournament']);
                $data['started']=$this->model->hasMatches((int)$data['tournament']['id']);
            }
            $data['title'] = $data['mode'] === 'create' ? 'Crear torneo' : $data['tournament']['name'];
            $render('tournaments/page', $data);
        } catch (\Throwable $e) {
            error_log('Torneos: ' . $e->getMessage());
            $this->error($render, 503, 'No se pudieron cargar los torneos. Comprueba la conexión y la migración de la base de datos.');
        }
    }

    private function values(array $workspace, array $modules, array $organizers, ?array $old): array
    {
        if ($old && $this->model->hasMatches((int)$old['id'])) {
            foreach (['has_team','max_participants','league_legs','swiss_rounds','third_place','team_size','points_win','points_draw','points_loss','allow_draws','score_order','score_label','status'] as $key) $_POST[$key]=(string)$old[$key];
            $_POST['format']=$old['format_code'];
            foreach (['start_date','end_date'] as $key) $_POST[$key]=(new \DateTimeImmutable($old[$key],new \DateTimeZone('UTC')))->setTimezone(new \DateTimeZone('America/Montevideo'))->format('Y-m-d\TH:i');
        }
        $name=$this->text($_POST,'name'); $description=$this->text($_POST,'description');
        if ($name==='' || mb_strlen($name)>120 || preg_match('/[\x00-\x1F\x7F]/u',$name) || mb_strlen($description)>500) throw new \InvalidArgumentException('Revisa el nombre (hasta 120 caracteres) y la descripción (hasta 500).');
        $selected=$this->text($_POST,'discipline');
        $category='otros'; $discipline=$this->text($_POST,'custom_discipline');
        if ($selected!=='other') {
            foreach (Tournament::DISCIPLINES as $key=>$names) if (in_array($selected,$names,true)) { $category=$key; $discipline=$selected; break; }
        }
        if ($discipline==='' || mb_strlen($discipline)>80 || preg_match('/[\x00-\x1F\x7F]/u',$discipline)) throw new \InvalidArgumentException('Selecciona una disciplina o escribe una de hasta 80 caracteres en Otros.');
        $code=$this->text($_POST,'format'); $module=null;
        foreach ($modules as $m) if ($m['code']===$code) $module=$m;
        if (!$module && $old && $old['format_code']===$code) $module=['id'=>$old['module_id'] ?? $this->moduleId($code), 'code'=>$code];
        if (!$module) throw new \InvalidArgumentException('Selecciona un formato habilitado en el entorno.');
        $hasTeam=$this->text($_POST,'has_team')==='1' ? 1 : 0;
        $max=(int)$this->text($_POST,'max_participants');
        if ($max < ($code==='swiss' ? 4 : 2) || $max>256) throw new \InvalidArgumentException('El cupo debe estar entre ' . ($code==='swiss' ? 4 : 2) . ' y 256.');
        $teamSize=$hasTeam ? (int)$this->text($_POST,'team_size') : null;
        if ($hasTeam && ($teamSize<2 || $teamSize>30)) throw new \InvalidArgumentException('Cada equipo debe admitir entre 2 y 30 integrantes.');
        $legs=$code==='league' ? (int)$this->text($_POST,'league_legs') : 1;
        if (!in_array($legs,[1,2],true)) throw new \InvalidArgumentException('La liga puede tener una o dos vueltas.');
        $rounds=$code==='swiss' ? (int)$this->text($_POST,'swiss_rounds') : null;
        if ($code==='swiss' && ($rounds<2 || $rounds>12)) throw new \InvalidArgumentException('El sistema suizo requiere entre 2 y 12 rondas.');
        $third=$code==='elimination' && $this->text($_POST,'third_place')==='1' ? 1 : 0;
        $start=$this->date($this->text($_POST,'start_date'));
        $end=$this->date($this->text($_POST,'end_date'));
        if ($start >= $end) throw new \InvalidArgumentException('La fecha final debe ser posterior al comienzo.');
        $status=$workspace['role_name'] === Role::ORGANIZER && $old ? $old['status'] : $this->text($_POST,'status');
        if (!in_array($status,Tournament::STATUS,true)) throw new \InvalidArgumentException('Selecciona un estado válido.');
        $organizerId=$this->id($_POST,'organizer_id') ?: null;
        if ($organizerId && !in_array($organizerId,array_map('intval',array_column($organizers,'id')),true))
            throw new \InvalidArgumentException('El organizador debe pertenecer al entorno con ese rol.');
        $published=$this->text($_POST,'published')==='1' ? 1 : 0;
        if ($old) {
            $participants=(int)$old['member_count']+(int)$old['team_count'];
            if ($participants && ((int)$old['has_team']!==$hasTeam || $old['format_code']!==$code))
                throw new \InvalidArgumentException('No puedes cambiar el formato ni la modalidad con inscripciones existentes.');
            $currentCount=$hasTeam ? (int)$old['team_count'] : (int)$old['member_count'];
            if ($max<$currentCount) throw new \InvalidArgumentException('El cupo no puede quedar por debajo de las inscripciones actuales.');
            if ($hasTeam && $teamSize < $this->largestTeam((int)$old['id'])) throw new \InvalidArgumentException('El tamaño de equipo es menor que una plantilla actual.');
        }
        if ($old && $workspace['role_name'] === Role::ORGANIZER) {
            $organizerId = (int)$old['organizer_id']; $published = (int)$old['published']; $status = $old['status'];
        }
        $scoring=[];
        foreach (['points_win'=>3,'points_draw'=>1,'points_loss'=>0] as $key=>$default) {
            $value=$this->text($_POST,$key);
            if (!preg_match('/^\d{1,4}(?:\.\d{1,2})?$/',$value)) throw new \InvalidArgumentException('Revisa los puntos: de 0 a 9999,99, con hasta dos decimales.');
            $scoring[$key]=(float)$value;
        }
        if ($scoring['points_win']<=$scoring['points_loss'] || $scoring['points_draw']<$scoring['points_loss'] || $scoring['points_draw']>$scoring['points_win']) throw new \InvalidArgumentException('La victoria debe valer más que la derrota y el empate debe quedar entre ambas.');
        $scoring['allow_draws']=$code!=='elimination' && $this->text($_POST,'allow_draws')==='1' ? 1 : 0;
        $scoring['score_order']=$this->text($_POST,'score_order');
        $scoring['score_label']=$this->text($_POST,'score_label');
        if (!in_array($scoring['score_order'],['higher','lower'],true) || $scoring['score_label']==='' || mb_strlen($scoring['score_label'])>40) throw new \InvalidArgumentException('Revisa el nombre y el criterio del marcador.');
        $values = $scoring + compact('name','description','discipline') + [
            'workspace_id'=>(int)$workspace['id'], 'discipline_category'=>$category, 'has_team'=>$hasTeam,
            'max_participants'=>$max,'module_id'=>(int)$module['id'], 'league_legs'=>$legs,'swiss_rounds'=>$rounds,
            'third_place'=>$third,'team_size'=>$teamSize,'start_date'=>$start,'end_date'=>$end,'status'=>$status,
            'organizer_id'=>$organizerId,'published'=>$published,
        ];
        if ($old && $this->model->hasMatches((int)$old['id'])) {
            foreach (['module_id','has_team','max_participants','league_legs','swiss_rounds','third_place','team_size','points_win','points_draw','points_loss','allow_draws','score_order','score_label','status','start_date','end_date'] as $key) $values[$key]=$old[$key];
        }
        return $values;
    }

    private function moduleId(string $code): int
    {
        $stmt=\Database::getConnection()->prepare('SELECT id FROM modules WHERE code=?'); $stmt->execute([$code]);
        return (int)$stmt->fetchColumn();
    }

    private function largestTeam(int $id): int
    {
        $stmt=\Database::getConnection()->prepare('SELECT COUNT(*) AS n FROM tournament_members WHERE tournament_id=? AND team_id IS NOT NULL GROUP BY team_id ORDER BY n DESC LIMIT 1');
        $stmt->execute([$id]); return (int)$stmt->fetchColumn();
    }

    private function manageMembers(array $t): void
    {
        $action=$this->text($_POST,'action');
        if ($action==='team') {
            $name=$this->text($_POST,'team_name');
            if ($name==='' || mb_strlen($name)>100 || preg_match('/[\x00-\x1F\x7F]/u',$name)) throw new \InvalidArgumentException('Escribe un nombre de equipo de hasta 100 caracteres.');
            $this->model->createTeam($t,$this->userId,$name); return;
        }
        if ($action==='enroll') {
            $this->model->enroll($t,$this->userId,$this->id($_POST,'member_id'),$t['has_team'] ? $this->id($_POST,'team_id') : null);
            return;
        }
        throw new \InvalidArgumentException('Acción desconocida.');
    }

    private function date(string $value): string
    {
        $tz=new \DateTimeZone('America/Montevideo');
        $date=\DateTimeImmutable::createFromFormat('!Y-m-d\\TH:i',$value,$tz);
        if (!$date || $date->format('Y-m-d\\TH:i')!==$value) throw new \InvalidArgumentException('Introduce fechas y horas válidas.');
        return $date->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }
    private function id(array $input,string $key): int
    {
        $v=$this->text($input,$key);
        return (int)(filter_var($v,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]) ?: 0);
    }
    private function text(array $input,string $key): string { return is_string($input[$key] ?? null) ? trim($input[$key]) : ''; }
    private function error(callable $render,int $status,string $message): void
    {
        http_response_code($status);
        $render('tournaments/page',['mode'=>'error','title'=>'Torneos','error'=>$message,'flash'=>'']);
    }
}
