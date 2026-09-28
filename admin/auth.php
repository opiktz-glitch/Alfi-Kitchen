<?php
date_default_timezone_set('Asia/Jakarta');
if (session_status() === PHP_SESSION_NONE) {
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

function log_admin_action($action, $details = '') {
    $logFile = __DIR__ . '/../logs/admin_activity.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }

    $username = $_SESSION['username'] ?? 'unknown';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $entry = date('Y-m-d H:i:s') . ' | ACTION | username=' . $username . ' | action=' . $action . ' | details=' . $details . ' | ip=' . $ip . PHP_EOL;
    file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
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
?>
