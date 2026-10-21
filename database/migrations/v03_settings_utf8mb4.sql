-- ============================================================
-- Migrasi 003: Konversi setting_value ke utf8mb4_unicode_ci
-- ============================================================

ALTER TABLE `settings` MODIFY `setting_value` MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
