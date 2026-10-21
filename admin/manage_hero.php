<?php
require 'auth_check.php';
require '../config.php';
require_once __DIR__ . '/upload_helper.php';
require 'csrf_check.php';

$error = '';
$success = '';

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $deleteId = (int) $_POST['delete'];
    $stmt = $pdo->prepare("SELECT image FROM hero_images WHERE id = ?");
    $stmt->execute([$deleteId]);
    $img = $stmt->fetch();
    
    if ($img && !empty($img['image'])) {
        delete_uploaded_image($img['image']);
    }
    
    $stmt = $pdo->prepare("DELETE FROM hero_images WHERE id = ?");
    $stmt->execute([$deleteId]);
    log_admin_action('DELETE_HERO_IMAGE', 'id=' . $deleteId);
    header("Location: manage_hero.php");
    exit;
}

// Handle Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['hero_image'])) {
    $upload = store_uploaded_image($_FILES['hero_image'], 'hero', true);
    if ($upload['error']) {
        $error = $upload['error'];
    } else {
        $db_path = $upload['path'];
        $stmt = $pdo->prepare("INSERT INTO hero_images (image) VALUES (?)");
        $stmt->execute([$db_path]);
        log_admin_action('ADD_HERO_IMAGE', 'file=' . $db_path);
        $success = "Gambar berhasil ditambahkan!";
    }
}

// Fetch images
$stmt = $pdo->query("SELECT * FROM hero_images ORDER BY id DESC");
$hero_images = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Kelola Slider Animasi - Alfi Kitchen</title>
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <header class="admin-header">
        <h2>Alfi Kitchen Admin</h2>
        <div>
            <a href="index.php" class="btn btn-cancel">Dashboard</a>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
    </header>
    <div class="admin-container">
        
        <div class="header-action">
            <h2>Kelola Slider Animasi</h2>
            <a href="index.php" class="btn btn-cancel">Kembali</a>
        </div>
        
        <?php if($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="form-card">
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                <div class="form-group">
                    <label>Unggah Gambar Animasi Baru</label>
                    <input type="file" name="hero_image" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp" required>
                    <small style="color: var(--muted); margin-top: 5px; display: block;">
                        <strong>Ketentuan Gambar yang Ideal (Agar Tidak Distorsi/Penyok):</strong><br>
                        1. <strong>Rasio Landscape (16:9 atau 2:1)</strong> - Resolusi yang sangat direkomendasikan adalah <strong>1920 x 1080 pixel</strong> atau <strong>1600 x 800 pixel</strong>.<br>
                        2. <strong>Penyesuaian Otomatis (Fill)</strong> - Agar gambar selalu masuk 100% utuh tanpa menyisakan ruang kosong dan tanpa terpotong, gambar akan <strong>dipaksa ditarik/ditekan</strong> (di-<em>stretch</em>) menyesuaikan bingkai layar HP maupun Laptop. Pastikan gambar Anda memang berbentuk memanjang agar objeknya tidak terlihat terlalu gepeng/lonjong.
                    </small>
                </div>
                <button type="submit" class="btn btn-primary">Unggah Gambar</button>
            </form>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Gambar</th>
                        <th>Path File</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($hero_images)): ?>
                        <tr><td colspan="3" style="text-align:center; padding: 30px;">Belum ada gambar animasi. Sistem akan menggunakan gambar bawaan.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($hero_images as $h): ?>
                    <tr>
                        <td>
                            <img src="../<?= htmlspecialchars($h['image']) ?>" class="thumb thumb-hero" alt="Hero Slide">
                        </td>
                        <td style="color:var(--muted);"><?= htmlspecialchars($h['image']) ?></td>
                        <td>
                            <form method="POST" action="manage_hero.php" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus gambar animasi ini?')">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                                <input type="hidden" name="delete" value="<?= (int) $h['id'] ?>">
                                <button type="submit" class="btn btn-danger">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
