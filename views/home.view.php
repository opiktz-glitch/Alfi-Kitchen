<?php include 'header.php'; ?>

<style>
/* Hero Touch Slider (Native, Ultra-Lightweight & Zero Unused JS) */
.hero.swiper {
  position: relative;
  overflow: hidden;
  width: 100%;
  aspect-ratio: 16 / 9;
  max-height: 520px;
  background: #f7e6d2;
  touch-action: pan-y pinch-zoom;
  user-select: none;
  cursor: grab;
}
.hero.swiper:active {
  cursor: grabbing;
}
.hero .swiper-wrapper {
  display: flex;
  width: 100%;
  height: 100%;
  transition: transform 0.6s cubic-bezier(0.25, 1, 0.5, 1);
  will-change: transform;
}
.hero .swiper-slide {
  flex: 0 0 100%;
  width: 100%;
  height: 100%;
  position: relative;
}
.hero .swiper-pagination {
  position: absolute;
  bottom: 14px;
  left: 0;
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 8px;
  z-index: 10;
  pointer-events: auto;
}
.hero .swiper-pagination-bullet {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.6);
  border: 1px solid rgba(0, 0, 0, 0.15);
  box-shadow: 0 1px 3px rgba(0,0,0,0.25);
  cursor: pointer;
  transition: all 0.3s ease;
  padding: 0;
  margin: 0;
}
.hero .swiper-pagination-bullet-active {
  background: #ffffff;
  width: 24px;
  border-radius: 12px;
}
</style>

<div class="hero swiper mySwiper" id="heroSlider" aria-roledescription="carousel" aria-label="Slider Promo">
  <div class="swiper-wrapper" id="heroWrapper">
      <?php if (!empty($hero_images)): ?>
          <?php foreach ($hero_images as $index => $img): ?>
              <?php
              $heroFile = urlencode($img['image']);
              $heroSrc420 = 'thumb.php?file=' . $heroFile . '&w=420&q=65';
              $heroSrc640 = 'thumb.php?file=' . $heroFile . '&w=640&q=65';
              $heroSrc800 = 'thumb.php?file=' . $heroFile . '&w=800&q=68';
              ?>
              <div class="swiper-slide">
                  <img src="<?= $heroSrc640 ?>"
                       srcset="<?= $heroSrc420 ?> 420w, <?= $heroSrc640 ?> 640w, <?= $heroSrc800 ?> 800w"
                       sizes="(max-width: 480px) 100vw, (max-width: 768px) 640px, 800px"
                       alt="Promo produk Alfi Kitchen: puding, dessert, dan salad buah rumahan"
                       width="800" height="450"
                       <?= $index === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?>
                       decoding="async"
                       style="width: 100%; height: 100%; object-fit: cover; display: block;">
              </div>
          <?php endforeach; ?>
      <?php else: ?>
          <div class="swiper-slide">
              <img src="thumb.php?file=hero.jpg&w=640&q=65"
                   srcset="thumb.php?file=hero.jpg&w=420&q=65 420w, thumb.php?file=hero.jpg&w=640&q=65 640w, thumb.php?file=hero.jpg&w=800&q=68 800w"
                   sizes="(max-width: 480px) 100vw, (max-width: 768px) 640px, 800px"
                   alt="Puding, dessert, dan salad buah rumahan Alfi Kitchen"
                   width="800" height="450"
                   fetchpriority="high"
                   decoding="async"
                   style="width: 100%; height: 100%; object-fit: cover; display: block;">
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

  <!-- Navigasi pagination bullet -->
  <div class="swiper-pagination" id="heroPagination"></div>
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
            <img class="ph" src="thumb.php?file=<?= urlencode($p['image']) ?>&w=80&q=65" width="70" height="70" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy" decoding="async" style="object-fit:cover;">
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

<script>
document.addEventListener("DOMContentLoaded", function() {
    var slider = document.getElementById("heroSlider");
    var wrapper = document.getElementById("heroWrapper");
    var pagination = document.getElementById("heroPagination");
    if (!slider || !wrapper) return;

    var slides = wrapper.querySelectorAll(".swiper-slide");
    var total = slides.length;
    if (total <= 1) return;

    var currentIndex = 0;
    var startX = 0, currentX = 0, isDragging = false;
    var timer = null;

    if (pagination) {
        pagination.innerHTML = "";
        for (var i = 0; i < total; i++) {
            var dot = document.createElement("button");
            dot.className = "swiper-pagination-bullet" + (i === 0 ? " swiper-pagination-bullet-active" : "");
            dot.setAttribute("aria-label", "Slide " + (i + 1));
            dot.setAttribute("type", "button");
            (function(idx) {
                dot.addEventListener("click", function() {
                    goToSlide(idx);
                    resetTimer();
                });
            })(i);
            pagination.appendChild(dot);
        }
    }

    function updatePagination() {
        if (!pagination) return;
        var dots = pagination.querySelectorAll(".swiper-pagination-bullet");
        dots.forEach(function(dot, idx) {
            dot.classList.toggle("swiper-pagination-bullet-active", idx === currentIndex);
        });
    }

    function goToSlide(index) {
        currentIndex = (index + total) % total;
        wrapper.style.transition = "transform 0.6s cubic-bezier(0.25, 1, 0.5, 1)";
        wrapper.style.transform = "translateX(-" + (currentIndex * 100) + "%)";
        updatePagination();
    }

    function startTimer() {
        stopTimer();
        timer = setInterval(function() {
            goToSlide(currentIndex + 1);
        }, 5000);
    }

    function stopTimer() {
        if (timer) clearInterval(timer);
    }

    function resetTimer() {
        stopTimer();
        startTimer();
    }

    // Touch events
    slider.addEventListener("touchstart", function(e) {
        if (e.touches.length !== 1) return;
        isDragging = true;
        startX = e.touches[0].clientX;
        currentX = startX;
        stopTimer();
    }, { passive: true });

    slider.addEventListener("touchmove", function(e) {
        if (!isDragging || e.touches.length !== 1) return;
        currentX = e.touches[0].clientX;
    }, { passive: true });

    slider.addEventListener("touchend", function() {
        if (!isDragging) return;
        isDragging = false;
        var diff = currentX - startX;
        if (diff > 40) {
            goToSlide(currentIndex - 1);
        } else if (diff < -40) {
            goToSlide(currentIndex + 1);
        }
        startTimer();
    });

    // Mouse drag
    slider.addEventListener("mousedown", function(e) {
        isDragging = true;
        startX = e.clientX;
        currentX = startX;
        stopTimer();
    });

    window.addEventListener("mousemove", function(e) {
        if (!isDragging) return;
        currentX = e.clientX;
    });

    window.addEventListener("mouseup", function() {
        if (!isDragging) return;
        isDragging = false;
        var diff = currentX - startX;
        if (diff > 40) {
            goToSlide(currentIndex - 1);
        } else if (diff < -40) {
            goToSlide(currentIndex + 1);
        }
        startTimer();
    });

    slider.addEventListener("mouseenter", stopTimer);
    slider.addEventListener("mouseleave", startTimer);

    startTimer();
});
</script>

<?php include 'footer.php'; ?>
