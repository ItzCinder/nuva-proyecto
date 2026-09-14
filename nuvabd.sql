CREATE DATABASE IF NOT EXISTS `NuvaBD` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `NuvaBD`;

SET FOREIGN_KEY_CHECKS=0;

-- Tabla: users
CREATE TABLE `users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `google_id` VARCHAR(500) NOT NULL,
  `email` VARCHAR(500) NOT NULL,
  `name` VARCHAR(500) NOT NULL,
  `picture_url` TEXT NOT NULL,
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
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_tournament_members_role_id_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  CONSTRAINT `fk_tournament_members_tournament_id_2` FOREIGN KEY (`tournament_id`) REFERENCES `tournament` (`id`),
  CONSTRAINT `fk_tournament_members_member_id_3` FOREIGN KEY (`member_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: tournament_config
CREATE TABLE `tournament_config` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `module_id` INT NOT NULL,
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
  `value` INT NOT NULL,
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
  `value` INT NOT NULL,
  `updated_at` TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_tournament_participant_stat_values_id_tournament_team_id_member_id_stat_type_id` (`id_tournament`, `team_id`, `member_id`, `stat_type_id`),
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