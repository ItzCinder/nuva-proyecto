-- Compatibilidad con instalaciones anteriores que guardaban estadísticas enteras.
ALTER TABLE member_stat_types
  ADD COLUMN IF NOT EXISTS unit VARCHAR(50) NULL COMMENT 'Unidad del dato cuando corresponda',
  ADD COLUMN IF NOT EXISTS data_type ENUM('integer','decimal') NOT NULL DEFAULT 'integer';
ALTER TABLE member_stat_values MODIFY COLUMN value DECIMAL(20,8) NOT NULL;
ALTER TABLE tournament_participant_stat_values MODIFY COLUMN value DECIMAL(20,8) NOT NULL;
