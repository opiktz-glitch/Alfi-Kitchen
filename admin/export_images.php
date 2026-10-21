<?php
require 'auth_check.php';
require_once __DIR__ . '/helpers/db_helper.php';


// Proteksi rate-limit cooldown: cegah server overload di InfinityFree (jeda minimal 60 detik)
$remainingWait = check_backup_cooldown('images', 60);
if ($remainingWait !== null) {
    header('Location: settings.php?error=backup_cooldown&type=images&wait=' . $remainingWait);
    exit;
}

log_admin_action('EXPORT_IMAGES_BACKUP', 'status=success');

$dirPath = uploads_directory();
$zipName = 'alfi_kitchen_images_' . date('Ymd_His') . '.zip';
$zipPath = sys_get_temp_dir() . '/' . $zipName;

if (!extension_loaded('zip')) {
    die("Error: Ekstensi ZIP tidak aktif di server ini.");
}

$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    die("Error: Tidak bisa membuat file ZIP.");
}

// Cek apakah folder uploads ada
if (is_dir($dirPath)) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dirPath),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $name => $file) {
        // Abaikan folder '.' dan '..'
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen(realpath($dirPath)) + 1);
            // Normalisasi separator agar entri ZIP konsisten antar-OS
            $relativePath = str_replace('\\', '/', $relativePath);
            $zip->addFile($filePath, $relativePath);
        }
    }
}

$zip->close();

if (file_exists($zipPath)) {
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipName . '"');
    header('Content-Length: ' . filesize($zipPath));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    readfile($zipPath);
    unlink($zipPath); // Hapus file zip sementara setelah didownload
    exit;
} else {
    die("Error: File ZIP gagal dibuat.");
}
?>
