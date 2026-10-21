<?php
/**
 * Alfi Kitchen — Contoh Konfigurasi Standar (config.example.php)
 *
 * Salin file ini menjadi "config.php" jika Anda tidak menggunakan sistem .env:
 * cp config.example.php config.php
 */

date_default_timezone_set('Asia/Jakarta');

// Muat .env jika tersedia
$envLoaderPath = __DIR__ . '/includes/env_loader.php';
if (is_file($envLoaderPath)) {
    require_once $envLoaderPath;
    if (function_exists('load_env')) {
        load_env(__DIR__ . '/.env');
    }
}

// URL resmi situs
if (!defined('SITE_URL')) {
    define('SITE_URL', function_exists('env') ? env('SITE_URL', 'http://localhost/Alfi_Kitchen') : 'http://localhost/Alfi_Kitchen');
}

// Kredensial Database
$host     = function_exists('env') ? env('DB_HOST', 'localhost') : 'localhost';
$dbname   = function_exists('env') ? env('DB_NAME', 'alfi_kitchen') : 'alfi_kitchen';
$username = function_exists('env') ? env('DB_USER', 'root') : 'root';
$password = function_exists('env') ? env('DB_PASS', '') : '';

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Koneksi database gagal. Server sedang sibuk, silakan coba beberapa saat lagi.");
}
