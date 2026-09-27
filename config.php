<?php
// Konfigurasi Database (Lokal / XAMPP)
// Untuk production (InfinityFree), file ini dikonfigurasi manual langsung di server
// dan tidak akan ditimpa oleh GitHub Actions (sudah di-exclude di deploy.yml)

$host     = 'localhost';
$dbname   = 'alfi_kitchen';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Koneksi database gagal. Server sedang sibuk, silakan coba beberapa saat lagi.");
}
?>
