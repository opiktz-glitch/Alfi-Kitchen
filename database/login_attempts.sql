CREATE TABLE IF NOT EXISTS login_attempts (
    attempt_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    attempt_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    window_started_at INT UNSIGNED NOT NULL,
    locked_until INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at INT UNSIGNED NOT NULL,
    PRIMARY KEY (attempt_key),
    KEY idx_login_attempts_updated_at (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;