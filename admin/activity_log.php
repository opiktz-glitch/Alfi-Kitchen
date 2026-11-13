<?php
require 'auth_check.php';
require 'csrf_check.php';

$message = '';
$messageClass = 'alert alert-success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_log'])) {
    if (clear_admin_activity_log()) {
        $message = "Log aktivitas berhasil dibersihkan.";
    } else {
        $message = "Gagal membersihkan log aktivitas.";
        $messageClass = 'alert alert-error';
    }
}

$logFile = __DIR__ . '/../logs/admin_activity.log';
$logs = [];
$maxEntries = 500;

if (is_readable($logFile)) {
    $handle = fopen($logFile, 'rb');
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if ($line !== '') {
                $logs[] = $line;
                if (count($logs) > $maxEntries) {
                    array_shift($logs);
                }
            }
        }
        fclose($handle);
    }
}

$logs = array_reverse($logs);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log Aktivitas Admin - Alfi Kitchen</title>
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h2>Log Aktivitas Admin</h2>
                <div class="admin-header-actions">
                    <a href="../" target="_blank" rel="noopener noreferrer" class="btn-site-preview"><?= admin_icon('external') ?>Lihat Website</a>
                    <a href="logout.php" class="btn btn-danger btn-with-icon"><?= admin_icon('logout') ?>Logout</a>
                </div>
            </header>
            <div class="admin-container">
        <?php if ($message): ?>
            <div class="<?= $messageClass ?> admin-flash"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="activity-toolbar">
            <p class="activity-summary">Menampilkan <?= count($logs) ?> aktivitas terbaru (maksimal <?= $maxEntries ?> entri).</p>
            <?php if (!empty($logs)): ?>
                <form method="POST" class="activity-clear-form" data-submit-lock onsubmit="return confirm('Hapus seluruh riwayat log aktivitas? Tindakan ini tidak dapat dibatalkan.');">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                    <input type="hidden" name="clear_log" value="1">
                    <button type="submit" class="btn btn-danger btn-with-icon"><?= admin_icon('delete') ?>Bersihkan Log</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="table-wrap table-responsive">
            <table>
                <thead>
                    <tr><th>Waktu</th><th>Event</th><th>Detail</th></tr>
                </thead>
                <tbody>
                    <?php if (!$logs): ?>
                        <tr><td colspan="3" class="empty">Belum ada aktivitas yang tercatat.</td></tr>
                    <?php else: ?>
                        <?php foreach ($logs as $line): ?>
                            <?php $parts = explode(' | ', $line, 3); ?>
                            <tr>
                                <td><?= htmlspecialchars($parts[0] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($parts[1] ?? 'LOG', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($parts[2] ?? $line, ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
        </div>
    </div>
</body>
</html>