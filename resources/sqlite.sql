-- #!sqlite
-- # { aethelis_players
-- #   {
CREATE TABLE IF NOT EXISTS aethelis_players (
    uuid VARCHAR(36) PRIMARY KEY,
    username VARCHAR(16) NOT NULL,
)