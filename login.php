<?php
date_default_timezone_set('Asia/Jakarta');
session_set_cookie_params([
    'lifetime' => 1800,
    'path' => '/',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();
// SEO: halaman login tidak boleh diindeks mesin pencari
header('X-Robots-Tag: noindex, nofollow, noarchive');
require 'config.php';
require 'admin/login_rate_limit.php';

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin/index.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $now = time();

    if (!hash_equals($_SESSION['csrf_token'] ?? '', is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '')) {
        $error = 'Sesi login tidak valid. Silakan muat ulang halaman dan coba lagi.';
    } else {
        $user = is_string($_POST['username'] ?? null) ? trim($_POST['username']) : '';
        $pass = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $attemptKeys = login_attempt_keys($user, $ip);

        try {
            $lockedFor = login_lockout_remaining($pdo, $attemptKeys, $now);

            if ($lockedFor > 0) {
                $remaining = max(1, ceil($lockedFor / 60));
                $error = "Terlalu banyak percobaan gagal. Silakan tunggu {$remaining} menit sebelum mencoba lagi.";
            } else {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
                $stmt->execute([$user]);
                $admin = $stmt->fetch();

                if ($admin && password_verify($pass, $admin['password'])) {
                    clear_login_attempts($pdo, [$attemptKeys['account']]);
                    session_regenerate_id(true);
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['username'] = $user;
                    $_SESSION['last_activity'] = time();
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                    $logFile = __DIR__ . '/logs/admin_activity.log';
                    $logDir = dirname($logFile);
                    if (!is_dir($logDir)) {
                        mkdir($logDir, 0777, true);
                    }
                    $logEntry = date('Y-m-d H:i:s') . ' | LOGIN_SUCCESS | username=' . $user . ' | ip=' . $ip . PHP_EOL;
                    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

                    header('Location: admin/index.php');
                    exit;
                }

                $lockedFor = record_login_failure($pdo, $attemptKeys, $now);
                if ($lockedFor > 0) {
                    $remaining = max(1, ceil($lockedFor / 60));
                    $error = "Terlalu banyak percobaan gagal. Silakan tunggu {$remaining} menit sebelum mencoba lagi.";
                } else {
                    $error = 'Username atau password salah.';
                }
            }
        } catch (PDOException $exception) {
            error_log('Login rate limiter failure: ' . $exception->getMessage());
            $error = 'Layanan login sementara tidak tersedia. Silakan coba lagi nanti.';
        }
    }
}

if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    $error = 'Sesi admin Anda telah habis karena terlalu lama tidak aktif. Silakan login kembali.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Login Admin - Alfi Kitchen</title>
    <style>
        body { font-family: Arial; background: #fff8ef; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 300px; text-align: center; }
        .login-box h2 { color: #4a3728; margin-bottom: 20px; }
        .login-box input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #f0ddc0; border-radius: 5px; box-sizing: border-box; }
        .login-box button { width: 100%; padding: 10px; background: #e8935a; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; }
        .login-box button:hover { background: #d78249; }
        .error { color: red; font-size: 0.9em; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Login Admin</h2>
        <?php if ($error) echo "<div class='error'>$error</div>"; ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Masuk</button>
        </form>
    </div>
</body>
</html>
