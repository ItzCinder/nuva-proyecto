<?php

namespace app\Models;

class Role
{
    public const OWNER = 'Dueño';
    public const ADMIN = 'Administrador General';
    public const ORGANIZER = 'Organizador';
    public const PARTICIPANT = 'Participante';

    // El usuario público no tiene un rol global guardado en la base.
    public static function findId(string $name): int
    {
        $statement = \Database::getConnection()->prepare('SELECT id FROM roles WHERE name = ? ORDER BY id LIMIT 1');
        $statement->execute([$name]);
        $id = $statement->fetchColumn();
        if (!$id) {
            throw new \RuntimeException('Faltan los roles. Ejecuta la migración de entornos.');
        }

        return (int) $id;
    }

    public static function can(string $role, string $permission): bool
    {
        static $permissions = [];
        if (!isset($permissions[$role])) {
            $statement = \Database::getConnection()->prepare(
                'SELECT p.name FROM permissions p
                 INNER JOIN role_permissions rp ON rp.permission_id = p.id
                 INNER JOIN roles r ON r.id = rp.role_id WHERE r.name = ?'
            );
            $statement->execute([$role]);
            $permissions[$role] = $statement->fetchAll(\PDO::FETCH_COLUMN);
        }

        return in_array($permission, $permissions[$role], true);
    }

    public static function descriptions(): array
    {
        return [
            self::OWNER => 'Gestiona el entorno y asigna o retira administradores generales.',
            self::ADMIN => 'Gestiona miembros, organizadores, módulos, invitaciones y configuración.',
            self::ORGANIZER => 'Accede únicamente a los torneos que tiene asignados.',
            self::PARTICIPANT => 'Consulta el entorno y sus propias inscripciones en torneos.',
        ];
    }
}
