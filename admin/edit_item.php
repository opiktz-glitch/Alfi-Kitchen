<?php
require 'auth_check.php';
require '../config.php';
require_once __DIR__ . '/upload_helper.php';
require 'csrf_check.php';

$id = $_GET['id'] ?? null;
$product_id = $_GET['product_id'] ?? null;

if (!$id || !$product_id) {
    die("ID tidak valid.");
}

$stmt = $pdo->prepare("SELECT * FROM product_items WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    die("Item tidak ditemukan.");
}

// Menangani Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_item'])) {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    
    // Upload Gambar Baru (jika ada)
    $upload = store_uploaded_image($_FILES['image'] ?? null, 'item');
    if ($upload['error']) {
        $uploadError = $upload['error'];
    } else {
        $imagePath = $upload['path'] ?? $item['image'];
        $stmt = $pdo->prepare("UPDATE product_items SET name = ?, description = ?, price = ?, image = ? WHERE id = ?");
        $stmt->execute([$name, $description, $price, $imagePath, $id]);
        if ($upload['path']) {
            delete_uploaded_image($item['image']);
        }

        header("Location: manage_items.php?product_id=$product_id");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Jenis Produk</title>
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <header class="admin-header">
        <h2>Alfi Kitchen Admin</h2>
        <div class="admin-header-actions">
            <a href="../" target="_blank" rel="noopener noreferrer" class="btn-site-preview">Lihat Website ↗</a>
            <a href="index.php" class="btn btn-cancel">Dashboard</a>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
    </header>
    <div class="admin-container">
        <div class="form-card form-card-sm">
            <h3>Edit Jenis Produk</h3>
            <?php if (!empty($uploadError)): ?>
                <p role="alert" style="color:#721c24;"><?= htmlspecialchars($uploadError, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
            <form action="edit_item.php?id=<?= $id ?>&product_id=<?= $product_id ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                
                <div class="form-group">
                    <label>Nama Varian/Jenis</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($item['name']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Harga (Rp)</label>
                    <input type="number" name="price" value="<?= htmlspecialchars($item['price']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Deskripsi (Opsional)</label>
                    <textarea name="description" rows="4"><?= htmlspecialchars($item['description'] ?? '') ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Gambar <span style="font-weight:normal; color:#888; font-size:0.85rem;">(Opsional)</span></label>
                    <?php if($item['image']): ?>
                        <img src="../<?= htmlspecialchars($item['image']) ?>" class="thumb-preview" alt="Preview">
                    <?php endif; ?>
                    <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
                    <small style="color: #888; display: block; margin-top: 5px;">Biarkan kosong jika tidak ingin mengubah gambar.</small>
                </div>
                
                <div class="btn-group">
                    <a href="manage_items.php?product_id=<?= $product_id ?>" class="btn btn-cancel">Batal</a>
                    <button type="submit" name="edit_item" class="btn btn-primary">Simpan Perubahan</button>
                </div>
                
            </form>
        </div>
    </div>
</body>
</html>
