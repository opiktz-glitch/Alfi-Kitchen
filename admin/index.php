<?php
require 'auth.php';
require '../config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

// Menangani Hapus Produk
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Token CSRF tidak valid.');
    }

    $productId = (int) $_POST['delete'];

    // Ambil path gambar sebelum hapus
    $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $prod = $stmt->fetch();
    if ($prod && $prod['image'] && file_exists('../' . $prod['image'])) {
        unlink('../' . $prod['image']);
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
    <title>Admin Panel - Alfi Kitchen</title>
    <style>
        :root { --accent: #e8935a; --accent-hover: #d17d47; --bg: #fff8ef; --text: #4a3728; --card: #ffffff; --line: #f0ddc0;}
        body { font-family: 'Inter', Arial, sans-serif; background: var(--bg); margin: 0; color: var(--text); }
        header { background: var(--text); color: white; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        header h2 { margin: 0; font-size: 1.5rem; }
        
        .container { max-width: 1000px; margin: 40px auto; padding: 0 20px;}
        .header-action { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;}
        .header-action h2 { margin: 0; font-size: 1.8rem; }
        
        .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); border: 1px solid var(--line); background: var(--card); }
        table { width: 100%; min-width: 500px; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; vertical-align: middle; border-bottom: 1px solid var(--line); }
        th { background: #fff1de; font-weight: 700; color: var(--text); }
        tr:last-child td { border-bottom: none; }
        
        .btn { 
            padding: 10px 18px; border: none; border-radius: 8px; cursor: pointer; 
            font-weight: bold; font-size: 0.95rem; text-align: center; display: inline-block;
            transition: all 0.2s ease; text-decoration: none; margin-right: 5px; margin-bottom: 5px;
        }
        .btn-primary { background: var(--accent); color: white; box-shadow: 0 4px 12px rgba(232,147,90,0.3); }
        .btn-primary:hover { background: var(--accent-hover); transform: translateY(-2px); box-shadow: 0 6px 15px rgba(232,147,90,0.4); }
        
        .btn-secondary { background: #8a745e; color: white; }
        .btn-secondary:hover { background: #73614e; transform: translateY(-2px); }
        
        .btn-danger { background: #d9534f; color: white; }
        .btn-danger:hover { background: #c9302c; transform: translateY(-2px); }
        
        .btn-cancel { background: #f0ddc0; color: var(--text); }
        .btn-cancel:hover { background: #e6ceaa; transform: translateY(-2px); }
        
        .thumb { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }

        @media (max-width: 600px) {
            header { padding: 15px 20px; }
            .container { margin: 20px auto; }
            .header-action { flex-direction: column; align-items: flex-start; }
            .btn { width: 100%; margin-right: 0; box-sizing: border-box; }
            td { display: block; border-bottom: none; padding: 10px 15px; }
            td:first-child { padding-top: 15px; }
            td:last-child { padding-bottom: 15px; border-bottom: 1px solid var(--line); }
            tr:last-child td:last-child { border-bottom: none; }
            th { display: none; }
            table { min-width: 100%; }
        }
    </style>
</head>
<body>
    <header>
        <h2>Alfi Kitchen Admin</h2>
        <a href="logout.php" style="color: #e8935a; font-weight: bold; text-decoration: none;">Logout</a>
    </header>
    <div class="container">
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
