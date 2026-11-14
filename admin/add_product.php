<?php
require 'auth_check.php';
require_once __DIR__ . '/../includes/bootstrap.php';
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

        set_admin_flash('Kategori "' . $name . '" berhasil ditambahkan.');
        header('Location: products.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Kategori - Admin</title>
    <link rel="stylesheet" href="assets/css/admin.css?v=<?= (int) filemtime(__DIR__ . '/assets/css/admin.css') . '-' . (int) filesize(__DIR__ . '/assets/css/admin.css') ?>">
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h2>Tambah Kategori</h2>
                <div class="admin-header-actions">
                    <a href="../" target="_blank" rel="noopener noreferrer" class="btn-site-preview"><?= admin_icon('external') ?>Lihat Website</a>
                    <a href="logout.php" class="btn btn-danger btn-with-icon admin-header-logout"><?= admin_icon('logout') ?>Logout</a>
                </div>
            </header>
            <div class="admin-container">
        <div class="form-card form-card-sm">
            <h3>Informasi Kategori</h3>
            <?php if (!empty($uploadError)): ?>
                <p role="alert" class="text-error"><?= htmlspecialchars($uploadError, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data" class="admin-form-grid" data-submit-lock>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                
                <div class="form-group">
                    <label>Nama Kategori</label>
                    <input type="text" name="name" required placeholder="Contoh: Kue Kering">
                </div>
                
                <div class="form-group">
                    <label>Gambar Kategori <span class="text-muted">(Wajib)</span></label>
                    <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp" required>
                </div>
                
                <div class="btn-group">
                    <a href="products.php" class="btn btn-cancel btn-with-icon"><?= admin_icon('back') ?>Batal</a>
                    <button type="submit" class="btn btn-primary btn-with-icon"><?= admin_icon('save') ?>Simpan Kategori</button>
                </div>
                
            </form>
        </div>
    </div>
        </div>
    </div>
</body>
</html>
