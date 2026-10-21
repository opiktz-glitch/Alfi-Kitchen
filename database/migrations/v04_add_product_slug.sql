-- ============================================================
-- Migrasi 004: Kolom slug produk untuk URL ramah-SEO /produk/{slug}
-- ============================================================

-- 1) Kolom slug
ALTER TABLE `products` ADD COLUMN `slug` VARCHAR(120) NULL AFTER `name`;

-- 2) Isi slug untuk produk yang belum punya slug
UPDATE `products` SET `slug` = CONCAT('produk-', `id`) WHERE `slug` IS NULL OR `slug` = '';

-- 3) Unique index agar slug tidak duplikat
CREATE UNIQUE INDEX `uq_products_slug` ON `products`(`slug`);
