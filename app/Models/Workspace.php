<?php

namespace app\Models;

class Workspace extends Model
{
    public function __construct()
    {
        parent::__construct();
        // Los nuevos registros del entorno utilizan UTC; las vistas muestran Uruguay.
        $this->db->exec("SET time_zone = '+00:00'");
    }

    public function allForUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT w.*, r.name AS role_name,
             (SELECT COUNT(*) FROM workspace_members wm WHERE wm.workspace_id = w.id) AS member_count
             FROM workspaces w
             LEFT JOIN workspace_members m ON m.workspace_id = w.id AND m.user_id = ?
             LEFT JOIN roles r ON r.id = m.role_id
             WHERE w.owner_id = ? OR (m.user_id IS NOT NULL AND r.name <> ?) ORDER BY w.id DESC'
        );
        $stmt->execute([$userId, $userId, Role::OWNER]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            if ((int) $row['owner_id'] === $userId) {
                $row['role_name'] = Role::OWNER;
            }
        }
        return $rows;
    }

    public function publicList(): array
    {
        return $this->db->query('SELECT w.id,w.name,w.description,COUNT(t.id) AS public_count FROM workspaces w JOIN tournament t ON t.workspace_id=w.id AND t.published=1 GROUP BY w.id,w.name,w.description ORDER BY w.name,w.id')->fetchAll();
    }

    public function publicInfo(int $id): ?array
    {
        $stmt=$this->db->prepare('SELECT w.id,w.name,w.description FROM workspaces w WHERE w.id=? AND EXISTS(SELECT 1 FROM tournament t WHERE t.workspace_id=w.id AND t.published=1)');
        $stmt->execute([$id]); return $stmt->fetch() ?: null;
    }

    // También se utiliza antes de cada escritura; el rol nunca viene del formulario.
    public function findForUser(int $id, int $userId, bool $lock = false): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT w.*, u.name AS owner_name FROM workspaces w
             INNER JOIN users u ON u.id = w.owner_id WHERE w.id = ?' . ($lock ? ' FOR UPDATE' : '')
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        if ((int) $row['owner_id'] === $userId) {
            $row['role_name'] = Role::OWNER;
            return $row;
        }
        $stmt = $this->db->prepare(
            'SELECT r.name FROM workspace_members m INNER JOIN roles r ON r.id = m.role_id
             WHERE m.workspace_id = ? AND m.user_id = ?' . ($lock ? ' FOR UPDATE' : '')
        );
        $stmt->execute([$id, $userId]);
        $role = $stmt->fetchColumn();
        if (!$role || $role === Role::OWNER) {
            return null;
        }
        $row['role_name'] = $role;
        return $row;
    }

    public function create(int $userId, string $name, string $description): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO workspaces (owner_id, name, description, picture, created_at)
             VALUES (?, ?, ?, '', UTC_TIMESTAMP())"
        );
        $stmt->execute([$userId, $name, $description]);
        $id = (int) $this->db->lastInsertId();
        $stmt = $this->db->prepare(
            'INSERT INTO workspace_members (workspace_id, user_id, role_id, assigned_at)
             VALUES (?, ?, ?, UTC_TIMESTAMP())'
        );
        $stmt->execute([$id, $userId, Role::findId(Role::OWNER)]);
        $stmt = $this->db->prepare('INSERT INTO workspace_settings (workspace_id, updated_at) VALUES (?, UTC_TIMESTAMP())');
        $stmt->execute([$id]);
        $stmt = $this->db->prepare(
            'INSERT INTO workspace_modules (workspace_id, module_id, is_active, updated_at)
             SELECT ?, id, 1, UTC_TIMESTAMP() FROM modules'
        );
        $stmt->execute([$id]);
        $this->log($id, $userId, 'workspace', $id, 'create', null, ['name' => $name, 'description' => $description]);
        $this->log($id, $userId, 'member', $userId, 'join', null, ['role' => Role::OWNER]);
        return $id;
    }

    public function updateInfo(array $workspace, int $userId, string $name, string $description): void
    {
        $stmt = $this->db->prepare('UPDATE workspaces SET name = ?, description = ? WHERE id = ?');
        $stmt->execute([$name, $description, $workspace['id']]);
        $this->log((int) $workspace['id'], $userId, 'workspace', $workspace['id'], 'update',
            ['name' => $workspace['name'], 'description' => $workspace['description']],
            ['name' => $name, 'description' => $description]);
    }

    public function members(int $id): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id, u.name, u.email, m.assigned_at, r.name AS role_name
             FROM workspace_members m INNER JOIN users u ON u.id = m.user_id
             INNER JOIN roles r ON r.id = m.role_id WHERE m.workspace_id = ? ORDER BY u.name, u.id'
        );
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }

    public function member(int $id, int $memberId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT m.*, r.name AS role_name FROM workspace_members m
             INNER JOIN roles r ON r.id = m.role_id WHERE m.workspace_id = ? AND m.user_id = ? FOR UPDATE'
        );
        $stmt->execute([$id, $memberId]);
        return $stmt->fetch() ?: null;
    }

    public function changeRole(int $id, int $actorId, array $member, string $role): void
    {
        if ($member['role_name'] === $role) {
            return;
        }
        $stmt = $this->db->prepare('UPDATE workspace_members SET role_id = ? WHERE workspace_id = ? AND user_id = ?');
        $stmt->execute([Role::findId($role), $id, $member['user_id']]);
        // Una baja de organizador no debe dejar asignaciones operativas activas.
        if ($role === Role::PARTICIPANT) {
            $this->clearAssignments($id, $actorId, (int) $member['user_id']);
        }
        $this->log($id, $actorId, 'member', $member['user_id'], 'role_change',
            ['role' => $member['role_name']], ['role' => $role]);
    }

    public function removeMember(int $id, int $actorId, array $member): void
    {
        $this->clearAssignments($id, $actorId, (int) $member['user_id']);
        $stmt = $this->db->prepare('DELETE FROM workspace_members WHERE workspace_id = ? AND user_id = ?');
        $stmt->execute([$id, $member['user_id']]);
        // Las inscripciones y mediciones históricas se conservan; no dan acceso sin pertenencia.
        $this->log($id, $actorId, 'member', $member['user_id'], 'leave', ['role' => $member['role_name']], null);
    }

    private function clearAssignments(int $id, int $actorId, int $memberId): void
    {
        $stmt = $this->db->prepare('SELECT id FROM tournament WHERE workspace_id = ? AND organizer_id = ?');
        $stmt->execute([$id, $memberId]);
        foreach ($stmt->fetchAll() as $tournament) {
            $this->log($id, $actorId, 'tournament', $tournament['id'], 'organizer_removed', ['organizer_id' => $memberId], ['organizer_id' => null]);
        }
        $stmt = $this->db->prepare('UPDATE tournament SET organizer_id = NULL WHERE workspace_id = ? AND organizer_id = ?');
        $stmt->execute([$id, $memberId]);
    }

    public function modules(int $id): array
    {
        $stmt = $this->db->prepare(
            'SELECT m.*, COALESCE(wm.is_active, 0) AS is_active FROM modules m
             LEFT JOIN workspace_modules wm ON wm.module_id = m.id AND wm.workspace_id = ? ORDER BY m.id'
        );
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }

    public function setModules(int $id, int $actorId, array $selected): void
    {
        foreach ($this->modules($id) as $module) {
            $active = in_array((string) $module['id'], $selected, true) ? 1 : 0;
            if ((int) $module['is_active'] === $active) {
                continue;
            }
            $stmt = $this->db->prepare(
                'INSERT INTO workspace_modules (workspace_id, module_id, is_active, updated_at)
                 VALUES (?, ?, ?, UTC_TIMESTAMP())
                 ON DUPLICATE KEY UPDATE is_active = VALUES(is_active), updated_at = VALUES(updated_at)'
            );
            $stmt->execute([$id, $module['id'], $active]);
            $this->log($id, $actorId, 'module', $module['id'], 'update',
                ['is_active' => (int) $module['is_active']], ['is_active' => $active]);
        }
    }

    public function createInvitation(int $id, int $userId): string
    {
        // Ocho caracteres sin I, O, 0 ni 1, para evitar confusiones al escribirlos.
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $stmt = $this->db->prepare(
            'INSERT INTO workspace_invitations (workspace_id, token_hash, created_by, created_at)
             VALUES (?, ?, ?, UTC_TIMESTAMP())'
        );
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $code = '';
            foreach (str_split(random_bytes(8)) as $byte) {
                $code .= $alphabet[ord($byte) % 32];
            }
            try {
                $stmt->execute([$id, hash('sha256', $code), $userId]);
                $invitationId = (int) $this->db->lastInsertId();
                $this->log($id, $userId, 'invitation', $invitationId, 'create', null, ['active' => true]);
                return $code;
            } catch (\PDOException $e) {
                // Si otro enlace ya usa el código, generar uno nuevo.
                if (($e->errorInfo[1] ?? 0) !== 1062) {
                    throw $e;
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
        $stmt = $this->db->prepare(
            'SELECT i.id, i.created_at, i.revoked_at, u.name AS creator_name
             FROM workspace_invitations i INNER JOIN users u ON u.id = i.created_by
             WHERE i.workspace_id = ? ORDER BY i.id DESC'
        );
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }

    public function findInvitation(string $code): ?array
    {
        $code = self::normalizeInvitationCode($code);
        if ($code === '') {
            return null;
        }
        $stmt = $this->db->prepare(
            'SELECT i.id AS invitation_id, w.id, w.name, w.description, w.owner_id
             FROM workspace_invitations i INNER JOIN workspaces w ON w.id = i.workspace_id
             WHERE i.token_hash = ? AND i.revoked_at IS NULL'
        );
        $stmt->execute([hash('sha256', $code)]);
        return $stmt->fetch() ?: null;
    }

    public function join(string $code, int $userId): int
    {
        $invitation = $this->findInvitation($code);
        if (!$invitation) {
            throw new \InvalidArgumentException('La invitación no es válida o fue revocada.');
        }
        // Mismo orden de bloqueo que la revocación: primero entorno, luego invitación.
        $stmt = $this->db->prepare('SELECT id FROM workspaces WHERE id = ? FOR UPDATE');
        $stmt->execute([$invitation['id']]);
        $stmt = $this->db->prepare('SELECT revoked_at FROM workspace_invitations WHERE id = ? FOR UPDATE');
        $stmt->execute([$invitation['invitation_id']]);
        $row = $stmt->fetch();
        if (!$row || $row['revoked_at'] !== null) {
            throw new \InvalidArgumentException('La invitación fue revocada. Solicita una nueva.');
        }
        $id = (int) $invitation['id'];
        if ($this->findForUser($id, $userId)) {
            return $id;
        }
        $stmt = $this->db->prepare(
            'INSERT INTO workspace_members (workspace_id, user_id, role_id, assigned_at)
             VALUES (?, ?, ?, UTC_TIMESTAMP())'
        );
        $stmt->execute([$id, $userId, Role::findId(Role::PARTICIPANT)]);
        $this->log($id, $userId, 'member', $userId, 'join', null, ['role' => Role::PARTICIPANT]);
        return $id;
    }

    public function revokeInvitation(int $id, int $userId, int $invitationId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE workspace_invitations SET revoked_at = UTC_TIMESTAMP()
             WHERE workspace_id = ? AND id = ? AND revoked_at IS NULL'
        );
        $stmt->execute([$id, $invitationId]);
        if (!$stmt->rowCount()) {
            throw new \InvalidArgumentException('La invitación no está activa en este entorno.');
        }
        $this->log($id, $userId, 'invitation', $invitationId, 'revoke', ['active' => true], ['active' => false]);
    }

    public function audit(int $id): array
    {
        $stmt = $this->db->prepare(
            'SELECT a.*, u.name AS actor_name FROM workspace_audit_logs a
             INNER JOIN users u ON u.id = a.user_id WHERE a.workspace_id = ? ORDER BY a.id DESC LIMIT 100'
        );
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }

    private function log(int $id, int $actorId, string $entity, $entityId, string $action, ?array $old, ?array $new): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO workspace_audit_logs
             (workspace_id, user_id, entity_type, entity_id, action, old_value, new_value, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())'
        );
        $stmt->execute([$id, $actorId, $entity, (string) $entityId, $action,
            $old === null ? null : json_encode($old, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            $new === null ? null : json_encode($new, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)]);
    }
}
