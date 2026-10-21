<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#fff8ef">
<title><?= htmlspecialchars($pageTitle ?? 'Alfi Kitchen — Landing Page', ENT_QUOTES, 'UTF-8') ?></title>
<meta name="description" content="<?= htmlspecialchars($pageDescription ?? 'Puding, dessert, dan salad buah buatan rumahan dari Alfi Kitchen. Pesan langsung melalui WhatsApp.', ENT_QUOTES, 'UTF-8') ?>">
<?php
require_once __DIR__ . '/seo_helpers.php';

// Nilai SEO bisa di-override per halaman sebelum include 'header.php':
// $canonicalPath (mis. 'product.php?id=3'), $pageImage (path/URL gambar), $ogType, $noindex
$seoTitle = $pageTitle ?? 'Alfi Kitchen — Landing Page';
$seoDescription = $pageDescription ?? 'Puding, dessert, dan salad buah buatan rumahan dari Alfi Kitchen. Pesan langsung melalui WhatsApp.';
$seoCanonical = site_url($canonicalPath ?? basename($_SERVER['SCRIPT_NAME'] ?? 'index.php'));
if (($canonicalPath ?? null) === null && basename($_SERVER['SCRIPT_NAME'] ?? '') === 'index.php') {
    $seoCanonical = site_url('');
}
$seoImage = $pageImage ?? (file_exists(__DIR__ . '/uploads/logo.png') ? 'uploads/logo.png' : 'hero.jpg');
if (!preg_match('#^https?://#i', $seoImage)) {
    $seoImage = site_url($seoImage);
}
// Dimensi + alt gambar share (WA/FB/Twitter butuh ini agar preview tidak pecah/kepotong)
$seoImageWidth = null;
$seoImageHeight = null;
$seoImageAlt = $seoTitle;
// Petakan URL gambar ke file lokal: buang base URL situs, sisanya path relatif dari root project
$__seoBase = rtrim(site_base_url(), '/');
$__seoImg = strtok($seoImage, '?#');
if (strpos($__seoImg, $__seoBase . '/') === 0) {
    $__seoRel = substr($__seoImg, strlen($__seoBase) + 1);
} else {
    $__seoRel = ltrim(preg_replace('#^https?://[^/]+/#', '', $__seoImg), '/');
    // Buang prefix subfolder instalasi (mis. Alfi_Kitchen/) bila ada
    $__seoBasePath = trim(parse_url($__seoBase, PHP_URL_PATH) ?? '', '/');
    if ($__seoBasePath !== '' && strpos($__seoRel, $__seoBasePath . '/') === 0) {
        $__seoRel = substr($__seoRel, strlen($__seoBasePath) + 1);
    }
}
$__seoFile = __DIR__ . '/' . $__seoRel;
if (is_file($__seoFile)) {
    $__seoSize = @getimagesize($__seoFile);
    if (is_array($__seoSize)) {
        $seoImageWidth = (int) $__seoSize[0];
        $seoImageHeight = (int) $__seoSize[1];
    }
}
unset($__seoBase, $__seoImg, $__seoRel, $__seoBasePath, $__seoFile, $__seoSize);
$seoType = $ogType ?? 'website';
?>
<base href="<?= htmlspecialchars(site_url(''), ENT_QUOTES, 'UTF-8') ?>">
<link rel="canonical" href="<?= htmlspecialchars($seoCanonical, ENT_QUOTES, 'UTF-8') ?>">
<?php if (!empty($noindex)): ?>
<meta name="robots" content="noindex, nofollow">
<?php else: ?>
<meta name="robots" content="index, follow, max-image-preview:large">
<?php endif; ?>
<meta property="og:site_name" content="Alfi Kitchen">
<meta property="og:locale" content="id_ID">
<meta property="og:type" content="<?= htmlspecialchars($seoType, ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:title" content="<?= htmlspecialchars($seoTitle, ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:description" content="<?= htmlspecialchars($seoDescription, ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:url" content="<?= htmlspecialchars($seoCanonical, ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:image" content="<?= htmlspecialchars($seoImage, ENT_QUOTES, 'UTF-8') ?>">
<?php if (!empty($seoImageWidth) && !empty($seoImageHeight)): ?>
<meta property="og:image:width" content="<?= (int) $seoImageWidth ?>">
<meta property="og:image:height" content="<?= (int) $seoImageHeight ?>">
<?php endif; ?>
<meta property="og:image:alt" content="<?= htmlspecialchars($seoImageAlt, ENT_QUOTES, 'UTF-8') ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= htmlspecialchars($seoTitle, ENT_QUOTES, 'UTF-8') ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($seoDescription, ENT_QUOTES, 'UTF-8') ?>">
<meta name="twitter:image" content="<?= htmlspecialchars($seoImage, ENT_QUOTES, 'UTF-8') ?>">
<?php if (!empty($jsonLd)): ?>
<script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<?php endif; ?>
<link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
<link rel="icon" href="favicon.ico" sizes="16x16 32x32 48x48">
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Playfair+Display:ital,wght@1,600&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Playfair+Display:ital,wght@1,600&display=swap"></noscript>
<?php if (!empty($preloadImage)): ?>
<link rel="preload" as="image" href="<?= htmlspecialchars($preloadImage, ENT_QUOTES, 'UTF-8') ?>" fetchpriority="high">
<?php endif; ?>
<style>
  :root{
    --bg:#fff8ef; --fg:#4a3728; --muted:#5c4a3d; --accent:#e8935a; --card:#fff1de; --line:#f0ddc0;
    box-sizing:border-box; padding-top:env(safe-area-inset-top,0px); padding-bottom:env(safe-area-inset-bottom,0px);
  }
  *{box-sizing:border-box; transition: all 0.2s ease;}
  html{scroll-padding-top:env(safe-area-inset-top,0px);}
  body{margin:0; background:var(--bg); color:var(--fg); font-family:'Inter', sans-serif; overflow-x:hidden;}

  header{
    position:absolute; top:16px; left:0; width:100%; z-index:10;
    display:none; /* Disembunyikan atas permintaan */
    align-items:center; justify-content: center;
    padding:16px 8vw; background:transparent; border-bottom:none;
  }
  /* Fix logo terpotong dan sesuaikan ukuran (2x lipat) */
  .logo-img{width:112px; height:112px; border-radius:50%; display:block; object-fit:cover; box-shadow: 0 4px 10px rgba(0,0,0,0.1); border: 2px solid var(--accent); padding: 2px;}
  .logo{font-weight:600; font-size:1.2rem; color:var(--fg); font-family: 'Playfair Display', serif; font-style: italic; text-align: center;}
  .logo-container:hover .logo-img{transform: scale(1.1) rotate(5deg); box-shadow: 0 6px 14px rgba(232, 147, 90, 0.4);}
  .logo-container:hover .logo{color: var(--accent);}

  /* Floating Logo di atas Slider */
  .floating-logo {
    position: absolute;
    top: 20px;
    left: 24px;
    z-index: 20;
    width: 75px; 
    height: 75px;
    border-radius: 50%;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    border: 2px solid rgba(255,255,255,0.8);
    display: block;
    background: #fff;
  }
  .floating-logo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
  .floating-logo:hover {
    transform: scale(1.1) rotate(5deg);
    box-shadow: 0 6px 20px rgba(232, 147, 90, 0.4);
    border-color: var(--accent);
  }
  
  /* Hero section */
  .hero{
    position: relative;
    display:block; /* Swiper butuh display default / block */
    width: calc(100% - 12vw) !important;
    height: min(50vh, 560px);
    margin: 32px auto 0 !important; /* Tambahkan sedikit margin atas agar tidak mentok tepi layar */
    border-radius: 24px;
    overflow: hidden;
    /* Hilangkan padding yang sebelumnya untuk konten teks */
  }
  
  /* Swiper Slide Container */
  .swiper-slide {
    background-color: var(--card);
  }

  /* Custom Pagination Bullets untuk Swiper */
  .swiper-pagination-bullet {
    background: #fff;
    opacity: 0.6;
  }
  .swiper-pagination-bullet-active {
    background: var(--accent);
    opacity: 1;
  }

  .stars{color:var(--accent); font-size:.85rem; margin-bottom:8px;}

  .ph{border-radius:14px; object-fit: cover;}
  .ph.a{background:linear-gradient(150deg,#ffca8f,#e8935a);}
  .ph.b{background:linear-gradient(150deg,#f0c9a0,#c98a52);}
  .ph.c{background:linear-gradient(150deg,#e8b27a,#a85c2e);}

  .welcome-split{
    display:grid; grid-template-columns:minmax(0, 1fr) minmax(0, 1fr); gap:24px; align-items:stretch;
    padding:clamp(24px, 4vw, 52px) 6vw clamp(36px, 6vw, 72px);
  }
  .welcome-split .col-text{
    display:flex; flex-direction:column; justify-content:center; align-items:flex-start;
    background:var(--card); border:1px solid var(--line); border-radius:16px; padding:28px;
    box-shadow: 0 10px 30px rgba(232, 147, 90, 0.05);
  }
  .welcome-split .col-text h1, .welcome-split .col-text h2{font-size:2.35rem; line-height:1.12; margin:0 0 14px; font-weight:800; text-align:left;}
  .welcome-split .col-text .stars{margin-bottom:10px;}
  .welcome-split .col-text p{color:var(--muted); font-size:1.02rem; margin:0; line-height:1.75; text-align:left; max-width:45ch;}
  
  .col-product{
    background:var(--card); border:1px solid var(--line); border-radius:16px; padding:28px;
    display:flex; flex-direction:column; justify-content:center;
    box-shadow: 0 10px 30px rgba(232, 147, 90, 0.05);
  }
  .col-product .col-product-title{margin:0 0 14px; font-size:1.2rem; font-weight:700; flex:0 0 auto;}
  .product-list{
    display:flex; flex-direction:column; gap:16px; 
    overflow-y:auto; flex:1; padding-right:10px;
    max-height:250px;
  }
  
  /* Custom Scrollbar untuk Produk Kami */
  .product-list::-webkit-scrollbar { width: 6px; }
  .product-list::-webkit-scrollbar-track { background: transparent; }
  .product-list::-webkit-scrollbar-thumb { background: transparent; border-radius: 10px; }
  
  /* Scrollbar muncul hanya ketika mouse melintas di kotak produk */
  .col-product:hover .product-list::-webkit-scrollbar-thumb { background: rgba(240, 221, 192, 0.8); }
  .product-list::-webkit-scrollbar-thumb:hover { background: var(--accent) !important; }
  
  /* Hover efek modern */
  .pl-item{display:flex; align-items:center; gap:16px; padding: 10px; border-radius: 16px; cursor: pointer;}
  .pl-item:hover {background: rgba(255,255,255,0.6); transform: translateX(5px);}
  .pl-item .ph{width:70px; height:70px; flex:0 0 auto; box-shadow: 0 4px 12px rgba(0,0,0,0.08);}
  .pl-info{display:flex; flex-direction:column; font-size:.95rem; font-weight: 600;}
  .pl-info .price{color:var(--accent); font-weight:800; font-size:.9rem; margin-top:4px;}

  @media (max-width:820px){
    .welcome-split{grid-template-columns:1fr; gap:18px; padding:28px 5vw 44px;}
    .welcome-split .col-text, .col-product{padding:24px;}
    .welcome-split .col-text h1, .welcome-split .col-text h2{font-size:2rem;}
    .hero{height: 45vh;}
  }

  @media (max-width:480px){
    .hero{height:42vh;}
    .welcome-split .col-text, .col-product{padding:20px;}
    .welcome-split .col-text h1, .welcome-split .col-text h2{font-size:1.75rem;}
    .welcome-split .col-text p{font-size:.98rem;}
    .pl-item .ph{width:60px; height:60px;}
  }

  footer{
    padding:30px 6vw; padding-bottom:calc(30px + env(safe-area-inset-bottom,0px));
    border-top:1px solid var(--line); color:var(--muted); font-size:.85rem;
    display:flex; justify-content:space-between; flex-wrap:wrap; gap:12px;
  }
  footer a{color:var(--fg); text-decoration:none; font-weight:600;}
  footer a:hover {color: var(--accent);}
</style>
</head>
<body>

<?php
$logo_path = 'uploads/logo.png';
$logo_file = __DIR__ . '/' . $logo_path;
// Pakai filemtime (bukan time()) agar logo tetap bisa di-cache browser/CDN
$fallbackLogo = require __DIR__ . '/includes/fallback_logo.php';
$logo_src = file_exists($logo_file) ? $logo_path . '?v=' . filemtime($logo_file) : $fallbackLogo;
?>
