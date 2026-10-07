<?php
require_once __DIR__ . '/config.php';

// Ambil nomor WhatsApp dari database
$wa_footer_stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'whatsapp_number'");
$wa_footer_stmt->execute();
$wa_footer_row = $wa_footer_stmt->fetch();
$wa_footer_number = $wa_footer_row ? $wa_footer_row['setting_value'] : '';
?>

<footer>
  <span>© <?php echo date("Y"); ?> Alfi Kitchen</span>
  <span><a href="about.php">Tentang Kami</a> · <a href="privacy.php">Kebijakan Privasi</a> · <a href="terms.php">Syarat & Ketentuan</a></span>
</footer>

<?php if ($wa_footer_number): ?>
<!-- Floating WhatsApp Button -->
<style>
  .wa-float {
    position: fixed;
    bottom: 90px;
    right: 24px;
    z-index: 9999;
    background: #25d366;
    color: #fff;
    border-radius: 50px;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 20px;
    text-decoration: none;
    font-family: 'Inter', sans-serif;
    font-weight: 600;
    font-size: 0.95rem;
    box-shadow: 0 6px 20px rgba(37, 211, 102, 0.4);
    transition: all 0.3s ease;
    max-width: 220px;
    overflow: hidden;
  }
  .wa-float:hover {
    background: #1ebe5d;
    transform: translateY(-3px);
    box-shadow: 0 10px 28px rgba(37, 211, 102, 0.5);
    max-width: 220px;
  }
  .wa-float svg {
    flex-shrink: 0;
    width: 26px;
    height: 26px;
  }
  .wa-float span {
    white-space: nowrap;
  }
  @media (max-width: 480px) {
    .wa-float span { display: none; }
    .wa-float { padding: 14px; border-radius: 50%; }
  }
</style>
<a href="https://wa.me/<?= htmlspecialchars($wa_footer_number) ?>?text=Halo%20Alfi%20Kitchen%2C%20saya%20ingin%20memesan%20produk%20Anda." class="wa-float" target="_blank" rel="noopener noreferrer" aria-label="Pesan via WhatsApp">
  <svg viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M16 3C9.37 3 4 8.37 4 15c0 2.65.87 5.1 2.36 7.09L4 29l7.18-2.31A12.93 12.93 0 0 0 16 27c6.63 0 12-5.37 12-12S22.63 3 16 3z" fill="#fff"/>
    <path d="M22.07 19.57c-.28-.14-1.65-.81-1.9-.9-.26-.09-.45-.14-.64.14-.19.28-.73.9-.9 1.09-.16.18-.33.2-.61.07-.28-.14-1.18-.43-2.24-1.38-.83-.74-1.39-1.65-1.55-1.93-.16-.28-.02-.43.12-.57.13-.12.28-.33.42-.49.14-.16.19-.28.28-.46.09-.18.05-.34-.02-.48-.07-.14-.64-1.54-.88-2.1-.23-.55-.47-.47-.64-.48H13.1c-.18 0-.47.07-.72.34-.25.27-.95.93-.95 2.27s.97 2.63 1.1 2.81c.14.18 1.91 2.91 4.62 4.08.65.28 1.15.45 1.54.57.65.2 1.24.17 1.7.1.52-.08 1.65-.67 1.88-1.32.23-.65.23-1.2.16-1.32-.07-.12-.25-.19-.53-.33z" fill="#25d366"/>
  </svg>
  <span>Pesan via WhatsApp</span>
</a>
<?php endif; ?>

</body>
</html>

