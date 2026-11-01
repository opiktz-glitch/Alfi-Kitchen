<?php
require 'auth_check.php';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/upload_helper.php';
require 'csrf_check.php';

// Menangani Hapus Produk
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $productId = (int) $_POST['delete'];

    $stmt = $pdo->prepare("SELECT name, image FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $prod = $stmt->fetch();

    if ($prod) {
        if (!empty($prod['image'])) {
            delete_uploaded_image($prod['image']);
        }

        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        log_admin_action('DELETE_PRODUCT', 'id=' . $productId);
        set_admin_flash('Kategori "' . $prod['name'] . '" berhasil dihapus.');
    } else {
        set_admin_flash('Kategori tidak ditemukan atau sudah dihapus.', 'error');
    }

    $redirectQuery = trim((string) ($_POST['search'] ?? ''));
    header('Location: index.php' . ($redirectQuery !== '' ? '?' . http_build_query(['search' => $redirectQuery]) : ''));
    exit;
}

$search = trim((string) ($_GET['search'] ?? ''));
$sql = "
    SELECT p.*,
           (SELECT COUNT(*) FROM product_items pi WHERE pi.product_id = p.id) AS item_count
    FROM products p
";
$params = [];
if ($search !== '') {
    $searchPattern = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search);
    $sql .= " WHERE p.name LIKE ? ESCAPE '!'";
    $params[] = '%' . $searchPattern . '%';
}
$sql .= ' ORDER BY p.id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
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
        <nav class="admin-nav" aria-label="Navigasi admin">
            <a href="manage_hero.php">Kelola Slider</a>
            <a href="settings.php">Pengaturan</a>
            <a href="activity_log.php">Log Aktivitas</a>
        </nav>
        <div class="admin-header-actions">
            <a href="../" target="_blank" rel="noopener noreferrer" class="btn-site-preview">Lihat Website ↗</a>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
    </header>
    <div class="admin-container">
        <div class="header-action">
            <h2>Daftar Kategori Produk</h2>
            <a href="add_product.php" class="btn btn-primary">+ Tambah Kategori</a>
        </div>
        <?php include __DIR__ . '/flash_message.php'; ?>
        <div class="category-toolbar">
            <form method="GET" action="index.php" role="search" class="category-search">
                <label for="category-search">Cari kategori</label>
                <div class="category-search-controls">
                    <input id="category-search" type="search" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Contoh: Puding">
                    <button type="submit" class="btn btn-secondary">Cari</button>
                    <?php if ($search !== ''): ?>
                        <a href="index.php" class="btn btn-cancel">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
            <?php if ($search !== ''): ?>
                <p class="category-search-result">Hasil pencarian untuk “<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>”: <?= count($products) ?> kategori</p>
            <?php endif; ?>
        </div>
        <div class="table-responsive category-table">
            <table>
                <thead>
                    <tr>
                        <th>Kategori</th>
                        <th>Jumlah Varian</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($products)): ?>
                        <tr>
                            <td colspan="3">
                                <div class="category-empty">
                                    <?php if ($search !== ''): ?>
                                        <h3>Kategori tidak ditemukan</h3>
                                        <p>Coba kata kunci lain atau reset pencarian.</p>
                                        <a href="index.php" class="btn btn-cancel">Reset Pencarian</a>
                                    <?php else: ?>
                                        <h3>Belum ada kategori produk</h3>
                                        <p>Tambahkan kategori pertama agar produk dapat mulai dikelola.</p>
                                        <a href="add_product.php" class="btn btn-primary">+ Tambah Kategori</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($products as $p): ?>
                    <tr>
                        <td data-label="Kategori">
                            <div class="category-summary">
                                <?php if (!empty($p['image'])): ?>
                                    <img src="../<?= htmlspecialchars($p['image'], ENT_QUOTES, 'UTF-8') ?>" class="category-thumb" alt="<?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy" decoding="async">
                                <?php else: ?>
                                    <div class="category-thumb category-thumb-placeholder" aria-hidden="true">AK</div>
                                <?php endif; ?>
                                <span class="category-name"><?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </td>
                        <td data-label="Jumlah varian">
                            <span class="category-count">
                                <?= number_format((int) $p['item_count']) ?> varian
                            </span>
                        </td>
                        <td data-label="Aksi">
                            <div class="category-actions">
                                <div class="category-actions-main">
                                    <a href="manage_items.php?product_id=<?= (int) $p['id'] ?>" class="btn btn-primary category-icon-button" aria-label="Kelola varian dan harga untuk <?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>" title="Kelola varian dan harga">
                                        <?= admin_icon('manage') ?>
                                    </a>
                                    <a href="edit_product.php?id=<?= (int) $p['id'] ?>" class="btn btn-cancel category-icon-button" aria-label="Edit kategori <?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>" title="Edit kategori">
                                        <?= admin_icon('edit') ?>
                                    </a>
                                </div>
                                <div class="category-actions-delete">
                                    <form method="POST" action="index.php<?= $search !== '' ? '?' . htmlspecialchars(http_build_query(['search' => $search]), ENT_QUOTES, 'UTF-8') : '' ?>" onsubmit="return confirm(<?= htmlspecialchars(json_encode('Hapus kategori "' . $p['name'] . '" beserta seluruh variannya?', JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>)">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                                        <input type="hidden" name="delete" value="<?= (int) $p['id'] ?>">
                                        <input type="hidden" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="submit" class="btn btn-danger category-icon-button" aria-label="Hapus kategori <?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>" title="Hapus kategori">
                                            <?= admin_icon('delete') ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
