<?php
$pageTitle = 'Tentang Kami | Alfi Kitchen';
require 'config.php';
include 'header.php';
?>
<style>
  .about-nav {
    width: min(1200px, 88vw);
    margin: 20px auto 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
  }
  .about-brand {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    color: var(--fg);
    text-decoration: none;
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 1.2rem;
    font-weight: 700;
  }
  .about-brand img {
    width: 48px;
    height: 48px;
    border: 2px solid var(--line);
    border-radius: 50%;
    object-fit: cover;
  }
  .about-nav-link {
    color: var(--fg);
    font-size: .92rem;
    font-weight: 700;
    text-decoration: none;
  }
  .about-nav-link:hover { color: var(--accent); }
  .about-hero {
    position: relative;
    isolation: isolate;
    display: flex;
    align-items: center;
    width: min(1440px, 94vw);
    min-height: min(66vh, 700px);
    min-height: min(66svh, 700px);
    margin: 0 auto;
    overflow: hidden;
    border-radius: 20px;
    background: #4a3728;
  }
  .about-hero::after {
    position: absolute;
    z-index: -1;
    inset: 0;
    background: rgba(42, 29, 20, .48);
    content: '';
  }
  .about-hero-image {
    position: absolute;
    z-index: -2;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center 55%;
  }
  .about-hero-copy {
    width: min(780px, 100%);
    padding: clamp(32px, 8vw, 96px);
    color: #fff;
  }
  .about-eyebrow {
    margin: 0 0 18px;
    color: #ffe0bd;
    font-size: .8rem;
    font-weight: 800;
    letter-spacing: 0;
    text-transform: uppercase;
  }
  .about-hero h1 {
    max-width: 12ch;
    margin: 0 0 20px;
    font-size: 3.4rem;
    line-height: 1.04;
  }
  .about-hero-copy > p:last-child {
    max-width: 52ch;
    margin: 0;
    color: rgba(255, 255, 255, .92);
    font-size: 1.08rem;
    line-height: 1.75;
  }
  .about-content {
    width: min(1100px, 88vw);
    margin: 0 auto;
  }
  .about-story {
    display: grid;
    grid-template-columns: minmax(0, 1.2fr) minmax(240px, .8fr);
    gap: 56px;
    align-items: start;
    padding: 76px 0 68px;
  }
  .about-story h2, .about-menu h2 {
    margin: 0 0 18px;
    font-size: 2rem;
    line-height: 1.2;
  }
  .about-story p {
    max-width: 62ch;
    margin: 0;
    color: var(--muted);
    font-size: 1.02rem;
    line-height: 1.8;
  }
  .about-note {
    padding-left: 24px;
    border-left: 3px solid var(--accent);
  }
  .about-note strong {
    display: block;
    margin-bottom: 10px;
    color: var(--fg);
    font-size: 1.05rem;
  }
  .about-menu {
    padding: 0 0 72px;
  }
  .about-menu-intro {
    margin: 0 0 26px;
    color: var(--muted);
    line-height: 1.7;
  }
  .about-offerings {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    border-top: 1px solid var(--line);
    border-bottom: 1px solid var(--line);
  }
  .about-offering {
    padding: 24px 20px 26px 0;
  }
  .about-offering + .about-offering {
    padding-left: 20px;
    border-left: 1px solid var(--line);
  }
  .about-offering h3 {
    margin: 0 0 8px;
    color: var(--fg);
    font-size: 1.1rem;
  }
  .about-offering p {
    margin: 0;
    color: var(--muted);
    font-size: .95rem;
    line-height: 1.65;
  }
  .about-cta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    padding: 28px 0 60px;
    border-top: 1px solid var(--line);
  }
  .about-cta p { margin: 0; color: var(--muted); }
  .about-cta a {
    flex: 0 0 auto;
    padding: 12px 20px;
    border-radius: 6px;
    background: var(--accent);
    color: #fff;
    font-weight: 700;
    text-decoration: none;
  }
  .about-cta a:hover { background: #d78249; }
  @media (max-width: 720px) {
    .about-hero { width: 100%; min-height: 560px; min-height: 70svh; border-radius: 0; }
    .about-hero h1 { font-size: 2.6rem; }
    .about-content { width: min(calc(100% - 40px), 560px); }
    .about-story { grid-template-columns: 1fr; gap: 28px; padding: 52px 0; }
    .about-offerings { grid-template-columns: 1fr; }
    .about-offering, .about-offering + .about-offering { padding: 20px 0; border-left: 0; }
    .about-offering + .about-offering { border-top: 1px solid var(--line); }
    .about-menu { padding-bottom: 48px; }
    .about-cta { align-items: flex-start; flex-direction: column; padding-bottom: 44px; }
  }
</style>

<nav class="about-nav" aria-label="Navigasi halaman Tentang Kami">
  <a class="about-brand" href="index.php">
    <img src="<?= htmlspecialchars($logo_src, ENT_QUOTES, 'UTF-8') ?>" alt="">
    <span>Alfi Kitchen</span>
  </a>
  <a class="about-nav-link" href="index.php#menu">Lihat Menu</a>
</nav>

<main>
  <section class="about-hero" aria-labelledby="about-title">
    <img class="about-hero-image" src="uploads/hero_6ab8cbbd1b056.jpg" alt="Sajian puding dan dessert Alfi Kitchen">
    <div class="about-hero-copy">
      <p class="about-eyebrow">Cerita Alfi Kitchen</p>
      <h1 id="about-title">Rasa rumahan untuk momen manis</h1>
      <p>Alfi Kitchen menghadirkan puding, dessert, dan salad buah buatan rumahan dari bahan pilihan, untuk menemani hari-hari dan momen istimewa Anda.</p>
    </div>
  </section>

  <div class="about-content">
    <section class="about-story" aria-labelledby="about-story-title">
      <div>
        <p class="about-eyebrow" style="color: var(--accent);">Tentang Kami</p>
        <h2 id="about-story-title">Dibuat untuk dinikmati bersama</h2>
        <p>Kami meracik pilihan sajian manis dan segar yang akrab di lidah: puding lembut berlapis buah, dessert dalam kemasan praktis, serta salad buah dengan saus creamy. Setiap pilihan dibuat rumahan menggunakan bahan pilihan.</p>
      </div>
      <aside class="about-note">
        <strong>Manis, segar, dan praktis</strong>
        <p>Pilih sajian yang paling cocok untuk menemani waktu santai, berbagi dengan keluarga, atau menjadi hadiah kecil untuk orang tersayang.</p>
      </aside>
    </section>

    <section class="about-menu" aria-labelledby="about-menu-title">
      <h2 id="about-menu-title">Pilihan dari Alfi Kitchen</h2>
      <p class="about-menu-intro">Temukan sajian yang sesuai dengan selera dan momen Anda.</p>
      <div class="about-offerings">
        <article class="about-offering">
          <h3>Puding</h3>
          <p>Puding lembut dengan lapisan buah yang menyegarkan.</p>
        </article>
        <article class="about-offering">
          <h3>Dessert</h3>
          <p>Sajian manis praktis untuk dinikmati kapan saja.</p>
        </article>
        <article class="about-offering">
          <h3>Salad Buah</h3>
          <p>Potongan buah dengan saus creamy yang pas.</p>
        </article>
      </div>
    </section>

    <section class="about-cta" aria-label="Jelajahi menu">
      <p>Sudah menemukan yang ingin dicoba?</p>
      <a href="index.php#menu">Jelajahi Menu</a>
    </section>
  </div>
</main>

<?php include 'footer.php'; ?>