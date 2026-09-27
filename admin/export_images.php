<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$dirPath = '../uploads';
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
