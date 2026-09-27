<?php
session_start();
require '../config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

// Dapatkan semua nama tabel dengan urutan yang benar (Parent sebelum Child)
$tables = ['users', 'products', 'product_items', 'hero_images'];

$sqlScript = "-- Export Database Alfi Kitchen\n";
$sqlScript .= "-- Tanggal: " . date('Y-m-d H:i:s') . "\n\n";
$sqlScript .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

// Drop tabel dari Child ke Parent (Reverse)
$reverse_tables = array_reverse($tables);
foreach ($reverse_tables as $table) {
    $sqlScript .= "DROP TABLE IF EXISTS `$table`;\n";
}
$sqlScript .= "\n";

// Create dan Insert dari Parent ke Child
foreach ($tables as $table) {
    // Tambahkan perintah Buat Tabel
    $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
    $row = $stmt->fetch(PDO::FETCH_NUM);
    $sqlScript .= $row[1] . ";\n\n";
    
    // Tambahkan data
    $stmt = $pdo->query("SELECT * FROM `$table`");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($rows as $row) {
        $sqlScript .= "INSERT INTO `$table` VALUES(";
        $values = [];
        foreach ($row as $value) {
            if ($value === null) {
                $values[] = "NULL";
            } else {
                $values[] = $pdo->quote($value);
            }
        }
        $sqlScript .= implode(", ", $values) . ");\n";
    }
    $sqlScript .= "\n\n";
}

$sqlScript .= "SET FOREIGN_KEY_CHECKS = 1;\n";

// Download file otomatis
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="alfi_kitchen_backup_' . date('Ymd_His') . '.sql"');
header('Cache-Control: no-cache, no-store, must-revalidate'); 
header('Pragma: no-cache'); 
header('Expires: 0'); 

echo $sqlScript;
exit;
?>
