<?php
session_start();
require '../config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$error = '';
$success = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("SELECT image FROM hero_images WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $img = $stmt->fetch();
    
    if ($img && file_exists('../' . $img['image'])) {
        unlink('../' . $img['image']);
    }
    
    $stmt = $pdo->prepare("DELETE FROM hero_images WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: manage_hero.php");
    exit;
}

// Handle Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['hero_image'])) {
    $file = $_FILES['hero_image'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($ext, $allowed)) {
            $filename = uniqid('hero_') . '.' . $ext;
            $path = '../uploads/' . $filename;
            
            if (!is_dir('../uploads')) {
                mkdir('../uploads', 0777, true);
            }
            
            if (move_uploaded_file($file['tmp_name'], $path)) {
                $db_path = 'uploads/' . $filename;
                $stmt = $pdo->prepare("INSERT INTO hero_images (image) VALUES (?)");
                $stmt->execute([$db_path]);
                $success = "Gambar berhasil ditambahkan!";
            } else {
                $error = "Gagal mengunggah gambar.";
            }
        } else {
            $error = "Format file tidak didukung (gunakan JPG, PNG, WEBP).";
        }
    }
}

// Fetch images
$stmt = $pdo->query("SELECT * FROM hero_images ORDER BY id DESC");
$hero_images = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Kelola Slider Animasi - Alfi Kitchen</title>
    <style>
        :root { --accent: #e8935a; --accent-hover: #d17d47; --bg: #fff8ef; --text: #4a3728; --card: #ffffff; --line: #f0ddc0;}
        body { font-family: 'Inter', Arial, sans-serif; background: var(--bg); margin: 0; color: var(--text); }
        header { background: var(--text); color: white; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        header h2 { margin: 0; font-size: 1.5rem; }
        
        .container { max-width: 1000px; margin: 40px auto; padding: 0 20px;}
        .header-action { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;}
        .header-action h2 { margin: 0; font-size: 1.8rem; }
        
        .form-card { background: var(--card); padding: 30px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); border: 1px solid var(--line); margin-bottom: 40px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.95rem; }
        .form-control { width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; font-size: 1rem; }
        
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
        
        .thumb { width: 120px; height: 70px; object-fit: cover; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

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
        <div>
            <a href="index.php" style="color: #f0ddc0; text-decoration: none; margin-right: 15px;">Dashboard</a>
            <a href="logout.php" style="color: #e8935a; font-weight: bold; text-decoration: none;">Logout</a>
        </div>
    </header>
    <div class="container">
        
        <div class="header-action">
            <h2>Kelola Slider Animasi</h2>
            <a href="index.php" class="btn btn-cancel">Kembali</a>
        </div>
        
        <?php if($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="form-card">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Unggah Gambar Animasi Baru</label>
                    <input type="file" name="hero_image" class="form-control" required>
                    <small style="color: var(--muted); margin-top: 5px; display: block;">
                        <strong>Ketentuan Gambar yang Ideal (Agar Tidak Distorsi/Penyok):</strong><br>
                        1. <strong>Rasio Landscape (16:9 atau 2:1)</strong> - Resolusi yang sangat direkomendasikan adalah <strong>1920 x 1080 pixel</strong> atau <strong>1600 x 800 pixel</strong>.<br>
                        2. <strong>Penyesuaian Otomatis (Fill)</strong> - Agar gambar selalu masuk 100% utuh tanpa menyisakan ruang kosong dan tanpa terpotong, gambar akan <strong>dipaksa ditarik/ditekan</strong> (di-<em>stretch</em>) menyesuaikan bingkai layar HP maupun Laptop. Pastikan gambar Anda memang berbentuk memanjang agar objeknya tidak terlihat terlalu gepeng/lonjong.
                    </small>
                </div>
                <button type="submit" class="btn btn-primary">Unggah Gambar</button>
            </form>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Gambar</th>
                        <th>Path File</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($hero_images)): ?>
                        <tr><td colspan="3" style="text-align:center; padding: 30px;">Belum ada gambar animasi. Sistem akan menggunakan gambar bawaan.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($hero_images as $h): ?>
                    <tr>
                        <td>
                            <img src="../<?= htmlspecialchars($h['image']) ?>" class="thumb">
                        </td>
                        <td style="color:var(--muted);"><?= htmlspecialchars($h['image']) ?></td>
                        <td>
                            <a href="manage_hero.php?delete=<?= $h['id'] ?>" class="btn btn-danger" onclick="return confirm('Yakin ingin menghapus gambar animasi ini?')">Hapus</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
