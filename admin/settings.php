<?php
session_start();
require '../config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['logo'])) {
    $target_dir = "../uploads/";
    $target_file = $target_dir . "logo.png";
    $imageFileType = strtolower(pathinfo($_FILES["logo"]["name"], PATHINFO_EXTENSION));

    if (in_array($imageFileType, ['jpg', 'jpeg', 'png'])) {
        if (move_uploaded_file($_FILES["logo"]["tmp_name"], $target_file)) {
            $message = "Logo berhasil diperbarui!";
        } else {
            $message = "Maaf, terjadi kesalahan saat mengunggah file.";
        }
    } else {
        $message = "Maaf, hanya file JPG, JPEG & PNG yang diperbolehkan.";
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $new_password = $_POST['new_password'];
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
    if ($stmt->execute([$hashed])) {
        $message = "Password berhasil diubah! Gunakan password baru ini untuk login berikutnya.";
    } else {
        $message = "Terjadi kesalahan saat mengubah password.";
    }
}
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
            <div class="alert"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if(file_exists('../uploads/logo.png')): ?>
            <img src="../uploads/logo.png?v=<?= time() ?>" class="current-logo" alt="Logo Saat Ini">
        <?php endif; ?>

        <form action="" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label>Upload Logo Baru (Rasio 1:1, JPG/PNG)</label>
                <input type="file" name="logo" accept="image/png, image/jpeg" required>
            </div>
            <button type="submit" class="btn">Simpan Logo</button>
        </form>

        <hr style="margin: 30px 0; border: 0; border-top: 1px solid #f0ddc0;">

        <h3>Backup Data & Gambar</h3>
        <p style="font-size: 14px; margin-bottom: 15px;">Anda bisa mengekspor database (.sql) dan seluruh gambar produk (.zip) ke laptop Anda.</p>
        <div style="display: flex; gap: 10px; margin-bottom: 10px;">
            <a href="export_db.php" class="btn" style="background: #28a745; text-align: center;">📥 Backup Database</a>
            <a href="export_images.php" class="btn" style="background: #17a2b8; text-align: center;">🖼️ Backup Semua Gambar</a>
        </div>
        <hr style="margin: 30px 0; border: 0; border-top: 1px solid #f0ddc0;">

        <h3>Ubah Password Admin</h3>
        <form action="" method="post">
            <div class="form-group">
                <label>Password Baru</label>
                <input type="password" name="new_password" placeholder="Minimal 6 karakter" required minlength="6" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </div>
            <button type="submit" name="change_password" class="btn" style="background: #8a745e;">Update Password</button>
        </form>
    </div>
</body>
</html>
