<?php
require 'auth_check.php';
require '../config.php';
require_once __DIR__ . '/upload_helper.php';
require 'csrf_check.php';

// Menangani Hapus Produk
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $productId = (int) $_POST['delete'];

    // Ambil path gambar sebelum hapus
    $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $prod = $stmt->fetch();
    if ($prod && !empty($prod['image'])) {
        delete_uploaded_image($prod['image']);
    }

    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    log_admin_action('DELETE_PRODUCT', 'id=' . $productId);
    header('Location: index.php');
    exit;
}

// Mengambil Daftar Produk
$stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin Panel - Alfi Kitchen</title>
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <header class="admin-header">
        <h2>Alfi Kitchen Admin</h2>
        <a href="logout.php" class="btn btn-danger">Logout</a>
    </header>
    <div class="admin-container">
        <div class="header-action">
            <h2>Daftar Kategori Produk</h2>
            <div>
                <a href="manage_hero.php" class="btn btn-secondary">Kelola Slider</a>
                <a href="settings.php" class="btn btn-secondary">Pengaturan</a>
                <a href="activity_log.php" class="btn btn-secondary">Log Aktivitas</a>
                <a href="add_product.php" class="btn btn-primary">+ Tambah Kategori</a>
            </div>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Gambar</th>
                        <th>Nama Kategori</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($products)): ?>
                        <tr><td colspan="3" style="text-align:center; padding: 30px;">Belum ada kategori produk.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($products as $p): ?>
                    <tr>
                        <td>
                            <?php if($p['image']): ?>
                                <img src="../<?= htmlspecialchars($p['image']) ?>" class="thumb">
                            <?php else: ?>
                                <span style="color:#aaa;">-</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight:600; font-size:1.05rem;"><?= htmlspecialchars($p['name']) ?></td>
                        <td>
                            <a href="manage_items.php?product_id=<?= $p['id'] ?>" class="btn btn-secondary">Kelola Jenis</a>
                            <a href="edit_product.php?id=<?= $p['id'] ?>" class="btn btn-cancel">Edit</a>
                            <form method="POST" action="index.php" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus?')">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                                <input type="hidden" name="delete" value="<?= (int) $p['id'] ?>">
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
