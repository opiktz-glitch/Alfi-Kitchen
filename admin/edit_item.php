<?php
session_start();
require '../config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

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
    $imagePath = $item['image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $newFilename = uniqid('item_') . '.' . $ext;
        $dest = '../uploads/' . $newFilename;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
            // Hapus gambar lama jika ada dan valid
            if ($imagePath && file_exists('../' . $imagePath)) {
                unlink('../' . $imagePath);
            }
            $imagePath = 'uploads/' . $newFilename;
        }
    }

    $stmt = $pdo->prepare("UPDATE product_items SET name = ?, description = ?, price = ?, image = ? WHERE id = ?");
    $stmt->execute([$name, $description, $price, $imagePath, $id]);
    
    header("Location: manage_items.php?product_id=$product_id");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Jenis Produk</title>
    <style>
        :root { --accent: #e8935a; --accent-hover: #d17d47; --bg: #fff8ef; --text: #4a3728; --card: #ffffff; }
        body { font-family: 'Inter', Arial, sans-serif; background: var(--bg); margin: 0; color: var(--text); }
        header { background: var(--text); color: white; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        header h2 { margin: 0; font-size: 1.5rem; }
        
        .container { 
            max-width: 600px; margin: 40px auto; 
            background: var(--card); padding: 30px 40px; 
            border-radius: 16px; 
            box-shadow: 0 15px 35px rgba(232, 147, 90, 0.08); 
            border: 1px solid rgba(232, 147, 90, 0.2);
        }
        .container h3 { margin-top: 0; font-size: 1.4rem; border-bottom: 2px solid #f0ddc0; padding-bottom: 10px; margin-bottom: 25px;}
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 0.95rem; }
        .form-group input[type="text"], .form-group input[type="number"], .form-group textarea, .form-group input[type="file"] { 
            width: 100%; padding: 12px 15px; 
            border: 2px solid #f0ddc0; border-radius: 8px; 
            box-sizing: border-box; font-size: 1rem; color: var(--text); font-family: inherit;
            transition: all 0.2s ease;
        }
        .form-group input[type="text"]:focus, .form-group input[type="number"]:focus, .form-group textarea:focus { 
            outline: none; border-color: var(--accent); 
            box-shadow: 0 0 0 4px rgba(232, 147, 90, 0.15); 
        }
        
        .thumb-preview { width: 80px; height: 80px; object-fit: cover; border-radius: 12px; border: 2px solid #f0ddc0; margin-bottom: 10px; display: block; }
        
        .btn-group { display: flex; gap: 15px; margin-top: 30px; }
        .btn { 
            padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; 
            font-weight: bold; font-size: 1rem; text-align: center; flex: 1;
            transition: all 0.2s ease; text-decoration: none;
        }
        .btn-primary { background: var(--accent); color: white; box-shadow: 0 4px 12px rgba(232,147,90,0.3); }
        .btn-primary:hover { background: var(--accent-hover); transform: translateY(-2px); box-shadow: 0 6px 15px rgba(232,147,90,0.4); }
        
        .btn-cancel { background: #f0ddc0; color: var(--text); }
        .btn-cancel:hover { background: #e6ceaa; transform: translateY(-2px); }
    </style>
</head>
<body>
    <header>
        <h2>Alfi Kitchen Admin</h2>
        <a href="logout.php" style="color: #e8935a; font-weight: bold; text-decoration: none;">Logout</a>
    </header>
    <div class="container">
        <h3>Edit Jenis Produk</h3>
        <form action="edit_item.php?id=<?= $id ?>&product_id=<?= $product_id ?>" method="POST" enctype="multipart/form-data">
            
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
                <input type="file" name="image" accept="image/*">
                <small style="color: #888; display: block; margin-top: 5px;">Biarkan kosong jika tidak ingin mengubah gambar.</small>
            </div>
            
            <div class="btn-group">
                <a href="manage_items.php?product_id=<?= $product_id ?>" class="btn btn-cancel">Batal</a>
                <button type="submit" name="edit_item" class="btn btn-primary">Simpan Perubahan</button>
            </div>
            
        </form>
    </div>
</body>
</html>
