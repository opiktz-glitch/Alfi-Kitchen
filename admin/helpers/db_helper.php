<?php
/**
 * Database helper untuk panel admin Alfi Kitchen.
 * Memisahkan query SQL dari presentasi (HTML) agar kode lebih rapi dan mudah dirawat.
 *
 * Semua fungsi menerima $pdo (PDO instance) sebagai parameter pertama.
 */

if (!defined('ABSPATH')) {
    // Path absolut ke root project (dua level di atas folder admin/helpers).
    // Penting: folder uploads berada di root project, BUKAN di dalam admin/.
    define('ABSPATH', __DIR__ . '/../../');
}

/**
 * Path absolut folder uploads di root project.
 */
if (!function_exists('uploads_directory')) {
    function uploads_directory(): string
    {
        return ABSPATH . 'uploads';
    }
}

/**
 * Resolve path relatif dari root project ke path absolut yang aman.
 *
 * Fungsi ini adalah SATU-SATUNYA sumber kebenaran untuk memetakan
 * path relatif (mis. 'uploads/abc.jpg') ke file di disk. Dipakai oleh:
 * - admin/helpers/db_helper.php  -> delete_uploaded_image()
 * - thumb.php                     -> penyajian gambar on-the-fly (anti LFI)
 *
 * Aturan keamanan:
 * - Input harus string non-kosong.
 * - Path traversal ('..') DITOLAK outright.
 * - Path absolut (leading '/' atau 'C:\') DITOLAK.
 * - Hasil resolve harus benar-benar berada di dalam root project.
 *
 * @param string $relativePath Path relatif dari root project (mis. 'uploads/abc.jpg')
 * @return string|null Path absolut, atau null bila tidak valid / di luar root project
 */
if (!function_exists('resolve_project_path')) {
    function resolve_project_path($relativePath): ?string
    {
        if (!is_string($relativePath) || trim($relativePath) === '') {
            return null;
        }

        // Tolak null byte (memicu error pada realpath dan bisa dipakai bypass).
        if (strpos($relativePath, "\0") !== false) {
            return null;
        }

        // Normalisasi separator windows/backslash agar konsisten.
        $relativePath = str_replace('\\', '/', $relativePath);
        $relativePath = ltrim(trim($relativePath), '/');

        if ($relativePath === '') {
            return null;
        }

        // Tolak path traversal ('..') sebelum menyentuh filesystem.
        if (strpos($relativePath, '..') !== false) {
            return null;
        }

        // Tolak path absolut windows (mis. 'C:/xampp/...') dan drive letter.
        if (preg_match('#^[a-zA-Z]:#', $relativePath)) {
            return null;
        }

        $rootReal = realpath(ABSPATH);
        if ($rootReal === false) {
            return null;
        }

        $target = realpath($rootReal . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
        if ($target === false || !is_file($target)) {
            return null;
        }

        // Pastikan target benar-benar berada di dalam root project.
        if (strpos($target, $rootReal . DIRECTORY_SEPARATOR) !== 0) {
            return null;
        }

        return $target;
    }
}

/**
 * Daftar gambar statis di root project yang boleh disajikan lewat thumb.php.
 * Sengaja Limited: hanya file gambar root yang benar-benar dipakai situs.
 */
if (!function_exists('thumbnail_allowed_root_files')) {
    function thumbnail_allowed_root_files(): array
    {
        return ['hero.jpg', 'apple-touch-icon.png'];
    }
}

/**
 * Resolve path gambar untuk presentation via thumb.php (anti LFI).
 *
 * Lebih ketat daripada resolve_project_path(): hanya path gambar yang
 * berada di lokasi resmi yang diizinkan, yaitu:
 *  1. File di dalam folder uploads/ (mis. 'uploads/abc.jpg')
 *  2. Gambar statis root yang ada di thumbnail_allowed_root_files()
 *
 * @param string $relativePath Path relatif dari root project
 * @return string|null Path absolut, atau null bila tidak diizinkan
 */
if (!function_exists('resolve_thumbnail_path')) {
    function resolve_thumbnail_path($relativePath): ?string
    {
        if (!is_string($relativePath) || trim($relativePath) === '') {
            return null;
        }

        // Tolak null byte sebelum touching filesystem.
        if (strpos($relativePath, "\0") !== false) {
            return null;
        }

        // Normalisasi separator windows/backslash.
        $relativePath = str_replace('\\', '/', $relativePath);
        $relativePath = ltrim(trim($relativePath), '/');

        if ($relativePath === '') {
            return null;
        }

        // Tolak path traversal outright.
        if (strpos($relativePath, '..') !== false) {
            return null;
        }

        // Tolak drive letter windows (mis. 'C:/xampp/...').
        if (preg_match('#^[a-zA-Z]:#', $relativePath)) {
            return null;
        }

        // Hanya dua lokasi resmi yang diizinkan.
        $isUpload = strpos($relativePath, 'uploads/') === 0;
        $isAllowedRoot = in_array($relativePath, thumbnail_allowed_root_files(), true);

        if (!$isUpload && !$isAllowedRoot) {
            return null;
        }

        // Resolve aman (menangani symlink escape lewat realpath).
        $target = resolve_project_path($relativePath);
        if ($target === null) {
            return null;
        }

        if ($isUpload) {
            // Pastikan file benar-benar berada di dalam folder uploads/.
            $uploadDirectory = realpath(uploads_directory());
            if (
                $uploadDirectory === false
                || strpos($target, $uploadDirectory . DIRECTORY_SEPARATOR) !== 0
            ) {
                return null;
            }
        }

        return $target;
    }
}

/**
 * Ambil nilai setting dari tabel settings berdasarkan kunci.
 *
 * @param PDO $pdo
 * @param string $key Kunci setting (contoh: 'whatsapp_number', 'home_title')
 * @param string $default Nilai default jika tidak ditemukan
 * @return string
 */
function get_setting(PDO $pdo, string $key, string $default = ''): string
{
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? (string) $row['setting_value'] : $default;
}

/**
 * Ambil nomor WhatsApp dari setting.
 *
 * @param PDO $pdo
 * @return string
 */
function get_whatsapp_number(PDO $pdo): string
{
    return get_setting($pdo, 'whatsapp_number', '');
}

/**
 * Ambil konten homepage (judul dan deskripsi) dari setting.
 *
 * @param PDO $pdo
 * @return array ['home_title' => '...', 'home_description' => '...']
 */
function get_home_settings(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('home_title', 'home_description')");
    $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    return [
        'home_title' => $rows['home_title'] ?? 'Selamat Datang di Alfi Kitchen',
        'home_description' => $rows['home_description'] ?? 'Puding lembut berlapis buah, Dessert sehat dalam kemasan praktis, dan Salad buah bersaus creamy — semua dibuat rumahan dari bahan pilihan, siap menemani hari-harimu.',
    ];
}

/**
 * Ambil semua produk (kategori utama) urut berdasarkan id ASC.
 *
 * @param PDO $pdo
 * @return array
 */
function get_products(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT * FROM products ORDER BY id ASC");
    return $stmt->fetchAll();
}

/**
 * Ambil produk berdasarkan ID.
 *
 * @param PDO $pdo
 * @param int $id
 * @return array|false
 */
function get_product_by_id(PDO $pdo, int $id)
{
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Ambil produk berdasarkan slug.
 *
 * @param PDO $pdo
 * @param string $slug
 * @return array|false
 */
function get_product_by_slug(PDO $pdo, string $slug)
{
    $stmt = $pdo->prepare("SELECT * FROM products WHERE slug = ?");
    $stmt->execute([$slug]);
    return $stmt->fetch();
}

/**
 * Ambil semua gambar hero (slider) urut berdasarkan id DESC.
 *
 * @param PDO $pdo
 * @return array
 */
function get_hero_images(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT * FROM hero_images ORDER BY id DESC");
    return $stmt->fetchAll();
}

/**
 * Ambil semua item/varian produk berdasarkan product_id.
 *
 * @param PDO $pdo
 * @param int $productId
 * @return array
 */
function get_product_items(PDO $pdo, int $productId): array
{
    $stmt = $pdo->prepare("SELECT * FROM product_items WHERE product_id = ? ORDER BY id DESC");
    $stmt->execute([$productId]);
    return $stmt->fetchAll();
}

/**
 * Ambil item/varian produk berdasarkan ID.
 *
 * @param PDO $pdo
 * @param int $id
 * @return array|false
 */
function get_product_item_by_id(PDO $pdo, int $id)
{
    $stmt = $pdo->prepare("SELECT * FROM product_items WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Hapus gambar file dari folder uploads dengan validasi path agar aman.
 *
 * Path relatif disimpan di database (contoh: 'uploads/abc.jpg') dan selalu
 * relatif terhadap ROOT PROJECT - bukan terhadap folder admin/.
 *
 * @param string $relativePath Path relatif dari root project (contoh: 'uploads/abc.jpg')
 * @return void
 */
if (!function_exists('delete_uploaded_image')) {
    function delete_uploaded_image($relativePath): void
    {
        if (!is_string($relativePath) || $relativePath === '') {
            return;
        }

        // Normalisasi separator windows/backslash agar konsisten.
        $relativePath = str_replace('\\', '/', $relativePath);

        // Hanya file di dalam folder uploads/ yang boleh dihapus.
        if (strpos($relativePath, 'uploads/') !== 0) {
            return;
        }

        // Resolve aman: menolak traversal, path absolut, dan file di luar root project.
        $target = resolve_project_path($relativePath);
        if ($target === null) {
            return;
        }

        $uploadDirectory = realpath(uploads_directory());

        if (
            $uploadDirectory
            && strpos($target, $uploadDirectory . DIRECTORY_SEPARATOR) === 0
            && is_file($target)
        ) {
            @unlink($target);
        }
    }
}