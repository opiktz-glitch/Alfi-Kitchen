<?php
require 'auth_check.php';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/helpers/db_helper.php';
require 'csrf_check.php';

$productCount = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$variantCount = (int) $pdo->query('SELECT COUNT(*) FROM product_items')->fetchColumn();

$hppTables = ['hpp_ingredients', 'hpp_recipes', 'hpp_recipe_lines', 'hpp_recipe_costs'];
$existingTables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$hppReady = count(array_diff($hppTables, $existingTables)) === 0;
$recipeCount = $hppReady ? (int) $pdo->query('SELECT COUNT(*) FROM hpp_recipes')->fetchColumn() : null;
$ingredientCount = $hppReady ? (int) $pdo->query('SELECT COUNT(*) FROM hpp_ingredients')->fetchColumn() : null;

$staleRecipeCount = 0;
if ($hppReady) {
    require_once __DIR__ . '/helpers/hpp_recipe_repository.php';
    $allHppRecipes = hpp_get_recipe_list($pdo);
    foreach ($allHppRecipes as $r) {
        if (!empty($r['ingredient_prices_changed'])) {
            $staleRecipeCount++;
        }
    }
}

// Data Status Toko
$currentWa = '';
try {
    $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'whatsapp_number' LIMIT 1");
    $currentWa = (string) $stmt->fetchColumn();
} catch (Exception $e) {}

$heroCount = 0;
try {
    $heroCount = (int) $pdo->query('SELECT COUNT(*) FROM hero_images')->fetchColumn();
} catch (Exception $e) {}

$hasLogo = file_exists(uploads_directory() . '/logo.png');

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

function get_action_badge_class(string $action): string {
    $act = strtoupper($action);
    if (str_contains($act, 'LOGIN')) return 'badge-login';
    if (str_contains($act, 'ADD') || str_contains($act, 'CREATE') || str_contains($act, 'RESTORE')) return 'badge-success';
    if (str_contains($act, 'UPDATE') || str_contains($act, 'EDIT') || str_contains($act, 'CHANGE') || str_contains($act, 'MIGRATE')) return 'badge-warning';
    if (str_contains($act, 'DELETE') || str_contains($act, 'REMOVE') || str_contains($act, 'CLEAR')) return 'badge-danger';
    return 'badge-neutral';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>Dashboard Admin - Alfi Kitchen</title>
    <link rel="stylesheet" href="assets/css/admin.css?v=<?= (int) filemtime(__DIR__ . '/assets/css/admin.css') . '-' . (int) filesize(__DIR__ . '/assets/css/admin.css') ?>">
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h2>Dashboard</h2>
                <div class="admin-header-actions">
                    <a href="../" target="_blank" rel="noopener noreferrer" class="btn-site-preview"><?= admin_icon('external') ?>Lihat Website</a>
                    <a href="logout.php" class="btn btn-danger btn-with-icon admin-header-logout"><?= admin_icon('logout') ?>Logout</a>
                </div>
            </header>
            <main class="admin-container admin-dashboard">
                <?php include __DIR__ . '/flash_message.php'; ?>

                <!-- Welcome Card -->
                <section class="dashboard-welcome-card">
                    <div class="dashboard-welcome-content">
                        <div class="dashboard-welcome-text">
                            <h3>Selamat Datang, <?= htmlspecialchars($_SESSION['admin_user'] ?? 'Administrator') ?> 👋</h3>
                            <p>Ringkasan status katalog dan aktivitas operasional toko Alfi Kitchen hari ini.</p>
                        </div>
                        <div class="dashboard-welcome-pills">
                            <span class="welcome-pill"><span class="pill-dot is-online"></span> Toko Online Aktif</span>
                            <span class="welcome-pill"><?= number_format($variantCount) ?> Varian Terdaftar</span>
                            <?php if ($staleRecipeCount > 0): ?>
                                <span class="welcome-pill is-alert">⚠ <?= $staleRecipeCount ?> Resep Perlu Disesuaikan</span>
                            <?php else: ?>
                                <span class="welcome-pill is-ok">✓ HPP Terkalkulasi</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <!-- Ringkasan Katalog (KPI Stat Cards) -->
                <section class="admin-dashboard-stats" aria-label="Ringkasan katalog">
                    <article class="admin-stat">
                        <div class="admin-stat-head">
                            <span class="admin-stat-label">Kategori produk</span>
                            <div class="admin-stat-icon"><?= admin_icon('manage') ?></div>
                        </div>
                        <strong class="admin-stat-value"><?= number_format($productCount) ?></strong>
                        <span class="dashboard-stat-meta"><?= number_format($variantCount) ?> total varian</span>
                    </article>
                    <article class="admin-stat">
                        <div class="admin-stat-head">
                            <span class="admin-stat-label">Varian produk</span>
                            <div class="admin-stat-icon"><?= admin_icon('image') ?></div>
                        </div>
                        <strong class="admin-stat-value"><?= number_format($variantCount) ?></strong>
                        <span class="dashboard-stat-meta">Siap pesan via WhatsApp</span>
                    </article>
                    <article class="admin-stat">
                        <div class="admin-stat-head">
                            <span class="admin-stat-label">Resep HPP</span>
                            <div class="admin-stat-icon"><?= admin_icon('calculator') ?></div>
                        </div>
                        <strong class="admin-stat-value"><?= $recipeCount === null ? '—' : number_format($recipeCount) ?></strong>
                        <?php if (!$hppReady): ?>
                            <small class="dashboard-stat-note">Migrasi HPP belum diterapkan</small>
                        <?php elseif ($staleRecipeCount > 0): ?>
                            <span class="dashboard-stat-meta is-warning">⚠ <?= $staleRecipeCount ?> perlu update harga</span>
                        <?php else: ?>
                            <span class="dashboard-stat-meta is-ok">✓ Biaya terhitung akurat</span>
                        <?php endif; ?>
                    </article>
                    <article class="admin-stat">
                        <div class="admin-stat-head">
                            <span class="admin-stat-label">Master bahan</span>
                            <div class="admin-stat-icon"><?= admin_icon('database') ?></div>
                        </div>
                        <strong class="admin-stat-value"><?= $ingredientCount === null ? '—' : number_format($ingredientCount) ?></strong>
                        <span class="dashboard-stat-meta">Bahan baku terdaftar</span>
                    </article>
                </section>

                <!-- Grid 2 Kolom Laptop: Aksi & Alur Kerja (Kiri) vs Status Toko & Log (Kanan) -->
                <div class="dashboard-grid">
                    <!-- Kolom Kiri -->
                    <div class="dashboard-col-main">
                        <?php if ($staleRecipeCount > 0): ?>
                            <div class="alert alert-warning dashboard-alert" role="alert">
                                <div class="dashboard-alert-text">
                                    <strong>Penyesuaian Biaya Resep:</strong> Ada <?= $staleRecipeCount ?> resep yang harga bahan bakunya berubah di Master Bahan. Silakan tinjau kalkulasi HPP.
                                </div>
                                <a href="hpp_recipes.php" class="btn btn-secondary btn-sm btn-with-icon"><?= admin_icon('calculator') ?><span>Tinjau Resep</span></a>
                            </div>
                        <?php endif; ?>

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
                    </div>

                    <!-- Kolom Kanan -->
                    <div class="dashboard-col-side">
                        <!-- Widget Ringkasan Status Toko -->
                        <section class="admin-card dashboard-section">
                            <div class="dashboard-section-heading">
                                <div>
                                    <h3>Status Sistem &amp; Toko</h3>
                                    <p>Kondisi fitur utama website Alfi Kitchen.</p>
                                </div>
                                <a href="settings.php" class="btn btn-sm btn-secondary btn-with-icon"><?= admin_icon('settings') ?><span>Pengaturan</span></a>
                            </div>
                            <div class="dashboard-health-list">
                                <div class="health-item">
                                    <div class="health-item-icon health-wa"><?= admin_icon('external') ?></div>
                                    <div class="health-item-info">
                                        <strong>WhatsApp Pemesanan</strong>
                                        <span><?= htmlspecialchars($currentWa ?: 'Belum diatur') ?></span>
                                    </div>
                                    <?php if ($currentWa): ?>
                                        <span class="health-status-badge is-ok">Aktif</span>
                                    <?php else: ?>
                                        <span class="health-status-badge is-warning">Perlu Diisi</span>
                                    <?php endif; ?>
                                </div>
                                <div class="health-item">
                                    <div class="health-item-icon health-slider"><?= admin_icon('slider') ?></div>
                                    <div class="health-item-info">
                                        <strong>Slider Beranda</strong>
                                        <span><?= $heroCount ?> Banner Promosi Aktif</span>
                                    </div>
                                    <span class="health-status-badge is-ok">Normal</span>
                                </div>
                                <div class="health-item">
                                    <div class="health-item-icon health-logo"><?= admin_icon('image') ?></div>
                                    <div class="health-item-info">
                                        <strong>Identitas Logo Toko</strong>
                                        <span><?= $hasLogo ? 'logo.png terpasang' : 'Logo default sistem' ?></span>
                                    </div>
                                    <span class="health-status-badge is-ok"><?= $hasLogo ? 'Kustom' : 'Default' ?></span>
                                </div>
                            </div>
                        </section>

                        <!-- Aktivitas Terbaru -->
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
                                                    <td class="table-cell-center activity-time-cell"><?= htmlspecialchars($activity['time'], ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td class="table-cell-center">
                                                        <span class="activity-badge <?= get_action_badge_class($activity['action']) ?>">
                                                            <?= htmlspecialchars($activity['action'], ENT_QUOTES, 'UTF-8') ?>
                                                        </span>
                                                    </td>
                                                    <td><?= htmlspecialchars($activity['details'], ENT_QUOTES, 'UTF-8') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </section>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
