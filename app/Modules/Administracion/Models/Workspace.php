<?php

namespace app\Models;

class Workspace extends Model
{
    public function __construct()
    {
        parent::__construct();
        // Los nuevos registros del entorno utilizan UTC; las vistas muestran Uruguay.
        $this->database->exec("SET time_zone = '+00:00'");
    }

    public function allForUser(int $userId): array
    {
        $statement = $this->database->prepare(
            'SELECT w.*, r.name AS role_name,
             (SELECT COUNT(*) FROM workspace_members wm WHERE wm.workspace_id = w.id) AS member_count
             FROM workspaces w
             LEFT JOIN workspace_members m ON m.workspace_id = w.id AND m.user_id = ?
             LEFT JOIN roles r ON r.id = m.role_id
             WHERE w.owner_id = ? OR (m.user_id IS NOT NULL AND r.name <> ?) ORDER BY w.id DESC'
        );
        $statement->execute([$userId, $userId, Role::OWNER]);
        $rows = $statement->fetchAll();
        foreach ($rows as &$row) {
            if ((int) $row['owner_id'] === $userId) {
                $row['role_name'] = Role::OWNER;
            }
        }

        return $rows;
    }

    public function publicList(): array
    {
        return $this->database->query('SELECT w.id,w.name,w.description,w.picture,COUNT(t.id) AS public_count FROM workspaces w JOIN tournament t ON t.workspace_id=w.id AND t.published=1 GROUP BY w.id,w.name,w.description,w.picture ORDER BY w.name,w.id')->fetchAll();
    }

    public function publicInfo(int $id): ?array
    {
        $statement = $this->database->prepare('SELECT w.id,w.name,w.description,w.picture FROM workspaces w WHERE w.id=? AND EXISTS(SELECT 1 FROM tournament t WHERE t.workspace_id=w.id AND t.published=1)');
        $statement->execute([$id]);
        return $statement->fetch() ?: null;
    }

    // También se utiliza antes de cada escritura; el rol nunca viene del formulario.
    public function findForUser(int $id, int $userId, bool $lock = false): ?array
    {
        $statement = $this->database->prepare(
            'SELECT w.*, u.name AS owner_name FROM workspaces w
             INNER JOIN users u ON u.id = w.owner_id WHERE w.id = ?' . ($lock ? ' FOR UPDATE' : '')
        );
        $statement->execute([$id]);
        $row = $statement->fetch();
        if (!$row) {
            return null;
        }

        if ((int) $row['owner_id'] === $userId) {
            $row['role_name'] = Role::OWNER;
            return $row;
        }

        $statement = $this->database->prepare(
            'SELECT r.name FROM workspace_members m INNER JOIN roles r ON r.id = m.role_id
             WHERE m.workspace_id = ? AND m.user_id = ?' . ($lock ? ' FOR UPDATE' : '')
        );
        $statement->execute([$id, $userId]);
        $role = $statement->fetchColumn();
        if (!$role || $role === Role::OWNER) {
            return null;
        }

        $row['role_name'] = $role;
        return $row;
    }

    public function create(int $userId, string $name, string $description, string $picture = ''): int
    {
        $statement = $this->database->prepare(
            "INSERT INTO workspaces (owner_id, name, description, picture, created_at)
             VALUES (?, ?, ?, ?, UTC_TIMESTAMP())"
        );
        $statement->execute([$userId, $name, $description, $picture]);
        $id = (int) $this->database->lastInsertId();
        $statement = $this->database->prepare(
            'INSERT INTO workspace_members (workspace_id, user_id, role_id, assigned_at)
             VALUES (?, ?, ?, UTC_TIMESTAMP())'
        );
        $statement->execute([$id, $userId, Role::findId(Role::OWNER)]);
        $statement = $this->database->prepare('INSERT INTO workspace_settings (workspace_id, updated_at) VALUES (?, UTC_TIMESTAMP())');
        $statement->execute([$id]);
        $statement = $this->database->prepare(
            'INSERT INTO workspace_modules (workspace_id, module_id, is_active, updated_at)
             SELECT ?, id, 1, UTC_TIMESTAMP() FROM modules'
        );
        $statement->execute([$id]);
        $this->log($id, $userId, 'workspace', $id, 'create', null, ['name' => $name, 'description' => $description, 'picture' => $picture]);
        $this->log($id, $userId, 'member', $userId, 'join', null, ['role' => Role::OWNER]);
        return $id;
    }

    public function updateInfo(array $workspace, int $userId, string $name, string $description, ?string $picture = null): void
    {
        if ($picture === null) {
            $picture = $workspace['picture'];
        }
        $statement = $this->database->prepare('UPDATE workspaces SET name = ?, description = ?, picture = ? WHERE id = ?');
        $statement->execute([$name, $description, $picture, $workspace['id']]);
        $this->log(
            (int) $workspace['id'],
            $userId,
            'workspace',
            $workspace['id'],
            'update',
            ['name' => $workspace['name'], 'description' => $workspace['description'], 'picture' => $workspace['picture']],
            ['name' => $name, 'description' => $description, 'picture' => $picture]
        );
    }

    public function members(int $id): array
    {
        $statement = $this->database->prepare(
            'SELECT u.id, u.name, u.email, m.assigned_at, r.name AS role_name
             FROM workspace_members m INNER JOIN users u ON u.id = m.user_id
             INNER JOIN roles r ON r.id = m.role_id WHERE m.workspace_id = ? ORDER BY u.name, u.id'
        );
        $statement->execute([$id]);
        return $statement->fetchAll();
    }

    public function member(int $id, int $memberId): ?array
    {
        $statement = $this->database->prepare(
            'SELECT m.*, r.name AS role_name FROM workspace_members m
             INNER JOIN roles r ON r.id = m.role_id WHERE m.workspace_id = ? AND m.user_id = ? FOR UPDATE'
        );
        $statement->execute([$id, $memberId]);
        return $statement->fetch() ?: null;
    }

    public function changeRole(int $id, int $actorId, array $member, string $role): void
    {
        if ($member['role_name'] === $role) {
            return;
        }

        $statement = $this->database->prepare('UPDATE workspace_members SET role_id = ? WHERE workspace_id = ? AND user_id = ?');
        $statement->execute([Role::findId($role), $id, $member['user_id']]);
        // Una baja de organizador no debe dejar asignaciones operativas activas.
        if ($role === Role::PARTICIPANT) {
            $this->clearAssignments($id, $actorId, (int) $member['user_id']);
        }

        $this->log(
            $id,
            $actorId,
            'member',
            $member['user_id'],
            'role_change',
            ['role' => $member['role_name']],
            ['role' => $role]
        );
    }

    public function removeMember(int $id, int $actorId, array $member): void
    {
        $this->clearAssignments($id, $actorId, (int) $member['user_id']);
        $statement = $this->database->prepare('DELETE FROM workspace_members WHERE workspace_id = ? AND user_id = ?');
        $statement->execute([$id, $member['user_id']]);
        // Las inscripciones y mediciones históricas se conservan; no dan acceso sin pertenencia.
        $this->log($id, $actorId, 'member', $member['user_id'], 'leave', ['role' => $member['role_name']], null);
    }

    private function clearAssignments(int $id, int $actorId, int $memberId): void
    {
        $statement = $this->database->prepare('SELECT id FROM tournament WHERE workspace_id = ? AND organizer_id = ?');
        $statement->execute([$id, $memberId]);
        foreach ($statement->fetchAll() as $tournament) {
            $this->log($id, $actorId, 'tournament', $tournament['id'], 'organizer_removed', ['organizer_id' => $memberId], ['organizer_id' => null]);
        }

        $statement = $this->database->prepare('UPDATE tournament SET organizer_id = NULL WHERE workspace_id = ? AND organizer_id = ?');
        $statement->execute([$id, $memberId]);
    }

    public function modules(int $id): array
    {
        $statement = $this->database->prepare(
            'SELECT m.*, COALESCE(wm.is_active, 0) AS is_active FROM modules m
             LEFT JOIN workspace_modules wm ON wm.module_id = m.id AND wm.workspace_id = ? ORDER BY m.id'
        );
        $statement->execute([$id]);
        return $statement->fetchAll();
    }

    public function setModules(int $id, int $actorId, array $selected): void
    {
        foreach ($this->modules($id) as $module) {
            $active = in_array((string) $module['id'], $selected, true) ? 1 : 0;
            if ((int) $module['is_active'] === $active) {
                continue;
            }

            $statement = $this->database->prepare(
                'INSERT INTO workspace_modules (workspace_id, module_id, is_active, updated_at)
                 VALUES (?, ?, ?, UTC_TIMESTAMP())
                 ON DUPLICATE KEY UPDATE is_active = VALUES(is_active), updated_at = VALUES(updated_at)'
            );
            $statement->execute([$id, $module['id'], $active]);
            $this->log(
                $id,
                $actorId,
                'module',
                $module['id'],
                'update',
                ['is_active' => (int) $module['is_active']],
                ['is_active' => $active]
            );
        }
    }

    public function createInvitation(int $id, int $userId): string
    {
        // Ocho caracteres sin I, O, 0 ni 1, para evitar confusiones al escribirlos.
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $statement = $this->database->prepare(
            'INSERT INTO workspace_invitations (workspace_id, token_hash, created_by, created_at)
             VALUES (?, ?, ?, UTC_TIMESTAMP())'
        );
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $code = '';
            foreach (str_split(random_bytes(8)) as $byte) {
                $code .= $alphabet[ord($byte) % 32];
            }

            try {
                $statement->execute([$id, hash('sha256', $code), $userId]);
                $invitationId = (int) $this->database->lastInsertId();
                $this->log($id, $userId, 'invitation', $invitationId, 'create', null, ['active' => true]);
                return $code;
            } catch (\PDOException $exception) {
                // Si otro enlace ya usa el código, generar uno nuevo.
                if (($exception->errorInfo[1] ?? 0) !== 1062) {
                    throw $exception;
                }
            }
        }

        throw new \RuntimeException('No se pudo generar un código de invitación único.');
    }

    public static function normalizeInvitationCode(string $code): string
    {
        $code = trim($code);
        // Los enlaces de 48 caracteres anteriores conservan su hash original.
        if (preg_match('/^[a-f0-9]{48}$/i', $code)) {
            return strtolower($code);
        }

        $code = strtoupper($code);
        return preg_match('/^[A-HJ-NP-Z2-9]{8}$/', $code) ? $code : '';
    }

    public function invitations(int $id): array
    {
        $statement = $this->database->prepare(
            'SELECT i.id, i.created_at, i.revoked_at, u.name AS creator_name
             FROM workspace_invitations i INNER JOIN users u ON u.id = i.created_by
             WHERE i.workspace_id = ? ORDER BY i.id DESC'
        );
        $statement->execute([$id]);
        return $statement->fetchAll();
    }

    public function findInvitation(string $code): ?array
    {
        $code = self::normalizeInvitationCode($code);
        if ($code === '') {
            return null;
        }

        $statement = $this->database->prepare(
            'SELECT i.id AS invitation_id, w.id, w.name, w.description, w.owner_id
             FROM workspace_invitations i INNER JOIN workspaces w ON w.id = i.workspace_id
             WHERE i.token_hash = ? AND i.revoked_at IS NULL'
        );
        $statement->execute([hash('sha256', $code)]);
        return $statement->fetch() ?: null;
    }

    public function join(string $code, int $userId): int
    {
        $invitation = $this->findInvitation($code);
        if (!$invitation) {
            throw new \InvalidArgumentException('La invitación no es válida o fue revocada.');
        }
        // Mismo orden de bloqueo que la revocación: primero entorno, luego invitación.
        $statement = $this->database->prepare('SELECT id FROM workspaces WHERE id = ? FOR UPDATE');
        $statement->execute([$invitation['id']]);
        $statement = $this->database->prepare('SELECT revoked_at FROM workspace_invitations WHERE id = ? FOR UPDATE');
        $statement->execute([$invitation['invitation_id']]);
        $row = $statement->fetch();
        if (!$row || $row['revoked_at'] !== null) {
            throw new \InvalidArgumentException('La invitación fue revocada. Solicita una nueva.');
        }

        $id = (int) $invitation['id'];
        if ($this->findForUser($id, $userId)) {
            return $id;
        }

        $statement = $this->database->prepare(
            'INSERT INTO workspace_members (workspace_id, user_id, role_id, assigned_at)
             VALUES (?, ?, ?, UTC_TIMESTAMP())'
        );
        $statement->execute([$id, $userId, Role::findId(Role::PARTICIPANT)]);
        $this->log($id, $userId, 'member', $userId, 'join', null, ['role' => Role::PARTICIPANT]);
        return $id;
    }

    public function revokeInvitation(int $id, int $userId, int $invitationId): void
    {
        $statement = $this->database->prepare(
            'UPDATE workspace_invitations SET revoked_at = UTC_TIMESTAMP()
             WHERE workspace_id = ? AND id = ? AND revoked_at IS NULL'
        );
        $statement->execute([$id, $invitationId]);
        if (!$statement->rowCount()) {
            throw new \InvalidArgumentException('La invitación no está activa en este entorno.');
        }

        $this->log($id, $userId, 'invitation', $invitationId, 'revoke', ['active' => true], ['active' => false]);
    }

    public function audit(int $id): array
    {
        return (new \App\Modules\Auditoria\Models\Audit())->recent($id);
    }

    private function log(int $id, int $actorId, string $entity, $entityId, string $action, ?array $old, ?array $new): void
    {
        (new \App\Modules\Auditoria\Models\Audit())->record($id, $actorId, $entity, (string)$entityId, $action, $old, $new);
    }
}
