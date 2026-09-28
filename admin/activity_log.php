<?php
require 'auth.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

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
    <style>
        :root { --accent: #e8935a; --bg: #fff8ef; --text: #4a3728; --line: #f0ddc0; }
        body { font-family: Arial, sans-serif; background: var(--bg); margin: 0; color: var(--text); }
        header { background: var(--text); color: white; padding: 15px 24px; display: flex; justify-content: space-between; align-items: center; gap: 16px; }
        header h1 { margin: 0; font-size: 1.3rem; }
        header a { color: #f4b17d; text-decoration: none; font-weight: bold; }
        main { max-width: 1100px; margin: 32px auto; padding: 0 20px; }
        .summary { margin: 0 0 16px; color: #716052; }
        .table-wrap { overflow-x: auto; background: white; border: 1px solid var(--line); border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; min-width: 650px; }
        th, td { padding: 12px 14px; text-align: left; vertical-align: top; border-bottom: 1px solid var(--line); }
        th { background: #fff1de; }
        tr:last-child td { border-bottom: 0; }
        td:first-child { white-space: nowrap; }
        td:nth-child(2) { font-weight: bold; white-space: nowrap; }
        td:last-child { overflow-wrap: anywhere; }
        .empty { padding: 28px; text-align: center; color: #716052; }
        @media (max-width: 600px) { header { padding: 14px 18px; } main { margin: 20px auto; padding: 0 12px; } }
    </style>
</head>
<body>
    <header>
        <h1>Log Aktivitas Admin</h1>
        <nav><a href="index.php">Dashboard</a> &nbsp; <a href="logout.php">Logout</a></nav>
    </header>
    <main>
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
    </main>
</body>
</html>