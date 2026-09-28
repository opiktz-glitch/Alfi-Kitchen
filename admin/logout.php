<?php
date_default_timezone_set('Asia/Jakarta');
session_start();

$logFile = __DIR__ . '/../logs/admin_activity.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}
$logEntry = date('Y-m-d H:i:s') . ' | LOGOUT | username=' . ($_SESSION['username'] ?? 'unknown') . ' | ip=' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . PHP_EOL;
file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

session_unset();
session_destroy();
header('Location: ../login.php');
exit;
?>
