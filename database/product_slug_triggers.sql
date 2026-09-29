-- ============================================================
-- OPSIONAL/LEGACY: Trigger regenerasi slug saat nama produk diubah.
--
-- PERHATIAN: trigger ini BERLAWANAN dengan kebijakan kunci slug permanen
-- (admin/edit_product.php mempertahankan slug agar URL /produk/{slug} stabil).
-- JANGAN dipasang bila ingin URL stabil seperti Tokopedia/Shopee.
-- File ini dipertahankan hanya sebagai dokumentasi/riwayat.
--
-- CATATAN: Shared hosting InfinityFree MENOLAK pembuatan trigger
-- (#1142 TRIGGER command denied). Jangan jalankan file ini di
-- InfinityFree — gunakan hanya di XAMPP lokal atau hosting yang
-- mengizinkan TRIGGER. Di aplikasi, regenerasi slug saat edit
-- sudah ditangani oleh admin/edit_product.php, jadi trigger ini
-- TIDAK wajib ada.
-- ============================================================
DROP TRIGGER IF EXISTS products_bu_slugs;
DELIMITER //
CREATE TRIGGER products_bu_slugs BEFORE UPDATE ON products
FOR EACH ROW
BEGIN
    DECLARE base_slug VARCHAR(120);
    -- Regenerasi slug hanya saat nama produk berubah (bukan saat slug diedit manual)
    IF NEW.name IS NOT NULL AND (NEW.name <> OLD.name OR NEW.slug IS NULL OR NEW.slug = '') THEN
        SET base_slug = LOWER(REGEXP_REPLACE(TRIM(NEW.name), '[^a-zA-Z0-9]+', '-'));
        SET base_slug = TRIM(BOTH '-' FROM base_slug);
        IF base_slug = '' THEN
            SET base_slug = CONCAT('produk-', NEW.id);
        END IF;
        SET NEW.slug = base_slug;
    END IF;
END//
DELIMITER ;
SHOW TRIGGERS;
