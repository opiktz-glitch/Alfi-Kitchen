-- ============================================================
-- Migrasi SEO: slug produk untuk URL ramah-SEO /produk/{slug}
-- Jalankan SEKALI di database lokal (phpMyAdmin/XAMPP) dan di
-- hosting (mis. via phpMyAdmin InfinityFree) sebelum deploy.
-- ============================================================

-- 1) Kolom slug
ALTER TABLE products ADD COLUMN slug VARCHAR(120) NULL AFTER name;

-- 2) Isi slug dari nama produk yang sudah ada
UPDATE products SET slug = LOWER(REGEXP_REPLACE(TRIM(name), '[^a-zA-Z0-9]+', '-'));
UPDATE products SET slug = TRIM(BOTH '-' FROM slug);
UPDATE products SET slug = CONCAT('produk-', id) WHERE slug IS NULL OR slug = '';

-- 3) Unique index agar slug tidak duplikat
CREATE UNIQUE INDEX uq_products_slug ON products(slug);

-- CATATAN (InfinityFree): pembuatan TRIGGER ditolak oleh shared hosting
-- (#1142 TRIGGER command denied). Slug produk DIKUNCI permanen: dibuat sekali
-- saat tambah produk (admin/add_product.php) dan tidak berubah saat nama
-- diedit (admin/edit_product.php), mengikuti pola Tokopedia/Shopee agar URL
-- /produk/{slug} stabil dan ranking tidak reset. Trigger sudah DIHAPUS
-- dari migrasi ini agar aman dijalankan ulang di hosting; trigger opsional
-- tersedia terpisah di database/product_slug_triggers.sql (hanya untuk
-- hosting yang mengizinkan TRIGGER, mis. XAMPP lokal). PERHATIAN: trigger
-- tersebut MEREGENERASI slug saat nama berubah dan BERLAWANAN dengan
-- kebijakan kunci permanen — jangan dipasang bila ingin URL stabil.
