-- ============================================================
-- Migrasi 006: Kolom harga jual mandiri pada resep HPP
-- ============================================================

ALTER TABLE `hpp_recipes`
    ADD COLUMN `selling_price` INT NULL AFTER `hpp_per_jar`;
