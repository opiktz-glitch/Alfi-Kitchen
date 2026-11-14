<?php
date_default_timezone_set('Asia/Jakarta');
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? 0) == 443)
        || (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_set_cookie_params([
        'lifetime' => 1800,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// SEO: halaman admin tidak boleh diindeks mesin pencari
if (!headers_sent()) {
    header('X-Robots-Tag: noindex, nofollow, noarchive');
}

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token ?? '');
}

function set_admin_flash(string $message, string $type = 'success'): void {
    if (!in_array($type, ['success', 'error'], true)) {
        throw new InvalidArgumentException('Jenis pesan admin tidak valid.');
    }

    $_SESSION['admin_flash'] = [
        'message' => $message,
        'type' => $type,
    ];
}

/**
 * @return array{message: string, type: string}|null
 */
function consume_admin_flash(): ?array {
    if (!isset($_SESSION['admin_flash']) || !is_array($_SESSION['admin_flash'])) {
        unset($_SESSION['admin_flash']);
        return null;
    }

    $flash = $_SESSION['admin_flash'];
    unset($_SESSION['admin_flash']);

    if (
        !isset($flash['message'], $flash['type'])
        || !is_string($flash['message'])
        || !is_string($flash['type'])
        || !in_array($flash['type'], ['success', 'error'], true)
    ) {
        return null;
    }

    return $flash;
}

if (!function_exists('format_decimal')) {
    /**
     * Format angka desimal: maksimal 2 angka di belakang koma, hilangkan ',00' jika bulat.
     */
    function format_decimal($number, int $maxDecimals = 2): string {
        if (!is_numeric($number)) {
            return '0';
        }
        $val = round((float) $number, $maxDecimals);
        $formatted = number_format($val, $maxDecimals, ',', '.');
        if (str_contains($formatted, ',')) {
            $formatted = rtrim(rtrim($formatted, '0'), ',');
        }
        return $formatted;
    }
}

if (!function_exists('format_rupiah')) {
    /**
     * Format mata uang rupiah: maksimal 2 desimal, tanpa ',00' jika bulat.
     */
    function format_rupiah($number, int $maxDecimals = 2, bool $withPrefix = true): string {
        $prefix = $withPrefix ? 'Rp' : '';
        return $prefix . format_decimal($number, $maxDecimals);
    }
}

function admin_icon(string $name): string {
    $paths = [
        'add' => '<path d="M12 5v14M5 12h14"/>',
        'back' => '<path d="m15 18-6-6 6-6M9 12h11"/>',
        'calculator' => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 7h8M8 12h2m4 0h2m-8 4h2m4 0h2"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
        'dashboard' => '<rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><rect x="14" y="14" width="6" height="6" rx="1"/>',
        'database' => '<ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v14c0 1.7 3.6 3 8 3s8-1.3 8-3V5M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3"/>',
        'manage' => '<path d="M4 6h16M4 12h16M4 18h16M8 4v4m8 2v4m-8 2v4"/>',
        'edit' => '<path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/>',
        'delete' => '<path d="M4 7h16M10 11v6m4-6v6M5 7l1 14h12l1-14M9 7V4h6v3"/>',
        'duplicate' => '<rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3"/>',
        'external' => '<path d="M14 3h7v7M10 14 21 3M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>',
        'image' => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/>',
        'info' => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4m0-4h.01"/>',
        'key' => '<circle cx="8" cy="15" r="5"/><path d="m21 2-9.6 9.6M15.5 7.5l3 3L22 7l-3-3"/>',
        'log' => '<path d="M9 6h11M9 12h11M9 18h11"/><path d="M4 6h.01M4 12h.01M4 18h.01"/>',
        'logout' => '<path d="M10 17l5-5-5-5M15 12H3"/><path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/>',
        'reset' => '<path d="M3 12a9 9 0 1 0 2.6-6.4L3 8"/><path d="M3 3v5h5"/>',
        'save' => '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8M7 3v5h8"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'settings' => '<path d="M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z"/><path d="m19.4 15 .1.1 1.4 1.1-1.4 2.4-1.7-.7a8 8 0 0 1-1.6.9l-.3 1.8h-2.8l-.3-1.8a8 8 0 0 1-1.6-.9l-1.7.7-1.4-2.4 1.4-1.1A7 7 0 0 1 7.3 13l-1.8-.3V10l1.8-.3a8 8 0 0 1 .8-1.7L6.7 6.6 8.1 4.2l1.7.7a8 8 0 0 1 1.6-.9l.3-1.8h2.8l.3 1.8a8 8 0 0 1 1.6.9l1.7-.7 1.4 2.4-1.4 1.1a8 8 0 0 1 .8 1.7l1.8.3v2.8l-1.8.3a8 8 0 0 1-.8 1.7Z"/>',
        'slider' => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9" r="1.5"/><path d="m21 15-5-5L5 20"/>',
        'upload' => '<path d="M12 16V4m-4 4 4-4 4 4"/><path d="M4 16v4a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-4"/>',
    ];

    if (!isset($paths[$name])) {
        throw new InvalidArgumentException('Ikon admin tidak dikenal.');
    }

    return '<svg class="admin-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . $paths[$name] . '</svg>';
}

/**
 * Catat aktivitas admin ke file log dengan rotasi otomatis jika > 2 MB.
 */
function log_admin_action($action, $details = '') {
    $logFile = __DIR__ . '/../logs/admin_activity.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }

    // Rotasi otomatis jika ukuran log melebihi 2 MB (hemat resource & disk)
    if (is_file($logFile) && filesize($logFile) > 2 * 1024 * 1024) {
        $archiveFile = $logDir . '/admin_activity_' . date('Y_m_d_His') . '.log';
        @rename($logFile, $archiveFile);
    }

    $username = $_SESSION['username'] ?? 'unknown';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $entry = date('Y-m-d H:i:s') . ' | ACTION | username=' . $username . ' | action=' . $action . ' | details=' . $details . ' | ip=' . $ip . PHP_EOL;
    file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}

/**
 * Kosongkan file log aktivitas admin.
 */
function clear_admin_activity_log(): bool {
    $logFile = __DIR__ . '/../logs/admin_activity.log';
    if (!is_file($logFile)) {
        return true;
    }
    $res = @file_put_contents($logFile, '', LOCK_EX) !== false;
    if ($res) {
        log_admin_action('CLEAR_ACTIVITY_LOG', 'status=success');
    }
    return $res;
}

/**
 * Periksa cooldown rate limit untuk operasi backup (Database / Gambar).
 *
 * Mencegah server overload / suspend di InfinityFree akibat penarikan data berulang.
 *
 * @param string $type Jenis backup ('database' atau 'images')
 * @param int $cooldownSeconds Durasi jeda dalam detik (default 60 detik)
 * @return int|null Mengembalikan sisa detik jika masih cooldown, atau null jika diizinkan
 */
function check_backup_cooldown(string $type, int $cooldownSeconds = 60): ?int {
    $key = 'last_backup_time_' . $type;
    $now = time();

    if (isset($_SESSION[$key])) {
        $elapsed = $now - (int)$_SESSION[$key];
        if ($elapsed < $cooldownSeconds) {
            return $cooldownSeconds - $elapsed;
        }
    }

    // Perbarui timestamp backup terakhir
    $_SESSION[$key] = $now;
    return null;
}

$admin_session_timeout = 1800;

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = time();
    }

    if ((time() - $_SESSION['last_activity']) > $admin_session_timeout) {
        session_unset();
        session_destroy();
        header('Location: ../login.php?timeout=1');
        exit;
    }

    $_SESSION['last_activity'] = time();
}

generate_csrf_token();
