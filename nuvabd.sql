CREATE DATABASE IF NOT EXISTS `NuvaBD` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `NuvaBD`;

SET FOREIGN_KEY_CHECKS=0;

-- Tabla: users
CREATE TABLE `users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `google_id` VARCHAR(500) NOT NULL,
  `email` VARCHAR(500) NOT NULL,
  `name` VARCHAR(500) NOT NULL,
  `picture_url` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: workspaces
CREATE TABLE `workspaces` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `owner_id` INT NOT NULL,
  `name` VARCHAR(500) NOT NULL,
  `description` VARCHAR(500),
  `picture` VARCHAR(500) NOT NULL,
  `created_at` TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_workspaces_owner_id_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: tournament
CREATE TABLE `tournament` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `workspace_id` INT NOT NULL,
  `organizer_id` INT,
  `name` VARCHAR(120) NOT NULL,
  `description` VARCHAR(500) NOT NULL DEFAULT '',
  `discipline_category` VARCHAR(20) NOT NULL DEFAULT 'otros',
  `discipline` VARCHAR(80) NOT NULL DEFAULT 'Otra disciplina',
  `has_team` BOOLEAN NOT NULL DEFAULT 0,
  `max_participants` SMALLINT UNSIGNED NOT NULL DEFAULT 16,
  `published` BOOLEAN NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL,
  `start_date` TIMESTAMP,
  `end_date` TIMESTAMP,
  `status` TEXT,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_tournament_organizer_id_1` FOREIGN KEY (`organizer_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_tournament_workspace_id_2` FOREIGN KEY (`workspace_id`) REFERENCES `workspaces` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: modules
CREATE TABLE `modules` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(500) NOT NULL,
  `name` VARCHAR(500),
  `description` TEXT,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_modules_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: roles
CREATE TABLE `roles` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(500) NOT NULL,
  `description` TEXT NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: workspace_members
CREATE TABLE `workspace_members` (
  `workspace_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `role_id` INT NOT NULL,
  `assigned_at` TIMESTAMP,
  PRIMARY KEY (`workspace_id`, `user_id`),
  CONSTRAINT `fk_workspace_members_role_id_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  CONSTRAINT `fk_workspace_members_user_id_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_workspace_members_workspace_id_3` FOREIGN KEY (`workspace_id`) REFERENCES `workspaces` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: permissions
CREATE TABLE `permissions` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: role_permissions
CREATE TABLE `role_permissions` (
  `role_id` INT NOT NULL,
  `permission_id` INT,
  CONSTRAINT `fk_role_permissions_permission_id_1` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`),
  CONSTRAINT `fk_role_permissions_role_id_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: tournament_members
CREATE TABLE `tournament_members` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `tournament_id` INT NOT NULL,
  `member_id` INT NOT NULL,
  `role_id` INT NOT NULL,
  `team_id` INT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_tournament_members_role_id_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  CONSTRAINT `fk_tournament_members_tournament_id_2` FOREIGN KEY (`tournament_id`) REFERENCES `tournament` (`id`),
  CONSTRAINT `fk_tournament_members_member_id_3` FOREIGN KEY (`member_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: tournament_config
CREATE TABLE `tournament_config` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `module_id` INT NOT NULL,
  `league_legs` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `swiss_rounds` TINYINT UNSIGNED NULL,
  `third_place` BOOLEAN NOT NULL DEFAULT 0,
  `team_size` TINYINT UNSIGNED NULL,
  `updated_at` TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_tournament_config_module_id_1` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`),
  CONSTRAINT `fk_tournament_config_id_2` FOREIGN KEY (`id`) REFERENCES `tournament` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: member_stat_types
CREATE TABLE `member_stat_types` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `workspace_id` INT,
  `code` VARCHAR(500) NOT NULL,
  `name` VARCHAR(500) NOT NULL,
  `description` TEXT,
  `is_system_default` BOOLEAN NOT NULL,
  `unit` VARCHAR(50) NULL COMMENT 'Unidad del dato; NULL cuando no corresponda (ej.: s, m, kg, %)',
  `data_type` ENUM('integer', 'decimal') NOT NULL DEFAULT 'integer' COMMENT 'Tipo numerico admitido por la estadistica (RF-016)',
  `created_by` INT,
  `created_at` TIMESTAMP NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_member_stat_types_workspace_id_code` (`workspace_id`, `code`),
  CONSTRAINT `fk_member_stat_types_created_by_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_member_stat_types_workspace_id_2` FOREIGN KEY (`workspace_id`) REFERENCES `workspaces` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: member_stat_values
CREATE TABLE `member_stat_values` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `workspace_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `stat_type_id` INT NOT NULL,
  `value` DECIMAL(20,8) NOT NULL,
  `updated_at` TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_member_stat_values_workspace_id_user_id_stat_type_id` (`workspace_id`, `user_id`, `stat_type_id`),
  CONSTRAINT `fk_member_stat_values_stat_type_id_1` FOREIGN KEY (`stat_type_id`) REFERENCES `member_stat_types` (`id`),
  CONSTRAINT `fk_member_stat_values_user_id_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_member_stat_values_workspace_id_3` FOREIGN KEY (`workspace_id`) REFERENCES `workspaces` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: workspace_audit_logs
CREATE TABLE `workspace_audit_logs` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `workspace_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `entity_type` VARCHAR(100) NOT NULL,
  `entity_id` VARCHAR(100) NOT NULL,
  `action` VARCHAR(50) NOT NULL,
  `old_value` TEXT,
  `new_value` TEXT,
  `created_at` TIMESTAMP NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_workspace_audit_logs_workspace_id_created_at` (`workspace_id`, `created_at`),
  KEY `idx_workspace_audit_logs_workspace_id_entity_type` (`workspace_id`, `entity_type`),
  CONSTRAINT `fk_workspace_audit_logs_user_id_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_workspace_audit_logs_workspace_id_2` FOREIGN KEY (`workspace_id`) REFERENCES `workspaces` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: tournament_teams
CREATE TABLE `tournament_teams` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `tournament_id` INT,
  `name` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_tournament_teams_tournament_id_1` FOREIGN KEY (`tournament_id`) REFERENCES `tournament` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: tournament_participant_stat_values
CREATE TABLE `tournament_participant_stat_values` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `id_tournament` INT NOT NULL,
  `team_id` INT,
  `member_id` INT,
  `stat_type_id` INT NOT NULL,
  `value` DECIMAL(20,8) NOT NULL,
  `updated_at` TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_tournament_participant_stat_values_id_tournament_te_dcc88df0` (`id_tournament`, `team_id`, `member_id`, `stat_type_id`),
  CONSTRAINT `fk_tournament_participant_stat_values_stat_type_id_1` FOREIGN KEY (`stat_type_id`) REFERENCES `member_stat_types` (`id`),
  CONSTRAINT `fk_tournament_participant_stat_values_id_tournament_2` FOREIGN KEY (`id_tournament`) REFERENCES `tournament` (`id`),
  CONSTRAINT `fk_tournament_participant_stat_values_member_id_3` FOREIGN KEY (`member_id`) REFERENCES `tournament_members` (`id`),
  CONSTRAINT `fk_tournament_participant_stat_values_team_id_4` FOREIGN KEY (`team_id`) REFERENCES `tournament_teams` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: workspace_settings
CREATE TABLE `workspace_settings` (
  `workspace_id` INT NOT NULL AUTO_INCREMENT,
  `updated_at` TIMESTAMP,
  PRIMARY KEY (`workspace_id`),
  CONSTRAINT `fk_workspace_settings_workspace_id_1` FOREIGN KEY (`workspace_id`) REFERENCES `workspaces` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: workspace_modules
CREATE TABLE `workspace_modules` (
  `workspace_id` INT NOT NULL,
  `module_id` INT NOT NULL,
  `is_active` BOOLEAN,
  `updated_at` TIMESTAMP,
  PRIMARY KEY (`workspace_id`, `module_id`),
  CONSTRAINT `fk_workspace_modules_module_id_1` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`),
  CONSTRAINT `fk_workspace_modules_workspace_id_2` FOREIGN KEY (`workspace_id`) REFERENCES `workspaces` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: tournament_match
-- NOTA: team_1_id y team_2_id son referencias polimorficas:
--   si has_team = TRUE  -> apuntan a tournament_teams.id
--   si has_team = FALSE -> apuntan a tournament_members.id (participante individual)
CREATE TABLE `tournament_match` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `id_tournament` INT NOT NULL,
  `has_team` BOOLEAN NOT NULL,
  `team_1_id` INT NOT NULL,
  `team_2_id` INT NOT NULL,
  `score_1` INT NOT NULL,
  `score_2` INT NOT NULL,
  `round_name` VARCHAR(500) NOT NULL,
  `scheduled_at` TIMESTAMP NOT NULL,
  `played_at` TIMESTAMP,
  `status` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tournament_match_id_tournament_scheduled_at` (`id_tournament`, `scheduled_at`),
  CONSTRAINT `fk_tournament_match_id_tournament_1` FOREIGN KEY (`id_tournament`) REFERENCES `tournament` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: workspace_member_stat_config
CREATE TABLE `workspace_member_stat_config` (
  `workspace_id` INT NOT NULL,
  `stat_type_id` INT NOT NULL,
  `is_visible` BOOLEAN,
  `updated_by` INT,
  `updated_at` TIMESTAMP,
  PRIMARY KEY (`workspace_id`, `stat_type_id`),
  CONSTRAINT `fk_workspace_member_stat_config_stat_type_id_1` FOREIGN KEY (`stat_type_id`) REFERENCES `member_stat_types` (`id`),
  CONSTRAINT `fk_workspace_member_stat_config_updated_by_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_workspace_member_stat_config_workspace_id_3` FOREIGN KEY (`workspace_id`) REFERENCES `workspaces` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;


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

-- Ampliación compatible con los torneos existentes. No elimina datos.
ALTER TABLE tournament_config
  ADD COLUMN IF NOT EXISTS points_win DECIMAL(8,2) NOT NULL DEFAULT 3,
  ADD COLUMN IF NOT EXISTS points_draw DECIMAL(8,2) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS points_loss DECIMAL(8,2) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS allow_draws BOOLEAN NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS score_order VARCHAR(10) NOT NULL DEFAULT 'higher',
  ADD COLUMN IF NOT EXISTS score_label VARCHAR(40) NOT NULL DEFAULT 'Marcador';

CREATE TABLE IF NOT EXISTS tournament_rounds (
  tournament_id INT NOT NULL,
  number SMALLINT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  status VARCHAR(12) NOT NULL DEFAULT 'Abierta',
  PRIMARY KEY (tournament_id, number),
  CONSTRAINT fk_round_tournament FOREIGN KEY (tournament_id) REFERENCES tournament(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE tournament_match
  MODIFY team_1_id INT NULL,
  MODIFY team_2_id INT NULL,
  MODIFY score_1 DECIMAL(10,2) NULL,
  MODIFY score_2 DECIMAL(10,2) NULL,
  MODIFY scheduled_at TIMESTAMP NULL,
  ADD COLUMN IF NOT EXISTS round_no SMALLINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS position SMALLINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS winner_id INT NULL,
  ADD COLUMN IF NOT EXISTS source_1_id INT NULL,
  ADD COLUMN IF NOT EXISTS source_2_id INT NULL,
  ADD COLUMN IF NOT EXISTS source_kind VARCHAR(10) NOT NULL DEFAULT 'winner',
  ADD COLUMN IF NOT EXISTS notes VARCHAR(500) NOT NULL DEFAULT '',
  ADD COLUMN IF NOT EXISTS revision INT UNSIGNED NOT NULL DEFAULT 0;
CREATE UNIQUE INDEX IF NOT EXISTS uq_match_position ON tournament_match(id_tournament, round_no, position);

-- Conserva la identidad y el orden del sorteo aun si cambia el perfil del usuario.
CREATE TABLE IF NOT EXISTS tournament_seeds (
  tournament_id INT NOT NULL,
  entrant_id INT NOT NULL,
  seed SMALLINT UNSIGNED NOT NULL,
  name VARCHAR(150) NOT NULL,
  PRIMARY KEY (tournament_id, entrant_id),
  UNIQUE KEY uq_seed (tournament_id, seed),
  CONSTRAINT fk_seed_tournament FOREIGN KEY (tournament_id) REFERENCES tournament(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
