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

function admin_icon(string $name): string {
    $paths = [
        'manage' => '<path d="M4 6h16M4 12h16M4 18h16M8 4v4m8 2v4m-8 2v4"/>',
        'edit' => '<path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/>',
        'delete' => '<path d="M4 7h16M10 11v6m4-6v6M5 7l1 14h12l1-14M9 7V4h6v3"/>',
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
