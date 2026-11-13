<?php
require 'auth_check.php';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/helpers/hpp_recipe_repository.php';
require 'csrf_check.php';

$escape = static function ($value): string {
    return htmlspecialchars(is_scalar($value) ? (string) $value : '', ENT_QUOTES, 'UTF-8');
};
$schemaTables = ['hpp_ingredients', 'hpp_recipes', 'hpp_recipe_lines', 'hpp_recipe_costs'];
$existingTables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$schemaReady = count(array_diff($schemaTables, $existingTables)) === 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$schemaReady) {
        set_admin_flash('Migrasi database HPP belum diterapkan. Jalankan migrasi melalui menu Pengaturan sebelum mengelola resep.', 'error');
        header('Location: settings.php');
        exit;
    }

    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
    try {
        $recipeId = hpp_recipe_positive_id($_POST['recipe_id'] ?? null, 'ID resep');
        if ($action === 'duplicate_recipe') {
            $duplicateId = hpp_duplicate_recipe($pdo, $recipeId);
            log_admin_action('HPP_DUPLICATE_RECIPE', 'source_id=' . $recipeId . ', duplicate_id=' . $duplicateId);
            set_admin_flash('Resep berhasil diduplikasi. Hubungan ke harga publik tidak disalin.');
            header('Location: hpp_edit.php?id=' . $duplicateId);
            exit;
        }
        if ($action === 'delete_recipe') {
            $deletedRecipe = hpp_delete_recipe($pdo, $recipeId);
            log_admin_action(
                'HPP_DELETE_RECIPE',
                'id=' . $recipeId . ', linked_product_item=' . ($deletedRecipe['product_item_id'] ?? 'none')
            );
            set_admin_flash(
                'Resep "' . $deletedRecipe['name'] . '" berhasil dihapus. Harga publik produk tidak berubah.'
            );
            header('Location: hpp_recipes.php');
            exit;
        }
        throw new InvalidArgumentException('Aksi resep tidak dikenali.');
    } catch (InvalidArgumentException $exception) {
        set_admin_flash($exception->getMessage(), 'error');
        header('Location: hpp_recipes.php');
        exit;
    }
}

$recipes = $schemaReady ? hpp_get_recipe_list($pdo) : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex, nofollow">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Resep HPP - Alfi Kitchen</title>
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h2>Daftar Resep HPP</h2>
                <div class="admin-header-actions">
                    <a href="../" target="_blank" rel="noopener noreferrer" class="btn-site-preview"><?= admin_icon('external') ?>Lihat Website</a>
                    <a href="hpp_edit.php" class="btn btn-primary btn-with-icon"><?= admin_icon('add') ?>Buat Resep</a>
                    <a href="logout.php" class="btn btn-danger btn-with-icon"><?= admin_icon('logout') ?>Logout</a>
                </div>
            </header>
            <main class="admin-container hpp-list-page">
                <?php include __DIR__ . '/flash_message.php'; ?>
                <?php if (!$schemaReady): ?>
                    <div class="form-card">
                        <div class="alert alert-error" role="alert">
                            Tabel kalkulator HPP belum tersedia. Terapkan migrasi database terlebih dahulu di
                            <a href="settings.php">Pengaturan</a>.
                        </div>
                    </div>
                <?php else: ?>
                    <p class="hpp-help-text">Harga publik hanya ditampilkan sebagai referensi. Buka resep untuk mengubah kalkulasi; perubahan HPP tidak mengubah harga jual produk.</p>
                    <div class="table-responsive hpp-recipe-list-wrap">
                        <table class="hpp-recipe-list-table">
                            <thead>
                                <tr>
                                    <th>Nama resep / varian</th>
                                    <th>HPP per jar</th>
                                    <th>Harga jual saat ini</th>
                                    <th>Status bahan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recipes === []): ?>
                                    <tr>
                                        <td colspan="5">
                                            <div class="category-empty">
                                                <h3>Belum ada resep</h3>
                                                <p>Buat resep untuk menyimpan bahan, biaya operasional, dan hasil kalkulasi HPP.</p>
                                                <a href="hpp_edit.php" class="btn btn-primary btn-with-icon"><?= admin_icon('add') ?>Buat Resep HPP</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                <?php foreach ($recipes as $recipe): ?>
                                    <?php
                                    $recipeId = (int) $recipe['id'];
                                    $recipeLabel = trim((string) $recipe['name'] . ((string) $recipe['variant'] !== '' ? ' — ' . (string) $recipe['variant'] : ''));
                                    ?>
                                    <tr>
                                        <td data-label="Nama resep / varian">
                                            <strong><?= $escape($recipe['name']) ?></strong>
                                            <?php if ((string) $recipe['variant'] !== ''): ?>
                                                <span class="hpp-recipe-variant"><?= $escape($recipe['variant']) ?></span>
                                            <?php endif; ?>
                                            <?php if ($recipe['product_item_id'] !== null): ?>
                                                <small class="hpp-recipe-product">
                                                    Terhubung ke <?= $escape($recipe['product_name'] ?? '') ?>
                                                    — <?= $escape($recipe['item_name'] ?? '') ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="HPP per jar">Rp<?= number_format((int) $recipe['hpp_per_jar'], 0, ',', '.') ?></td>
                                        <td data-label="Harga jual saat ini">
                                            <?php if ($recipe['public_price'] !== null): ?>
                                                Rp<?= number_format((float) $recipe['public_price'], 0, ',', '.') ?>
                                            <?php else: ?>
                                                <span class="hpp-muted-value">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Status bahan">
                                            <?php if ($recipe['ingredient_prices_changed']): ?>
                                                <span class="hpp-status-badge is-changed">Harga bahan berubah</span>
                                            <?php else: ?>
                                                <span class="hpp-status-badge is-current">Sesuai snapshot</span>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Aksi">
                                            <div class="hpp-recipe-actions">
                                                <a href="hpp_edit.php?id=<?= $recipeId ?>" class="category-icon-button category-action-primary" aria-label="Buka resep" title="Buka resep"><?= admin_icon('edit') ?>Buka</a>
                                                <form method="post" action="hpp_recipes.php" class="hpp-inline-form" data-submit-lock>
                                                    <input type="hidden" name="csrf_token" value="<?= $escape(generate_csrf_token()) ?>">
                                                    <input type="hidden" name="action" value="duplicate_recipe">
                                                    <input type="hidden" name="recipe_id" value="<?= $recipeId ?>">
                                                    <button type="submit" class="category-icon-button category-action-secondary" aria-label="Duplikat resep" title="Duplikat resep"><?= admin_icon('duplicate') ?>Duplikat</button>
                                                </form>
                                                <form method="post" action="hpp_recipes.php" class="hpp-inline-form hpp-recipe-delete-form" data-submit-lock onsubmit="return confirm(<?= htmlspecialchars(json_encode('Hapus resep "' . $recipeLabel . '"? Harga jual produk di website tidak berubah. Tindakan ini tidak dapat dibatalkan.', JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>)">
                                                    <input type="hidden" name="csrf_token" value="<?= $escape(generate_csrf_token()) ?>">
                                                    <input type="hidden" name="action" value="delete_recipe">
                                                    <input type="hidden" name="recipe_id" value="<?= $recipeId ?>">
                                                    <button type="submit" class="category-icon-button category-action-delete" aria-label="Hapus resep" title="Hapus resep"><?= admin_icon('delete') ?>Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
</body>
</html>
