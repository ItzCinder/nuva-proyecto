<?php

namespace App\Controllers;

use app\Models\Role;
use app\Models\User;
use app\Models\Workspace;
use App\Modules\Administracion\Services\EnvironmentPicture;

class WorkspaceController
{
    private Workspace $model;
    private int $userId;
    private string $newPicture = '';
    private string $oldPicture = '';

    public function handle(string $page, callable $render): void
    {
        $code = Workspace::normalizeInvitationCode($this->text($_GET, 'code'));
        if ($page === 'environment-invitation' && $code !== '') {
            $_SESSION['pending_invitation'] = $code;
        }

        if (empty($_SESSION['authenticated']) || empty($_SESSION['user_id'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                http_response_code(401);
            }

            $render('login');
            return;
        }

        try {
            $this->userId = (int) $_SESSION['user_id'];
            if (User::findById($this->userId) === null) {
                unset($_SESSION['authenticated'], $_SESSION['user_id']);
                $render('login');
                return;
            }

            $this->model = new Workspace();
            if (empty($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }

            $data = ['error' => '', 'title' => 'Mis entornos', 'screen' => 'list'];
            $data['flash'] = $_SESSION['environment_flash'] ?? '';
            unset($_SESSION['environment_flash']);

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $csrf = $this->text($_POST, 'csrf_token');
                if (!hash_equals($_SESSION['csrf_token'], $csrf)) {
                    nuva_security_event('csrf_rejected');
                    $this->error($render, 403, 'El formulario venció. Recarga la página e inténtalo de nuevo.');
                    return;
                }

                $database = \Database::getConnection();
                try {
                    $database->beginTransaction();
                    $target = $this->write($page, $code);
                    $database->commit();
                    if ($this->oldPicture !== '') {
                        EnvironmentPicture::remove($this->oldPicture);
                    }
                } catch (\InvalidArgumentException $exception) {
                    nuva_security_event('invalid_form');
                    $database->rollBack();
                    EnvironmentPicture::remove($this->newPicture);
                    http_response_code(422);
                    $data['error'] = $exception->getMessage();
                } catch (\DomainException $exception) {
                    nuva_security_event('permission_denied');
                    $database->rollBack();
                    EnvironmentPicture::remove($this->newPicture);
                    $this->error($render, 403, $exception->getMessage());
                    return;
                } catch (\Throwable $exception) {
                    EnvironmentPicture::remove($this->newPicture);
                    if ($database->inTransaction()) {
                        $database->rollBack();
                    }

                    throw $exception;
                }

                if (isset($target)) {
                    $_SESSION['environment_flash'] = 'Los cambios se guardaron correctamente.';
                    header('Location: ' . $target, true, 303);
                    return;
                }
            }

            if ($page === 'environment-create') {
                $data['screen'] = 'create';
                $data['title'] = 'Crear entorno';
            } elseif ($page === 'environment-invitation') {
                $data['screen'] = 'invitation';
                $data['title'] = 'Invitación a un entorno';
                $data['code'] = $code;
                $data['invitation'] = $this->model->findInvitation($code);
                $data['alreadyMember'] = $data['invitation'] && $this->model->findForUser((int) $data['invitation']['id'], $this->userId);
                if (!$data['invitation'] && $data['error'] === '') {
                    http_response_code(404);
                }
            } elseif ($page === 'environment') {
                $id = $this->id($_GET, 'id');
                $workspace = $this->model->findForUser($id, $this->userId);
                if (!$workspace || !Role::can($workspace['role_name'], 'environment.view')) {
                    $this->error($render, 403, 'Este entorno no está disponible para tu cuenta.');
                    return;
                }

                $sections = [
                    'overview' => ['Resumen', 'environment.view'],

                    'tournaments' => ['Torneos', 'environment.view'],

                    'members' => ['Miembros y roles', 'members.manage'],

                    'invitations' => ['Invitaciones', 'invitations.manage'],

                    'settings' => ['Información del entorno', 'environment.edit'],

                    'modules' => ['Módulos', 'modules.manage'],

                    'audit' => ['Auditoría', 'audit.view'],
                ];
                $section = $this->text($_GET, 'section') ?: 'overview';
                if (!isset($sections[$section]) || !Role::can($workspace['role_name'], $sections[$section][1])) {
                    $this->error($render, 403, 'Tu rol no permite acceder a esta sección.');
                    return;
                }

                $data['screen'] = 'detail';
                $data['title'] = $workspace['name'];
                $data['workspace'] = $workspace;
                $data['section'] = $section;
                $data['sections'] = $sections;
                $data['userId'] = $this->userId;
                if ($section === 'members') {
                    $data['members'] = $this->model->members($id);
                } elseif ($section === 'modules') {
                    $data['modules'] = $this->model->modules($id);
                } elseif ($section === 'invitations') {
                    $data['invitations'] = $this->model->invitations($id);
                    $data['newCode'] = $_SESSION['new_invitation'][$id] ?? '';
                    unset($_SESSION['new_invitation'][$id]);
                } elseif ($section === 'audit') {
                    $data['logs'] = $this->model->audit($id);
                } elseif ($section === 'tournaments') {
                    $data['tournaments'] = (new \app\Models\Tournament())->workspaceList($workspace, $this->userId);
                    $data['tournamentCards'] = \App\Modules\ConsultaPublica\Services\TournamentCollection::items($data['tournaments']);
                }
            } else {
                $data['workspaces'] = $this->model->allForUser($this->userId);
            }

            $render('environments/page', $data);
        } catch (\Throwable $exception) {
            nuva_log_error('Entornos', $exception);
            $this->error($render, 503, 'No se pudieron cargar los entornos. Comprueba la conexión y la instalación de la base de datos.');
        }
    }

    private function write(string $page, string $code): string
    {
        $action = $this->text($_POST, 'action');
        if ($page === 'environment-create' && $action === 'create') {
            [$name, $description] = $this->information();
            $picture = $this->picture('');
            $id = $this->model->create($this->userId, $name, $description, $picture);
            return '?page=environment&id=' . $id;
        }

        if ($page === 'environment-invitation' && $action === 'join') {
            if ($this->text($_POST, 'accept') !== '1') {
                throw new \InvalidArgumentException('Debes aceptar tu incorporación al entorno.');
            }

            $id = $this->model->join($code, $this->userId);
            unset($_SESSION['pending_invitation']);
            return '?page=environment&id=' . $id;
        }

        if ($page !== 'environment') {
            throw new \InvalidArgumentException('La acción no es válida.');
        }

        $id = $this->id($_GET, 'id');
        $workspace = $this->model->findForUser($id, $this->userId, true);
        if (!$workspace) {
            throw new \DomainException('Ya no perteneces a este entorno.');
        }

        $role = $workspace['role_name'];
        $permissions = [
            'update' => 'environment.edit',
            'role' => 'members.manage',
            'remove' => 'members.manage',
            'leave' => 'environment.leave',
            'modules' => 'modules.manage',
            'invite' => 'invitations.manage',
            'revoke' => 'invitations.manage',
        ];
        if (!isset($permissions[$action]) || !Role::can($role, $permissions[$action])) {
            throw new \DomainException('Tu rol no permite realizar esta acción.');
        }

        $section = 'overview';
        switch ($action) {
            case 'update':
                [$name, $description] = $this->information();
                $picture = $this->picture($workspace['picture']);
                $this->model->updateInfo($workspace, $this->userId, $name, $description, $picture);
                $section = 'settings';
                break;
            case 'role':
            case 'remove':
            case 'leave':
                $memberId = $action === 'leave' ? $this->userId : $this->id($_POST, 'member_id');
                $member = $this->model->member($id, $memberId);
                if (!$member) {
                    throw new \InvalidArgumentException('El usuario ya no pertenece a este entorno.');
                }

                if ($memberId === (int) $workspace['owner_id']) {
                    throw new \DomainException('El dueño no puede abandonar el entorno ni perder su rol.');
                }

                $newRole = $this->text($_POST, 'role');
                if ($action === 'role' && !in_array($newRole, [Role::ADMIN, Role::ORGANIZER, Role::PARTICIPANT], true)) {
                    throw new \InvalidArgumentException('Selecciona un rol válido.');
                }
                // Solo el dueño puede asignar o retirar administradores, incluso por POST directo.
                if ($action !== 'leave' && ($member['role_name'] === Role::ADMIN || $newRole === Role::ADMIN)
                    && !Role::can($role, 'admins.manage')) {
                    throw new \DomainException('Solo el dueño puede asignar o retirar administradores generales.');
                }

                if ($action === 'role') {
                    $this->model->changeRole($id, $this->userId, $member, $newRole);
                } else {
                    if ($this->text($_POST, 'confirm') !== '1') {
                        throw new \InvalidArgumentException('Confirma la baja del miembro antes de continuar.');
                    }

                    $this->model->removeMember($id, $this->userId, $member);
                    if ($memberId === $this->userId) {
                        return '?page=my-environments';
                    }
                }

                $section = 'members';
                if ($action === 'role' && $memberId === $this->userId && !Role::can($newRole, 'members.manage')) {
                    $section = 'overview';
                }
                break;
            case 'modules':
                $selected = $_POST['modules'] ?? [];
                if (!is_array($selected)) {
                    throw new \InvalidArgumentException('La selección de módulos no es válida.');
                }

                $validIds = array_map('strval', array_column($this->model->modules($id), 'id'));
                foreach ($selected as $value) {
                    if (!is_string($value) || !in_array($value, $validIds, true)) {
                        throw new \InvalidArgumentException('La selección contiene un módulo desconocido.');
                    }
                }

                $this->model->setModules($id, $this->userId, $selected);
                $section = 'modules';
                break;
            case 'invite':
                $_SESSION['new_invitation'][$id] = $this->model->createInvitation($id, $this->userId);
                $section = 'invitations';
                break;
            case 'revoke':
                $this->model->revokeInvitation($id, $this->userId, $this->id($_POST, 'invitation_id'));
                $section = 'invitations';
                break;
        }

        return '?page=environment&id=' . $id . '&section=' . $section;
    }

    private function picture(string $current): string
    {
        $file = $_FILES['picture'] ?? null;
        if (is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $this->newPicture = EnvironmentPicture::save($file);
            $this->oldPicture = $current;
            return $this->newPicture;
        }
        if ($this->text($_POST, 'remove_picture') === '1') {
            $this->oldPicture = $current;
            return '';
        }
        return $current;
    }

    private function information(): array
    {
        $name = $this->text($_POST, 'name');
        $description = $this->text($_POST, 'description');
        if ($name === '' || mb_strlen($name) > 120) {
            throw new \InvalidArgumentException('El nombre es obligatorio y debe tener hasta 120 caracteres.');
        }

        if (mb_strlen($description) > 500) {
            throw new \InvalidArgumentException('La descripción debe tener hasta 500 caracteres.');
        }

        return [$name, $description];
    }

    private function text(array $source, string $key): string
    {
        return is_string($source[$key] ?? null) ? trim($source[$key]) : '';
    }

    private function id(array $source, string $key): int
    {
        $value = $this->text($source, $key);
        return (int) (filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0);
    }

    private function error(callable $render, int $status, string $message): void
    {
        http_response_code($status);
        $render('environments/page', [
            'screen' => 'error',
            'title' => 'Entornos',
            'error' => $message,
            'flash' => ''
        ]);
    }
}
