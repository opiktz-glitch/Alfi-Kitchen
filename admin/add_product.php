<?php
require 'auth_check.php';
require '../config.php';
require_once __DIR__ . '/upload_helper.php';
require 'csrf_check.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];

    $upload = store_uploaded_image($_FILES['image'] ?? null, 'category', true);
    if ($upload['error']) {
        $uploadError = $upload['error'];
    } else {
        $imagePath = $upload['path'];
        $stmt = $pdo->prepare("INSERT INTO products (name, price, image) VALUES (?, ?, ?)");
        $stmt->execute([$name, 0, $imagePath]);
        $newId = (int) $pdo->lastInsertId();

        // SEO: slug URL ramah-SEO /produk/{slug} (anti-bentrok dengan produk lain)
        require_once __DIR__ . '/../includes/seo_helpers.php';
        $slug = generate_unique_product_slug($pdo, $name, $newId);
        if ($slug === '') {
            $slug = 'produk-' . $newId;
        }
        $pdo->prepare("UPDATE products SET slug = ? WHERE id = ?")->execute([$slug, $newId]);

        log_admin_action('ADD_PRODUCT', 'name=' . $name . ', image=' . $imagePath);

        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Kategori - Admin</title>
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <header class="admin-header"><h2>Alfi Kitchen Admin</h2></header>
    <div class="admin-container">
        <div class="form-card form-card-sm">
            <h3>Tambah Kategori Baru</h3>
            <?php if (!empty($uploadError)): ?>
                <p role="alert" style="color:#721c24;"><?= htmlspecialchars($uploadError, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                
                <div class="form-group">
                    <label>Nama Kategori</label>
                    <input type="text" name="name" required placeholder="Contoh: Kue Kering">
                </div>
                
                <div class="form-group">
                    <label>Gambar Kategori <span style="font-weight:normal; color:#888; font-size:0.85rem;">(Wajib)</span></label>
                    <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp" required>
                </div>
                
                <div class="btn-group">
                    <a href="index.php" class="btn btn-cancel">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Kategori</button>
                </div>
                
            </form>
        </div>
    </div>
</body>
</html>
