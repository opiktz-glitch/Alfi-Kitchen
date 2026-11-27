-- ============================================================
-- Migrasi 007: Statistik kunjungan harian sederhana
-- ============================================================

CREATE TABLE IF NOT EXISTS `site_visit_stats` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `visit_date` DATE NOT NULL,
    `page_key` VARCHAR(120) NOT NULL,
    `page_label` VARCHAR(160) NOT NULL,
    `views_count` INT NOT NULL DEFAULT 0,
    `unique_count` INT NOT NULL DEFAULT 0,
    UNIQUE KEY `uq_visit_date_page` (`visit_date`, `page_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `site_visit_uniques` (
    `visit_date` DATE NOT NULL,
    `page_key` VARCHAR(120) NOT NULL,
    `visitor_hash` CHAR(64) NOT NULL,
    PRIMARY KEY (`visit_date`, `page_key`, `visitor_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
