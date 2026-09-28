<?php
require 'auth.php';
require '../config.php';
require 'upload_helper.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Token CSRF tidak valid.');
    }

    $name = $_POST['name'];

    $upload = store_uploaded_image($_FILES['image'] ?? null, 'category', true);
    if ($upload['error']) {
        $uploadError = $upload['error'];
    } else {
        $imagePath = $upload['path'];
        $stmt = $pdo->prepare("INSERT INTO products (name, price, image) VALUES (?, ?, ?)");
        $stmt->execute([$name, 0, $imagePath]);
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
</body>
</html>
