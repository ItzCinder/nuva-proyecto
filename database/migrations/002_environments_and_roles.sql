-- Roles y funciones de entornos. Puede ejecutarse nuevamente sin duplicar semillas.
USE `NuvaBD`;

CREATE TABLE IF NOT EXISTS `workspace_invitations` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `workspace_id` INT NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `created_by` INT NOT NULL,
  `created_at` DATETIME NOT NULL,
  `revoked_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_workspace_invitations_token_hash` (`token_hash`),
  CONSTRAINT `fk_workspace_invitations_workspace` FOREIGN KEY (`workspace_id`) REFERENCES `workspaces` (`id`),
  CONSTRAINT `fk_workspace_invitations_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

START TRANSACTION;
INSERT INTO roles (name, description) SELECT 'Dueño', 'Crea el entorno y administra todos sus recursos. Asigna administradores generales.' WHERE NOT EXISTS (SELECT 1 FROM roles WHERE name = 'Dueño');
INSERT INTO roles (name, description) SELECT 'Administrador General', 'Administra la información, miembros y módulos de su entorno.' WHERE NOT EXISTS (SELECT 1 FROM roles WHERE name = 'Administrador General');
INSERT INTO roles (name, description) SELECT 'Organizador', 'Opera únicamente los torneos que tiene asignados en este entorno.' WHERE NOT EXISTS (SELECT 1 FROM roles WHERE name = 'Organizador');
INSERT INTO roles (name, description) SELECT 'Participante', 'Consulta su entorno y los torneos en los que está inscrito.' WHERE NOT EXISTS (SELECT 1 FROM roles WHERE name = 'Participante');
INSERT INTO permissions (name) SELECT 'environment.view' WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'environment.view');
INSERT INTO permissions (name) SELECT 'environment.edit' WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'environment.edit');
INSERT INTO permissions (name) SELECT 'members.manage' WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'members.manage');
INSERT INTO permissions (name) SELECT 'admins.manage' WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'admins.manage');
INSERT INTO permissions (name) SELECT 'modules.manage' WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'modules.manage');
INSERT INTO permissions (name) SELECT 'invitations.manage' WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'invitations.manage');
INSERT INTO permissions (name) SELECT 'audit.view' WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'audit.view');
INSERT INTO permissions (name) SELECT 'environment.leave' WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'environment.leave');
INSERT INTO role_permissions (role_id, permission_id) SELECT r.id, p.id FROM roles r CROSS JOIN permissions p WHERE r.name = 'Dueño' AND p.name = 'environment.view' AND NOT EXISTS (SELECT 1 FROM role_permissions rp WHERE rp.role_id = r.id AND rp.permission_id = p.id);
INSERT INTO role_permissions (role_id, permission_id) SELECT r.id, p.id FROM roles r CROSS JOIN permissions p WHERE r.name = 'Dueño' AND p.name = 'environment.edit' AND NOT EXISTS (SELECT 1 FROM role_permissions rp WHERE rp.role_id = r.id AND rp.permission_id = p.id);
INSERT INTO role_permissions (role_id, permission_id) SELECT r.id, p.id FROM roles r CROSS JOIN permissions p WHERE r.name = 'Dueño' AND p.name = 'members.manage' AND NOT EXISTS (SELECT 1 FROM role_permissions rp WHERE rp.role_id = r.id AND rp.permission_id = p.id);
INSERT INTO role_permissions (role_id, permission_id) SELECT r.id, p.id FROM roles r CROSS JOIN permissions p WHERE r.name = 'Dueño' AND p.name = 'admins.manage' AND NOT EXISTS (SELECT 1 FROM role_permissions rp WHERE rp.role_id = r.id AND rp.permission_id = p.id);
INSERT INTO role_permissions (role_id, permission_id) SELECT r.id, p.id FROM roles r CROSS JOIN permissions p WHERE r.name = 'Dueño' AND p.name = 'modules.manage' AND NOT EXISTS (SELECT 1 FROM role_permissions rp WHERE rp.role_id = r.id AND rp.permission_id = p.id);
INSERT INTO role_permissions (role_id, permission_id) SELECT r.id, p.id FROM roles r CROSS JOIN permissions p WHERE r.name = 'Dueño' AND p.name = 'invitations.manage' AND NOT EXISTS (SELECT 1 FROM role_permissions rp WHERE rp.role_id = r.id AND rp.permission_id = p.id);
INSERT INTO role_permissions (role_id, permission_id) SELECT r.id, p.id FROM roles r CROSS JOIN permissions p WHERE r.name = 'Dueño' AND p.name = 'audit.view' AND NOT EXISTS (SELECT 1 FROM role_permissions rp WHERE rp.role_id = r.id AND rp.permission_id = p.id);
INSERT INTO role_permissions (role_id, permission_id) SELECT r.id, p.id FROM roles r CROSS JOIN permissions p WHERE r.name = 'Administrador General' AND p.name = 'environment.view' AND NOT EXISTS (SELECT 1 FROM role_permissions rp WHERE rp.role_id = r.id AND rp.permission_id = p.id);
INSERT INTO role_permissions (role_id, permission_id) SELECT r.id, p.id FROM roles r CROSS JOIN permissions p WHERE r.name = 'Administrador General' AND p.name = 'environment.edit' AND NOT EXISTS (SELECT 1 FROM role_permissions rp WHERE rp.role_id = r.id AND rp.permission_id = p.id);
INSERT INTO role_permissions (role_id, permission_id) SELECT r.id, p.id FROM roles r CROSS JOIN permissions p WHERE r.name = 'Administrador General' AND p.name = 'members.manage' AND NOT EXISTS (SELECT 1 FROM role_permissions rp WHERE rp.role_id = r.id AND rp.permission_id = p.id);
INSERT INTO role_permissions (role_id, permission_id) SELECT r.id, p.id FROM roles r CROSS JOIN permissions p WHERE r.name = 'Administrador General' AND p.name = 'modules.manage' AND NOT EXISTS (SELECT 1 FROM role_permissions rp WHERE rp.role_id = r.id AND rp.permission_id = p.id);
INSERT INTO role_permissions (role_id, permission_id) SELECT r.id, p.id FROM roles r CROSS JOIN permissions p WHERE r.name = 'Administrador General' AND p.name = 'invitations.manage' AND NOT EXISTS (SELECT 1 FROM role_permissions rp WHERE rp.role_id = r.id AND rp.permission_id = p.id);
INSERT INTO role_permissions (role_id, permission_id) SELECT r.id, p.id FROM roles r CROSS JOIN permissions p WHERE r.name = 'Administrador General' AND p.name = 'audit.view' AND NOT EXISTS (SELECT 1 FROM role_permissions rp WHERE rp.role_id = r.id AND rp.permission_id = p.id);
INSERT INTO role_permissions (role_id, permission_id) SELECT r.id, p.id FROM roles r CROSS JOIN permissions p WHERE r.name = 'Administrador General' AND p.name = 'environment.leave' AND NOT EXISTS (SELECT 1 FROM role_permissions rp WHERE rp.role_id = r.id AND rp.permission_id = p.id);
INSERT INTO role_permissions (role_id, permission_id) SELECT r.id, p.id FROM roles r CROSS JOIN permissions p WHERE r.name = 'Organizador' AND p.name = 'environment.view' AND NOT EXISTS (SELECT 1 FROM role_permissions rp WHERE rp.role_id = r.id AND rp.permission_id = p.id);
INSERT INTO role_permissions (role_id, permission_id) SELECT r.id, p.id FROM roles r CROSS JOIN permissions p WHERE r.name = 'Organizador' AND p.name = 'environment.leave' AND NOT EXISTS (SELECT 1 FROM role_permissions rp WHERE rp.role_id = r.id AND rp.permission_id = p.id);
INSERT INTO role_permissions (role_id, permission_id) SELECT r.id, p.id FROM roles r CROSS JOIN permissions p WHERE r.name = 'Participante' AND p.name = 'environment.view' AND NOT EXISTS (SELECT 1 FROM role_permissions rp WHERE rp.role_id = r.id AND rp.permission_id = p.id);
INSERT INTO role_permissions (role_id, permission_id) SELECT r.id, p.id FROM roles r CROSS JOIN permissions p WHERE r.name = 'Participante' AND p.name = 'environment.leave' AND NOT EXISTS (SELECT 1 FROM role_permissions rp WHERE rp.role_id = r.id AND rp.permission_id = p.id);
INSERT INTO modules (code, name, description) SELECT 'league', 'Liga', 'Todos contra todos.' WHERE NOT EXISTS (SELECT 1 FROM modules WHERE code = 'league');
INSERT INTO modules (code, name, description) SELECT 'swiss', 'Sistema Suizo', 'Emparejamientos por rendimiento acumulado.' WHERE NOT EXISTS (SELECT 1 FROM modules WHERE code = 'swiss');
INSERT INTO modules (code, name, description) SELECT 'elimination', 'Eliminación Directa', 'Llaves de eliminación.' WHERE NOT EXISTS (SELECT 1 FROM modules WHERE code = 'elimination');

-- El dueño se identifica siempre por workspaces.owner_id.
INSERT INTO workspace_members (workspace_id, user_id, role_id, assigned_at)
SELECT w.id, w.owner_id, (SELECT MIN(id) FROM roles WHERE name = 'Dueño'), NOW()
FROM workspaces w
WHERE NOT EXISTS (SELECT 1 FROM workspace_members wm WHERE wm.workspace_id = w.id AND wm.user_id = w.owner_id);

COMMIT;
