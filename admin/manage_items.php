<?php
require 'auth.php';
require '../config.php';
require 'upload_helper.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

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
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Token CSRF tidak valid.');
    }

    $deleteId = (int) $_POST['delete'];
    $stmt = $pdo->prepare("SELECT image FROM product_items WHERE id = ?");
    $stmt->execute([$deleteId]);
    $item = $stmt->fetch();
    if ($item && $item['image'] && file_exists('../' . $item['image'])) {
        unlink('../' . $item['image']);
    }

    $stmt = $pdo->prepare("DELETE FROM product_items WHERE id = ?");
    $stmt->execute([$deleteId]);
    log_admin_action('DELETE_PRODUCT_ITEM', 'product_id=' . $product_id . ', id=' . $deleteId);
    header("Location: manage_items.php?product_id=$product_id");
    exit;
}

// Tambah Jenis Produk Cepat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Token CSRF tidak valid.');
    }

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
    <style>
        :root { --accent: #e8935a; --accent-hover: #d17d47; --bg: #fff8ef; --text: #4a3728; --card: #ffffff; --line: #f0ddc0;}
        body { font-family: 'Inter', Arial, sans-serif; background: var(--bg); margin: 0; color: var(--text); }
        header { background: var(--text); color: white; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        header h2 { margin: 0; font-size: 1.5rem; }
        
        .container { max-width: 1000px; margin: 40px auto; padding: 0 20px;}
        .header-action { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;}
        .header-action h2 { margin: 0; }
        
        .form-card { 
            background: var(--card); padding: 30px 40px; 
            border-radius: 16px; 
            box-shadow: 0 15px 35px rgba(232, 147, 90, 0.08); 
            border: 1px solid rgba(232, 147, 90, 0.2);
            margin-bottom: 40px;
        }
        .form-card h3 { margin-top: 0; font-size: 1.4rem; border-bottom: 2px solid var(--line); padding-bottom: 10px; margin-bottom: 25px;}
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 0.95rem; }
        .form-group input[type="text"], .form-group input[type="number"], .form-group textarea, .form-group input[type="file"] { 
            width: 100%; padding: 12px 15px; 
            border: 2px solid var(--line); border-radius: 8px; 
            box-sizing: border-box; font-size: 1rem; color: var(--text); font-family: inherit;
            transition: all 0.2s ease;
        }
        .form-group input[type="text"]:focus, .form-group input[type="number"]:focus, .form-group textarea:focus { 
            outline: none; border-color: var(--accent); 
            box-shadow: 0 0 0 4px rgba(232, 147, 90, 0.15); 
        }
        
        table { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 20px; background: var(--card); border-radius: 12px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.05); border: 1px solid var(--line); }
        th, td { padding: 15px; text-align: left; vertical-align: middle; border-bottom: 1px solid var(--line); }
        th { background: #fff1de; font-weight: 700; color: var(--text); }
        tr:last-child td { border-bottom: none; }
        
        .btn { 
            padding: 10px 18px; border: none; border-radius: 8px; cursor: pointer; 
            font-weight: bold; font-size: 0.95rem; text-align: center; display: inline-block;
            transition: all 0.2s ease; text-decoration: none;
        }
        .btn-primary { background: var(--accent); color: white; box-shadow: 0 4px 12px rgba(232,147,90,0.3); }
        .btn-primary:hover { background: var(--accent-hover); transform: translateY(-2px); box-shadow: 0 6px 15px rgba(232,147,90,0.4); }
        
        .btn-danger { background: #d9534f; color: white; }
        .btn-danger:hover { background: #c9302c; transform: translateY(-2px); }
        
        .btn-cancel { background: #f0ddc0; color: var(--text); }
        .btn-cancel:hover { background: #e6ceaa; transform: translateY(-2px); }
        
        .thumb { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <header>
        <h2>Alfi Kitchen Admin</h2>
        <a href="logout.php" style="color: #e8935a; font-weight: bold; text-decoration: none;">Logout</a>
    </header>
    <div class="container">
        
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
</body>
</html>
