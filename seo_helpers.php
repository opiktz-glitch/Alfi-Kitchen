<?php
/**
 * Helper SEO: membangun URL absolut situs secara dinamis
 * (berfungsi di localhost/Alfi_Kitchen maupun di domain production).
 */

if (!function_exists('site_base_url')) {
    function site_base_url(): string
    {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? '') == 443)
            || (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        $scheme = $isHttps ? 'https' : 'http';

        $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
        // Cegah Host header injection: hanya izinkan karakter host yang valid.
        if (!preg_match('/^[A-Za-z0-9.\-]+(:\d{1,5})?$/', $host)) {
            $host = $_SERVER['SERVER_NAME'] ?? 'localhost';
        }

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
