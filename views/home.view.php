<?php include 'header.php'; ?>

<style>
.home-shell {
  max-width: 1120px;
  margin: 0 auto;
  padding: 0 32px;
}

/* Top Navbar Minimalis */
.home-nav {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px 0;
  flex-wrap: wrap;
  gap: 12px 24px;
}
.home-brand {
  font-family: 'Playfair Display', Georgia, serif;
  font-size: 26px;
  font-weight: 700;
  color: var(--fg);
  text-decoration: none;
  letter-spacing: -0.01em;
}
.home-nav-links {
  display: flex;
  align-items: center;
  gap: 28px;
  font-weight: 600;
  font-size: 15px;
}
.home-nav-links a {
  color: var(--fg);
  text-decoration: none;
}
.home-nav-links a:hover,
.home-nav-links a.home-nav-cta {
  color: var(--accent);
}
.home-nav-links a.home-nav-cta:hover {
  opacity: 0.85;
}

/* Hero Split (Opsi A — Krem Hangat) */
.home-hero-split {
  display: grid;
  grid-template-columns: minmax(0, 1.05fr) minmax(0, 1fr);
  gap: 48px;
  align-items: center;
  padding: 32px 0 64px;
}
.home-hero-copy {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 22px;
  min-width: 0;
}
.home-eyebrow {
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 0.08em;
  color: var(--accent);
  text-transform: uppercase;
}
.home-hero-copy h1 {
  margin: 0;
  font-family: 'Playfair Display', Georgia, serif;
  font-size: clamp(2.15rem, 4.8vw, 60px);
  line-height: 1.05;
  font-weight: 700;
  color: var(--fg);
}
.home-hero-copy p {
  margin: 0;
  font-size: clamp(1rem, 1.4vw, 18px);
  line-height: 1.7;
  color: var(--muted);
  max-width: 46ch;
}
.home-hero-actions {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}
.home-btn-primary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: var(--accent);
  color: #fff;
  padding: 15px 28px;
  border-radius: 999px;
  font-weight: 700;
  font-size: 15px;
  text-decoration: none;
  box-shadow: 0 8px 20px rgba(180, 80, 26, 0.22);
}
.home-btn-primary:hover {
  background: #9c4314;
  transform: translateY(-2px);
}
.home-btn-outline {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 2px solid var(--fg);
  color: var(--fg);
  padding: 13px 26px;
  border-radius: 999px;
  font-weight: 700;
  font-size: 15px;
  text-decoration: none;
  background: transparent;
}
.home-btn-outline:hover {
  background: var(--fg);
  color: var(--bg);
}

/* Right Hero Slider Frame + Floating Badge */
.home-hero-media {
  position: relative;
  min-width: 0;
}
.home-hero-media .hero.swiper {
  position: relative;
  overflow: hidden;
  width: 100% !important;
  height: 480px;
  max-height: none;
  margin: 0 !important;
  border-radius: 32px;
  background: #f4cf9f;
  touch-action: pan-y pinch-zoom;
  user-select: none;
  cursor: grab;
  box-shadow: 0 18px 40px rgba(59, 42, 30, 0.12);
}
.home-hero-media .hero.swiper:active {
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
.hero .swiper-slide img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  user-select: none;
  -webkit-user-drag: none;
}
.hero-nav-btn {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  z-index: 15;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: none;
  background: rgba(255, 255, 255, 0.85);
  color: var(--fg);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(59, 42, 30, 0.2);
  font-size: 22px;
  line-height: 1;
  padding: 0 0 2px;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.25s ease, background 0.2s ease, transform 0.2s ease;
}
.hero:hover .hero-nav-btn,
.hero:focus-within .hero-nav-btn {
  opacity: 1;
  pointer-events: auto;
}
.hero-nav-btn:hover {
  background: #fff;
  transform: translateY(-50%) scale(1.06);
}
.hero-nav-btn:focus-visible {
  outline: 2px solid var(--accent);
  outline-offset: 2px;
}
.hero-nav-prev { left: 12px; }
.hero-nav-next { right: 12px; }
.home-hero-media .floating-logo {
  top: 16px;
  left: 16px;
  width: 64px;
  height: 64px;
  border: 2px solid #fff;
  background: var(--accent);
  box-shadow: 0 4px 14px rgba(59, 42, 30, 0.25);
}
.hero .swiper-pagination {
  position: absolute;
  bottom: 16px;
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
  background: rgba(255, 255, 255, 0.7);
  border: none;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
  cursor: pointer;
  transition: all 0.3s ease;
  padding: 0;
  margin: 0;
}
.hero .swiper-pagination-bullet-active {
  background: #ffffff;
  width: 24px;
  border-radius: 4px;
}
.home-fresh-badge {
  position: absolute;
  left: -16px;
  bottom: 32px;
  z-index: 25;
  background: #fff;
  color: var(--fg);
  padding: 12px 20px;
  border-radius: 999px;
  font-family: 'Playfair Display', Georgia, serif;
  font-style: italic;
  font-size: 20px;
  box-shadow: 0 10px 26px rgba(59, 42, 30, 0.14);
  pointer-events: none;
}

/* Section Produk */
.home-products-section {
  padding-bottom: 64px;
}
.home-products-heading {
  margin: 0 0 24px;
  font-family: 'Playfair Display', Georgia, serif;
  font-size: clamp(1.7rem, 3vw, 34px);
  font-weight: 700;
  color: var(--fg);
  text-align: center;
}
.home-product-grid {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 24px;
}
.home-product-card {
  box-sizing: border-box;
  flex: 0 1 calc((100% - 48px) / 3);
  min-width: min(100%, 260px);
  max-width: 360px;
  background: #fff;
  border-radius: 24px;
  padding: 16px;
  display: flex;
  flex-direction: column;
  text-decoration: none;
  color: inherit;
  box-shadow: 0 6px 20px rgba(59, 42, 30, 0.04);
}
.home-product-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 14px 30px rgba(59, 42, 30, 0.09);
}
.home-product-card:focus-visible {
  outline: 3px solid var(--accent);
  outline-offset: 2px;
}
.home-product-thumb {
  height: 180px;
  width: 100%;
  border-radius: 16px;
  overflow: hidden;
  background: #f9dcc0;
}
.home-product-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.home-product-thumb .ph {
  width: 100%;
  height: 100%;
  border-radius: 0;
}
.home-product-card h3 {
  margin: 16px 8px 6px;
  font-size: 20px;
  font-weight: 700;
  color: var(--fg);
}
.home-product-card p {
  margin: 0 8px 8px;
  color: var(--muted);
  line-height: 1.6;
  font-size: 0.95rem;
}

@media (max-width: 860px) {
  .home-hero-split {
    grid-template-columns: 1fr;
    gap: 32px;
    padding: 20px 0 48px;
  }
  .home-hero-media .hero.swiper {
    height: auto;
    aspect-ratio: 4 / 3;
    max-height: 440px;
  }
  .home-fresh-badge {
    left: 12px;
    bottom: 20px;
    font-size: 17px;
    padding: 9px 16px;
  }
}

@media (max-width: 480px) {
  .home-shell {
    padding: 0 20px;
  }
  .home-nav {
    padding: 18px 0;
  }
  .home-brand {
    font-size: 22px;
  }
  .home-nav-links {
    gap: 18px;
    font-size: 14px;
  }
  .home-hero-media .hero.swiper {
    border-radius: 24px;
  }
  .home-product-grid {
    gap: 18px;
  }
}
</style>

<div class="home-shell">
  <nav class="home-nav" aria-label="Navigasi Utama">
    <a href="index.php" class="home-brand">Alfi Kitchen</a>
    <div class="home-nav-links">
      <a href="#menu">Menu</a>
      <a href="about.php">Tentang</a>
      <a href="<?= htmlspecialchars($heroWaUrl ?? '#menu', ENT_QUOTES, 'UTF-8') ?>"<?= !empty($waDigits) ? ' target="_blank" rel="noopener noreferrer"' : '' ?> class="home-nav-cta">Pesan</a>
    </div>
  </nav>

  <main>
    <section class="home-hero-split" aria-label="Perkenalan Alfi Kitchen">
      <div class="home-hero-copy">
        <span class="home-eyebrow">PUDING · DESSERT · SALAD BUAH</span>
        <h1><?= nl2br(htmlspecialchars($homeTitle, ENT_QUOTES, 'UTF-8')) ?></h1>
        <p><?= nl2br(htmlspecialchars($homeDescription, ENT_QUOTES, 'UTF-8')) ?></p>
        <div class="home-hero-actions">
          <a href="<?= htmlspecialchars($heroWaUrl ?? '#menu', ENT_QUOTES, 'UTF-8') ?>"<?= !empty($waDigits) ? ' target="_blank" rel="noopener noreferrer"' : '' ?> class="home-btn-primary">Pesan via WhatsApp</a>
          <a href="#menu" class="home-btn-outline">Lihat Menu</a>
        </div>
      </div>

      <div class="home-hero-media">
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
                       sizes="(max-width: 480px) 100vw, (max-width: 860px) 640px, 540px"
                       alt="Promo produk Alfi Kitchen: puding, dessert, dan salad buah rumahan"
                       width="800" height="450"
                       draggable="false"
                       <?= $index === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?>
                       decoding="async">
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="swiper-slide">
                <img src="thumb.php?file=hero.jpg&w=640&q=65"
                     srcset="thumb.php?file=hero.jpg&w=420&q=65 420w, thumb.php?file=hero.jpg&w=640&q=65 640w, thumb.php?file=hero.jpg&w=800&q=68 800w"
                     sizes="(max-width: 480px) 100vw, (max-width: 860px) 640px, 540px"
                     alt="Puding, dessert, dan salad buah rumahan Alfi Kitchen"
                     width="800" height="450"
                     fetchpriority="high"
                     decoding="async">
              </div>
            <?php endif; ?>
          </div>

          <?php
          $logo_path = 'uploads/logo.png';
          $logo_file = __DIR__ . '/../' . $logo_path;
          $fallbackLogo = require __DIR__ . '/../includes/fallback_logo.php';
          $logo_src = file_exists($logo_file) ? 'uploads/logo.png?v=' . filemtime($logo_file) : $fallbackLogo;
          ?>
          <a href="index.php" class="floating-logo" title="Alfi Kitchen">
            <img src="<?= $logo_src ?>" width="64" height="64" alt="Alfi Kitchen Logo">
          </a>

          <?php if (count($hero_images) > 1): ?>
            <button type="button" class="hero-nav-btn hero-nav-prev" id="heroPrev" aria-label="Slide sebelumnya">&#8249;</button>
            <button type="button" class="hero-nav-btn hero-nav-next" id="heroNext" aria-label="Slide berikutnya">&#8250;</button>
          <?php endif; ?>

          <div class="swiper-pagination" id="heroPagination"></div>
        </div>

        <span class="home-fresh-badge">Always Fresh</span>
      </div>
    </section>

    <section class="home-products-section" id="menu" aria-labelledby="home-products-heading">
      <h2 class="home-products-heading" id="home-products-heading">Pilihan dari Alfi Kitchen</h2>
      <?php if (empty($products)): ?>
        <p class="product-list-empty">Produk sedang disiapkan. Silakan kembali lagi nanti.</p>
      <?php else: ?>
        <div class="home-product-grid">
          <?php foreach ($products as $p): ?>
            <?php $itemCount = (int) ($p['item_count'] ?? 0); ?>
            <a href="produk/<?= htmlspecialchars(ensure_product_slug($pdo, $p), ENT_QUOTES, 'UTF-8') ?>" class="home-product-card">
              <div class="home-product-thumb">
                <?php if (!empty($p['image'])): ?>
                  <img src="thumb.php?file=<?= urlencode($p['image']) ?>&w=480&q=75" width="480" height="320" alt="<?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy" decoding="async">
                <?php else: ?>
                  <div class="ph <?= htmlspecialchars($p['image_class'] ?? 'a', ENT_QUOTES, 'UTF-8') ?>"></div>
                <?php endif; ?>
              </div>
              <h3><?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?></h3>
              <p><?= $itemCount > 0 ? $itemCount . ' varian pilihan buatan rumahan.' : 'Sajian buatan rumahan dari bahan pilihan.' ?></p>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </main>
</div>

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
    var startX = 0, currentX = 0, isDragging = false, justTouched = false;
    var timer = null;
    var prevBtn = document.getElementById("heroPrev");
    var nextBtn = document.getElementById("heroNext");

    function isInteractiveTarget(el) {
        return !!(el && el.closest && el.closest(".floating-logo, .swiper-pagination, .hero-nav-btn"));
    }

    if (prevBtn) {
        prevBtn.addEventListener("click", function(e) {
            e.stopPropagation();
            goToSlide(currentIndex - 1);
            resetTimer();
        });
    }
    if (nextBtn) {
        nextBtn.addEventListener("click", function(e) {
            e.stopPropagation();
            goToSlide(currentIndex + 1);
            resetTimer();
        });
    }

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
        if (e.touches.length !== 1 || isInteractiveTarget(e.target)) return;
        isDragging = true;
        justTouched = true;
        startX = e.touches[0].clientX;
        currentX = startX;
        stopTimer();
    }, { passive: true });

    slider.addEventListener("touchmove", function(e) {
        if (!isDragging || e.touches.length !== 1) return;
        currentX = e.touches[0].clientX;
    }, { passive: true });

    slider.addEventListener("touchend", function(e) {
        if (!isDragging) return;
        isDragging = false;
        var diff = currentX - startX;
        if (diff > 40) {
            goToSlide(currentIndex - 1);
        } else if (diff < -40 || (Math.abs(diff) < 8 && !isInteractiveTarget(e.target))) {
            goToSlide(currentIndex + 1);
        }
        startTimer();
        setTimeout(function() { justTouched = false; }, 350);
    });

    // Mouse drag & klik gambar untuk lanjut slide
    slider.addEventListener("mousedown", function(e) {
        if (justTouched || e.button !== 0 || isInteractiveTarget(e.target)) return;
        isDragging = true;
        startX = e.clientX;
        currentX = startX;
        stopTimer();
    });

    window.addEventListener("mousemove", function(e) {
        if (!isDragging) return;
        currentX = e.clientX;
    });

    window.addEventListener("mouseup", function(e) {
        if (!isDragging) return;
        isDragging = false;
        var diff = currentX - startX;
        if (diff > 40) {
            goToSlide(currentIndex - 1);
        } else if (diff < -40 || (Math.abs(diff) < 8 && slider.contains(e.target) && !isInteractiveTarget(e.target))) {
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
