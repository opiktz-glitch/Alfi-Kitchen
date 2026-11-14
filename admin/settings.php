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
                <div class="settings-grid">
                    <section class="settings-panel">
                        <h3>Unggah Gambar Slider Baru</h3>
                        <p class="settings-copy">Unggah foto banner promosi atau puding untuk slider beranda (Format: JPG/PNG/WebP, disarankan rasio lanskap/banner).</p>
                        <form action="" method="post" enctype="multipart/form-data" data-submit-lock>
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                            <div class="form-group">
                                <label>Pilih Gambar Slider</label>
                                <input type="file" name="hero_image" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-with-icon"><?= admin_icon('upload') ?>Unggah Gambar Slider</button>
                        </form>
                    </section>
                    <section class="settings-panel">
                        <h3>Daftar Slider Beranda Aktif (<?= count($hero_images) ?>)</h3>
                        <p class="settings-copy">Gambar-gambar berikut akan otomatis tampil bergantian di slider utama beranda website.</p>
                        <?php if (empty($hero_images)): ?>
                            <p class="dashboard-empty">Belum ada gambar slider yang diunggah.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Pratinjau</th>
                                            <th>Nama Berkas</th>
                                            <th class="table-cell-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($hero_images as $h): ?>
                                            <tr>
                                                <td class="table-cell-center">
                                                    <img src="../<?= htmlspecialchars($h['image']) ?>" class="thumb thumb-hero" alt="Hero Slide">
                                                </td>
                                                <td><?= htmlspecialchars(basename((string) $h['image'])) ?></td>
                                                <td class="table-cell-center">
                                                    <form method="post" action="" class="inline-action-form" data-submit-lock onsubmit="return confirm(<?= htmlspecialchars(json_encode('Hapus gambar slider "' . basename((string) $h['image']) . '"?'), ENT_QUOTES, 'UTF-8') ?>)">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                                                        <input type="hidden" name="delete_hero_image" value="<?= (int) $h['id'] ?>">
                                                        <button type="submit" class="category-icon-button category-action-delete" title="Hapus gambar slider" aria-label="Hapus gambar slider">
                                                            <?= admin_icon('delete') ?><span class="action-button-label">Hapus</span>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>
            </div>

            <!-- Tab 3: Logo & Konten -->
            <div id="tab-tampilan" class="settings-tab-pane" role="tabpanel" aria-labelledby="tab-btn-tampilan"<?= $activeTab === 'tab-tampilan' ? '' : ' hidden' ?>>
                <div class="settings-grid">
                    <section class="settings-panel">
                        <h3>Pengaturan Logo</h3>
                        <?php if(file_exists(uploads_directory() . '/logo.png')): ?>
                            <img src="../uploads/logo.png?v=<?= time() ?>" class="current-logo" alt="Logo Saat Ini">
                        <?php endif; ?>

                        <form action="" method="post" enctype="multipart/form-data" data-submit-lock>
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                            <div class="form-group">
                                <label>Upload Logo Baru (Rasio 1:1, PNG/JPG/GIF/WEBP)</label>
                                <input type="file" name="logo" accept="image/png,image/jpeg,image/jpg,image/gif,image/webp" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-with-icon"><?= admin_icon('save') ?>Simpan Logo</button>
                        </form>
                    </section>
                    <section class="settings-panel">
                        <h3>Konten Sambutan Homepage</h3>
                        <p class="settings-copy">Ubah judul dan deskripsi yang tampil di halaman utama.</p>
                        <form action="" method="post" data-submit-lock>
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                            <div class="form-group">
                                <label for="home-title">Judul</label>
                                <textarea id="home-title" name="home_title" rows="2" maxlength="160" required><?= htmlspecialchars($current_home_title, ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="home-description">Deskripsi</label>
                                <textarea id="home-description" name="home_description" rows="5" maxlength="1200" required><?= htmlspecialchars($current_home_description, ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>
                            <button type="submit" name="save_home_content" class="btn btn-primary btn-with-icon"><?= admin_icon('save') ?>Simpan Konten Homepage</button>
                        </form>
                    </section>
                </div>
            </div>

            <!-- Tab 3: Database & Backup -->
            <div id="tab-database" class="settings-tab-pane" role="tabpanel" aria-labelledby="tab-btn-database"<?= $activeTab === 'tab-database' ? '' : ' hidden' ?>>
                <div class="settings-grid">
                    <section class="settings-panel">
                        <h3>Backup Data &amp; Gambar</h3>
                        <p class="settings-copy">Anda bisa mengekspor database (.sql) dan seluruh gambar produk (.zip) ke laptop Anda.</p>
                        <div class="settings-actions">
                            <a href="export_db.php" class="btn btn-backup-database btn-with-icon" data-download-status data-loading-label="Menyiapkan backup database..."><?= admin_icon('database') ?>Backup Database</a>
                            <a href="export_images.php" class="btn btn-backup-images btn-with-icon" data-download-status data-loading-label="Menyiapkan backup gambar..."><?= admin_icon('image') ?>Backup Semua Gambar</a>
                        </div>
                    </section>

                    <section class="settings-panel">
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
    });
    </script>
</body>
</html>
