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
    header('Location: products.php' . ($redirectQuery !== '' ? '?' . http_build_query(['search' => $redirectQuery]) : ''));
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

$productIds = array_column($products, 'id');
$itemsByProduct = [];
if (!empty($productIds)) {
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $itemStmt = $pdo->prepare("SELECT * FROM product_items WHERE product_id IN ($placeholders) ORDER BY price ASC, id ASC");
    $itemStmt->execute($productIds);
    while ($row = $itemStmt->fetch(PDO::FETCH_ASSOC)) {
        $itemsByProduct[(int) $row['product_id']][] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>Kategori Produk - Alfi Kitchen</title>
    <link rel="stylesheet" href="assets/css/admin.css?v=<?= (int) filemtime(__DIR__ . '/assets/css/admin.css') . '-' . (int) filesize(__DIR__ . '/assets/css/admin.css') ?>">
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h2>Daftar Kategori Produk</h2>
                <div class="admin-header-actions">
                    <a href="../" target="_blank" rel="noopener noreferrer" class="btn-site-preview"><?= admin_icon('external') ?>Lihat Website</a>
                    <a href="logout.php" class="btn btn-danger btn-with-icon admin-header-logout"><?= admin_icon('logout') ?>Logout</a>
                </div>
            </header>
            <div class="admin-container">
        <div class="page-actions">
            <a href="add_product.php" class="btn btn-primary btn-with-icon"><?= admin_icon('add') ?>Tambah Kategori</a>
            <?php if (!empty($products)): ?>
                <button type="button" id="toggle-all-variants" class="btn btn-secondary btn-with-icon"><?= admin_icon('manage') ?><span id="toggle-all-text">Buka Semua Varian</span></button>
            <?php endif; ?>
        </div>
        <?php include __DIR__ . '/flash_message.php'; ?>
        <div class="category-toolbar">
            <form method="GET" action="products.php" role="search" class="category-search">
                <label for="category-search">Cari kategori</label>
                <div class="category-search-controls">
                    <input id="category-search" type="search" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Contoh: Puding">
                    <button type="submit" class="btn btn-secondary btn-with-icon"><?= admin_icon('search') ?>Cari</button>
                    <?php if ($search !== ''): ?>
                        <a href="products.php" class="btn btn-cancel btn-with-icon"><?= admin_icon('reset') ?>Reset</a>
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
                                        <a href="products.php" class="btn btn-cancel btn-with-icon"><?= admin_icon('reset') ?>Reset Pencarian</a>
                                    <?php else: ?>
                                        <h3>Belum ada kategori produk</h3>
                                        <p>Tambahkan kategori pertama agar produk dapat mulai dikelola.</p>
                                        <a href="add_product.php" class="btn btn-primary btn-with-icon"><?= admin_icon('add') ?>Tambah Kategori</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($products as $p): ?>
                    <tr class="category-main-row" data-product-id="<?= (int) $p['id'] ?>">
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
                        <td data-label="Jumlah varian" class="table-cell-center">
                            <button type="button" class="category-count category-expand-toggle" data-target="variants-row-<?= (int) $p['id'] ?>" aria-expanded="false" aria-controls="variants-row-<?= (int) $p['id'] ?>" title="Klik untuk melihat / menutup daftar varian">
                                <span><?= number_format((int) $p['item_count']) ?> varian</span>
                                <span class="expand-caret" aria-hidden="true">▾</span>
                            </button>
                        </td>
                        <td data-label="Aksi" class="table-cell-actions">
                            <div class="category-actions">
                                <div class="category-actions-main">
                                    <a href="manage_items.php?product_id=<?= (int) $p['id'] ?>" class="category-icon-button category-action-primary" aria-label="Kelola varian dan harga untuk <?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>" title="Kelola varian dan harga">
                                        <?= admin_icon('manage') ?>
                                        <span class="action-button-label">Kelola</span>
                                    </a>
                                    <a href="edit_product.php?id=<?= (int) $p['id'] ?>" class="category-icon-button category-action-secondary" aria-label="Edit kategori <?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>" title="Edit kategori">
                                        <?= admin_icon('edit') ?>
                                        <span class="action-button-label">Edit</span>
                                    </a>
                                </div>
                                <div class="category-actions-delete">
                                    <form method="POST" action="products.php<?= $search !== '' ? '?' . htmlspecialchars(http_build_query(['search' => $search]), ENT_QUOTES, 'UTF-8') : '' ?>" data-submit-lock onsubmit="return confirm(<?= htmlspecialchars(json_encode('Hapus kategori "' . $p['name'] . '" beserta seluruh varian dan harga jualnya dari website? Tindakan ini tidak dapat dibatalkan.', JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>)">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                                        <input type="hidden" name="delete" value="<?= (int) $p['id'] ?>">
                                        <input type="hidden" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="submit" class="category-icon-button category-action-delete" aria-label="Hapus kategori <?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>" title="Hapus kategori">
                                            <?= admin_icon('delete') ?>
                                            <span class="action-button-label">Hapus</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr id="variants-row-<?= (int) $p['id'] ?>" class="category-variants-row" hidden>
                        <td colspan="3" class="category-variants-cell">
                            <div class="category-variants-drawer">
                                <div class="variants-drawer-header">
                                    <div class="variants-drawer-title">
                                        <?= admin_icon('manage') ?>
                                        <strong>Varian &amp; Harga: <?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    </div>
                                    <div class="variants-drawer-actions">
                                        <a href="manage_items.php?product_id=<?= (int) $p['id'] ?>" class="btn btn-secondary btn-sm btn-with-icon"><?= admin_icon('add') ?> Kelola / Tambah Varian</a>
                                    </div>
                                </div>
                                <?php $categoryItems = $itemsByProduct[(int) $p['id']] ?? []; ?>
                                <?php if (empty($categoryItems)): ?>
                                    <div class="variants-empty-mini">
                                        <p>Kategori ini belum memiliki varian harga atau ukuran.</p>
                                        <a href="manage_items.php?product_id=<?= (int) $p['id'] ?>" class="btn btn-primary btn-sm btn-with-icon"><?= admin_icon('add') ?> Tambah Varian Pertama</a>
                                    </div>
                                <?php else: ?>
                                    <div class="variants-mini-grid">
                                        <?php foreach ($categoryItems as $item): ?>
                                            <div class="variant-mini-card">
                                                <div class="variant-mini-card-head">
                                                    <?php if (!empty($item['image'])): ?>
                                                        <img src="../<?= htmlspecialchars($item['image'], ENT_QUOTES, 'UTF-8') ?>" class="variant-mini-thumb" alt="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                                                    <?php else: ?>
                                                        <div class="variant-mini-thumb variant-mini-placeholder"><?= admin_icon('image') ?></div>
                                                    <?php endif; ?>
                                                    <div class="variant-mini-meta">
                                                        <span class="variant-mini-name"><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                                        <strong class="variant-mini-price">Rp <?= format_decimal(ceil((float) $item['price']), 0) ?></strong>
                                                    </div>
                                                </div>
                                                <div class="variant-mini-card-actions">
                                                    <a href="edit_item.php?id=<?= (int) $item['id'] ?>&amp;product_id=<?= (int) $p['id'] ?>" class="btn-variant-edit" title="Ubah harga / varian"><?= admin_icon('edit') ?> Ubah</a>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.category-expand-toggle').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                var targetId = btn.getAttribute('data-target');
                var row = document.getElementById(targetId);
                if (!row) return;
                var isHidden = row.hasAttribute('hidden');
                if (isHidden) {
                    row.removeAttribute('hidden');
                    btn.setAttribute('aria-expanded', 'true');
                    btn.classList.add('is-expanded');
                } else {
                    row.setAttribute('hidden', '');
                    btn.setAttribute('aria-expanded', 'false');
                    btn.classList.remove('is-expanded');
                }
            });
        });

        var toggleAllBtn = document.getElementById('toggle-all-variants');
        if (toggleAllBtn) {
            var allOpen = false;
            toggleAllBtn.addEventListener('click', function() {
                allOpen = !allOpen;
                document.querySelectorAll('.category-variants-row').forEach(function(row) {
                    if (allOpen) {
                        row.removeAttribute('hidden');
                    } else {
                        row.setAttribute('hidden', '');
                    }
                });
                document.querySelectorAll('.category-expand-toggle').forEach(function(btn) {
                    btn.setAttribute('aria-expanded', allOpen ? 'true' : 'false');
                    btn.classList.toggle('is-expanded', allOpen);
                });
                var txt = document.getElementById('toggle-all-text');
                if (txt) {
                    txt.textContent = allOpen ? 'Tutup Semua Varian' : 'Buka Semua Varian';
                }
            });
        }
    });
    </script>
</body>
</html>
