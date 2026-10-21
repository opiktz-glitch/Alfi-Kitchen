<?php include 'header.php'; ?>

<!-- Tambahkan CSS Swiper -->
<link rel="preload" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css"></noscript>

  <div class="hero swiper mySwiper">
  <div class="swiper-wrapper">
      <?php if (!empty($hero_images)): ?>
          <?php foreach ($hero_images as $index => $img): ?>
              <div class="swiper-slide">
                  <img src="thumb.php?file=<?= urlencode($img['image']) ?>&w=800" alt="Promo produk Alfi Kitchen: puding, dessert, dan salad buah rumahan" width="800" height="450" <?= $index === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?> style="width: 100%; height: 100%; object-fit: cover; display: block;">
              </div>
          <?php endforeach; ?>
      <?php else: ?>
          <div class="swiper-slide">
              <img src="thumb.php?file=hero.jpg&w=800" alt="Puding, dessert, dan salad buah rumahan Alfi Kitchen" width="800" height="450" fetchpriority="high" style="width: 100%; height: 100%; object-fit: cover; display: block;">
          </div>
      <?php endif; ?>
  </div>
  
  <!-- Logo Melayang (Floating) -->
  <?php
  $logo_path = 'uploads/logo.png';
  $logo_file = __DIR__ . '/../' . $logo_path;
  // Pakai filemtime (bukan time()) agar logo tetap bisa di-cache browser/CDN
  $fallbackLogo = require __DIR__ . '/../includes/fallback_logo.php';
  $logo_src = file_exists($logo_file) ? 'thumb.php?file=' . urlencode($logo_path) . '&w=150&v=' . filemtime($logo_file) : $fallbackLogo;
  ?>
  <a href="index.php" class="floating-logo" title="Alfi Kitchen">
      <img src="<?= $logo_src ?>" width="75" height="75" alt="Alfi Kitchen Logo">
  </a>

  <!-- Jika ingin menambahkan tombol navigasi kecil (opsional, biarkan Swiper yang menangani) -->
  <div class="swiper-pagination"></div>
</div>

<main class="welcome-split">
  <div class="col-text">
    <h1><?= nl2br(htmlspecialchars($homeTitle, ENT_QUOTES, 'UTF-8')) ?></h1>
    <p><?= nl2br(htmlspecialchars($homeDescription, ENT_QUOTES, 'UTF-8')) ?></p>
  </div>
  <div class="col-product" id="menu">
    <h2 class="col-product-title">Produk Kami</h2>
    <div class="product-list">
      <?php foreach($products as $p): ?>
      <a href="produk/<?= htmlspecialchars(ensure_product_slug($pdo, $p), ENT_QUOTES, 'UTF-8') ?>" class="pl-item" style="text-decoration:none; color:inherit;">
        <?php if(!empty($p['image'])): ?>
            <img class="ph" src="thumb.php?file=<?= urlencode($p['image']) ?>&w=140" width="70" height="70" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy" decoding="async" style="object-fit:cover;">
        <?php else: ?>
            <div class="ph <?= htmlspecialchars($p['image_class'] ?? 'a') ?>"></div>
        <?php endif; ?>
        <div class="pl-info" style="font-weight:600; font-size:1.05rem;">
          <span><?= htmlspecialchars($p['name']) ?></span>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</main>

<!-- Tambahkan JS Swiper -->
<script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js" defer></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    var swiper = new Swiper(".mySwiper", {
        loop: true,
        grabCursor: true, // Ubah kursor jadi tangan saat di-hover
        autoplay: {
            delay: 5000,
            disableOnInteraction: false,
        },
        pagination: {
            el: ".swiper-pagination",
            clickable: true,
        },
        speed: 800 // Kecepatan transisi slide
    });
});
</script>

<?php include 'footer.php'; ?>
