<?php
/**
 * Alfi Kitchen — Lightweight Environment (.env) Loader
 *
 * Membaca file konfigurasi lingkungan (.env) tanpa ketergantungan library Composer eksternal.
 * Mendukung format KEY=VALUE, komentar (#), pembersihan kutipan string,
 * dan penyediaan helper global env().
 */

if (!function_exists('load_env')) {
    /**
     * Muat file .env ke dalam $_ENV, $_SERVER, dan getenv().
     *
     * @param string $path Path absolut atau relatif ke file .env
     * @return bool True jika file berhasil dimuat, False jika tidak ada/tidak dapat dibaca
     */
    function load_env(string $path): bool
    {
        if (!is_file($path) || !is_readable($path)) {
            return false;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return false;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Lewati baris kosong atau komentar
            if ($line === '' || $line[0] === '#') {
                continue;
            }

            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $key = trim($parts[0]);
            $val = trim($parts[1]);

            // Hapus kutipan pembungkus jika ada (contoh: "nilai" atau 'nilai')
            if (strlen($val) >= 2) {
                $first = $val[0];
                $last = substr($val, -1);
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $val = substr($val, 1, -1);
                }
            }

            // Dukungan variabel kosong
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $val;
            }
            if (!array_key_exists($key, $_SERVER)) {
                $_SERVER[$key] = $val;
            }
            if (function_exists('putenv')) {
                @putenv("$key=$val");
            }
        }

        return true;
    }
}

if (!function_exists('env')) {
    /**
     * Ambil nilai environment variable dengan nilai fallback default.
     *
     * @param string $key Nama variable
     * @param mixed $default Nilai default jika key tidak ditemukan
     * @return mixed
     */
    function env(string $key, $default = null)
    {
        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }
        if (array_key_exists($key, $_SERVER)) {
            return $_SERVER[$key];
        }
        $val = function_exists('getenv') ? getenv($key) : false;
        if ($val !== false) {
            return $val;
        }
        return $default;
    }
}
