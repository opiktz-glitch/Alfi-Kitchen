<?php
require 'auth.php';
require '../config.php';
require 'upload_helper.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$message = '';
$messageClass = 'alert';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['logo'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Token CSRF tidak valid.');
    }

    $upload = store_uploaded_image($_FILES['logo'], 'logo', true, ['image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/webp', 'image/pjpeg'], 'logo.png');
    if ($upload['error']) {
        $message = $upload['error'];
        $messageClass = 'alert alert-error';
    } else {
        $message = "Logo berhasil diperbarui!";
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Token CSRF tidak valid.');
    }

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
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Token CSRF tidak valid.');
    }

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
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Token CSRF tidak valid.');
    }

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
    <style>
        body { font-family: Arial, sans-serif; background: #fff8ef; margin: 0; color: #4a3728; }
        header { background: #4a3728; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        header a { color: #e8935a; text-decoration: none; font-weight: bold; margin-left: 15px;}
        .container { padding: 20px; max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; margin-top: 20px; border: 1px solid #f0ddc0;}
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold;}
        .btn { padding: 10px 15px; background: #e8935a; color: white; text-decoration: none; border-radius: 5px; border: none; cursor: pointer; }
        .alert { padding: 10px; background: #d4edda; color: #155724; border-radius: 5px; margin-bottom: 15px; }
        .alert-error { background: #f8d7da; color: #721c24; }
        .current-logo { width: 80px; height: 80px; object-fit: cover; border-radius: 50%; margin-bottom: 15px; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <header>
        <h2>Alfi Kitchen - Pengaturan</h2>
        <div>
            <a href="index.php">Kembali</a>
            <a href="logout.php">Logout</a>
        </div>
    </header>
    <div class="container">
        <h3>Pengaturan Logo</h3>
        <?php if($message): ?>
            <div class="<?= htmlspecialchars($messageClass) ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if(file_exists('../uploads/logo.png')): ?>
            <img src="../uploads/logo.png?v=<?= time() ?>" class="current-logo" alt="Logo Saat Ini">
        <?php endif; ?>

        <form action="" method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
            <div class="form-group">
                <label>Upload Logo Baru (Rasio 1:1, PNG/JPG/GIF/WEBP)</label>
                <input type="file" name="logo" accept="image/png,image/jpeg,image/jpg,image/gif,image/webp" required>
            </div>
            <button type="submit" class="btn">Simpan Logo</button>
        </form>

        <hr style="margin: 30px 0; border: 0; border-top: 1px solid #f0ddc0;">

        <h3>Konten Sambutan Homepage</h3>
        <p style="font-size: 14px; margin-bottom: 15px;">Ubah judul dan deskripsi yang tampil di halaman utama.</p>
        <form action="" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-group">
                <label for="home-title">Judul</label>
                <textarea id="home-title" name="home_title" rows="2" maxlength="160" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;"><?= htmlspecialchars($current_home_title, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <div class="form-group">
                <label for="home-description">Deskripsi</label>
                <textarea id="home-description" name="home_description" rows="5" maxlength="1200" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;"><?= htmlspecialchars($current_home_description, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <button type="submit" name="save_home_content" class="btn">Simpan Konten Homepage</button>
        </form>

        <hr style="margin: 30px 0; border: 0; border-top: 1px solid #f0ddc0;">

        <h3>Backup Data & Gambar</h3>
        <p style="font-size: 14px; margin-bottom: 15px;">Anda bisa mengekspor database (.sql) dan seluruh gambar produk (.zip) ke laptop Anda.</p>
        <div style="display: flex; gap: 10px; margin-bottom: 10px;">
            <a href="export_db.php" class="btn" style="background: #28a745; text-align: center;">📥 Backup Database</a>
            <a href="export_images.php" class="btn" style="background: #17a2b8; text-align: center;">🖼️ Backup Semua Gambar</a>
        </div>
        <hr style="margin: 30px 0; border: 0; border-top: 1px solid #f0ddc0;">

        <h3>Nomor WhatsApp</h3>
        <p style="font-size: 14px; margin-bottom: 15px;">Nomor ini akan digunakan untuk tombol "Pesan via WhatsApp" yang muncul di semua halaman. Gunakan format internasional tanpa tanda + (contoh: 6281234567890).</p>
        <form action="" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
            <div class="form-group">
                <label>Nomor WhatsApp</label>
                <input type="text" name="whatsapp_number" value="<?= htmlspecialchars($current_wa) ?>" placeholder="Contoh: 6281234567890" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </div>
            <button type="submit" name="save_whatsapp" class="btn" style="background: #25d366;">💬 Simpan Nomor WhatsApp</button>
        </form>

        <hr style="margin: 30px 0; border: 0; border-top: 1px solid #f0ddc0;">

        <h3>Ubah Password Admin</h3>
        <form action="" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
            <div class="form-group">
                <label>Password Baru</label>
                <input type="password" name="new_password" placeholder="Minimal 6 karakter" required minlength="6" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </div>
            <button type="submit" name="change_password" class="btn" style="background: #8a745e;">Update Password</button>
        </form>
    </div>
</body>
</html>
