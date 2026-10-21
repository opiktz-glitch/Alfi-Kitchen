<?php
require 'auth_check.php';
require '../config.php';
require_once __DIR__ . '/upload_helper.php';
require 'csrf_check.php';
require_once __DIR__ . '/../includes/seo_helpers.php';

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = $_GET['id'];

// Ambil data lama
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    die("Produk tidak ditemukan.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];

    $upload = store_uploaded_image($_FILES['image'] ?? null, 'category');
    if ($upload['error']) {
        $uploadError = $upload['error'];
    } else {
        $imagePath = $upload['path'] ?? $product['image'];
        // SEO: slug dikunci permanen (dibuat sekali saat tambah produk).
        // Nama boleh diganti kapan pun, URL /produk/{slug} tetap sama agar
        // ranking Google & link yang sudah tersebar tidak reset/404.
        $slug = (string) ($product['slug'] ?? '');
        if ($slug === '') {
            // Backfill sekali untuk produk lama yang belum punya slug
            $slug = generate_unique_product_slug($pdo, (string) $product['name'], (int) $id);
            if ($slug === '') {
                $slug = 'produk-' . (int) $id;
            }
        }
        $stmt = $pdo->prepare("UPDATE products SET name = ?, slug = ?, image = ? WHERE id = ?");
        $stmt->execute([$name, $slug, $imagePath, $id]);
        if ($upload['path']) {
            delete_uploaded_image($product['image']);
        }
        log_admin_action('EDIT_PRODUCT', 'id=' . $id . ', name=' . $name . ', image=' . $imagePath);

        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Kategori - Admin</title>
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <header class="admin-header"><h2>Alfi Kitchen Admin</h2></header>
    <div class="admin-container">
        <div class="form-card form-card-sm">
            <h3>Edit Kategori Produk</h3>
            <?php if (!empty($uploadError)): ?>
                <p role="alert" style="color:#721c24;"><?= htmlspecialchars($uploadError, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                
                <div class="form-group">
                    <label>Nama Kategori</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required placeholder="Contoh: Puding">
                    <?php if (!empty($product['slug'])): ?>
                        <small style="color: #888; display: block; margin-top: 5px;">URL permanen: <code>/produk/<?= htmlspecialchars($product['slug']) ?></code> (tidak berubah walau nama diganti)</small>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label>Gambar Kategori <span style="font-weight:normal; color:#888; font-size:0.85rem;">(Opsional)</span></label>
                    <?php if($product['image']): ?>
                        <img src="../<?= htmlspecialchars($product['image']) ?>" class="preview-img" alt="Preview">
                    <?php endif; ?>
                    <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
                    <small style="color: #888; display: block; margin-top: 5px;">Biarkan kosong jika tidak ingin mengubah gambar.</small>
                </div>
                
                <div class="btn-group">
                    <a href="index.php" class="btn btn-cancel">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
                
            </form>
        </div>
    </div>
</body>
</html>
