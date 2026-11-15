<?php
require 'auth_check.php';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/helpers/backup_helper.php';
require_once __DIR__ . '/../includes/DatabaseMigrator.php';
require 'csrf_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: settings.php?tab=tab-database');
    exit;
}

if (!isset($_FILES['backup_file']) || !is_array($_FILES['backup_file']) || ($_FILES['backup_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    set_admin_flash('Harap pilih file backup database (.sql) yang valid.', 'error');
    header('Location: settings.php?tab=tab-database');
    exit;
}

$file = $_FILES['backup_file'];
$fileName = (string) ($file['name'] ?? '');
$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if ($ext !== 'sql') {
    set_admin_flash('Format file tidak didukung. Hanya file berekstensi .sql yang dapat dipulihkan.', 'error');
    header('Location: settings.php?tab=tab-database');
    exit;
}

$fileSize = (int) ($file['size'] ?? 0);
if ($fileSize <= 0 || $fileSize > 10 * 1024 * 1024) {
    set_admin_flash('Ukuran file backup tidak valid atau melebihi batas maksimal (10 MB).', 'error');
    header('Location: settings.php?tab=tab-database');
    exit;
}

$tmpPath = (string) ($file['tmp_name'] ?? '');
if (!is_uploaded_file($tmpPath)) {
    set_admin_flash('File unggahan tidak valid.', 'error');
    header('Location: settings.php?tab=tab-database');
    exit;
}

$sqlContent = file_get_contents($tmpPath);
if ($sqlContent === false || trim($sqlContent) === '') {
    set_admin_flash('File backup kosong atau tidak dapat dibaca.', 'error');
    header('Location: settings.php?tab=tab-database');
    exit;
}

// 1. Ekstrak metadata dan periksa kesesuaian skema migrasi (Strict Enforcement)
$metadata = extract_sql_backup_metadata($sqlContent);
$migrator = new DatabaseMigrator($pdo);
$systemMigrations = array_keys($migrator->getAppliedMigrations());

$compat = verify_backup_schema_compatibility($metadata['migrations'], $systemMigrations);
if (!$compat['compatible']) {
    log_admin_action('RESTORE_DATABASE_REJECTED', 'reason=' . $compat['reason'] . ', file=' . basename($fileName));
    set_admin_flash('Restore database ditolak: ' . $compat['reason'], 'error');
    header('Location: settings.php?tab=tab-database');
    exit;
}

// 2. Split statement dan periksa whitelist keamanan tabel
$statements = split_sql_dump_statements($sqlContent);
$safety = validate_sql_dump_safety($statements);
if (!$safety['safe']) {
    log_admin_action('RESTORE_DATABASE_SAFETY_BLOCKED', 'reason=' . $safety['reason'] . ', file=' . basename($fileName));
    set_admin_flash('Restore database dibatalkan demi keamanan: ' . $safety['reason'], 'error');
    header('Location: settings.php?tab=tab-database');
    exit;
}

// 3. Simpan username admin yang sedang aktif untuk proteksi sesi (Anti-Lockout)
$currentAdminUser = $_SESSION['username'] ?? 'admin';

// 4. Eksekusi Restore Database
try {
    $executedCount = execute_database_restore($pdo, $statements);

    // 5. Anti-Lockout: Perbarui ID user sesi aktif dengan data user hasil restore
    $checkUser = $pdo->prepare("SELECT id, username FROM users WHERE username = ?");
    $checkUser->execute([$currentAdminUser]);
    $userRow = $checkUser->fetch(PDO::FETCH_ASSOC);
    if ($userRow) {
        $_SESSION['user_id'] = (int) $userRow['id'];
        $_SESSION['username'] = (string) $userRow['username'];
    }

    log_admin_action('RESTORE_DATABASE_SUCCESS', 'schema=' . ($compat['backup_latest'] ?? 'ok') . ', file=' . basename($fileName) . ', queries=' . $executedCount);
    set_admin_flash('Database berhasil dipulihkan dari file "' . htmlspecialchars(basename($fileName), ENT_QUOTES, 'UTF-8') . '" (Versi skema: ' . ($compat['backup_latest'] ?? 'v05_hpp.sql') . ', ' . $executedCount . ' perintah dieksekusi).');
} catch (Throwable $e) {
    log_admin_action('RESTORE_DATABASE_ERROR', 'error=' . $e->getMessage() . ', file=' . basename($fileName));
    set_admin_flash('Terjadi kegagalan saat mengeksekusi restore database: ' . $e->getMessage(), 'error');
}

header('Location: settings.php?tab=tab-database');
exit;
