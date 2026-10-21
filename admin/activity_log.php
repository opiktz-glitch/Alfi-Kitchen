<?php
require 'auth_check.php';

$logFile = __DIR__ . '/../logs/admin_activity.log';
$logs = [];
$maxEntries = 500;

if (is_readable($logFile)) {
    $handle = fopen($logFile, 'rb');
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            $logs[] = trim($line);
            if (count($logs) > $maxEntries) {
                array_shift($logs);
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
        <p class="summary">Menampilkan <?= count($logs) ?> aktivitas terbaru (maksimal <?= $maxEntries ?> entri).</p>
        <div class="table-wrap">
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