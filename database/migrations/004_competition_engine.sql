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
