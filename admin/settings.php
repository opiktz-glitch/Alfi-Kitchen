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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['logo'])) {
    $upload = store_uploaded_image($_FILES['logo'], 'logo', true, ['image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/webp', 'image/pjpeg'], 'logo.png');
    if ($upload['error']) {
        $message = $upload['error'];
        $messageClass = 'alert alert-error';
    } else {
        $message = "Logo berhasil diperbarui!";
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
$footer_stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('footer_instagram','footer_facebook','footer_tiktok','footer_hours','footer_address','footer_email','footer_maps')");
$footer_info = $footer_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pengaturan - Alfi Kitchen</title>
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h2>Alfi Kitchen - Pengaturan</h2>
                <div class="admin-header-actions">
                    <a href="../" target="_blank" rel="noopener noreferrer" class="btn-site-preview">Lihat Website ↗</a>
                    <a href="index.php" class="btn btn-cancel">Dashboard</a>
                    <a href="logout.php" class="btn btn-danger">Logout</a>
                </div>
            </header>
            <?php include __DIR__ . '/breadcrumb.php'; ?>
            <div class="admin-container">
        <div class="form-card settings-card">
            <?php if($message): ?>
                <div class="<?= htmlspecialchars($messageClass) ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <div class="settings-grid">
            <section class="settings-panel">
            <h3>Pengaturan Logo</h3>
            <?php if(file_exists(uploads_directory() . '/logo.png')): ?>
                <img src="../uploads/logo.png?v=<?= time() ?>" class="current-logo" alt="Logo Saat Ini">
            <?php endif; ?>

            <form action="" method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                <div class="form-group">
                    <label>Upload Logo Baru (Rasio 1:1, PNG/JPG/GIF/WEBP)</label>
                    <input type="file" name="logo" accept="image/png,image/jpeg,image/jpg,image/gif,image/webp" required>
                </div>
                <button type="submit" class="btn btn-primary">Simpan Logo</button>
            </form>
            </section>
            <section class="settings-panel">
            <h3>Konten Sambutan Homepage</h3>
            <p style="font-size: 14px; margin-bottom: 15px; color: var(--muted);">Ubah judul dan deskripsi yang tampil di halaman utama.</p>
            <form action="" method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <div class="form-group">
                    <label for="home-title">Judul</label>
                    <textarea id="home-title" name="home_title" rows="2" maxlength="160" required><?= htmlspecialchars($current_home_title, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div class="form-group">
                    <label for="home-description">Deskripsi</label>
                    <textarea id="home-description" name="home_description" rows="5" maxlength="1200" required><?= htmlspecialchars($current_home_description, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <button type="submit" name="save_home_content" class="btn btn-primary">Simpan Konten Homepage</button>
            </form>
            </section>
            <section class="settings-panel">
            <h3>Backup Data & Gambar</h3>
            <p style="font-size: 14px; margin-bottom: 15px; color: var(--muted);">Anda bisa mengekspor database (.sql) dan seluruh gambar produk (.zip) ke laptop Anda.</p>
            <div style="display: flex; gap: 10px; margin-bottom: 10px; flex-wrap: wrap;">
                <a href="export_db.php" class="btn btn-backup-database">Backup Database</a>
                <a href="export_images.php" class="btn btn-backup-images">Backup Semua Gambar</a>
            </div>

            <hr class="section-hr">

            <?php
            require_once __DIR__ . '/../includes/DatabaseMigrator.php';
            $migrator = new DatabaseMigrator($pdo);
            $statusList = $migrator->getStatus();
            $pendingMigrations = $migrator->getPendingMigrations();
            $lastApplied = null;
            foreach ($statusList as $st) {
                if ($st['status'] === 'applied') {
                    $lastApplied = $st['version'];
                }
            }
            ?>
            <h3>Versi & Migrasi Database</h3>
            <p style="font-size: 14px; margin-bottom: 15px; color: var(--muted);">
                Sistem migrasi skema database otomatis. Anda dapat menerapkan pembaruan struktur tabel langsung dari panel admin ini tanpa harus membuka phpMyAdmin cPanel.
            </p>
            <div style="background: #fff8ef; border: 1px solid var(--line); border-radius: 8px; padding: 15px; margin-bottom: 15px;">
                <div style="margin-bottom: 8px;">
                    <strong>Versi Skema Terakhir:</strong> <code><?= htmlspecialchars($lastApplied ?? 'Belum ada migrasi tercatat') ?></code>
                </div>
                <div>
                    <?php if (empty($pendingMigrations)): ?>
                        <span style="color: #155724; font-weight: bold;">✓ Database up-to-date (Semua versi migrasi sudah aktif)</span>
                    <?php else: ?>
                        <span style="color: #856404; font-weight: bold;">⚠️ Ada <?= count($pendingMigrations) ?> migrasi yang belum diterapkan:</span>
                        <ul style="margin: 8px 0 0 20px; font-size: 13px; color: #856404;">
                            <?php foreach ($pendingMigrations as $p): ?>
                                <li><code><?= htmlspecialchars($p) ?></code></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (!empty($pendingMigrations)): ?>
            <form action="" method="post" style="margin-bottom: 15px;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                <button type="submit" name="run_migrations" class="btn btn-primary" onclick="return confirm('Terapkan migrasi database sekarang?')">Jalankan Migrasi</button>
            </form>
            <?php endif; ?>
            </section>
            <section class="settings-panel">
            <h3>Info Footer</h3>
            <p style="font-size: 14px; margin-bottom: 15px; color: var(--muted);">Informasi yang tampil di footer website.</p>
            <form action="" method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                <div class="form-group"><label>Instagram URL</label><input type="text" name="footer_instagram" value="<?= htmlspecialchars($current_footer['instagram']) ?>" placeholder="https://instagram.com/..."></div>
                <div class="form-group"><label>Facebook URL</label><input type="text" name="footer_facebook" value="<?= htmlspecialchars($current_footer['facebook']) ?>" placeholder="https://facebook.com/..."></div>
                <div class="form-group"><label>TikTok URL</label><input type="text" name="footer_tiktok" value="<?= htmlspecialchars($current_footer['tiktok']) ?>" placeholder="https://tiktok.com/@..."></div>
                <div class="form-group"><label>Jam Operasional</label><input type="text" name="footer_hours" value="<?= htmlspecialchars($current_footer['hours']) ?>" placeholder="Senin-Sabtu 09:00-17:00"></div>
                <div class="form-group"><label>Alamat</label><textarea name="footer_address" rows="2"><?= htmlspecialchars($current_footer['address']) ?></textarea></div>
                <div class="form-group"><label>Email</label><input type="email" name="footer_email" value="<?= htmlspecialchars($current_footer['email']) ?>" placeholder="info@alfikitchen.com"></div>
                <div class="form-group"><label>Link Google Maps</label><input type="text" name="footer_maps" value="<?= htmlspecialchars($current_footer['maps']) ?>" placeholder="https://maps.google.com/..."></div>
                <button type="submit" name="save_footer_info" class="btn btn-primary">Simpan Info Footer</button>
            </form>
            </section>
            <section class="settings-panel">
            <h3>Nomor WhatsApp</h3>
            <p style="font-size: 14px; margin-bottom: 15px; color: var(--muted);">Nomor ini akan digunakan untuk tombol "Pesan via WhatsApp" yang muncul di semua halaman. Gunakan format internasional tanpa tanda + (contoh: 6281234567890).</p>
            <form action="" method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                <div class="form-group">
                    <label>Nomor WhatsApp</label>
                    <input type="text" name="whatsapp_number" value="<?= htmlspecialchars($current_wa) ?>" placeholder="Contoh: 6281234567890" required>
                </div>
                <button type="submit" name="save_whatsapp" class="btn btn-save-whatsapp">Simpan Nomor WhatsApp</button>
            </form>
            </section>
            <section class="settings-panel">
            <h3>Ubah Password Admin</h3>
            <form action="" method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                <div class="form-group">
                    <label>Password Baru</label>
                    <input type="password" name="new_password" placeholder="Minimal 6 karakter" required minlength="6">
                </div>
                <button type="submit" name="change_password" class="btn btn-secondary">Perbarui Password</button>
            </form>
            </section>
            </div>
        </div>
    </div>
        </div>
    </div>
</body>
</html>
