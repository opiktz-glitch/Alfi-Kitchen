<?php
require 'auth_check.php';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/helpers/hpp_ingredient_validation.php';
require 'csrf_check.php';

$formError = '';
$formValues = [];
$editingId = 0;
$requiredTables = ['hpp_ingredients', 'hpp_recipes', 'hpp_recipe_lines', 'hpp_recipe_costs'];
$existingTables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$schemaReady = count(array_diff($requiredTables, $existingTables)) === 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$schemaReady) {
    set_admin_flash('Migrasi database HPP belum diterapkan. Jalankan migrasi melalui menu Pengaturan sebelum mengelola bahan.', 'error');
    header('Location: settings.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';

    if ($action === 'save_ingredient') {
        $hasPostedId = array_key_exists('id', $_POST);
        $rawId = $_POST['id'] ?? null;
        $postedId = !$hasPostedId
            ? 0
            : (is_scalar($rawId) ? filter_var($rawId, FILTER_VALIDATE_INT) : false);
        $editingId = $postedId === false || $postedId < 0 ? 0 : (int) $postedId;
        $formValues = $_POST;

        try {
            if ($hasPostedId && ($postedId === false || $postedId < 1)) {
                throw new InvalidArgumentException('ID bahan tidak valid.');
            }
            $ingredient = hpp_validate_ingredient_input($_POST);

            if ($editingId > 0) {
                $currentStmt = $pdo->prepare(
                    'SELECT id, name, base_unit, pack_size, pack_price, note '
                    . 'FROM hpp_ingredients WHERE id = ?'
                );
                $currentStmt->execute([$editingId]);
                $currentIngredient = $currentStmt->fetch(PDO::FETCH_ASSOC);
                if (!$currentIngredient) {
                    throw new InvalidArgumentException('Bahan tidak ditemukan atau sudah dihapus.');
                }

                $usageStmt = $pdo->prepare(
                    'SELECT COUNT(DISTINCT recipe_id) FROM hpp_recipe_lines WHERE ingredient_id = ?'
                );
                $usageStmt->execute([$editingId]);
                $recipeCount = (int) $usageStmt->fetchColumn();
                if ($recipeCount > 0 && $ingredient['base_unit'] !== $currentIngredient['base_unit']) {
                    throw new InvalidArgumentException(
                        'Satuan dasar tidak dapat diubah karena bahan ini sudah dipakai resep. Buat bahan baru dengan satuan yang benar.'
                    );
                }

                $updateStmt = $pdo->prepare(
                    'UPDATE hpp_ingredients '
                    . 'SET name = ?, base_unit = ?, pack_size = ?, pack_price = ?, note = ? '
                    . 'WHERE id = ?'
                );
                $updateStmt->execute([
                    $ingredient['name'],
                    $ingredient['base_unit'],
                    $ingredient['pack_size'],
                    $ingredient['pack_price'],
                    $ingredient['note'] !== '' ? $ingredient['note'] : null,
                    $editingId,
                ]);

                log_admin_action('HPP_UPDATE_INGREDIENT', 'id=' . $editingId);
                $message = 'Bahan "' . $ingredient['name'] . '" berhasil diperbarui.';
                if (
                    $recipeCount > 0
                    && (
                        (float) $currentIngredient['pack_size'] !== (float) $ingredient['pack_size']
                        || (int) $currentIngredient['pack_price'] !== $ingredient['pack_price']
                    )
                ) {
                    $message .= ' ' . $recipeCount . ' resep memakai bahan ini; snapshot harga resep tidak berubah sampai diperbarui dari master.';
                }
                set_admin_flash($message);
            } else {
                $insertStmt = $pdo->prepare(
                    'INSERT INTO hpp_ingredients (name, base_unit, pack_size, pack_price, note) '
                    . 'VALUES (?, ?, ?, ?, ?)'
                );
                $insertStmt->execute([
                    $ingredient['name'],
                    $ingredient['base_unit'],
                    $ingredient['pack_size'],
                    $ingredient['pack_price'],
                    $ingredient['note'] !== '' ? $ingredient['note'] : null,
                ]);

                log_admin_action('HPP_ADD_INGREDIENT', 'id=' . (int) $pdo->lastInsertId());
                set_admin_flash('Bahan "' . $ingredient['name'] . '" berhasil ditambahkan.');
            }

            header('Location: hpp_ingredients.php');
            exit;
        } catch (InvalidArgumentException $exception) {
            $formError = $exception->getMessage();
        } catch (PDOException $exception) {
            if (($exception->errorInfo[0] ?? $exception->getCode()) === '23000') {
                $formError = 'Nama bahan sudah digunakan. Gunakan nama yang berbeda.';
            } else {
                throw $exception;
            }
        }
    } elseif ($action === 'delete_ingredient') {
        $rawDeleteId = $_POST['id'] ?? null;
        $deleteId = is_scalar($rawDeleteId)
            ? (filter_var($rawDeleteId, FILTER_VALIDATE_INT) ?: 0)
            : 0;
        if ($deleteId < 1) {
            set_admin_flash('ID bahan tidak valid.', 'error');
            header('Location: hpp_ingredients.php');
            exit;
        }

        $ingredientStmt = $pdo->prepare('SELECT name FROM hpp_ingredients WHERE id = ?');
        $ingredientStmt->execute([$deleteId]);
        $ingredientName = $ingredientStmt->fetchColumn();

        if ($ingredientName === false) {
            set_admin_flash('Bahan tidak ditemukan atau sudah dihapus.', 'error');
        } else {
            $recipesStmt = $pdo->prepare(
                'SELECT DISTINCT r.name, r.variant '
                . 'FROM hpp_recipe_lines l '
                . 'INNER JOIN hpp_recipes r ON r.id = l.recipe_id '
                . 'WHERE l.ingredient_id = ? ORDER BY r.name, r.variant'
            );
            $recipesStmt->execute([$deleteId]);
            $recipes = $recipesStmt->fetchAll(PDO::FETCH_ASSOC);

            if ($recipes !== []) {
                $recipeNames = array_map(
                    static function (array $recipe): string {
                        return $recipe['variant'] !== ''
                            ? $recipe['name'] . ' (' . $recipe['variant'] . ')'
                            : $recipe['name'];
                    },
                    $recipes
                );
                $shownNames = array_slice($recipeNames, 0, 5);
                $remaining = count($recipeNames) - count($shownNames);
                $message = 'Bahan "' . $ingredientName . '" masih dipakai oleh '
                    . count($recipeNames) . ' resep: ' . implode(', ', $shownNames);
                if ($remaining > 0) {
                    $message .= ' dan ' . $remaining . ' resep lainnya';
                }
                $message .= '. Hapus bahan dari resep terlebih dahulu.';
                set_admin_flash($message, 'error');
            } else {
                try {
                    $deleteStmt = $pdo->prepare('DELETE FROM hpp_ingredients WHERE id = ?');
                    $deleteStmt->execute([$deleteId]);
                    log_admin_action('HPP_DELETE_INGREDIENT', 'id=' . $deleteId);
                    set_admin_flash('Bahan "' . $ingredientName . '" berhasil dihapus.');
                } catch (PDOException $exception) {
                    if (($exception->errorInfo[0] ?? $exception->getCode()) !== '23000') {
                        throw $exception;
                    }
                    set_admin_flash(
                        'Bahan tidak dapat dihapus karena sudah dipakai resep. Muat ulang halaman dan periksa resep terkait.',
                        'error'
                    );
                }
            }
        }

        header('Location: hpp_ingredients.php');
        exit;
    }
}

$ingredients = [];
if ($schemaReady) {
    $ingredientsStmt = $pdo->query(
        'SELECT i.id, i.name, i.base_unit, i.pack_size, i.pack_price, i.note, '
        . 'COUNT(DISTINCT l.recipe_id) AS recipe_count '
        . 'FROM hpp_ingredients i '
        . 'LEFT JOIN hpp_recipe_lines l ON l.ingredient_id = i.id '
        . 'GROUP BY i.id, i.name, i.base_unit, i.pack_size, i.pack_price, i.note '
        . 'ORDER BY i.name ASC'
    );
    $ingredients = $ingredientsStmt->fetchAll(PDO::FETCH_ASSOC);
}
$escape = static function ($value): string {
    return htmlspecialchars(is_scalar($value) ? (string) $value : '', ENT_QUOTES, 'UTF-8');
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex, nofollow">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Master Bahan HPP - Alfi Kitchen</title>
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h2>Master Bahan HPP</h2>
                <div class="admin-header-actions">
                    <a href="../" target="_blank" rel="noopener noreferrer" class="btn-site-preview"><?= admin_icon('external') ?>Lihat Website</a>
                    <a href="hpp_edit.php" class="btn btn-primary btn-with-icon"><?= admin_icon('add') ?>Buat Resep HPP</a>
                    <a href="logout.php" class="btn btn-danger btn-with-icon"><?= admin_icon('logout') ?>Logout</a>
                </div>
            </header>
            <main class="admin-container">
                <?php include __DIR__ . '/flash_message.php'; ?>
                <?php if ($formError !== '' && $schemaReady): ?>
                    <div class="alert alert-error" role="alert"><?= $escape($formError) ?></div>
                <?php endif; ?>

                <?php if (!$schemaReady): ?>
                    <div class="form-card">
                        <div class="alert alert-error" role="alert">
                            Tabel kalkulator HPP belum tersedia. Terapkan migrasi database terlebih dahulu di
                            <a href="settings.php">Pengaturan</a>.
                        </div>
                    </div>
                <?php else: ?>
                <div class="form-card">
                    <h3>Tambah Bahan</h3>
                    <p class="hpp-help-text">Harga satuan dihitung dari harga kemasan ÷ isi kemasan. Perubahan master tidak mengubah snapshot resep secara otomatis.</p>
                    <form id="add-ingredient-form" method="post" action="hpp_ingredients.php" class="admin-form-grid" data-submit-lock>
                        <input type="hidden" name="csrf_token" value="<?= $escape(generate_csrf_token()) ?>">
                        <input type="hidden" name="action" value="save_ingredient">
                        <div class="form-group">
                            <label for="new-name">Nama bahan</label>
                            <input id="new-name" type="text" name="name" maxlength="120" required value="<?= $editingId === 0 ? $escape($formValues['name'] ?? '') : '' ?>">
                        </div>
                        <div class="form-group">
                            <label for="new-unit">Satuan dasar</label>
                            <select id="new-unit" name="base_unit" required>
                                <?php foreach (['g' => 'Gram (g)', 'ml' => 'Mililiter (ml)', 'pcs' => 'Buah / pcs'] as $unit => $label): ?>
                                    <option value="<?= $unit ?>"<?= ($editingId === 0 ? ($formValues['base_unit'] ?? 'g') : 'g') === $unit ? ' selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="new-pack-size">Isi kemasan (dalam satuan dasar)</label>
                            <input id="new-pack-size" type="text" name="pack_size" inputmode="decimal" pattern="[0-9]+([.,][0-9]{1,3})?" placeholder="Contoh: 1000 atau 0,5" required value="<?= $editingId === 0 ? $escape($formValues['pack_size'] ?? '') : '' ?>">
                        </div>
                        <div class="form-group">
                            <label for="new-pack-price">Harga kemasan (Rp)</label>
                            <input id="new-pack-price" type="number" name="pack_price" min="0" max="2147483647" step="1" required value="<?= $editingId === 0 ? $escape($formValues['pack_price'] ?? '') : '' ?>">
                        </div>
                        <div class="form-group form-span-full">
                            <label for="new-note">Catatan (opsional)</label>
                            <input id="new-note" type="text" name="note" maxlength="255" value="<?= $editingId === 0 ? $escape($formValues['note'] ?? '') : '' ?>">
                        </div>
                        <div class="form-span-full">
                            <button type="submit" class="btn btn-primary btn-with-icon"><?= admin_icon('add') ?>Tambah Bahan</button>
                        </div>
                    </form>
                </div>

                <div class="category-toolbar">
                    <div>
                        <h3>Daftar Master Bahan</h3>
                        <p class="hpp-help-text"><?= count($ingredients) ?> bahan tersimpan. Harga per satuan adalah hasil hitung dan tidak dapat diedit langsung.</p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="hpp-ingredients-table">
                        <thead>
                            <tr>
                                <th>Nama bahan / catatan</th>
                                <th>Satuan</th>
                                <th>Isi kemasan</th>
                                <th>Harga kemasan</th>
                                <th>Harga per satuan</th>
                                <th>Resep memakai</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($ingredients === []): ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="category-empty">
                                            <h3>Master bahan masih kosong</h3>
                                            <p>Tambahkan bahan pertama melalui formulir di atas.</p>
                                            <a href="#add-ingredient-form" class="btn btn-primary btn-with-icon"><?= admin_icon('add') ?>Tambah Bahan</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($ingredients as $ingredient): ?>
                                <?php
                                $ingredientId = (int) $ingredient['id'];
                                $rowIsInvalid = $formError !== '' && $editingId === $ingredientId;
                                $rowValue = static function (string $key) use ($ingredient, $formValues, $rowIsInvalid) {
                                    $value = $rowIsInvalid ? ($formValues[$key] ?? '') : $ingredient[$key];
                                    return is_scalar($value) ? $value : '';
                                };
                                $rowUnit = (int) $ingredient['recipe_count'] > 0
                                    ? (string) $ingredient['base_unit']
                                    : (string) $rowValue('base_unit');
                                ?>
                                <tr>
                                    <td colspan="7" class="hpp-table-form-cell">
                                        <form method="post" action="hpp_ingredients.php" class="hpp-ingredient-row" data-submit-lock>
                                            <input type="hidden" name="csrf_token" value="<?= $escape(generate_csrf_token()) ?>">
                                            <input type="hidden" name="action" value="save_ingredient">
                                            <input type="hidden" name="id" value="<?= $ingredientId ?>">
                                            <div class="form-group">
                                                <label for="ingredient-name-<?= $ingredientId ?>">Nama bahan / catatan</label>
                                                <input id="ingredient-name-<?= $ingredientId ?>" type="text" name="name" maxlength="120" required value="<?= $escape($rowValue('name')) ?>">
                                                <input type="text" name="note" maxlength="255" aria-label="Catatan <?= $escape($ingredient['name']) ?>" placeholder="Catatan opsional" value="<?= $escape($rowValue('note') ?? '') ?>">
                                            </div>
                                            <div class="form-group">
                                                <label for="ingredient-unit-<?= $ingredientId ?>">Satuan</label>
                                                <select id="ingredient-unit-<?= $ingredientId ?>" name="base_unit" required<?= (int) $ingredient['recipe_count'] > 0 ? ' disabled' : '' ?>>
                                                    <?php foreach (['g' => 'g', 'ml' => 'ml', 'pcs' => 'pcs'] as $unit => $label): ?>
                                                        <option value="<?= $unit ?>"<?= $rowUnit === $unit ? ' selected' : '' ?>><?= $label ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <?php if ((int) $ingredient['recipe_count'] > 0): ?>
                                                    <input type="hidden" name="base_unit" value="<?= $escape($ingredient['base_unit']) ?>">
                                                    <small class="hpp-help-text">Tidak dapat diubah karena sudah dipakai.</small>
                                                <?php endif; ?>
                                            </div>
                                            <div class="form-group">
                                                <label for="ingredient-size-<?= $ingredientId ?>">Isi kemasan (<?= $escape($rowUnit) ?>)</label>
                                                <input id="ingredient-size-<?= $ingredientId ?>" type="text" name="pack_size" inputmode="decimal" pattern="[0-9]+([.,][0-9]{1,3})?" required value="<?= $escape($rowValue('pack_size')) ?>">
                                            </div>
                                            <div class="form-group">
                                                <label for="ingredient-price-<?= $ingredientId ?>">Harga kemasan (Rp)</label>
                                                <input id="ingredient-price-<?= $ingredientId ?>" type="number" name="pack_price" min="0" max="2147483647" step="1" required value="<?= $escape($rowValue('pack_price')) ?>">
                                            </div>
                                            <div class="form-group hpp-unit-cost">
                                                <label>Harga per satuan</label>
                                                <output>Rp<?= number_format(hpp_unit_cost($ingredient['pack_price'], $ingredient['pack_size']), 6, ',', '.') ?> / <?= $escape($ingredient['base_unit']) ?></output>
                                            </div>
                                            <div class="form-group hpp-recipe-usage">
                                                <label>Resep memakai</label>
                                                <span><?= (int) $ingredient['recipe_count'] ?> resep</span>
                                            </div>
                                            <div class="hpp-row-actions">
                                                <button type="submit" class="btn btn-primary btn-with-icon"><?= admin_icon('save') ?>Simpan</button>
                                            </div>
                                        </form>
                                        <form method="post" action="hpp_ingredients.php" class="hpp-delete-form" data-submit-lock onsubmit="return confirm(<?= htmlspecialchars(json_encode('Hapus bahan "' . $ingredient['name'] . '" dari master? Bahan yang masih dipakai resep tidak dapat dihapus. Tindakan ini tidak dapat dibatalkan.', JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>)">
                                            <input type="hidden" name="csrf_token" value="<?= $escape(generate_csrf_token()) ?>">
                                            <input type="hidden" name="action" value="delete_ingredient">
                                            <input type="hidden" name="id" value="<?= $ingredientId ?>">
                                            <button type="submit" class="btn btn-danger btn-with-icon"><?= admin_icon('delete') ?>Hapus</button>
                                        </form>
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
