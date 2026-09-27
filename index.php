<?php 
require 'config.php';

// Fetch products from database
$stmt = $pdo->query("SELECT * FROM products ORDER BY id ASC");
$products = $stmt->fetchAll();

// Fetch hero images
$stmt2 = $pdo->query("SELECT * FROM hero_images ORDER BY id DESC");
$hero_images = $stmt2->fetchAll();

require 'views/home.view.php';
