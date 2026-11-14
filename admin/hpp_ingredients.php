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
        $rawId = $_POST['id'] ?? null;
        $hasExplicitId = $rawId !== null && $rawId !== '' && $rawId !== '0';
        $postedId = !$hasExplicitId
            ? 0
            : (is_scalar($rawId) ? filter_var($rawId, FILTER_VALIDATE_INT) : false);
        $editingId = $postedId === false || $postedId < 0 ? 0 : (int) $postedId;
        $formValues = $_POST;

        try {
            if ($hasExplicitId && ($postedId === false || $postedId < 1)) {
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
    <link rel="stylesheet" href="assets/css/admin.css?v=<?= (int) filemtime(__DIR__ . '/assets/css/admin.css') . '-' . (int) filesize(__DIR__ . '/assets/css/admin.css') ?>">
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h2>Master Bahan HPP</h2>
                <div class="admin-header-actions">
                    <a href="../" target="_blank" rel="noopener noreferrer" class="btn-site-preview"><?= admin_icon('external') ?>Lihat Website</a>
                    <a href="logout.php" class="btn btn-danger btn-with-icon admin-header-logout"><?= admin_icon('logout') ?>Logout</a>
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
                <?php
                $cleanPackSize = static function ($value): string {
                    if ($value === null || $value === '') {
                        return '';
                    }
                    $str = (string) $value;
                    if (str_contains($str, '.')) {
                        $str = rtrim(rtrim($str, '0'), '.');
                    }
                    return $str;
                };
                ?>
                <div class="master-toolbar">
                    <div class="master-search">
                        <input type="search" id="ingredient-search" placeholder="Cari nama bahan atau catatan..." class="form-control form-control-sm" autocomplete="off">
                    </div>
                    <div class="master-toolbar-actions">
                        <button type="button" class="btn btn-primary btn-sm btn-with-icon admin-drawer-toggle" data-target="drawer-add-ingredient" id="btn-open-add-drawer" aria-expanded="<?= ($formError !== '' || $editingId > 0) ? 'true' : 'false' ?>">
                            <?= admin_icon('add') ?><span>Tambah Bahan</span>
                        </button>
                    </div>
                </div>

                <!-- Formulir Tambah / Ubah Bahan Baku (Format Admin Drawer Card) -->
                <div id="drawer-add-ingredient" class="admin-drawer-card form-card" <?= ($formError !== '' || $editingId > 0) ? '' : 'hidden' ?>>
                    <div class="admin-drawer-header">
                        <h3 id="ingredient-drawer-title"><?= ($formError !== '' && $editingId > 0) ? 'Ubah Bahan Baku' : 'Tambah Bahan Baku' ?></h3>
                        <button type="button" class="btn-drawer-close" data-target="drawer-add-ingredient" aria-label="Tutup formulir">✕</button>
                    </div>
                    <p class="hpp-help-text">
                        Snapshot harga resep tidak berubah secara otomatis saat master bahan diubah.
                    </p>
                    <form id="add-ingredient-form" method="post" action="hpp_ingredients.php" class="admin-form-grid" data-submit-lock>
                        <input type="hidden" name="csrf_token" value="<?= $escape(generate_csrf_token()) ?>">
                        <input type="hidden" name="action" value="save_ingredient">
                        <input type="hidden" id="ingredient-form-id" name="id" value="<?= $editingId > 0 ? $editingId : '0' ?>">
                        
                        <div class="form-group form-span-full">
                            <label for="ingredient-form-name">Nama Bahan Baku</label>
                            <input id="ingredient-form-name" type="text" name="name" maxlength="120" required value="<?= $escape($formValues['name'] ?? '') ?>" placeholder="Bubuk Puding Coklat, Susu Evaporasi, Cup 200ml">
                        </div>
                        <div class="form-group">
                            <label for="ingredient-form-pack-size">Isi Kemasan</label>
                            <input id="ingredient-form-pack-size" type="text" name="pack_size" inputmode="decimal" pattern="[0-9]+([.,][0-9]{1,3})?" placeholder="1000 atau 0,5" required value="<?= $escape($cleanPackSize($formValues['pack_size'] ?? '')) ?>">
                        </div>
                        <div class="form-group">
                            <label for="ingredient-form-unit">Satuan Dasar</label>
                            <select id="ingredient-form-unit" name="base_unit" required>
                                <?php foreach (['g' => 'Gram (g)', 'ml' => 'Mililiter (ml)', 'pcs' => 'Buah / pcs'] as $unit => $label): ?>
                                    <option value="<?= $unit ?>"<?= ($formValues['base_unit'] ?? 'g') === $unit ? ' selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" id="ingredient-form-unit-hidden" name="base_unit" value="<?= $escape($formValues['base_unit'] ?? 'g') ?>" disabled>
                            <small id="ingredient-form-unit-help" class="hpp-help-text" hidden>Satuan dasar tidak dapat diubah karena bahan ini sudah dipakai resep.</small>
                        </div>
                        <div class="form-group">
                            <label for="ingredient-form-pack-price">Harga Kemasan (Rp)</label>
                            <input id="ingredient-form-pack-price" type="number" name="pack_price" min="0" max="2147483647" step="1" placeholder="45000" required value="<?= $escape($formValues['pack_price'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="ingredient-form-note">Catatan / Merek / Toko (opsional)</label>
                            <input id="ingredient-form-note" type="text" name="note" maxlength="255" placeholder="Merk Haan / Toko Bahan Kue" value="<?= $escape($formValues['note'] ?? '') ?>">
                        </div>
                        <div class="form-group form-span-full admin-drawer-footer-actions">
                            <button type="button" class="btn btn-cancel btn-with-icon" id="btn-cancel-ingredient"><?= admin_icon('back') ?><span>Batal</span></button>
                            <button type="submit" id="ingredient-submit-btn" class="btn btn-primary btn-with-icon">
                                <?= admin_icon($editingId > 0 ? 'save' : 'add') ?>
                                <span><?= $editingId > 0 ? 'Simpan Perubahan' : 'Tambah Bahan' ?></span>
                            </button>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="hpp-ingredients-table">
                        <thead>
                            <tr>
                                <th>Bahan Baku</th>
                                <th>Isi Kemasan</th>
                                <th>Harga Kemasan</th>
                                <th>Catatan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($ingredients === []): ?>
                                <tr>
                                    <td colspan="5">
                                        <div class="category-empty">
                                            <h3>Master bahan masih kosong</h3>
                                            <p>Tambahkan bahan pertama untuk mulai menghitung HPP resep.</p>
                                            <button type="button" class="btn btn-primary btn-with-icon admin-drawer-toggle" data-target="drawer-add-ingredient"><?= admin_icon('add') ?>Tambah Bahan</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($ingredients as $ingredient): ?>
                                <?php
                                $ingredientId = (int) $ingredient['id'];
                                $recipeCount = (int) $ingredient['recipe_count'];
                                $ingredientJson = [
                                    'id' => $ingredientId,
                                    'name' => (string) $ingredient['name'],
                                    'base_unit' => (string) $ingredient['base_unit'],
                                    'pack_size' => $cleanPackSize($ingredient['pack_size']),
                                    'pack_price' => (string) $ingredient['pack_price'],
                                    'note' => (string) ($ingredient['note'] ?? ''),
                                    'recipe_count' => $recipeCount,
                                ];
                                ?>
                                <tr class="ingredient-data-row" data-ingredient-id="<?= $ingredientId ?>">
                                    <td data-label="Bahan Baku">
                                        <strong class="ingredient-name"><?= $escape($ingredient['name']) ?></strong>
                                    </td>
                                    <td data-label="Isi Kemasan">
                                        <span class="ingredient-pack-size-val"><?= format_decimal($ingredient['pack_size'], 2) ?> <?= $escape($ingredient['base_unit']) ?></span>
                                    </td>
                                    <td data-label="Harga Kemasan">
                                        <strong class="ingredient-pack-price">Rp <?= format_decimal($ingredient['pack_price'], 0) ?></strong>
                                    </td>
                                    <td data-label="Catatan" class="ingredient-col-note">
                                        <?= !empty($ingredient['note']) ? $escape($ingredient['note']) : '<span class="text-muted">—</span>' ?>
                                    </td>
                                    <td data-label="Aksi" class="table-actions text-right">
                                        <div class="table-action-group">
                                            <button type="button" class="category-icon-button category-action-secondary btn-edit-ingredient" data-ingredient="<?= $escape(json_encode($ingredientJson)) ?>" aria-label="Edit bahan <?= $escape($ingredient['name']) ?>" title="Edit bahan"><?= admin_icon('edit') ?><span class="action-button-label">Edit</span></button>
                                            <form method="post" action="hpp_ingredients.php" class="inline-action-form" data-submit-lock onsubmit="return confirm(<?= htmlspecialchars(json_encode('Hapus bahan "' . $ingredient['name'] . '" dari master? Bahan yang masih dipakai resep tidak dapat dihapus. Tindakan ini tidak dapat dibatalkan.', JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>)">
                                                <input type="hidden" name="csrf_token" value="<?= $escape(generate_csrf_token()) ?>">
                                                <input type="hidden" name="action" value="delete_ingredient">
                                                <input type="hidden" name="id" value="<?= $ingredientId ?>">
                                                <button type="submit" class="category-icon-button category-action-delete" aria-label="Hapus bahan <?= $escape($ingredient['name']) ?>" title="Hapus bahan"><?= admin_icon('delete') ?><span class="action-button-label">Hapus</span></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var drawer = document.getElementById('drawer-add-ingredient');
                    var drawerTitle = document.getElementById('ingredient-drawer-title');
                    var inputId = document.getElementById('ingredient-form-id');
                    var inputName = document.getElementById('ingredient-form-name');
                    var selectUnit = document.getElementById('ingredient-form-unit');
                    var hiddenUnit = document.getElementById('ingredient-form-unit-hidden');
                    var inputSize = document.getElementById('ingredient-form-pack-size');
                    var inputPrice = document.getElementById('ingredient-form-pack-price');
                    var inputNote = document.getElementById('ingredient-form-note');
                    var unitHelp = document.getElementById('ingredient-form-unit-help');
                    var submitBtn = document.getElementById('ingredient-submit-btn');
                    var openAddBtn = document.getElementById('btn-open-add-drawer');

                    function cleanNumber(val) {
                        if (val === null || val === undefined) return '';
                        var s = String(val);
                        if (s.indexOf('.') !== -1) {
                            s = s.replace(/\.?0+$/, '');
                        }
                        return s;
                    }

                    function resetFormToAdd() {
                        if (!drawerTitle || !submitBtn || !inputId) return;
                        drawerTitle.textContent = 'Tambah Bahan Baku';
                        submitBtn.innerHTML = '<?= admin_icon('add') ?><span>Tambah Bahan</span>';
                        inputId.value = '0';
                        inputName.value = '';
                        selectUnit.value = 'g';
                        selectUnit.disabled = false;
                        if (hiddenUnit) hiddenUnit.disabled = true;
                        if (unitHelp) unitHelp.hidden = true;
                        inputSize.value = '';
                        inputPrice.value = '';
                        inputNote.value = '';
                    }

                    if (openAddBtn) {
                        openAddBtn.addEventListener('click', function (e) {
                            if (drawer && !drawer.hasAttribute('hidden') && inputId && inputId.value !== '0') {
                                e.stopImmediatePropagation();
                                resetFormToAdd();
                                drawer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                                if (inputName) inputName.focus();
                                return;
                            }
                            resetFormToAdd();
                        });
                    }

                    var cancelBtn = document.getElementById('btn-cancel-ingredient');
                    if (cancelBtn) {
                        cancelBtn.addEventListener('click', function () {
                            if (drawer) {
                                drawer.setAttribute('hidden', '');
                                if (openAddBtn) openAddBtn.setAttribute('aria-expanded', 'false');
                            }
                            resetFormToAdd();
                        });
                    }

                    document.querySelectorAll('.btn-drawer-close[data-target="drawer-add-ingredient"]').forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            resetFormToAdd();
                        });
                    });

                    // Edit button click handler
                    document.querySelectorAll('.btn-edit-ingredient').forEach(function (btn) {
                        btn.addEventListener('click', function (e) {
                            e.preventDefault();
                            var raw = btn.getAttribute('data-ingredient');
                            if (!raw) return;
                            var data = JSON.parse(raw);
                            drawerTitle.textContent = 'Ubah Bahan: ' + data.name;
                            submitBtn.innerHTML = '<?= admin_icon('save') ?><span>Simpan Perubahan</span>';
                            inputId.value = data.id;
                            inputName.value = data.name;
                            selectUnit.value = data.base_unit;
                            var isLocked = parseInt(data.recipe_count, 10) > 0;
                            selectUnit.disabled = isLocked;
                            if (hiddenUnit) {
                                hiddenUnit.value = data.base_unit;
                                hiddenUnit.disabled = !isLocked;
                            }
                            if (unitHelp) {
                                unitHelp.hidden = !isLocked;
                            }
                            inputSize.value = cleanNumber(data.pack_size);
                            inputPrice.value = cleanNumber(data.pack_price);
                            inputNote.value = data.note || '';

                            if (drawer) {
                                drawer.removeAttribute('hidden');
                                drawer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                                if (openAddBtn) openAddBtn.setAttribute('aria-expanded', 'true');
                            }
                            inputName.focus();
                        });
                    });

                    // Instant filter search
                    var search = document.getElementById('ingredient-search');
                    if (search) {
                        search.addEventListener('input', function () {
                            var q = search.value.toLowerCase().trim();
                            document.querySelectorAll('.ingredient-data-row').forEach(function (row) {
                                var text = (row.textContent || '').toLowerCase();
                                row.style.display = (!q || text.indexOf(q) !== -1) ? '' : 'none';
                            });
                        });
                    }
                });
                </script>
                <?php endif; ?>
            </main>
        </div>
    </div>
</body>
</html>
