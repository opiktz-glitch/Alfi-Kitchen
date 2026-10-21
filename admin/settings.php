<?php
require 'auth_check.php';
require '../config.php';
require_once __DIR__ . '/upload_helper.php';
require 'csrf_check.php';

$message = '';
$messageClass = 'alert';
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
}

// Ambil nomor WhatsApp dari database
$wa_stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'whatsapp_number'");
$wa_stmt->execute();
$wa_row = $wa_stmt->fetch();
$current_wa = $wa_row ? $wa_row['setting_value'] : '';

$home_content_stmt = $pdo->query(
    "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('home_title', 'home_description')"
);
$home_content = $home_content_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
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
    <header class="admin-header">
        <h2>Alfi Kitchen - Pengaturan</h2>
        <div>
            <a href="index.php" class="btn btn-cancel">Kembali</a>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
    </header>
    <div class="admin-container">
        <div class="form-card form-card-sm">
            <h3 style="margin-top:0;">Pengaturan Logo</h3>
            <?php if($message): ?>
                <div class="<?= htmlspecialchars($messageClass) ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
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

            <hr class="section-hr">

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

            <hr class="section-hr">

            <h3>Backup Data & Gambar</h3>
            <p style="font-size: 14px; margin-bottom: 15px; color: var(--muted);">Anda bisa mengekspor database (.sql) dan seluruh gambar produk (.zip) ke laptop Anda.</p>
            <div style="display: flex; gap: 10px; margin-bottom: 10px; flex-wrap: wrap;">
                <a href="export_db.php" class="btn" style="background: #28a745; color: white;">📥 Backup Database</a>
                <a href="export_images.php" class="btn" style="background: #17a2b8; color: white;">🖼️ Backup Semua Gambar</a>
            </div>

            <hr class="section-hr">

            <h3>Nomor WhatsApp</h3>
            <p style="font-size: 14px; margin-bottom: 15px; color: var(--muted);">Nomor ini akan digunakan untuk tombol "Pesan via WhatsApp" yang muncul di semua halaman. Gunakan format internasional tanpa tanda + (contoh: 6281234567890).</p>
            <form action="" method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                <div class="form-group">
                    <label>Nomor WhatsApp</label>
                    <input type="text" name="whatsapp_number" value="<?= htmlspecialchars($current_wa) ?>" placeholder="Contoh: 6281234567890" required>
                </div>
                <button type="submit" name="save_whatsapp" class="btn" style="background: #25d366; color: white;">💬 Simpan Nomor WhatsApp</button>
            </form>

            <hr class="section-hr">

            <h3>Ubah Password Admin</h3>
            <form action="" method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                <div class="form-group">
                    <label>Password Baru</label>
                    <input type="password" name="new_password" placeholder="Minimal 6 karakter" required minlength="6">
                </div>
                <button type="submit" name="change_password" class="btn btn-secondary">Update Password</button>
            </form>
        </div>
    </div>
</body>
</html>
