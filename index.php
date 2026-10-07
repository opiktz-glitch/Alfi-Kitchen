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
if (!empty($hero_images)) {
    $preloadImage = $hero_images[0]['image'];
}

$homeSettings = $pdo->query(
	"SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('home_title', 'home_description')"
)->fetchAll(PDO::FETCH_KEY_PAIR);
$homeTitle = $homeSettings['home_title'] ?? "Selamat Datang di\nAlfi Kitchen";
$homeDescription = $homeSettings['home_description'] ?? 'Puding lembut berlapis buah, Dessert sehat dalam kemasan praktis, dan Salad buah bersaus creamy — semua dibuat rumahan dari bahan pilihan, siap menemani hari-harimu.';

// SEO: structured data bisnis (JSON-LD)
require_once __DIR__ . '/seo_helpers.php';
$waStmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'whatsapp_number'");
$waStmt->execute();
$waDigits = preg_replace('/\D+/', '', (string) ($waStmt->fetchColumn() ?: ''));

$jsonLd = [
	'@context' => 'https://schema.org',
	'@type' => 'FoodEstablishment',
	'name' => 'Alfi Kitchen',
	'description' => $pageDescription,
	'url' => site_url(''),
	'image' => site_url(file_exists(__DIR__ . '/uploads/logo.png') ? 'uploads/logo.png' : 'hero.jpg'),
	'servesCuisine' => ['Puding', 'Dessert', 'Salad Buah'],
	'inLanguage' => 'id',
];
if ($waDigits !== '') {
	$jsonLd['telephone'] = '+' . $waDigits;
}

require 'views/home.view.php';
