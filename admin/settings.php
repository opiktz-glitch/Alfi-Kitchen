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
                    <section class="settings-panel">
                        <h3>Nomor WhatsApp</h3>
                        <p class="settings-copy">Nomor ini akan digunakan untuk tombol "Pesan via WhatsApp" yang muncul di semua halaman. Gunakan format internasional tanpa tanda + (contoh: 6281234567890).</p>
                        <form action="" method="post" data-submit-lock>
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                            <div class="form-group">
                                <label>Nomor WhatsApp</label>
                                <input type="text" name="whatsapp_number" value="<?= htmlspecialchars($current_wa) ?>" placeholder="Contoh: 6281234567890" required>
                            </div>
                            <button type="submit" name="save_whatsapp" class="btn btn-save-whatsapp btn-with-icon"><?= admin_icon('save') ?>Simpan Nomor WhatsApp</button>
                        </form>
                    </section>
                    <section class="settings-panel">
                        <h3>Info Footer</h3>
                        <p class="settings-copy">Informasi yang tampil di footer website.</p>
                        <form action="" method="post" data-submit-lock>
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                            <div class="form-group"><label>Instagram URL</label><input type="text" name="footer_instagram" value="<?= htmlspecialchars($current_footer['instagram']) ?>" placeholder="https://instagram.com/..."></div>
                            <div class="form-group"><label>Facebook URL</label><input type="text" name="footer_facebook" value="<?= htmlspecialchars($current_footer['facebook']) ?>" placeholder="https://facebook.com/..."></div>
                            <div class="form-group"><label>TikTok URL</label><input type="text" name="footer_tiktok" value="<?= htmlspecialchars($current_footer['tiktok']) ?>" placeholder="https://tiktok.com/@..."></div>
                            <div class="form-group"><label>Jam Operasional</label><input type="text" name="footer_hours" value="<?= htmlspecialchars($current_footer['hours']) ?>" placeholder="Senin-Sabtu 09:00-17:00"></div>
                            <div class="form-group"><label>Alamat</label><textarea name="footer_address" rows="2"><?= htmlspecialchars($current_footer['address']) ?></textarea></div>
                            <div class="form-group"><label>Email</label><input type="email" name="footer_email" value="<?= htmlspecialchars($current_footer['email']) ?>" placeholder="info@alfikitchen.com"></div>
                            <div class="form-group"><label>Link Google Maps</label><input type="text" name="footer_maps" value="<?= htmlspecialchars($current_footer['maps']) ?>" placeholder="https://maps.google.com/..."></div>
                            <button type="submit" name="save_footer_info" class="btn btn-primary btn-with-icon"><?= admin_icon('save') ?>Simpan Info Footer</button>
                        </form>
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
                                <li><strong>Rasio 16:9 (Lanskap)</strong>: Sangat cocok untuk tampilan desktop maupun mobile.</li>
                                <li><strong>Resolusi Rekomendasi</strong>: Minimal 1280 × 720 px (atau 1600 × 900 px).</li>
                                <li><strong>Area Teks Aman</strong>: Pusatkan teks promosi di bagian tengah agar tidak terpotong logo floating di beranda.</li>
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
                    <section class="settings-panel">
                        <h3>Unduh Backup Data &amp; Gambar</h3>
                        <p class="settings-copy">Anda bisa mengekspor database (.sql) dan seluruh gambar produk (.zip) ke laptop Anda.</p>
                        <div class="settings-actions">
                            <a href="export_db.php" class="btn btn-backup-database btn-with-icon" data-download-status data-loading-label="Menyiapkan backup database..."><?= admin_icon('database') ?>Backup Database</a>
                            <a href="export_images.php" class="btn btn-backup-images btn-with-icon" data-download-status data-loading-label="Menyiapkan backup gambar..."><?= admin_icon('image') ?>Backup Semua Gambar</a>
                        </div>
                    </section>

                    <section class="settings-panel">
                        <h3>Versi &amp; Migrasi Database</h3>
                        <p class="settings-copy">
                            Sistem migrasi skema database otomatis. Anda dapat menerapkan pembaruan struktur tabel langsung dari panel admin ini tanpa harus membuka phpMyAdmin cPanel.
                        </p>
                        <div class="migration-status">
                            <div class="migration-status-version">
                                <strong>Versi Skema Terakhir:</strong> <code><?= htmlspecialchars($lastApplied ?? 'Belum ada migrasi tercatat') ?></code>
                            </div>
                            <div>
                                <?php if (empty($pendingMigrations)): ?>
                                    <span class="migration-status-ok"><?= admin_icon('check') ?>Database up-to-date (Semua versi migrasi sudah aktif)</span>
                                <?php else: ?>
                                    <span class="migration-status-pending">Ada <?= count($pendingMigrations) ?> migrasi yang belum diterapkan:</span>
                                    <ul class="migration-status-list">
                                        <?php foreach ($pendingMigrations as $p): ?>
                                            <li><code><?= htmlspecialchars($p) ?></code></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!empty($pendingMigrations)): ?>
                        <form action="" method="post" class="form-spaced" data-submit-lock>
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                            <button type="submit" name="run_migrations" class="btn btn-primary btn-with-icon" onclick="return confirm('Terapkan migrasi database sekarang?')"><?= admin_icon('check') ?>Jalankan Migrasi</button>
                        </form>
                        <?php endif; ?>
                    </section>

                    <section class="settings-panel">
                        <h3>Restore Database (.sql)</h3>
                        <p class="settings-copy">
                            Pulihkan database dari file backup <code>.sql</code>. Sistem wajib memverifikasi kesesuaian versi skema sebelum mengeksekusi restore.
                        </p>
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
                    </section>

                    <section class="settings-panel">
                        <h3>Restore Gambar (.zip)</h3>
                        <p class="settings-copy">
                            Ekstrak dan pulihkan file gambar produk, slider beranda, dan logo dari file arsip <code>.zip</code> hasil backup ke folder <code>uploads/</code>.
                        </p>
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
                    </section>
                </div>
            </div>

            <!-- Tab 4: Keamanan Akun -->
            <div id="tab-keamanan" class="settings-tab-pane" role="tabpanel" aria-labelledby="tab-btn-keamanan"<?= $activeTab === 'tab-keamanan' ? '' : ' hidden' ?>>
                <div class="settings-grid">
                    <section class="settings-panel">
                        <h3>Ubah Password Admin</h3>
                        <form action="" method="post" data-submit-lock>
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                            <div class="form-group">
                                <label>Password Baru</label>
                                <input type="password" name="new_password" placeholder="Minimal 6 karakter" required minlength="6">
                            </div>
                            <button type="submit" name="change_password" class="btn btn-secondary btn-with-icon"><?= admin_icon('key') ?>Perbarui Password</button>
                        </form>
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

        // Slider Drag & Drop + Instant Live Preview
        var dropzone = document.getElementById('hero-dropzone');
        var fileInput = document.getElementById('hero-file-input');
        var promptArea = document.getElementById('hero-dropzone-prompt');
        var previewArea = document.getElementById('hero-preview-area');
        var previewImg = document.getElementById('hero-preview-img');
        var previewFilename = document.getElementById('hero-preview-filename');
        var resetBtn = document.getElementById('hero-preview-reset-btn');

        if (dropzone && fileInput) {
            function handleFile(file) {
                if (!file || !file.type.match(/^image\//)) return;
                var reader = new FileReader();
                reader.onload = function (e) {
                    if (previewImg) previewImg.src = e.target.result;
                    if (previewFilename) {
                        var sizeKb = Math.round(file.size / 1024);
                        previewFilename.textContent = file.name + ' (' + sizeKb + ' KB)';
                    }
                    if (promptArea) promptArea.hidden = true;
                    if (previewArea) previewArea.hidden = false;
                };
                reader.readAsDataURL(file);
            }

            fileInput.addEventListener('change', function () {
                if (this.files && this.files[0]) {
                    handleFile(this.files[0]);
                }
            });

            ['dragenter', 'dragover'].forEach(function (eventName) {
                dropzone.addEventListener(eventName, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.add('is-dragover');
                }, false);
            });

            ['dragleave', 'drop'].forEach(function (eventName) {
                dropzone.addEventListener(eventName, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.remove('is-dragover');
                }, false);
            });

            dropzone.addEventListener('drop', function (e) {
                var dt = e.dataTransfer;
                if (dt && dt.files && dt.files.length > 0) {
                    fileInput.files = dt.files;
                    handleFile(dt.files[0]);
                }
            });

            if (resetBtn) {
                resetBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    fileInput.value = '';
                    if (previewImg) previewImg.src = '';
                    if (previewArea) previewArea.hidden = true;
                    if (promptArea) promptArea.hidden = false;
                });
            }
        }

        // Tab Tampilan: Logo Drag & Drop + Pre-upload Preview
        var logoDropzone = document.getElementById('logo-dropzone');
        var logoInput = document.getElementById('logo-file-input');
        var logoPrompt = document.getElementById('logo-dropzone-prompt');
        var logoPreviewArea = document.getElementById('logo-preview-area');
        var logoPreviewImg = document.getElementById('logo-preview-img');
        var logoPreviewFilename = document.getElementById('logo-preview-filename');
        var logoResetBtn = document.getElementById('logo-preview-reset-btn');

        if (logoDropzone && logoInput) {
            function handleLogo(file) {
                if (!file || file.type !== 'image/png') {
                    alert('Format file harus berupa gambar PNG yang valid (.png)');
                    return;
                }
                var reader = new FileReader();
                reader.onload = function (e) {
                    if (logoPreviewImg) logoPreviewImg.src = e.target.result;
                    if (logoPreviewFilename) {
                        var sizeKb = Math.round(file.size / 1024);
                        logoPreviewFilename.textContent = file.name + ' (' + sizeKb + ' KB)';
                    }
                    if (logoPrompt) logoPrompt.hidden = true;
                    if (logoPreviewArea) logoPreviewArea.hidden = false;
                };
                reader.readAsDataURL(file);
            }

            logoInput.addEventListener('change', function () {
                if (this.files && this.files[0]) {
                    handleLogo(this.files[0]);
                }
            });

            ['dragenter', 'dragover'].forEach(function (eventName) {
                logoDropzone.addEventListener(eventName, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    logoDropzone.classList.add('is-dragover');
                }, false);
            });

            ['dragleave', 'drop'].forEach(function (eventName) {
                logoDropzone.addEventListener(eventName, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    logoDropzone.classList.remove('is-dragover');
                }, false);
            });

            logoDropzone.addEventListener('drop', function (e) {
                var dt = e.dataTransfer;
                if (dt && dt.files && dt.files.length > 0) {
                    logoInput.files = dt.files;
                    handleLogo(dt.files[0]);
                }
            });

            if (logoResetBtn) {
                logoResetBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    logoInput.value = '';
                    if (logoPreviewImg) logoPreviewImg.src = '';
                    if (logoPreviewArea) logoPreviewArea.hidden = true;
                    if (logoPrompt) logoPrompt.hidden = false;
                });
            }
        }

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
            if (simTitle) {
                simTitle.innerHTML = val.trim() ? escapeHtml(val) : '<em>(Judul Sambutan)</em>';
            }
        }

        function updateDesc() {
            if (!descInput) return;
            var val = descInput.value;
            if (descCounter) {
                descCounter.textContent = val.length + ' / ' + descInput.maxLength;
                descCounter.classList.toggle('is-warning', val.length > 1000);
                descCounter.classList.toggle('is-max', val.length >= descInput.maxLength);
            }
            if (simDesc) {
                simDesc.innerHTML = val.trim() ? escapeHtml(val) : '<em>(Deskripsi Sambutan)</em>';
            }
        }

        if (titleInput) {
            titleInput.addEventListener('input', updateTitle);
            updateTitle();
        }
        if (descInput) {
            descInput.addEventListener('input', updateDesc);
            updateDesc();
        }
    });
    </script>
</body>
</html>
