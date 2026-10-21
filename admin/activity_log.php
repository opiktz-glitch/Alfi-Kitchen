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
    <header class="admin-header">
        <h1>Log Aktivitas Admin</h1>
        <nav><a href="index.php" class="btn btn-cancel">Dashboard</a> &nbsp; <a href="logout.php" class="btn btn-danger">Logout</a></nav>
    </header>
    <div class="admin-container">
        <?php if ($message): ?>
            <div class="<?= $messageClass ?>" style="margin-bottom: 16px;"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
            <p class="summary" style="margin: 0;">Menampilkan <?= count($logs) ?> aktivitas terbaru (maksimal <?= $maxEntries ?> entri).</p>
            <?php if (!empty($logs)): ?>
                <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mengosongkan seluruh riwayat log aktivitas?');" style="margin: 0;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                    <input type="hidden" name="clear_log" value="1">
                    <button type="submit" class="btn btn-danger" style="padding: 6px 14px; font-size: 13px;">🗑️ Bersihkan Log</button>
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
</body>
</html>