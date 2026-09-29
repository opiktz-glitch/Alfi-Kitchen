<?php
/**
 * Helper SEO: URL absolut selalu memakai domain resmi (SITE_URL).
 * - SITE_URL didefinisikan di config.php (tidak ikut deploy, beda lokal vs hosting).
 * - Fallback dinamis hanya dipakai kalau SITE_URL belum di-set (mis. robots.php
 *   yang tidak load config.php, atau CLI tanpa $_SERVER).
 */

if (!function_exists('site_base_url')) {
    function site_base_url(): string
    {
        // 1. Domain resmi dari config.php (tidak ikut deploy, beda lokal vs hosting).
        if (defined('SITE_URL') && is_string(SITE_URL) && SITE_URL !== '') {
            return rtrim(SITE_URL, '/');
        }

        // 2. Fallback: host lokal (dev) boleh dinamis, host lain kunci ke domain resmi.
        //    Ini penting karena config.php production di-maintain manual di server
        //    (di-exclude dari deploy) sehingga SITE_URL bisa belum ada di sana,
        //    dan karena robots.php tidak me-load config.php sama sekali.
        $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
        // Cegah Host header injection: hanya izinkan karakter host yang valid.
        if (!preg_match('/^[A-Za-z0-9.\-]+(:\d{1,5})?$/', $host)) {
            $host = $_SERVER['SERVER_NAME'] ?? 'localhost';
        }
        $hostLower = strtolower(preg_replace('/:\d+$/', '', $host));
        if ($hostLower !== 'localhost' && $hostLower !== '127.0.0.1' && $hostLower !== '::1') {
            return 'https://kitchen.pojokberkah.online';
        }

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? '') == 443)
            || (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        $scheme = $isHttps ? 'https' : 'http';

        // Semua halaman publik berada di root project.
        $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        $dir = rtrim($dir, '/');

        return $scheme . '://' . $host . $dir;
    }
}

if (!function_exists('site_url')) {
    function site_url(string $path = ''): string
    {
        return site_base_url() . '/' . ltrim($path, '/');
    }
}

/**
 * Bangun meta description unik per halaman produk (maks ~160 karakter).
 * Prioritas: deskripsi item termurah/mewakili + rentang harga + ajakan WA.
 * Fallback ke format lama bila tidak ada data item.
 */
if (!function_exists('build_product_description')) {
    function build_product_description(string $productName, array $items): string
    {
        $productName = trim($productName);
        if (empty($items)) {
            return 'Lihat pilihan ' . $productName . ' dari Alfi Kitchen. Tanyakan ketersediaan, harga, dan pengiriman langsung melalui WhatsApp.';
        }

        // Kumpulkan harga valid & deskripsi pertama yang terisi
        $prices = [];
        $sampleDesc = '';
        $itemNames = [];
        foreach ($items as $it) {
            $price = (float) ($it['price'] ?? 0);
            if ($price > 0) {
                $prices[] = $price;
            }
            if ($sampleDesc === '' && !empty($it['description'])) {
                $sampleDesc = trim(preg_replace('/\s+/', ' ', (string) $it['description']));
            }
            if (!empty($it['name'])) {
                $itemNames[] = trim((string) $it['name']);
            }
        }

        $count = count($items);
        $pricePart = '';
        if (!empty($prices)) {
            $min = min($prices);
            $max = max($prices);
            if (count($prices) > 1 && $min !== $max) {
                $pricePart = ' Mulai Rp ' . number_format($min, 0, ',', '.') . '–Rp ' . number_format($max, 0, ',', '.') . '.';
            } else {
                $pricePart = ' Harga Rp ' . number_format($min, 0, ',', '.') . '.';
            }
        }

        // Contoh: "Dessert Alfi Kitchen. 2 varian: Classic Tiramisu, Cendol. ..."
        $variantPart = '';
        if (!empty($itemNames)) {
            $shown = array_slice($itemNames, 0, 3);
            $variantPart = ' ' . $count . ' varian: ' . implode(', ', $shown);
            if ($count > 3) {
                $variantPart .= ', dll.';
            } else {
                $variantPart .= '.';
            }
        }

        $desc = $productName . ' Alfi Kitchen.' . $variantPart;
        if ($sampleDesc !== '') {
            $desc .= ' ' . $sampleDesc;
        }
        $desc .= $pricePart . ' Pesan via WhatsApp.';

        // Potong rapi di batas kata, maks 160 karakter
        $desc = trim(preg_replace('/\s+/', ' ', $desc));
        if (mb_strlen($desc, 'UTF-8') > 160) {
            $cut = mb_substr($desc, 0, 157, 'UTF-8');
            $lastSpace = mb_strrpos($cut, ' ', 0, 'UTF-8');
            if ($lastSpace !== false && $lastSpace > 100) {
                $cut = mb_substr($cut, 0, $lastSpace, 'UTF-8');
            }
            $desc = rtrim($cut, " \t\n\r\0\x0B,.") . '...';
        }

        return $desc;
    }
}

/**
 * Ubah teks menjadi slug URL yang aman (huruf kecil, angka, tanda hubung).
 * Contoh: "Puding Buah Spesial" -> "puding-buah-spesial"
 */
if (!function_exists('slugify_text')) {
    function slugify_text(string $text): string
    {
        $text = strtolower(trim($text));
        // Huruf non-ASCII (á, é, dll) dinormalkan dulu jika ext-intl tersedia
        if (class_exists('Normalizer')) {
            $normalized = Normalizer::normalize($text, Normalizer::FORM_KD);
            if (is_string($normalized)) {
                $text = $normalized;
            }
        }
        $text = preg_replace('/[\x80-\xff]/', '', $text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim((string) $text, '-');

        return substr($text, 0, 100);
    }
}

/**
 * Buat slug unik dari nama produk, menghindari bentrok dengan produk lain.
 * $excludeId = 0 untuk produk baru; isi id produk saat regenerasi (edit).
 */
if (!function_exists('generate_unique_product_slug')) {
    function generate_unique_product_slug(PDO $pdo, string $name, int $excludeId = 0): string
    {
        $slug = slugify_text($name);
        if ($slug === '') {
            return $slug; // kosong; pemanggil menentukan fallback (mis. produk-{id})
        }

        $base = $slug;
        $n = 2;
        $stmt = $pdo->prepare("SELECT 1 FROM products WHERE slug = ? AND id <> ?");
        while (true) {
            $stmt->execute([$slug, $excludeId]);
            if (!$stmt->fetchColumn()) {
                break;
            }
            $slug = $base . '-' . $n;
            $n++;
        }

        return $slug;
    }
}

/**
 * Ambil slug produk; jika belum ada (mis. setelah migrasi), buat dari nama
 * dan simpan ke database. Mengembalikan slug yang bisa dipakai di URL.
 */
if (!function_exists('ensure_product_slug')) {
    function ensure_product_slug(PDO $pdo, array $product): string
    {
        $id = (int) ($product['id'] ?? 0);
        $slug = (string) ($product['slug'] ?? '');
        if ($slug !== '') {
            return $slug;
        }

        $slug = generate_unique_product_slug($pdo, (string) ($product['name'] ?? ''), $id);
        if ($slug === '') {
            $slug = 'produk-' . $id;
        }

        try {
            $upd = $pdo->prepare("UPDATE products SET slug = ? WHERE id = ?");
            $upd->execute([$slug, $id]);
        } catch (PDOException $e) {
            // Kolom slug belum dimigrasi / bentrok serentak: pakai slug sementara
            return $slug . '-' . $id;
        }

        return $slug;
    }
}
