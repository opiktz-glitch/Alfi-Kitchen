<?php 
require 'config.php';
$pageTitle = 'Puding, Dessert & Salad Buah Rumahan | Alfi Kitchen';
$pageDescription = 'Pesan puding, dessert, dan salad buah rumahan dari Alfi Kitchen. Dibuat dari bahan pilihan dan dapat dipesan langsung melalui WhatsApp.';

// Fetch products from database
$stmt = $pdo->query("SELECT * FROM products ORDER BY id ASC");
$products = $stmt->fetchAll();

// Fetch hero images
$stmt2 = $pdo->query("SELECT * FROM hero_images ORDER BY id DESC");
$hero_images = $stmt2->fetchAll();

$homeSettings = $pdo->query(
	"SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('home_title', 'home_description')"
)->fetchAll(PDO::FETCH_KEY_PAIR);
$homeTitle = $homeSettings['home_title'] ?? "Selamat Datang di\nAlfi Kitchen";
$homeDescription = $homeSettings['home_description'] ?? 'Puding lembut berlapis buah, Dessert sehat dalam kemasan praktis, dan Salad buah bersaus creamy — semua dibuat rumahan dari bahan pilihan, siap menemani hari-harimu.';

require 'views/home.view.php';
