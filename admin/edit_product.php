<?php
session_start();
require '../config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

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
    $imagePath = $product['image']; // default ke gambar lama
    
    // Cek jika ada upload gambar baru
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $newName = uniqid() . '.' . $ext;
        $targetDir = '../uploads/';
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        
        // Hapus gambar lama jika ada
        if ($imagePath && file_exists('../' . $imagePath)) {
            unlink('../' . $imagePath);
        }
        
        move_uploaded_file($_FILES['image']['tmp_name'], $targetDir . $newName);
        $imagePath = 'uploads/' . $newName;
    }

    $stmt = $pdo->prepare("UPDATE products SET name = ?, image = ? WHERE id = ?");
    $stmt->execute([$name, $imagePath, $id]);
    
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Kategori - Admin</title>
    <style>
        :root { --accent: #e8935a; --accent-hover: #d17d47; --bg: #fff8ef; --text: #4a3728; --card: #ffffff; }
        body { font-family: 'Inter', Arial, sans-serif; background: var(--bg); margin: 0; color: var(--text); }
        header { background: var(--text); color: white; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        header h2 { margin: 0; font-size: 1.5rem; }
        
        .container { 
            max-width: 500px; margin: 40px auto; 
            background: var(--card); padding: 30px 40px; 
            border-radius: 16px; 
            box-shadow: 0 15px 35px rgba(232, 147, 90, 0.08); 
            border: 1px solid rgba(232, 147, 90, 0.2);
        }
        .container h3 { margin-top: 0; font-size: 1.4rem; border-bottom: 2px solid #f0ddc0; padding-bottom: 10px; margin-bottom: 25px;}
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 0.95rem; }
        .form-group input[type="text"], .form-group input[type="file"] { 
            width: 100%; padding: 12px 15px; 
            border: 2px solid #f0ddc0; border-radius: 8px; 
            box-sizing: border-box; font-size: 1rem; color: var(--text);
            transition: all 0.2s ease;
        }
        .form-group input[type="text"]:focus { 
            outline: none; border-color: var(--accent); 
            box-shadow: 0 0 0 4px rgba(232, 147, 90, 0.15); 
        }
        
        .preview-img { width: 80px; height: 80px; object-fit: cover; border-radius: 12px; border: 2px solid #f0ddc0; margin-bottom: 10px; display: block; }
        
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
    <header><h2>Alfi Kitchen Admin</h2></header>
    <div class="container">
        <h3>Edit Kategori Produk</h3>
        <form method="POST" enctype="multipart/form-data">
            
            <div class="form-group">
                <label>Nama Kategori</label>
                <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required placeholder="Contoh: Puding">
            </div>
            
            <div class="form-group">
                <label>Gambar Kategori <span style="font-weight:normal; color:#888; font-size:0.85rem;">(Opsional)</span></label>
                <?php if($product['image']): ?>
                    <img src="../<?= htmlspecialchars($product['image']) ?>" class="preview-img" alt="Preview">
                <?php endif; ?>
                <input type="file" name="image" accept="image/*">
                <small style="color: #888; display: block; margin-top: 5px;">Biarkan kosong jika tidak ingin mengubah gambar.</small>
            </div>
            
            <div class="btn-group">
                <a href="index.php" class="btn btn-cancel">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
            
        </form>
    </div>
</body>
</html>
