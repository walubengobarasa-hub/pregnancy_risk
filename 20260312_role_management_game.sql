-- Run after backing up your database.
-- Adjust statements if your existing schema already contains some columns/constraints.

ALTER TABLE users
  MODIFY COLUMN role ENUM('admin','clinician','user') NOT NULL DEFAULT 'user';

ALTER TABLE assessments
  ADD COLUMN IF NOT EXISTS user_id INT NULL,
  ADD INDEX IF NOT EXISTS idx_assessments_user_id (user_id);

ALTER TABLE assessments
  ADD CONSTRAINT fk_assessments_user
  FOREIGN KEY (user_id) REFERENCES users(id)
  ON DELETE SET NULL;

ALTER TABLE impact_outcomes
  ADD COLUMN IF NOT EXISTS game_score INT NULL,
  ADD COLUMN IF NOT EXISTS game_level VARCHAR(50) NULL;
