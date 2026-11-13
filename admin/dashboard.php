<?php
require 'auth_check.php';
require_once __DIR__ . '/../includes/bootstrap.php';
require 'csrf_check.php';

$productCount = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$variantCount = (int) $pdo->query('SELECT COUNT(*) FROM product_items')->fetchColumn();

$hppTables = ['hpp_ingredients', 'hpp_recipes', 'hpp_recipe_lines', 'hpp_recipe_costs'];
$existingTables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$hppReady = count(array_diff($hppTables, $existingTables)) === 0;
$recipeCount = $hppReady ? (int) $pdo->query('SELECT COUNT(*) FROM hpp_recipes')->fetchColumn() : null;
$ingredientCount = $hppReady ? (int) $pdo->query('SELECT COUNT(*) FROM hpp_ingredients')->fetchColumn() : null;

$recentActivities = [];
$logFile = __DIR__ . '/../logs/admin_activity.log';
if (is_readable($logFile)) {
    $logLines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($logLines === false) {
        throw new RuntimeException('Log aktivitas tidak dapat dibaca.');
    }

    foreach (array_reverse(array_slice($logLines, -5)) as $line) {
        if (preg_match('/^(\S+\s+\S+)\s+\|\s+ACTION\s+\|\s+username=(.*?)\s+\|\s+action=(.*?)\s+\|\s+details=(.*?)\s+\|\s+ip=/u', $line, $matches) === 1) {
            $recentActivities[] = [
                'time' => $matches[1],
                'action' => $matches[3],
                'details' => $matches[4],
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>Dashboard Admin - Alfi Kitchen</title>
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h2>Dashboard</h2>
                <div class="admin-header-actions">
                    <a href="../" target="_blank" rel="noopener noreferrer" class="btn-site-preview"><?= admin_icon('external') ?>Lihat Website</a>
                    <a href="logout.php" class="btn btn-danger btn-with-icon"><?= admin_icon('logout') ?>Logout</a>
                </div>
            </header>
            <main class="admin-container admin-dashboard">
                <?php include __DIR__ . '/flash_message.php'; ?>
                <section class="admin-dashboard-stats" aria-label="Ringkasan katalog">
                    <article class="admin-stat">
                        <span class="admin-stat-label">Kategori produk</span>
                        <strong class="admin-stat-value"><?= number_format($productCount) ?></strong>
                    </article>
                    <article class="admin-stat">
                        <span class="admin-stat-label">Varian produk</span>
                        <strong class="admin-stat-value"><?= number_format($variantCount) ?></strong>
                    </article>
                    <article class="admin-stat">
                        <span class="admin-stat-label">Resep HPP</span>
                        <strong class="admin-stat-value"><?= $recipeCount === null ? '—' : number_format($recipeCount) ?></strong>
                        <?php if (!$hppReady): ?><small class="dashboard-stat-note">Migrasi HPP belum diterapkan</small><?php endif; ?>
                    </article>
                    <article class="admin-stat">
                        <span class="admin-stat-label">Master bahan</span>
                        <strong class="admin-stat-value"><?= $ingredientCount === null ? '—' : number_format($ingredientCount) ?></strong>
                    </article>
                </section>

                <section class="admin-card dashboard-section" aria-labelledby="dashboard-shortcuts-title">
                    <div class="dashboard-section-heading">
                        <div>
                            <h3 id="dashboard-shortcuts-title">Akses cepat</h3>
                            <p>Pilih bagian yang ingin Anda kelola.</p>
                        </div>
                    </div>
                    <div class="dashboard-shortcuts">
                        <a href="products.php" class="dashboard-shortcut-card">
                            <span class="dashboard-shortcut-icon"><?= admin_icon('manage') ?></span>
                            <span class="dashboard-shortcut-copy">
                                <strong>Kelola Kategori &amp; Varian</strong>
                                <small>Atur kategori produk beserta variannya.</small>
                            </span>
                            <?= admin_icon('external') ?>
                        </a>
                        <a href="add_product.php" class="dashboard-shortcut-card">
                            <span class="dashboard-shortcut-icon"><?= admin_icon('add') ?></span>
                            <span class="dashboard-shortcut-copy">
                                <strong>Tambah Kategori</strong>
                                <small>Tambahkan kelompok produk baru.</small>
                            </span>
                            <?= admin_icon('external') ?>
                        </a>
                        <a href="hpp_recipes.php" class="dashboard-shortcut-card">
                            <span class="dashboard-shortcut-icon"><?= admin_icon('calculator') ?></span>
                            <span class="dashboard-shortcut-copy">
                                <strong>Kelola Resep HPP</strong>
                                <small>Periksa biaya dan kalkulasi resep.</small>
                            </span>
                            <?= admin_icon('external') ?>
                        </a>
                        <a href="hpp_ingredients.php" class="dashboard-shortcut-card">
                            <span class="dashboard-shortcut-icon"><?= admin_icon('manage') ?></span>
                            <span class="dashboard-shortcut-copy">
                                <strong>Kelola Master Bahan</strong>
                                <small>Perbarui bahan dan harga satuannya.</small>
                            </span>
                            <?= admin_icon('external') ?>
                        </a>
                    </div>
                </section>

                <section class="admin-card dashboard-section" aria-labelledby="dashboard-activity-title">
                    <div class="dashboard-section-heading">
                        <div>
                            <h3 id="dashboard-activity-title">Aktivitas terbaru</h3>
                            <p>Lima aksi admin terakhir yang tercatat.</p>
                        </div>
                        <a href="activity_log.php" class="btn btn-cancel btn-with-icon"><?= admin_icon('log') ?>Lihat Semua</a>
                    </div>
                    <?php if ($recentActivities === []): ?>
                        <p class="dashboard-empty">Belum ada aktivitas yang tercatat.</p>
                    <?php else: ?>
                        <div class="table-responsive dashboard-activity-table">
                            <table>
                                <thead>
                                    <tr><th>Waktu</th><th>Aksi</th><th>Detail</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentActivities as $activity): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($activity['time'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($activity['action'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($activity['details'], ENT_QUOTES, 'UTF-8') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>
            </main>
        </div>
    </div>
</body>
</html>
