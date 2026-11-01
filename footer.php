<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!isset($wa_footer_number)) {
    $wa_footer_stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'whatsapp_number'");
    $wa_footer_stmt->execute();
    $wa_footer_row = $wa_footer_stmt->fetch();
    $wa_footer_number = $wa_footer_row ? $wa_footer_row['setting_value'] : '';
}

$fi_stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('footer_instagram','footer_facebook','footer_tiktok','footer_hours','footer_address','footer_email','footer_maps')");
$fi = $fi_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<footer class="site-footer">
  <div class="footer-inner">
    <div class="footer-col footer-brand">
      <?php if(file_exists(__DIR__ . '/uploads/logo.png')): ?>
        <img src="uploads/logo.png?v=<?= time() ?>" class="footer-logo" alt="Alfi Kitchen">
      <?php else: ?>
        <span class="footer-brand-text">Alfi Kitchen</span>
      <?php endif; ?>
    </div>
    <div class="footer-col footer-links">
      <a href="about.php">Tentang Kami</a>
      <a href="privacy.php">Kebijakan Privasi</a>
      <a href="terms.php">Syarat & Ketentuan</a>
    </div>
    <div class="footer-col footer-contact">
      <?php if (!empty($fi['footer_hours'])): ?>
        <div class="footer-item"><span class="footer-label">Jam:</span> <span><?= htmlspecialchars($fi['footer_hours']) ?></span></div>
      <?php endif; ?>
      <?php if (!empty($fi['footer_address'])): ?>
        <div class="footer-item"><span class="footer-label">Alamat:</span> <span><?= htmlspecialchars($fi['footer_address']) ?></span></div>
      <?php endif; ?>
      <?php if (!empty($fi['footer_email'])): ?>
        <div class="footer-item"><span class="footer-label">Email:</span> <a href="mailto:<?= htmlspecialchars($fi['footer_email']) ?>" class="footer-link"><?= htmlspecialchars($fi['footer_email']) ?></a></div>
      <?php endif; ?>
      <?php if (!empty($fi['footer_maps'])): ?>
        <div class="footer-item"><span class="footer-label">Maps:</span> <a href="<?= htmlspecialchars($fi['footer_maps']) ?>" target="_blank" rel="noopener" class="footer-link">Lihat di Maps</a></div>
      <?php endif; ?>
    </div>
    <div class="footer-col footer-social">
      <?php if (!empty($fi['footer_instagram'])): ?>
        <a href="<?= htmlspecialchars($fi['footer_instagram']) ?>" target="_blank" rel="noopener" class="footer-social-link instagram" aria-label="Instagram"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="17.5" x2="19.18" y2="19.18"/></svg></a>
      <?php endif; ?>
      <?php if (!empty($fi['footer_facebook'])): ?>
        <a href="<?= htmlspecialchars($fi['footer_facebook']) ?>" target="_blank" rel="noopener" class="footer-social-link facebook" aria-label="Facebook"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg></a>
      <?php endif; ?>
      <?php if (!empty($fi['footer_tiktok'])): ?>
        <a href="<?= htmlspecialchars($fi['footer_tiktok']) ?>" target="_blank" rel="noopener" class="footer-social-link tiktok" aria-label="TikTok"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.19 11.2c.36-.2.59-.53.59-.95v-1c0-.8-.4-1.57-1.08-2.03-1.26-.84-3.32-.52-4.59 1.53C14.28 10.34 14 11.08 14 12c0 .94-.28 1.67-.69 2.18-1.34 1.68-1.94 3.67-1.04 4.89.83 1.14 3.06 1.62 4.33 1s1.5-.7 1.72-1c.61-.8.8-2.06.47-3.15-.4-1.33-1-2.3-1.87-3.02-.64-.53-.83-1.5-.56-2.26.4-1.03 1.67-1.76 2.68-1.4z"/></svg></a>
      <?php endif; ?>
    </div>
  </div>
  <div class="footer-bottom">
    <span>© <?= date("Y"); ?> Alfi Kitchen</span>
  </div>
</footer>

<?php if ($wa_footer_number): ?>
<style>
  .wa-float {
    position: fixed;
    bottom: calc(16px + env(safe-area-inset-bottom, 0px));
    right: 16px;
    z-index: 9999;
    width: 56px;
    height: 56px;
    background: #075e54;
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    text-decoration: none;
    font-family: 'Inter', sans-serif;
    font-weight: 600;
    box-shadow: 0 6px 20px rgba(7, 94, 84, 0.4);
    transition: all 0.3s ease;
  }
  .wa-float:hover {
    background: #128c7e;
    transform: translateY(-3px);
    box-shadow: 0 10px 28px rgba(7, 94, 84, 0.5);
  }
  .wa-float svg {
    flex-shrink: 0;
    width: 30px;
    height: 30px;
  }
  @media (max-width: 480px) {
    .wa-float { width: 52px; height: 52px; }
    footer { padding-bottom: calc(76px + env(safe-area-inset-bottom, 0px)); }
  }
</style>
<?php
require_once __DIR__ . '/includes/seo_helpers.php';
$waUrl = whatsapp_url($wa_footer_number, 'Halo Alfi Kitchen, saya ingin memesan produk Anda.');
?>
<a href="<?= htmlspecialchars($waUrl) ?>" class="wa-float" target="_blank" rel="noopener noreferrer" aria-label="Pesan via WhatsApp">
  <svg viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M16 3C9.37 3 4 8.37 4 15c0 2.65.87 5.1 2.36 7.09L4 29l7.18-2.31A12.93 12.93 0 0 0 16 27c6.63 0 12-5.37 12-12S22.63 3 16 3z" fill="#fff"/>
    <path d="M22.07 19.57c-.28-.14-1.65-.81-1.9-.9-.26-.09-.45-.14-.64.14-.19.28-.73.9-.9 1.09-.16.18-.33.2-.61.07-.28-.14-1.18-.43-2.24-1.38-.83-.74-1.39-1.65-1.55-1.93-.16-.28-.02-.43.12-.57.13-.12.28-.33.42-.49.14-.16.19-.28.28-.46.09-.18.05-.34-.02-.48-.07-.14-.64-1.54-.88-2.1-.23-.55-.47-.47-.64-.48H13.1c-.18 0-.47.07-.72.34-.25.27-.95.93-.95 2.27s.97 2.63 1.1 2.81c.14.18 1.91 2.91 4.62 4.08.65.28 1.15.45 1.54.57.65.2 1.24.17 1.7.1.52-.08 1.65-.67 1.88-1.32.23-.65.23-1.2.16-1.32-.07-.12-.25-.19-.53-.33z" fill="#25d366"/>
  </svg>
</a>
<?php endif; ?>

</body>
</html>