<?php
require 'auth_check.php';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/helpers/db_helper.php';
require_once __DIR__ . '/helpers/backup_helper.php';
require 'csrf_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: settings.php?tab=tab-database');
    exit;
}

if (!isset($_FILES['images_zip']) || !is_array($_FILES['images_zip']) || ($_FILES['images_zip']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    set_admin_flash('Harap pilih file backup gambar (.zip) yang valid.', 'error');
    header('Location: settings.php?tab=tab-database');
    exit;
}

$file = $_FILES['images_zip'];
$fileName = (string) ($file['name'] ?? '');
$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if ($ext !== 'zip') {
    set_admin_flash('Format file tidak didukung. Hanya file arsip berekstensi .zip yang dapat dipulihkan.', 'error');
    header('Location: settings.php?tab=tab-database');
    exit;
}

$fileSize = (int) ($file['size'] ?? 0);
if ($fileSize <= 0 || $fileSize > 50 * 1024 * 1024) {
    set_admin_flash('Ukuran file arsip tidak valid atau melebihi batas maksimal (50 MB).', 'error');
    header('Location: settings.php?tab=tab-database');
    exit;
}

$tmpPath = (string) ($file['tmp_name'] ?? '');
if (!is_uploaded_file($tmpPath)) {
    set_admin_flash('File unggahan tidak valid.', 'error');
    header('Location: settings.php?tab=tab-database');
    exit;
}

$uploadsDir = uploads_directory();
$result = extract_images_archive($tmpPath, $uploadsDir);

if (!$result['success']) {
    log_admin_action('RESTORE_IMAGES_FAILED', 'error=' . ($result['error'] ?? 'unknown') . ', file=' . basename($fileName));
    set_admin_flash('Gagal memulihkan gambar: ' . ($result['error'] ?? 'Terjadi kesalahan tidak diketahui.'), 'error');
    header('Location: settings.php?tab=tab-database');
    exit;
}

log_admin_action('RESTORE_IMAGES_SUCCESS', 'extracted=' . $result['extracted_count'] . ', skipped=' . $result['skipped_count'] . ', file=' . basename($fileName));

$msg = 'Berhasil memulihkan ' . $result['extracted_count'] . ' gambar ke folder uploads.';
if ($result['skipped_count'] > 0) {
    $msg .= ' (' . $result['skipped_count'] . ' entri bukan gambar diabaikan demi keamanan).';
}
set_admin_flash($msg);

header('Location: settings.php?tab=tab-database');
exit;
