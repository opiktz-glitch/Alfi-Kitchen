<?php
/**
 * Pemeriksaan login admin.
 * Di-include di setiap halaman admin yang membutuhkan autentikasi.
 * Jika belum login, arahkan ke halaman login.
 */
require 'auth.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}