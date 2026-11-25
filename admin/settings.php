<?php
require 'auth_check.php';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/upload_helper.php';
require 'csrf_check.php';

$message = '';
$messageClass = 'alert';

if (isset($_GET['error']) && $_GET['error'] === 'backup_cooldown') {
    $wait = (int)($_GET['wait'] ?? 60);
    $typeLabel = ($_GET['type'] ?? '') === 'images' ? 'Gambar' : 'Database';
    $message = "Terlalu banyak permintaan backup {$typeLabel}. Harap tunggu {$wait} detik sebelum mengunduh kembali demi menjaga stabilitas server.";
    $messageClass = 'alert alert-error';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['hero_image'])) {
    $upload = store_uploaded_image($_FILES['hero_image'], 'hero', true);
    if ($upload['error']) {
        $message = $upload['error'];
        $messageClass = 'alert alert-error';
    } else {
        $db_path = $upload['path'];
        $stmt = $pdo->prepare("INSERT INTO hero_images (image) VALUES (?)");
        $stmt->execute([$db_path]);
        log_admin_action('ADD_HERO_IMAGE', 'file=' . $db_path);
        $message = "Gambar slider berhasil ditambahkan!";
        $messageClass = 'alert alert-success';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_hero_image'])) {
    $deleteId = (int) $_POST['delete_hero_image'];
    $stmt = $pdo->prepare("SELECT image FROM hero_images WHERE id = ?");
    $stmt->execute([$deleteId]);
    $img = $stmt->fetch();

    if ($img && !empty($img['image'])) {
        delete_uploaded_image($img['image']);
    }

    $stmt = $pdo->prepare("DELETE FROM hero_images WHERE id = ?");
    $stmt->execute([$deleteId]);
    log_admin_action('DELETE_HERO_IMAGE', 'id=' . $deleteId);
    $message = "Gambar slider berhasil dihapus!";
    $messageClass = 'alert alert-success';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['logo'])) {
    $upload = store_uploaded_image($_FILES['logo'], 'logo', true, ['image/png', 'image/x-png'], 'logo.png');
    if ($upload['error']) {
        $message = $upload['error'];
        $messageClass = 'alert alert-error';
    } else {
        log_admin_action('UPDATE_LOGO', 'filename=logo.png');
        $message = "Logo berhasil diperbarui!";
        $messageClass = 'alert alert-success';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $new_password = $_POST['new_password'];
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
    if ($stmt->execute([$hashed])) {
        log_admin_action('CHANGE_ADMIN_PASSWORD', 'password_updated=true');
        $message = "Password berhasil diubah! Gunakan password baru ini untuk login berikutnya.";
    } else {
        $message = "Terjadi kesalahan saat mengubah password.";
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_home_content'])) {
    $home_title = trim(is_string($_POST['home_title'] ?? null) ? $_POST['home_title'] : '');
    $home_description = trim(is_string($_POST['home_description'] ?? null) ? $_POST['home_description'] : '');

    if ($home_title === '' || $home_description === '') {
        $message = 'Judul dan deskripsi homepage wajib diisi.';
        $messageClass = 'alert alert-error';
    } elseif (strlen($home_title) > 640 || strlen($home_description) > 4800) {
        $message = 'Judul atau deskripsi terlalu panjang.';
        $messageClass = 'alert alert-error';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?), (?, ?) '
            . 'ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        if ($stmt->execute(['home_title', $home_title, 'home_description', $home_description])) {
            log_admin_action('SAVE_HOME_CONTENT', 'fields=title,description');
            $message = 'Judul dan deskripsi homepage berhasil disimpan!';
        } else {
            $message = 'Gagal menyimpan konten homepage.';
            $messageClass = 'alert alert-error';
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_whatsapp'])) {
    $wa_number = preg_replace('/[^0-9]/', '', $_POST['whatsapp_number']);
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('whatsapp_number', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    if ($stmt->execute([$wa_number, $wa_number])) {
        log_admin_action('SAVE_WHATSAPP_NUMBER', 'number=' . $wa_number);
        $message = "Nomor WhatsApp berhasil disimpan!";
    } else {
        $message = "Gagal menyimpan nomor WhatsApp.";
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_footer_info'])) {
    $keys = ['footer_instagram','footer_facebook','footer_tiktok','footer_hours','footer_address','footer_email','footer_maps'];
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    foreach ($keys as $k) {
        $v = trim((string)($_POST[$k] ?? ''));
        $stmt->execute([$k, $v, $v]);
    }
    log_admin_action('SAVE_FOOTER_INFO', 'updated');
    $message = "Info footer berhasil disimpan!";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migrations'])) {
    require_once __DIR__ . '/../includes/DatabaseMigrator.php';
    $migrator = new DatabaseMigrator($pdo);
    $res = $migrator->migrate();
    if ($res['success']) {
        $count = count($res['executed']);
        $message = $count > 0 
            ? "Berhasil menerapkan $count migrasi database!" 
            : "Database sudah berada pada versi terbaru.";
        $messageClass = 'alert alert-success';
        log_admin_action('DATABASE_MIGRATE', 'executed=' . implode(',', $res['executed']));
    } else {
        $message = "Gagal menerapkan migrasi: " . implode(', ', $res['errors']);
        $messageClass = 'alert alert-error';
    }
}

// Ambil nomor WhatsApp dari database
$wa_stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'whatsapp_number'");
$wa_stmt->execute();
$wa_row = $wa_stmt->fetch();
$current_wa = $wa_row ? $wa_row['setting_value'] : '';

$home_content_stmt = $pdo->query(
    "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('home_title', 'home_description')"
);
$home_content = $home_content_stmt ? $home_content_stmt->fetchAll(PDO::FETCH_KEY_PAIR) : [];
$footer_stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('footer_instagram','footer_facebook','footer_tiktok','footer_hours','footer_address','footer_email','footer_maps')");
$footer_info = $footer_stmt ? $footer_stmt->fetchAll(PDO::FETCH_KEY_PAIR) : [];
$current_footer = [
    'instagram' => $footer_info['footer_instagram'] ?? '',
    'facebook' => $footer_info['footer_facebook'] ?? '',
    'tiktok' => $footer_info['footer_tiktok'] ?? '',
    'hours' => $footer_info['footer_hours'] ?? '',
    'address' => $footer_info['footer_address'] ?? '',
    'email' => $footer_info['footer_email'] ?? '',
    'maps' => $footer_info['footer_maps'] ?? '',
];
$current_home_title = $home_content['home_title'] ?? "Selamat Datang di\nAlfi Kitchen";
$current_home_description = $home_content['home_description'] ?? 'Puding lembut berlapis buah, Dessert sehat dalam kemasan praktis, dan Salad buah bersaus creamy — semua dibuat rumahan dari bahan pilihan, siap menemani hari-harimu.';

$hero_images = $pdo->query("SELECT * FROM hero_images ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

$activeTab = 'tab-kontak';
if (isset($_GET['tab'])) {
    $tabParam = preg_replace('/[^a-z0-9_-]/', '', $_GET['tab']);
    $activeTab = str_starts_with($tabParam, 'tab-') ? $tabParam : 'tab-' . $tabParam;
} elseif (isset($_GET['error']) && $_GET['error'] === 'backup_cooldown') {
    $activeTab = 'tab-database';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['hero_image']) || isset($_POST['delete_hero_image'])) {
        $activeTab = 'tab-slider';
    } elseif (isset($_FILES['logo']) || isset($_POST['save_home_content'])) {
        $activeTab = 'tab-tampilan';
    } elseif (isset($_POST['change_password'])) {
        $activeTab = 'tab-keamanan';
    } elseif (isset($_POST['run_migrations'])) {
        $activeTab = 'tab-database';
    } elseif (isset($_POST['save_whatsapp']) || isset($_POST['save_footer_info'])) {
        $activeTab = 'tab-kontak';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>Pengaturan - Alfi Kitchen</title>
    <link rel="stylesheet" href="assets/css/admin.css?v=<?= (int) filemtime(__DIR__ . '/assets/css/admin.css') . '-' . (int) filesize(__DIR__ . '/assets/css/admin.css') ?>">
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h2>Pengaturan</h2>
                <div class="admin-header-actions">
                    <a href="../" target="_blank" rel="noopener noreferrer" class="btn-site-preview"><?= admin_icon('external') ?>Lihat Website</a>
                    <a href="logout.php" class="btn btn-danger btn-with-icon admin-header-logout"><?= admin_icon('logout') ?>Logout</a>
                </div>
            </header>
            <div class="admin-container">
                <?php include __DIR__ . '/flash_message.php'; ?>
                <nav class="admin-subnav" aria-label="Navigasi Pengaturan dan Sistem">
                    <a href="settings.php" class="admin-subnav-link is-active"><?= admin_icon('settings') ?> Pengaturan Toko</a>
                    <a href="activity_log.php" class="admin-subnav-link"><?= admin_icon('log') ?> Log Aktivitas</a>
                </nav>
        <div class="form-card settings-card">
            <?php if($message): ?>
                <div class="<?= htmlspecialchars($messageClass) ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <div class="settings-tabs-nav" role="tablist" aria-label="Kategori Pengaturan">
                <button type="button" role="tab" class="settings-tab-btn<?= $activeTab === 'tab-kontak' ? ' is-active' : '' ?>" data-tab="tab-kontak" id="tab-btn-kontak" aria-selected="<?= $activeTab === 'tab-kontak' ? 'true' : 'false' ?>" aria-controls="tab-kontak">
                    <?= admin_icon('external') ?> <span>Kontak &amp; WhatsApp</span>
                </button>
                <button type="button" role="tab" class="settings-tab-btn<?= $activeTab === 'tab-slider' ? ' is-active' : '' ?>" data-tab="tab-slider" id="tab-btn-slider" aria-selected="<?= $activeTab === 'tab-slider' ? 'true' : 'false' ?>" aria-controls="tab-slider">
                    <?= admin_icon('slider') ?> <span>Slider Beranda</span>
                </button>
                <button type="button" role="tab" class="settings-tab-btn<?= $activeTab === 'tab-tampilan' ? ' is-active' : '' ?>" data-tab="tab-tampilan" id="tab-btn-tampilan" aria-selected="<?= $activeTab === 'tab-tampilan' ? 'true' : 'false' ?>" aria-controls="tab-tampilan">
                    <?= admin_icon('image') ?> <span>Logo &amp; Konten</span>
                </button>
                <button type="button" role="tab" class="settings-tab-btn<?= $activeTab === 'tab-database' ? ' is-active' : '' ?>" data-tab="tab-database" id="tab-btn-database" aria-selected="<?= $activeTab === 'tab-database' ? 'true' : 'false' ?>" aria-controls="tab-database">
                    <?= admin_icon('database') ?> <span>Database &amp; Backup</span>
                </button>
                <button type="button" role="tab" class="settings-tab-btn<?= $activeTab === 'tab-keamanan' ? ' is-active' : '' ?>" data-tab="tab-keamanan" id="tab-btn-keamanan" aria-selected="<?= $activeTab === 'tab-keamanan' ? 'true' : 'false' ?>" aria-controls="tab-keamanan">
                    <?= admin_icon('key') ?> <span>Keamanan Akun</span>
                </button>
            </div>

            <!-- Tab 1: Kontak & WhatsApp -->
            <div id="tab-kontak" class="settings-tab-pane" role="tabpanel" aria-labelledby="tab-btn-kontak"<?= $activeTab === 'tab-kontak' ? '' : ' hidden' ?>>
                <div class="settings-grid">
                    <!-- Kolom Kiri: Nomor WhatsApp & Live Chat Simulator -->
                    <section class="settings-panel slider-upload-panel">
                        <div class="slider-panel-header">
                            <h3>Nomor WhatsApp Toko</h3>
                            <p class="settings-copy">Digunakan untuk tombol pemesanan "Pesan via WhatsApp" di seluruh halaman website.</p>
                        </div>
                        <form action="" method="post" data-submit-lock id="form-whatsapp">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                            <div class="form-group">
                                <label for="wa-number-input">Nomor WhatsApp</label>
                                <input type="text" id="wa-number-input" name="whatsapp_number" value="<?= htmlspecialchars($current_wa) ?>" required class="form-control">
                                <small class="settings-hint">Gunakan format internasional tanpa + atau spasi (contoh: <code>6281234567890</code>)</small>
                            </div>
                            <button type="submit" name="save_whatsapp" class="btn btn-save-whatsapp btn-with-icon"><?= admin_icon('save') ?><span>Simpan Nomor WhatsApp</span></button>
                        </form>

                        <!-- Live WhatsApp Simulator & Test Link -->
                        <div class="simulator-wrapper simulator-wrapper-spaced">
                            <div class="simulator-header">
                                <span>📱 Simulasi Tombol WhatsApp di Website</span>
                                <small>Live Preview</small>
                            </div>
                            <div class="wa-simulator-card">
                                <div class="wa-sim-bubble">
                                    <div class="wa-sim-icon"><?= admin_icon('external') ?></div>
                                    <div class="wa-sim-text">
                                        <strong>Pesan via WhatsApp</strong>
                                        <span id="wa-sim-number"><?= htmlspecialchars($current_wa ?: 'Belum diatur') ?></span>
                                    </div>
                                </div>
                                <a id="wa-sim-test-link" href="https://wa.me/<?= htmlspecialchars(preg_replace('/[^0-9]/', '', (string)$current_wa)) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-secondary btn-with-icon" title="Uji tautan WhatsApp">
                                    <?= admin_icon('external') ?><span>Uji Chat</span>
                                </a>
                            </div>
                        </div>

                        <!-- Box Panduan WhatsApp -->
                        <div class="slider-spec-guide">
                            <div class="slider-spec-title">
                                <span>💡 Panduan WhatsApp Ideal:</span>
                            </div>
                            <ul class="slider-spec-list">
                                <li><strong>Awalan 62</strong>: Gunakan <code>628...</code> agar tautan otomatis berfungsi di semua perangkat.</li>
                                <li><strong>Format Otomatis</strong>: Pengunjung yang mengklik tombol akan langsung diarahkan ke pesan draft dengan nama produk.</li>
                            </ul>
                        </div>
                    </section>

                    <!-- Kolom Kanan: Info Footer & Live Preview -->
                    <section class="settings-panel slider-gallery-panel">
                        <div class="slider-panel-header">
                            <h3>Info Footer &amp; Media Sosial</h3>
                            <p class="settings-copy">Informasi kontak, jam operasional, dan tautan sosial yang tampil di bagian bawah website.</p>
                        </div>
                        <form action="" method="post" data-submit-lock id="footer-info-form">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                            
                            <div class="settings-group-title"><span>Media Sosial</span></div>
                            <div class="form-group"><label for="footer-ig-input">Instagram URL <span class="field-info-tip" tabindex="0" data-tip="Masukkan tautan profil lengkap diawali https://instagram.com/..." title="Masukkan tautan profil lengkap diawali https://instagram.com/..." aria-label="Masukkan tautan profil lengkap diawali https://instagram.com/..."><?= admin_icon('info') ?></span></label><input type="text" id="footer-ig-input" name="footer_instagram" value="<?= htmlspecialchars($current_footer['instagram']) ?>" class="form-control"></div>
                            <div class="form-group"><label for="footer-fb-input">Facebook URL</label><input type="text" id="footer-fb-input" name="footer_facebook" value="<?= htmlspecialchars($current_footer['facebook']) ?>" class="form-control"></div>
                            <div class="form-group"><label for="footer-tt-input">TikTok URL</label><input type="text" id="footer-tt-input" name="footer_tiktok" value="<?= htmlspecialchars($current_footer['tiktok']) ?>" class="form-control"></div>

                            <div class="settings-group-title"><span>Operasional &amp; Lokasi</span></div>
                            <div class="form-group"><label for="footer-hours-input">Jam Operasional <span class="field-info-tip" tabindex="0" data-tip="Contoh: Senin–Sabtu 09:00–17:00" title="Contoh: Senin–Sabtu 09:00–17:00" aria-label="Contoh: Senin–Sabtu 09:00–17:00"><?= admin_icon('info') ?></span></label><input type="text" id="footer-hours-input" name="footer_hours" value="<?= htmlspecialchars($current_footer['hours']) ?>" class="form-control"></div>
                            <div class="form-group"><label for="footer-addr-input">Alamat Toko</label><textarea id="footer-addr-input" name="footer_address" rows="2" class="form-control"><?= htmlspecialchars($current_footer['address']) ?></textarea></div>
                            <div class="form-group"><label for="footer-email-input">Email</label><input type="email" id="footer-email-input" name="footer_email" value="<?= htmlspecialchars($current_footer['email']) ?>" class="form-control"></div>
                            <div class="form-group"><label for="footer-maps-input">Link Google Maps <span class="field-info-tip" tabindex="0" data-tip="Tempel tautan lokasi dari Google Maps (https://maps.google.com/...)" title="Tempel tautan lokasi dari Google Maps (https://maps.google.com/...)" aria-label="Tempel tautan lokasi dari Google Maps"><?= admin_icon('info') ?></span></label><input type="text" id="footer-maps-input" name="footer_maps" value="<?= htmlspecialchars($current_footer['maps']) ?>" class="form-control"></div>

                            <button type="submit" name="save_footer_info" class="btn btn-primary btn-with-icon"><?= admin_icon('save') ?><span>Simpan Info Footer</span></button>
                        </form>

                        <!-- Live Footer Preview Bar -->
                        <div class="simulator-wrapper simulator-wrapper-spaced">
                            <div class="simulator-header">
                                <span>🖥️ Pratinjau Footer Website</span>
                                <small>Live Preview</small>
                            </div>
                            <div class="footer-simulator-card">
                                <div class="sim-footer-hours" id="sim-footer-hours"><?= htmlspecialchars($current_footer['hours'] ?: 'Jam Operasional: Belum diisi') ?></div>
                                <div class="sim-footer-addr" id="sim-footer-addr"><?= htmlspecialchars($current_footer['address'] ?: 'Alamat toko belum diisi') ?></div>
                                <div class="sim-footer-links">
                                    <span class="sim-badge<?= !empty($current_footer['instagram']) ? ' is-active' : '' ?>" id="sim-badge-ig">Instagram</span>
                                    <span class="sim-badge<?= !empty($current_footer['facebook']) ? ' is-active' : '' ?>" id="sim-badge-fb">Facebook</span>
                                    <span class="sim-badge<?= !empty($current_footer['tiktok']) ? ' is-active' : '' ?>" id="sim-badge-tt">TikTok</span>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <!-- Tab 2: Slider Beranda -->
            <div id="tab-slider" class="settings-tab-pane" role="tabpanel" aria-labelledby="tab-btn-slider"<?= $activeTab === 'tab-slider' ? '' : ' hidden' ?>>
                <div class="settings-grid settings-grid-slider">
                    <!-- Kolom Kiri: Unggah Banner Slider Baru -->
                    <section class="settings-panel slider-upload-panel">
                        <div class="slider-panel-header">
                            <h3>Unggah Banner Slider Baru</h3>
                            <p class="settings-copy">Unggah foto banner promosi atau produk untuk slider beranda.</p>
                        </div>
                        <form action="" method="post" enctype="multipart/form-data" data-submit-lock id="slider-upload-form">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                            
                            <!-- Modern Drag & Drop Zone -->
                            <div class="hero-dropzone" id="hero-dropzone" tabindex="0" role="button" aria-label="Pilih atau seret gambar banner ke sini">
                                <input type="file" id="hero-file-input" name="hero_image" class="hero-file-input" accept="image/jpeg,image/png,image/gif,image/webp" required>
                                <div class="hero-dropzone-content" id="hero-dropzone-prompt">
                                    <div class="hero-dropzone-icon">
                                        <?= admin_icon('upload') ?>
                                    </div>
                                    <div class="hero-dropzone-text">
                                        <strong>Pilih atau seret foto ke sini</strong>
                                        <span>Format: JPG, PNG, GIF, WebP (Maks. 5 MB)</span>
                                    </div>
                                </div>

                                <!-- Pre-upload Live Preview Area -->
                                <div class="hero-preview-area" id="hero-preview-area" hidden>
                                    <div class="hero-preview-frame">
                                        <img id="hero-preview-img" src="" alt="Pratinjau Banner Slider">
                                        <span class="hero-safe-logo" aria-hidden="true">Logo</span>
                                        <span class="hero-safe-fresh" aria-hidden="true">Always Fresh</span>
                                    </div>
                                    <div class="hero-preview-meta">
                                        <span id="hero-preview-filename" class="hero-preview-filename"></span>
                                        <button type="button" class="btn btn-secondary btn-sm" id="hero-preview-reset-btn">Ganti Foto</button>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-with-icon btn-upload-slider" id="btn-submit-slider">
                                <?= admin_icon('upload') ?><span>Unggah Banner Slider</span>
                            </button>
                        </form>

                        <!-- Box Panduan Spesifikasi Banner -->
                        <div class="slider-spec-guide">
                            <div class="slider-spec-title">
                                <span>💡 Panduan Banner Ideal:</span>
                            </div>
                            <ul class="slider-spec-list">
                                <li><strong>Rasio 1:1 (Persegi) atau 4:3</strong>: Cocok untuk frame slider beranda desktop (~492 × 480 px) maupun mobile (4:3).</li>
                                <li><strong>Resolusi Rekomendasi</strong>: Minimal 1000 × 1000 px (1:1) atau 1200 × 900 px (4:3).</li>
                                <li><strong>Area Aman (Safe-Zone)</strong>: Pusatkan objek &amp; teks utama di tengah; hindari sudut kiri atas (logo) dan kiri bawah (badge <em>Always Fresh</em>).</li>
                            </ul>
                        </div>
                    </section>

                    <!-- Kolom Kanan: Galeri Slider Aktif -->
                    <section class="settings-panel slider-gallery-panel">
                        <div class="slider-gallery-header">
                            <div>
                                <h3>Daftar Slider Beranda Aktif</h3>
                                <p class="settings-copy">Banner berikut otomatis tampil bergantian di slider utama beranda website.</p>
                            </div>
                            <span class="slider-count-badge"><?= count($hero_images) ?> Banner Aktif</span>
                        </div>

                        <?php if (empty($hero_images)): ?>
                            <div class="slider-empty-state">
                                <div class="slider-empty-icon"><?= admin_icon('slider') ?></div>
                                <h4>Belum ada banner slider</h4>
                                <p class="dashboard-empty">Silakan unggah foto banner pertama Anda menggunakan formulir di sebelah kiri.</p>
                            </div>
                        <?php else: ?>
                            <div class="hero-cards-grid">
                                <?php foreach ($hero_images as $index => $h): ?>
                                    <article class="hero-banner-card">
                                        <div class="hero-banner-media">
                                            <img src="../<?= htmlspecialchars($h['image']) ?>" class="thumb-hero" alt="Slide <?= $index + 1 ?>" loading="lazy">
                                            <span class="hero-slide-badge">
                                                <?= $index === 0 ? '★ Slide #1 (Utama)' : 'Slide #' . ($index + 1) ?>
                                            </span>
                                        </div>
                                        <div class="hero-banner-footer">
                                            <div class="hero-banner-info">
                                                <span class="hero-banner-name" title="<?= htmlspecialchars(basename((string) $h['image'])) ?>">
                                                    <?= htmlspecialchars(basename((string) $h['image'])) ?>
                                                </span>
                                            </div>
                                            <div class="hero-banner-actions">
                                                <form method="post" action="" class="inline-action-form" data-submit-lock onsubmit="return confirm(<?= htmlspecialchars(json_encode('Hapus gambar slider "' . basename((string) $h['image']) . '"?'), ENT_QUOTES, 'UTF-8') ?>)">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                                                    <input type="hidden" name="delete_hero_image" value="<?= (int) $h['id'] ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm hero-delete-btn" title="Hapus gambar slider" aria-label="Hapus gambar slider <?= htmlspecialchars(basename((string) $h['image'])) ?>">
                                                        <?= admin_icon('delete') ?><span>Hapus</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>
            </div>

            <!-- Tab 3: Logo & Konten -->
            <div id="tab-tampilan" class="settings-tab-pane" role="tabpanel" aria-labelledby="tab-btn-tampilan"<?= $activeTab === 'tab-tampilan' ? '' : ' hidden' ?>>
                <div class="settings-grid settings-grid-tampilan">
                    <!-- Kolom Kiri: Pengaturan Logo -->
                    <section class="settings-panel slider-upload-panel">
                        <div class="slider-panel-header">
                            <h3>Pengaturan Logo Toko</h3>
                            <p class="settings-copy">Logo ini digunakan sebagai identitas toko pada floating logo beranda dan header dokumen.</p>
                        </div>

                        <?php
                        $logoFile = uploads_directory() . '/logo.png';
                        $hasLogo = file_exists($logoFile);
                        ?>
                        <?php if ($hasLogo): ?>
                            <div class="logo-active-card">
                                <div class="logo-active-avatar">
                                    <img src="../uploads/logo.png?v=<?= filemtime($logoFile) ?>" alt="Logo Saat Ini">
                                </div>
                                <div class="logo-active-info">
                                    <span class="logo-active-badge">✓ Logo Aktif</span>
                                    <span class="logo-active-meta">Format: PNG murni (logo.png)</span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <form action="" method="post" enctype="multipart/form-data" data-submit-lock id="logo-upload-form">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                            
                            <!-- Modern Drag & Drop Zone Logo -->
                            <div class="hero-dropzone" id="logo-dropzone" tabindex="0" role="button" aria-label="Pilih atau seret file logo PNG ke sini">
                                <input type="file" id="logo-file-input" name="logo" class="hero-file-input" accept="image/png" required>
                                <div class="hero-dropzone-content" id="logo-dropzone-prompt">
                                    <div class="hero-dropzone-icon">
                                        <?= admin_icon('image') ?>
                                    </div>
                                    <div class="hero-dropzone-text">
                                        <strong>Pilih atau seret logo PNG ke sini</strong>
                                        <span>Format: PNG murni (.png), maks. 5 MB</span>
                                    </div>
                                </div>

                                <!-- Pre-upload Live Preview Area -->
                                <div class="hero-preview-area" id="logo-preview-area" hidden>
                                    <div class="logo-active-avatar logo-preview-avatar">
                                        <img id="logo-preview-img" src="" alt="Pratinjau Logo Baru">
                                    </div>
                                    <div class="hero-preview-meta">
                                        <span id="logo-preview-filename" class="hero-preview-filename"></span>
                                        <button type="button" class="btn btn-secondary btn-sm" id="logo-preview-reset-btn">Ganti Foto</button>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-with-icon btn-upload-slider">
                                <?= admin_icon('save') ?><span>Simpan Logo PNG</span>
                            </button>
                        </form>

                        <!-- Box Panduan Spesifikasi Logo -->
                        <div class="slider-spec-guide">
                            <div class="slider-spec-title">
                                <span>💡 Panduan Logo Ideal:</span>
                            </div>
                            <ul class="slider-spec-list">
                                <li><strong>Format Wajib</strong>: Khusus file gambar PNG murni (disimpan sebagai <code>logo.png</code>).</li>
                                <li><strong>Rasio 1:1</strong>: Bentuk persegi atau lingkaran (rekomendasi minimal 512 × 512 px).</li>
                                <li><strong>Transparansi</strong>: Gunakan latar belakang transparan agar menyatu indah dengan warna latar beranda.</li>
                            </ul>
                        </div>
                    </section>

                    <!-- Kolom Kanan: Konten Sambutan & Live Simulator -->
                    <section class="settings-panel slider-gallery-panel">
                        <div class="slider-panel-header">
                            <h3>Konten Sambutan Homepage</h3>
                            <p class="settings-copy">Ubah judul dan deskripsi pembuka yang dilihat pengunjung pada kolom kiri beranda.</p>
                        </div>

                        <form action="" method="post" data-submit-lock id="home-content-form">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                            
                            <div class="form-group">
                                <label for="home-title-input">Judul Sambutan</label>
                                <textarea id="home-title-input" name="home_title" rows="2" maxlength="160" required><?= htmlspecialchars($current_home_title, ENT_QUOTES, 'UTF-8') ?></textarea>
                                <div class="field-meta">
                                    <span id="title-counter" class="char-counter">0 / 160</span>
                                    <small class="settings-hint">Ideal 30–60 karakter untuk judul menarik</small>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="home-desc-input">Deskripsi Sambutan</label>
                                <textarea id="home-desc-input" name="home_description" rows="5" maxlength="1200" required><?= htmlspecialchars($current_home_description, ENT_QUOTES, 'UTF-8') ?></textarea>
                                <div class="field-meta">
                                    <span id="desc-counter" class="char-counter">0 / 1200</span>
                                    <small class="settings-hint">Ideal 150–300 karakter untuk narasi pembuka</small>
                                </div>
                            </div>

                            <button type="submit" name="save_home_content" class="btn btn-primary btn-with-icon">
                                <?= admin_icon('save') ?><span>Simpan Konten Homepage</span>
                            </button>
                        </form>

                        <div class="section-hr"></div>

                        <!-- Homepage Mini Simulator (Realtime Live Preview) -->
                        <div class="simulator-wrapper">
                            <div class="simulator-header">
                                <span>📱 Simulasi Tampilan di Beranda (Live Preview)</span>
                                <small>Teks ter-update seketika saat Anda mengetik</small>
                            </div>
                            <div class="home-simulator-card" aria-live="polite">
                                <h4 id="sim-title" class="sim-title"><?= nl2br(htmlspecialchars($current_home_title, ENT_QUOTES, 'UTF-8')) ?></h4>
                                <p id="sim-desc" class="sim-desc"><?= nl2br(htmlspecialchars($current_home_description, ENT_QUOTES, 'UTF-8')) ?></p>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <!-- Tab 3: Database & Backup -->
            <div id="tab-database" class="settings-tab-pane" role="tabpanel" aria-labelledby="tab-btn-database"<?= $activeTab === 'tab-database' ? '' : ' hidden' ?>>
                <?php
                require_once __DIR__ . '/../includes/DatabaseMigrator.php';
                require_once __DIR__ . '/helpers/backup_helper.php';
                $migrator = new DatabaseMigrator($pdo);
                $statusList = $migrator->getStatus();
                $pendingMigrations = $migrator->getPendingMigrations();
                $lastApplied = null;
                foreach ($statusList as $st) {
                    if ($st['status'] === 'applied') {
                        $lastApplied = $st['version'];
                    }
                }
                $latestRestoreDb = get_latest_restore_activity(null, 'database');
                $latestRestoreImages = get_latest_restore_activity(null, 'images');
                ?>
                <div class="settings-grid">
                    <!-- Kolom Kiri: Ekspor Cadangan Data & Kesehatan Skema -->
                    <section class="settings-panel slider-upload-panel">
                        <div class="slider-panel-header">
                            <h3>Cadangkan Data &amp; Status Skema</h3>
                            <p class="settings-copy">Unduh salinan cadangan database dan seluruh foto produk untuk penyimpanan aman di laptop Anda.</p>
                        </div>
                        
                        <!-- Quick Backup Cards -->
                        <div class="backup-cards-grid">
                            <div class="backup-card">
                                <div class="backup-card-head">
                                    <div class="backup-card-icon"><?= admin_icon('database') ?></div>
                                    <div class="backup-card-info">
                                        <strong>Backup Database (.sql)</strong>
                                        <span>Katalog, varian, dan data HPP</span>
                                    </div>
                                </div>
                                <a href="export_db.php" class="btn btn-backup-database btn-sm btn-with-icon" data-download-status data-loading-label="Menyiapkan backup database...">
                                    <?= admin_icon('database') ?><span>Unduh .SQL</span>
                                </a>
                            </div>
                            <div class="backup-card">
                                <div class="backup-card-head">
                                    <div class="backup-card-icon"><?= admin_icon('image') ?></div>
                                    <div class="backup-card-info">
                                        <strong>Backup Gambar (.zip)</strong>
                                        <span>Seluruh foto di uploads/</span>
                                    </div>
                                </div>
                                <a href="export_images.php" class="btn btn-backup-images btn-sm btn-with-icon" data-download-status data-loading-label="Menyiapkan backup gambar...">
                                    <?= admin_icon('image') ?><span>Unduh .ZIP</span>
                                </a>
                            </div>
                        </div>

                        <!-- Database Schema Card -->
                        <div class="db-schema-card">
                            <div class="db-schema-header">
                                <div>
                                    <strong>Versi Skema Terakhir:</strong>
                                    <div class="db-schema-ver"><code><?= htmlspecialchars($lastApplied ?? 'v05_hpp.sql') ?></code></div>
                                </div>
                                <?php if (empty($pendingMigrations)): ?>
                                    <span class="db-health-badge is-healthy"><?= admin_icon('check') ?>Up to Date</span>
                                <?php else: ?>
                                    <span class="db-health-badge is-pending">Perlu Update (<?= count($pendingMigrations) ?>)</span>
                                <?php endif; ?>
                            </div>
                            <p class="settings-copy db-schema-copy">Sistem migrasi skema database otomatis langsung dari panel admin tanpa phpMyAdmin cPanel.</p>
                            
                            <?php if (empty($pendingMigrations)): ?>
                                <div class="migration-status-ok"><?= admin_icon('check') ?>Database up-to-date (Semua versi migrasi sudah aktif)</div>
                            <?php else: ?>
                                <span class="migration-status-pending">Ada <?= count($pendingMigrations) ?> migrasi yang belum diterapkan:</span>
                                <ul class="migration-status-list">
                                    <?php foreach ($pendingMigrations as $p): ?>
                                        <li><code><?= htmlspecialchars($p) ?></code></li>
                                    <?php endforeach; ?>
                                </ul>
                                <form action="" method="post" class="form-spaced" data-submit-lock>
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                                    <button type="submit" name="run_migrations" class="btn btn-primary btn-with-icon" onclick="return confirm('Terapkan migrasi database sekarang?')"><?= admin_icon('check') ?><span>Jalankan Migrasi</span></button>
                                </form>
                            <?php endif; ?>
                        </div>

                        <!-- Box Panduan Backup -->
                        <div class="slider-spec-guide">
                            <div class="slider-spec-title">
                                <span>💡 Panduan Backup Aman:</span>
                            </div>
                            <ul class="slider-spec-list">
                                <li><strong>Rutin Cadangkan</strong>: Unduh backup minimal sekali seminggu atau sebelum memperbarui resep masakan.</li>
                                <li><strong>Simpan di Luar Server</strong>: Simpan file backup di drive laptop atau Google Drive Anda.</li>
                            </ul>
                        </div>
                    </section>

                    <!-- Kolom Kanan: Pemulihan Data & Gambar (Restore) -->
                    <section class="settings-panel slider-gallery-panel">
                        <div class="slider-panel-header">
                            <h3>Pemulihan Data &amp; Gambar (Restore)</h3>
                            <p class="settings-copy">Pulihkan data database atau berkas foto produk jika terjadi kendala pada website.</p>
                        </div>

                        <!-- Restore Database (.sql) -->
                        <div class="restore-card">
                            <div class="restore-card-header">
                                <h4>Restore Database (.sql)</h4>
                                <span class="restore-limit-badge">Maks. 10 MB</span>
                            </div>
                            <p class="settings-copy">Pulihkan database dari file backup <code>.sql</code>. Sistem wajib memverifikasi kesesuaian versi skema sebelum mengeksekusi restore.</p>
                            <div class="alert alert-error hpp-slim-alert">
                                <span class="hpp-slim-alert-icon"><?= admin_icon('info') ?></span>
                                <span class="hpp-slim-alert-text">PERINGATAN: Seluruh data katalog, varian, dan resep saat ini akan ditimpa oleh data file backup.</span>
                            </div>
                            <form action="restore_db.php" method="post" enctype="multipart/form-data" data-submit-lock onsubmit="return confirm('PERINGATAN: Seluruh data saat ini akan digantikan oleh isi file backup database. Tindakan ini tidak dapat dibatalkan.\n\nPastikan versi skema backup sama dengan versi sistem saat ini (<?= htmlspecialchars($lastApplied ?? 'v05_hpp.sql') ?>).\n\nLanjutkan restore database?')">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                                <div class="form-group">
                                    <label for="restore-sql-file">Pilih File Backup (.sql)</label>
                                    <input type="file" id="restore-sql-file" name="backup_file" accept=".sql" required class="form-control">
                                    <small class="hpp-help-text">Maksimal 10 MB. Versi skema aktif saat ini: <strong><?= htmlspecialchars($lastApplied ?? 'v05_hpp.sql') ?></strong></small>
                                </div>
                                <button type="submit" class="btn btn-danger btn-with-icon" data-loading-label="Memverifikasi &amp; me-restore...">
                                    <?= admin_icon('upload') ?><span>Restore Database</span>
                                </button>
                            </form>
                            <?php if ($latestRestoreDb): ?>
                                <div class="restore-status-box <?= $latestRestoreDb['status'] === 'success' ? 'is-success' : 'is-error' ?>" role="status">
                                    <div class="restore-status-header">
                                        <strong>Riwayat Restore Database Terakhir</strong>
                                        <span class="restore-status-time"><?= htmlspecialchars($latestRestoreDb['formatted_date'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <div class="restore-status-body <?= $latestRestoreDb['status'] === 'success' ? 'is-success' : 'is-error' ?>">
                                        <?= admin_icon($latestRestoreDb['status'] === 'success' ? 'check' : 'info') ?>
                                        <span>
                                            <?php if ($latestRestoreDb['file']): ?>
                                                <code><?= htmlspecialchars($latestRestoreDb['file'], ENT_QUOTES, 'UTF-8') ?></code> &mdash;
                                            <?php endif; ?>
                                            <?= htmlspecialchars($latestRestoreDb['summary'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Restore Gambar (.zip) -->
                        <div class="restore-card restore-card-spaced">
                            <div class="restore-card-header">
                                <h4>Restore Gambar (.zip)</h4>
                                <span class="restore-limit-badge">Maks. 50 MB</span>
                            </div>
                            <p class="settings-copy">Ekstrak dan pulihkan file gambar produk, slider beranda, dan logo dari file arsip <code>.zip</code> hasil backup ke folder <code>uploads/</code>.</p>
                            <form action="restore_images.php" method="post" enctype="multipart/form-data" data-submit-lock onsubmit="return confirm('Ekstrak dan pulihkan file gambar dari file arsip ZIP ini ke folder uploads?')">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                                <div class="form-group">
                                    <label for="restore-images-file">Pilih File Arsip Gambar (.zip)</label>
                                    <input type="file" id="restore-images-file" name="images_zip" accept=".zip,application/zip" required class="form-control">
                                    <small class="hpp-help-text">Maksimal 50 MB. Hanya file gambar (.jpg, .png, .webp, .svg, .gif) yang akan diekstrak.</small>
                                </div>
                                <button type="submit" class="btn btn-secondary btn-with-icon" data-loading-label="Mengekstrak gambar...">
                                    <?= admin_icon('upload') ?><span>Restore Gambar</span>
                                </button>
                            </form>
                            <?php if ($latestRestoreImages): ?>
                                <div class="restore-status-box <?= $latestRestoreImages['status'] === 'success' ? 'is-success' : 'is-error' ?>" role="status">
                                    <div class="restore-status-header">
                                        <strong>Riwayat Restore Gambar Terakhir</strong>
                                        <span class="restore-status-time"><?= htmlspecialchars($latestRestoreImages['formatted_date'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <div class="restore-status-body <?= $latestRestoreImages['status'] === 'success' ? 'is-success' : 'is-error' ?>">
                                        <?= admin_icon($latestRestoreImages['status'] === 'success' ? 'check' : 'info') ?>
                                        <span>
                                            <?php if ($latestRestoreImages['file']): ?>
                                                <code><?= htmlspecialchars($latestRestoreImages['file'], ENT_QUOTES, 'UTF-8') ?></code> &mdash;
                                            <?php endif; ?>
                                            <?= htmlspecialchars($latestRestoreImages['summary'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
            </div>

            <!-- Tab 4: Keamanan Akun -->
            <div id="tab-keamanan" class="settings-tab-pane" role="tabpanel" aria-labelledby="tab-btn-keamanan"<?= $activeTab === 'tab-keamanan' ? '' : ' hidden' ?>>
                <div class="settings-grid">
                    <!-- Kolom Kiri: Formulir Ubah Password -->
                    <section class="settings-panel slider-upload-panel">
                        <div class="slider-panel-header">
                            <h3>Ubah Password Admin</h3>
                            <p class="settings-copy">Perbarui kata sandi akun administrator untuk menjaga keamanan toko online Anda.</p>
                        </div>
                        <form action="" method="post" data-submit-lock id="change-password-form">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                            
                            <div class="form-group">
                                <label for="new-password-input">Password Baru <span class="field-info-tip" tabindex="0" data-tip="Minimal 6 karakter. Disarankan kombinasi huruf besar, kecil, dan angka." title="Minimal 6 karakter. Disarankan kombinasi huruf besar, kecil, dan angka." aria-label="Minimal 6 karakter. Disarankan kombinasi huruf besar, kecil, dan angka."><?= admin_icon('info') ?></span></label>
                                <div class="password-input-wrapper">
                                    <input type="password" id="new-password-input" name="new_password" required minlength="6" class="form-control" autocomplete="new-password">
                                    <button type="button" class="btn-toggle-password" id="btn-toggle-pwd" aria-label="Tampilkan atau sembunyikan password" title="Lihat password">
                                        👁️
                                    </button>
                                </div>
                                <div class="pwd-strength-container" id="pwd-strength-box" hidden>
                                    <div class="pwd-strength-bar">
                                        <div class="pwd-strength-meter" id="pwd-meter"></div>
                                    </div>
                                    <span class="pwd-strength-label" id="pwd-label">Kekuatan: Lemah</span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="confirm-password-input">Konfirmasi Password Baru</label>
                                <input type="password" id="confirm-password-input" class="form-control" autocomplete="new-password">
                                <small id="pwd-match-hint" class="settings-hint"></small>
                            </div>

                            <button type="submit" name="change_password" id="btn-submit-password" class="btn btn-secondary btn-with-icon">
                                <?= admin_icon('key') ?><span>Perbarui Password</span>
                            </button>
                        </form>

                        <div class="slider-spec-guide spec-guide-spaced">
                            <div class="slider-spec-title">
                                <span>💡 Tips Kata Sandi yang Kuat:</span>
                            </div>
                            <ul class="slider-spec-list">
                                <li><strong>Minimal 6 Karakter</strong>: Disarankan kombinasi huruf besar, kecil, dan angka.</li>
                                <li><strong>Keunikan</strong>: Hindari menggunakan kata umum atau tanggal lahir.</li>
                            </ul>
                        </div>
                    </section>

                    <!-- Kolom Kanan: Ringkasan Sesi & Keamanan -->
                    <section class="settings-panel slider-gallery-panel">
                        <div class="slider-panel-header">
                            <h3>Ringkasan Status Keamanan</h3>
                            <p class="settings-copy">Informasi sesi akun yang aktif dan mekanisme proteksi sistem Alfi Kitchen.</p>
                        </div>

                        <div class="security-overview-card">
                            <div class="sec-item">
                                <div class="sec-icon-ok"><?= admin_icon('check') ?></div>
                                <div class="sec-info">
                                    <strong>Pengguna Aktif:</strong>
                                    <span><?= htmlspecialchars($_SESSION['admin_user'] ?? 'Administrator') ?></span>
                                </div>
                            </div>
                            <div class="sec-item">
                                <div class="sec-icon-ok"><?= admin_icon('check') ?></div>
                                <div class="sec-info">
                                    <strong>Batas Timeout Sesi:</strong>
                                    <span>1.800 detik (30 Menit) otomatis logout jika idle</span>
                                </div>
                            </div>
                            <div class="sec-item">
                                <div class="sec-icon-ok"><?= admin_icon('check') ?></div>
                                <div class="sec-info">
                                    <strong>Proteksi Formulir (CSRF Token):</strong>
                                    <span>Aktif memvalidasi setiap pengiriman formulir POST</span>
                                </div>
                            </div>
                            <div class="sec-item">
                                <div class="sec-icon-ok"><?= admin_icon('check') ?></div>
                                <div class="sec-info">
                                    <strong>Keamanan Cookie Sesi:</strong>
                                    <span>HttpOnly + SameSite=Lax (Aman dari serangan XSS)</span>
                                </div>
                            </div>
                        </div>

                        <div class="sec-audit-box">
                            <div class="sec-audit-header">
                                <strong>Log Aktivitas Administrator</strong>
                                <a href="activity_log.php" class="btn btn-sm btn-secondary btn-with-icon"><?= admin_icon('log') ?><span>Lihat Log Lengkap</span></a>
                            </div>
                            <p class="settings-copy sec-copy-spaced">Seluruh riwayat login, perubahan profil, dan aktivitas data dicatat secara otomatis dalam sistem.</p>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var tabBtns = document.querySelectorAll('.settings-tab-btn');
        var tabPanes = document.querySelectorAll('.settings-tab-pane');

        function switchTab(targetId) {
            tabBtns.forEach(function (btn) {
                var isTarget = btn.getAttribute('data-tab') === targetId;
                btn.classList.toggle('is-active', isTarget);
                btn.setAttribute('aria-selected', isTarget ? 'true' : 'false');
            });
            tabPanes.forEach(function (pane) {
                if (pane.id === targetId) {
                    pane.removeAttribute('hidden');
                } else {
                    pane.setAttribute('hidden', '');
                }
            });
            if (history.replaceState) {
                history.replaceState(null, '', '#' + targetId);
            }
        }

        tabBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                switchTab(btn.getAttribute('data-tab'));
            });
        });

        var hash = window.location.hash ? window.location.hash.substring(1) : '';
        if (hash && document.getElementById(hash)) {
            switchTab(hash);
        }

        // Reusable Drag & Drop with Instant Live Preview
        function initDropzone(dzId, inputId, promptId, prevAreaId, imgId, nameId, resetId, acceptType) {
            var dz = document.getElementById(dzId), inp = document.getElementById(inputId);
            var pmt = document.getElementById(promptId), prev = document.getElementById(prevAreaId);
            var img = document.getElementById(imgId), nm = document.getElementById(nameId), rst = document.getElementById(resetId);
            if (!dz || !inp) return;
            function show(file) {
                if (!file || (acceptType === 'png' ? file.type !== 'image/png' : !file.type.match(/^image\//))) {
                    if (acceptType === 'png') alert('Format file harus berupa gambar PNG yang valid (.png)');
                    return;
                }
                var r = new FileReader();
                r.onload = function(e) {
                    if (img) img.src = e.target.result;
                    if (nm) nm.textContent = file.name + ' (' + Math.round(file.size / 1024) + ' KB)';
                    if (pmt) pmt.hidden = true;
                    if (prev) prev.hidden = false;
                };
                r.readAsDataURL(file);
            }
            inp.addEventListener('change', function() { if (this.files && this.files[0]) show(this.files[0]); });
            ['dragenter', 'dragover'].forEach(function(ev) { dz.addEventListener(ev, function(e) { e.preventDefault(); dz.classList.add('is-dragover'); }); });
            ['dragleave', 'drop'].forEach(function(ev) { dz.addEventListener(ev, function(e) { e.preventDefault(); dz.classList.remove('is-dragover'); }); });
            dz.addEventListener('drop', function(e) { if (e.dataTransfer && e.dataTransfer.files[0]) { inp.files = e.dataTransfer.files; show(e.dataTransfer.files[0]); } });
            if (rst) rst.addEventListener('click', function(e) { e.preventDefault(); inp.value = ''; if (img) img.src = ''; if (prev) prev.hidden = true; if (pmt) pmt.hidden = false; });
        }
        initDropzone('hero-dropzone', 'hero-file-input', 'hero-dropzone-prompt', 'hero-preview-area', 'hero-preview-img', 'hero-preview-filename', 'hero-preview-reset-btn', 'image');
        initDropzone('logo-dropzone', 'logo-file-input', 'logo-dropzone-prompt', 'logo-preview-area', 'logo-preview-img', 'logo-preview-filename', 'logo-preview-reset-btn', 'png');

        // Live Homepage Simulator & Character Counters
        var titleInput = document.getElementById('home-title-input');
        var descInput = document.getElementById('home-desc-input');
        var titleCounter = document.getElementById('title-counter');
        var descCounter = document.getElementById('desc-counter');
        var simTitle = document.getElementById('sim-title');
        var simDesc = document.getElementById('sim-desc');

        function escapeHtml(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML.replace(/\n/g, '<br>');
        }
        function updateTitle() {
            if (!titleInput) return;
            var val = titleInput.value;
            if (titleCounter) {
                titleCounter.textContent = val.length + ' / ' + titleInput.maxLength;
                titleCounter.classList.toggle('is-warning', val.length > 120);
                titleCounter.classList.toggle('is-max', val.length >= titleInput.maxLength);
            }
            if (simTitle) simTitle.innerHTML = val.trim() ? escapeHtml(val) : '<em>(Judul Sambutan)</em>';
        }
        function updateDesc() {
            if (!descInput) return;
            var val = descInput.value;
            if (descCounter) {
                descCounter.textContent = val.length + ' / ' + descInput.maxLength;
                descCounter.classList.toggle('is-warning', val.length > 1000);
                descCounter.classList.toggle('is-max', val.length >= descInput.maxLength);
            }
            if (simDesc) simDesc.innerHTML = val.trim() ? escapeHtml(val) : '<em>(Deskripsi Sambutan)</em>';
        }
        if (titleInput) { titleInput.addEventListener('input', updateTitle); updateTitle(); }
        if (descInput) { descInput.addEventListener('input', updateDesc); updateDesc(); }

        // Live WhatsApp & Footer Simulator
        var waInput = document.getElementById('wa-number-input');
        var waSimNum = document.getElementById('wa-sim-number');
        var waTestLink = document.getElementById('wa-sim-test-link');
        if (waInput) {
            waInput.addEventListener('input', function() {
                var num = this.value.replace(/[^0-9]/g, '');
                if (waSimNum) waSimNum.textContent = num ? num : 'Belum diatur';
                if (waTestLink) waTestLink.href = 'https://wa.me/' + num;
            });
        }
        var ftHours = document.getElementById('footer-hours-input');
        var ftAddr = document.getElementById('footer-addr-input');
        var ftIg = document.getElementById('footer-ig-input');
        var ftFb = document.getElementById('footer-fb-input');
        var ftTt = document.getElementById('footer-tt-input');
        function updateFooterSim() {
            var sh = document.getElementById('sim-footer-hours'), sa = document.getElementById('sim-footer-addr');
            if (sh && ftHours) sh.textContent = ftHours.value.trim() || 'Jam Operasional: Belum diisi';
            if (sa && ftAddr) sa.textContent = ftAddr.value.trim() || 'Alamat toko belum diisi';
            var bIg = document.getElementById('sim-badge-ig'), bFb = document.getElementById('sim-badge-fb'), bTt = document.getElementById('sim-badge-tt');
            if (bIg && ftIg) bIg.classList.toggle('is-active', ftIg.value.trim().length > 0);
            if (bFb && ftFb) bFb.classList.toggle('is-active', ftFb.value.trim().length > 0);
            if (bTt && ftTt) bTt.classList.toggle('is-active', ftTt.value.trim().length > 0);
        }
        [ftHours, ftAddr, ftIg, ftFb, ftTt].forEach(function(el) { if (el) el.addEventListener('input', updateFooterSim); });

        // Password Show/Hide Toggle & Strength Meter
        var pwdInput = document.getElementById('new-password-input');
        var pwdToggle = document.getElementById('btn-toggle-pwd');
        var pwdBox = document.getElementById('pwd-strength-box');
        var pwdMeter = document.getElementById('pwd-meter');
        var pwdLabel = document.getElementById('pwd-label');
        var confirmPwd = document.getElementById('confirm-password-input');
        var matchHint = document.getElementById('pwd-match-hint');

        if (pwdToggle && pwdInput) {
            pwdToggle.addEventListener('click', function() {
                var isPwd = pwdInput.type === 'password';
                pwdInput.type = isPwd ? 'text' : 'password';
                if (confirmPwd) confirmPwd.type = pwdInput.type;
                pwdToggle.textContent = isPwd ? '🔒' : '👁️';
            });
        }
        function checkMatch() {
            if (!confirmPwd || !pwdInput || !matchHint) return;
            if (!confirmPwd.value) { matchHint.textContent = ''; return; }
            if (confirmPwd.value === pwdInput.value) {
                matchHint.textContent = '✓ Password cocok';
                matchHint.style.color = '#278347';
            } else {
                matchHint.textContent = '⚠ Konfirmasi password belum sama';
                matchHint.style.color = '#e53e3e';
            }
        }
        if (pwdInput) {
            pwdInput.addEventListener('input', function() {
                var val = this.value;
                if (!val) { if (pwdBox) pwdBox.hidden = true; checkMatch(); return; }
                if (pwdBox) pwdBox.hidden = false;
                var score = 0;
                if (val.length >= 6) score++;
                if (val.length >= 10) score++;
                if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
                if (/[0-9]/.test(val)) score++;
                if (/[^A-Za-z0-9]/.test(val)) score++;
                if (pwdMeter && pwdLabel) {
                    pwdMeter.className = 'pwd-strength-meter';
                    if (score <= 2) {
                        pwdMeter.classList.add('is-weak');
                        pwdLabel.textContent = 'Kekuatan: Lemah';
                        pwdLabel.style.color = '#e53e3e';
                    } else if (score <= 3) {
                        pwdMeter.classList.add('is-medium');
                        pwdLabel.textContent = 'Kekuatan: Cukup Baik';
                        pwdLabel.style.color = '#dd6b20';
                    } else {
                        pwdMeter.classList.add('is-strong');
                        pwdLabel.textContent = 'Kekuatan: Sangat Kuat';
                        pwdLabel.style.color = '#38a169';
                    }
                }
                checkMatch();
            });
        }
        if (confirmPwd) confirmPwd.addEventListener('input', checkMatch);
    });
    </script>
</body>
</html>
