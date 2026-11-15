<?php
require 'auth_check.php';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/helpers/backup_helper.php';
require_once __DIR__ . '/../includes/DatabaseMigrator.php';


// Proteksi rate-limit cooldown: cegah server overload di InfinityFree (jeda minimal 60 detik)
$remainingWait = check_backup_cooldown('database', 60);
if ($remainingWait !== null) {
    header('Location: settings.php?error=backup_cooldown&type=database&wait=' . $remainingWait);
    exit;
}

log_admin_action('EXPORT_DATABASE_BACKUP', 'status=success');

// Dapatkan daftar migrasi yang diterapkan untuk header metadata skema
$migrator = new DatabaseMigrator($pdo);
$appliedMigrations = array_keys($migrator->getAppliedMigrations());

// Dapatkan semua nama tabel dengan urutan yang benar (Parent sebelum Child)
$tables = [
    'users',
    'login_attempts',
    'products',
    'product_items',
    'hero_images',
    'settings',
    'schema_migrations',
    'hpp_ingredients',
    'hpp_recipes',
    'hpp_recipe_lines',
    'hpp_recipe_costs',
];

$sqlScript = generate_backup_header($appliedMigrations) . "\n\n";
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
