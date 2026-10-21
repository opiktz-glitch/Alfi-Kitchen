<?php
require 'auth_check.php';
require '../config.php';
require_once __DIR__ . '/upload_helper.php';
require 'csrf_check.php';

$product_id = $_GET['product_id'] ?? null;
$uploadError = '';
if(!$product_id) {
    die("Product ID tidak valid.");
}

// Ambil info Kategori (Daftar Produk utama)
$stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$main_product = $stmt->fetch();

if(!$main_product) {
    die("Produk utama tidak ditemukan.");
}

// Menangani Hapus Jenis Produk
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $deleteId = (int) $_POST['delete'];
    $stmt = $pdo->prepare("SELECT image FROM product_items WHERE id = ?");
    $stmt->execute([$deleteId]);
    $item = $stmt->fetch();
    if ($item && !empty($item['image'])) {
        delete_uploaded_image($item['image']);
    }

    $stmt = $pdo->prepare("DELETE FROM product_items WHERE id = ?");
    $stmt->execute([$deleteId]);
    log_admin_action('DELETE_PRODUCT_ITEM', 'product_id=' . $product_id . ', id=' . $deleteId);
    header("Location: manage_items.php?product_id=$product_id");
    exit;
}

// Tambah Jenis Produk Cepat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    
    $upload = store_uploaded_image($_FILES['image'] ?? null, 'item');
    if ($upload['error']) {
        $uploadError = $upload['error'];
    } else {
        $imagePath = $upload['path'] ?? '';
        $stmt = $pdo->prepare("INSERT INTO product_items (product_id, name, description, price, image) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$product_id, $name, $description, $price, $imagePath]);
        log_admin_action('ADD_PRODUCT_ITEM', 'product_id=' . $product_id . ', name=' . $name . ', image=' . $imagePath);
        header("Location: manage_items.php?product_id=$product_id");
        exit;
    }
}

// Mengambil Daftar Jenis Produk
$stmt = $pdo->prepare("SELECT * FROM product_items WHERE product_id = ? ORDER BY id DESC");
$stmt->execute([$product_id]);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Jenis - <?= htmlspecialchars($main_product['name']) ?></title>
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
        
        <div class="header-action">
            <h2>Kelola Jenis Produk: <?= htmlspecialchars($main_product['name']) ?></h2>
            <a href="index.php" class="btn btn-cancel">&laquo; Kembali ke Kategori</a>
        </div>

        <div class="form-card">
            <h3>Tambah Jenis Produk Baru</h3>
            <?php if ($uploadError): ?>
                <p role="alert" style="color:#721c24;"><?= htmlspecialchars($uploadError, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
            <form action="manage_items.php?product_id=<?= $product_id ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                <div class="form-group">
                    <label>Nama Varian/Jenis</label>
                    <input type="text" name="name" required placeholder="Contoh: Puding Mangga">
                </div>
                <div class="form-group">
                    <label>Harga (Rp)</label>
                    <input type="number" name="price" required placeholder="Contoh: 15000">
                </div>
                <div class="form-group">
                    <label>Deskripsi (Opsional)</label>
                    <textarea name="description" rows="3" placeholder="Tuliskan deskripsi singkat varian ini..."></textarea>
                </div>
                <div class="form-group">
                    <label>Gambar <span style="font-weight:normal; color:#888; font-size:0.85rem;">(Opsional) - Rekomendasi rasio gambar 1:1 (Persegi)</span></label>
                    <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
                </div>
                <button type="submit" name="add_item" class="btn btn-primary">Simpan Jenis Produk</button>
            </form>
        </div>

        <h3 style="margin-top: 40px; margin-bottom: 10px;">Daftar Jenis Produk</h3>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Gambar</th>
                        <th>Nama Jenis</th>
                        <th>Harga (Rp)</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($items)): ?>
                        <tr><td colspan="4" style="text-align:center; padding: 30px;">Belum ada varian produk.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <?php if($item['image']): ?>
                                <img src="../<?= htmlspecialchars($item['image']) ?>" class="thumb">
                            <?php else: ?>
                                <span style="color:#aaa;">-</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight:600; font-size:1.05rem;"><?= htmlspecialchars($item['name']) ?></td>
                        <td style="color:var(--accent); font-weight:bold;">Rp<?= number_format($item['price'], 0, ',', '.') ?></td>
                        <td>
                            <a href="edit_item.php?id=<?= $item['id'] ?>&product_id=<?= $product_id ?>" class="btn btn-cancel" style="margin-right:8px;">Edit</a>
                            <form method="POST" action="manage_items.php?product_id=<?= $product_id ?>" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus jenis ini?')">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                                <input type="hidden" name="delete" value="<?= (int) $item['id'] ?>">
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
