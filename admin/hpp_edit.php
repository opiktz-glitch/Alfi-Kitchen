<?php
require 'auth_check.php';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/helpers/hpp_recipe_repository.php';
require 'csrf_check.php';

$escape = static function ($value): string {
    return htmlspecialchars(is_scalar($value) ? (string) $value : '', ENT_QUOTES, 'UTF-8');
};
$formError = '';
$setupWarning = '';
$schemaTables = ['hpp_ingredients', 'hpp_recipes', 'hpp_recipe_lines', 'hpp_recipe_costs'];
$existingTables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$schemaReady = count(array_diff($schemaTables, $existingTables)) === 0;

$rawRecipeId = $_GET['id'] ?? null;
$recipeId = null;
if ($rawRecipeId !== null) {
    $parsedRecipeId = is_scalar($rawRecipeId) ? filter_var($rawRecipeId, FILTER_VALIDATE_INT) : false;
    if ($parsedRecipeId === false || $parsedRecipeId < 1) {
        http_response_code(400);
        $formError = 'ID resep tidak valid.';
    } else {
        $recipeId = (int) $parsedRecipeId;
    }
}

$recipe = null;
$ingredients = [];
$productItems = [];
$linkedProductItem = null;
$defaultCosts = [];
$markupScenarios = [50, 75, 100, 200];
$formValues = [];
$postedLines = null;
$postedCosts = null;
$refreshSnapshots = false;
$applyPreview = null;
$applyFormValues = [];

if ($schemaReady) {
    if ($recipeId !== null) {
        $recipe = hpp_get_recipe($pdo, $recipeId);
        if ($recipe === null) {
            http_response_code(404);
            $formError = 'Resep tidak ditemukan atau sudah dihapus.';
            $recipeId = null;
        }
    }
    if ($recipe !== null) {
        $productItemsStmt = $pdo->query(
            'SELECT pi.id, pi.name AS item_name, pi.price, p.name AS product_name '
            . 'FROM product_items pi INNER JOIN products p ON p.id = pi.product_id '
            . 'ORDER BY p.name, pi.name, pi.id'
        );
        $productItems = $productItemsStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($productItems as $productItem) {
            if ((int) $productItem['id'] === (int) ($recipe['product_item_id'] ?? 0)) {
                $linkedProductItem = $productItem;
                break;
            }
        }
    }

    $ingredientStmt = $pdo->query(
        'SELECT id, name, base_unit, pack_size, pack_price FROM hpp_ingredients ORDER BY name ASC'
    );
    $ingredients = $ingredientStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($ingredients as &$ingredient) {
        $ingredient['unit_cost'] = hpp_unit_cost($ingredient['pack_price'], $ingredient['pack_size']);
    }
    unset($ingredient);

    $settingsStmt = $pdo->query(
        "SELECT setting_key, setting_value FROM settings "
        . "WHERE setting_key IN ('hpp_default_costs', 'hpp_margins')"
    );
    $hppSettings = $settingsStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    if (isset($hppSettings['hpp_default_costs']) && $hppSettings['hpp_default_costs'] !== '') {
        try {
            $decodedCosts = json_decode($hppSettings['hpp_default_costs'], true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($decodedCosts)) {
                throw new JsonException('Pengaturan biaya bukan daftar.');
            }
            foreach ($decodedCosts as $cost) {
                if (
                    !is_array($cost)
                    || !is_string($cost['label'] ?? null)
                    || !in_array($cost['calc_type'] ?? null, ['pct_bahan', 'per_jar', 'flat'], true)
                    || (!is_string($cost['amount'] ?? null) && !is_int($cost['amount'] ?? null) && !is_float($cost['amount'] ?? null))
                    || !preg_match('/^\d+(?:[.,]\d{1,4})?$/', (string) ($cost['amount'] ?? ''))
                    || (float) str_replace(',', '.', (string) ($cost['amount'] ?? '')) > 99999999.9999
                    || (($cost['calc_type'] ?? null) === 'pct_bahan' && (float) str_replace(',', '.', (string) ($cost['amount'] ?? '')) > 100)
                ) {
                    throw new JsonException('Pengaturan biaya memiliki format yang tidak valid.');
                }
                $defaultCosts[] = [
                    'label' => $cost['label'],
                    'calc_type' => $cost['calc_type'],
                    'amount' => (string) $cost['amount'],
                ];
            }
        } catch (JsonException $exception) {
            $setupWarning = 'Pengaturan biaya default tidak valid. Tambahkan biaya secara manual sebelum menyimpan resep.';
            $defaultCosts = [];
        }
    } elseif ($recipe === null) {
        $setupWarning = 'Pengaturan biaya default belum tersedia. Tambahkan biaya secara manual sebelum menyimpan resep.';
    }

    if (isset($hppSettings['hpp_margins']) && $hppSettings['hpp_margins'] !== '') {
        $configuredMargins = explode(',', $hppSettings['hpp_margins']);
        $validMargins = [];
        foreach ($configuredMargins as $margin) {
            $margin = trim($margin);
            if (preg_match('/^\d+(?:[.,]\d{1,4})?$/', $margin) && (float) str_replace(',', '.', $margin) <= 10000) {
                $validMargins[] = (float) str_replace(',', '.', $margin);
            }
        }
        if ($validMargins !== []) {
            $markupScenarios = $validMargins;
        } else {
            $setupWarning = trim($setupWarning . ' Pengaturan skenario markup tidak valid; nilai bawaan digunakan.');
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$schemaReady) {
        set_admin_flash('Migrasi database HPP belum diterapkan. Jalankan migrasi melalui menu Pengaturan sebelum mengelola resep.', 'error');
        header('Location: settings.php');
        exit;
    }

    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
    if ($action === 'save_recipe') {
        $formValues = $_POST;
        $postedLines = is_array($_POST['lines'] ?? null) ? array_values($_POST['lines']) : [];
        $postedCosts = is_array($_POST['costs'] ?? null) ? array_values($_POST['costs']) : [];
        $refreshSnapshots = ($_POST['refresh_master_snapshots'] ?? '') === '1';

        if ($recipeId === null && $rawRecipeId !== null) {
            $formError = 'ID resep tidak valid.';
        } else {
            try {
                $saved = hpp_save_recipe(
                    $pdo,
                    $_POST,
                    $recipeId,
                    $refreshSnapshots
                );
                log_admin_action(
                    $recipeId === null ? 'HPP_CREATE_RECIPE' : 'HPP_UPDATE_RECIPE',
                    'id=' . $saved['id']
                );
                set_admin_flash('Resep berhasil disimpan. Harga publik tidak berubah.');
                header('Location: hpp_edit.php?id=' . $saved['id']);
                exit;
            } catch (InvalidArgumentException $exception) {
                $formError = $exception->getMessage();
            }
        }
    } elseif ($action === 'preview_apply_price' || $action === 'apply_public_price') {
        $applyFormValues = $_POST;
        if ($recipeId === null || $recipe === null) {
            $formError = 'Simpan resep terlebih dahulu sebelum menerapkan harga jual.';
        } else {
            try {
                if ($action === 'preview_apply_price') {
                    $applyPreview = hpp_get_public_price_preview(
                        $pdo,
                        $recipeId,
                        $_POST['product_item_id'] ?? null,
                        $_POST['final_price'] ?? null
                    );
                } else {
                    $applied = hpp_apply_public_price(
                        $pdo,
                        $recipeId,
                        $_POST['product_item_id'] ?? null,
                        $_POST['final_price'] ?? null,
                        $_POST['expected_old_price'] ?? null
                    );
                    log_admin_action(
                        'HPP_APPLY_PUBLIC_PRICE',
                        'recipe_id=' . $applied['recipe_id']
                        . ', product_item_id=' . $applied['product_item_id']
                        . ', old_price=' . $applied['old_price']
                        . ', new_price=' . $applied['new_price']
                    );
                    set_admin_flash(
                        'Harga varian "' . $applied['item_name'] . '" berhasil diperbarui menjadi Rp '
                        . format_decimal(ceil((float) $applied['new_price']), 0) . '.'
                    );
                    header('Location: hpp_edit.php?id=' . $recipeId);
                    exit;
                }
            } catch (InvalidArgumentException $exception) {
                $formError = $exception->getMessage();
            }
        }
    } else {
        $formError = 'Aksi tidak dikenali.';
    }
}

$changedIngredientPrices = false;
if ($recipe !== null && $schemaReady) {
    $changedIngredientPrices = hpp_recipe_has_changed_ingredient_prices($pdo, $recipeId);
}

$value = static function (string $key, $fallback = '') use ($formValues, $recipe): string {
    if (array_key_exists($key, $formValues)) {
        return is_scalar($formValues[$key]) ? (string) $formValues[$key] : '';
    }
    if ($recipe !== null && array_key_exists($key, $recipe)) {
        return is_scalar($recipe[$key]) ? (string) $recipe[$key] : '';
    }
    return is_scalar($fallback) ? (string) $fallback : '';
};

if ($postedLines !== null) {
    $viewLines = array_slice(
        array_values(array_filter($postedLines, 'is_array')),
        0,
        40
    );
} elseif ($recipe !== null) {
    $viewLines = $recipe['lines'];
} else {
    $viewLines = [];
}
if ($viewLines === []) {
    $viewLines = [['ingredient_id' => '', 'custom_name' => '', 'qty' => '', 'unit_cost' => '0']];
}

if ($postedCosts !== null) {
    $viewCosts = array_slice(
        array_values(array_filter($postedCosts, 'is_array')),
        0,
        15
    );
} elseif ($recipe !== null) {
    $viewCosts = $recipe['costs'];
} else {
    $viewCosts = $defaultCosts;
}

$lineSnapshots = [];
if ($recipe !== null) {
    foreach ($recipe['lines'] as $savedLine) {
        $lineSnapshots[(int) $savedLine['id']] = $savedLine;
    }
}
$ingredientById = [];
foreach ($ingredients as $ingredient) {
    $ingredientById[(int) $ingredient['id']] = $ingredient;
}
$cleanDecimal = static function ($val, int $maxDecimals = 2): string {
    if ($val === null || $val === '') {
        return '';
    }
    $strVal = str_replace(',', '.', trim((string) $val));
    if (!is_numeric($strVal)) {
        return (string) $val;
    }
    $rounded = round((float) $strVal, $maxDecimals);
    $formatted = number_format($rounded, $maxDecimals, '.', '');
    return str_contains($formatted, '.') ? rtrim(rtrim($formatted, '0'), '.') : $formatted;
};

foreach ($viewLines as &$viewLine) {
    if (!is_array($viewLine)) {
        continue;
    }
    $rawIngredientId = $viewLine['ingredient_id'] ?? null;
    $postedIngredientId = is_scalar($rawIngredientId)
        ? filter_var($rawIngredientId, FILTER_VALIDATE_INT)
        : false;
    if ($postedIngredientId === false || $postedIngredientId < 1 || !isset($ingredientById[$postedIngredientId])) {
        if (isset($viewLine['qty'])) {
            $viewLine['qty'] = $cleanDecimal($viewLine['qty'], 2);
        }
        if (isset($viewLine['unit_cost'])) {
            $viewLine['unit_cost'] = $cleanDecimal($viewLine['unit_cost'], 2);
        }
        continue;
    }
    $rawLineId = $viewLine['id'] ?? null;
    $postedLineId = is_scalar($rawLineId)
        ? filter_var($rawLineId, FILTER_VALIDATE_INT)
        : false;
    $savedLine = $postedLineId !== false && isset($lineSnapshots[$postedLineId])
        ? $lineSnapshots[$postedLineId]
        : null;
    $sameSavedIngredient = $savedLine !== null
        && (int) ($savedLine['ingredient_id'] ?? 0) === $postedIngredientId;
    $unitCost = $sameSavedIngredient
        ? $savedLine['unit_cost']
        : $ingredientById[$postedIngredientId]['unit_cost'];
    $viewLine['unit_cost'] = $cleanDecimal($unitCost, 2);
    if (isset($viewLine['qty'])) {
        $viewLine['qty'] = $cleanDecimal($viewLine['qty'], 2);
    }
}
unset($viewLine);

foreach ($viewCosts as &$viewCost) {
    if (is_array($viewCost) && isset($viewCost['amount'])) {
        $viewCost['amount'] = $cleanDecimal($viewCost['amount'], 2);
    }
}
unset($viewCost);
$csrfToken = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex, nofollow">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $recipeId === null ? 'Resep HPP Baru' : 'Edit Resep HPP' ?> - Alfi Kitchen</title>
    <link rel="stylesheet" href="assets/css/admin.css?v=<?= (int) filemtime(__DIR__ . '/assets/css/admin.css') . '-' . (int) filesize(__DIR__ . '/assets/css/admin.css') ?>">
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <h2><?= $recipeId === null ? 'Buat Resep HPP' : 'Edit Resep HPP' ?></h2>
                <div class="admin-header-actions">
                    <a href="../" target="_blank" rel="noopener noreferrer" class="btn-site-preview"><?= admin_icon('external') ?>Lihat Website</a>
                    <a href="logout.php" class="btn btn-danger btn-with-icon admin-header-logout"><?= admin_icon('logout') ?>Logout</a>
                </div>
            </header>
            <main class="admin-container hpp-editor">
                <?php include __DIR__ . '/flash_message.php'; ?>
                <?php if ($formError !== ''): ?>
                    <div class="alert alert-error" role="alert"><?= $escape($formError) ?></div>
                <?php endif; ?>
                <?php if ($setupWarning !== ''): ?>
                    <div class="alert alert-error" role="alert"><?= $escape($setupWarning) ?></div>
                <?php endif; ?>

                <?php if (!$schemaReady): ?>
                    <div class="form-card">
                        <div class="alert alert-error" role="alert">
                            Tabel kalkulator HPP belum tersedia. Terapkan migrasi database terlebih dahulu di
                            <a href="settings.php">Pengaturan</a>.
                        </div>
                    </div>
                <?php else: ?>

                <?php if ($changedIngredientPrices): ?>
                    <div class="alert alert-error hpp-slim-alert" role="alert">
                        <span class="hpp-slim-alert-icon"><?= admin_icon('info') ?></span>
                        <span class="hpp-slim-alert-text">Harga master beberapa bahan berubah. Snapshot resep tetap dipakai sampai Anda memilih pembaruan snapshot secara eksplisit.</span>
                    </div>
                <?php endif; ?>
                <div class="hpp-editor-tabs" role="tablist">
                    <button type="button" class="hpp-editor-tab<?= $applyPreview === null ? ' is-active' : '' ?>" data-tab-target="tab-pane-materials" role="tab" aria-selected="<?= $applyPreview === null ? 'true' : 'false' ?>">
                        <?= admin_icon('manage') ?> <span>1. Resep &amp; Bahan</span>
                    </button>
                    <button type="button" class="hpp-editor-tab" data-tab-target="tab-pane-costs" role="tab" aria-selected="false">
                        <?= admin_icon('settings') ?> <span>2. Biaya Operasional</span>
                    </button>
                    <button type="button" class="hpp-editor-tab<?= $applyPreview !== null ? ' is-active' : '' ?>" data-tab-target="tab-pane-calculation" role="tab" aria-selected="<?= $applyPreview !== null ? 'true' : 'false' ?>">
                        <?= admin_icon('calculator') ?> <span>3. Kalkulasi &amp; Harga Jual</span>
                        <span class="hpp-tab-badge hpp-tab-hpp-badge" data-tab-hpp-badge>HPP: Rp 0</span>
                    </button>
                </div>

                <form method="post" action="hpp_edit.php<?= $recipeId === null ? '' : '?id=' . $recipeId ?>" id="hpp-recipe-form" data-submit-lock>
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                    <input type="hidden" name="action" value="save_recipe">
                    <div class="hpp-editor-layout">

                    <div class="hpp-tab-pane" id="tab-pane-materials" data-tab-pane<?= $applyPreview !== null ? ' hidden' : '' ?>>
                    <section class="form-card hpp-strip-card">
                        <input type="hidden" name="round_step" value="<?= $escape($value('round_step', '1000')) ?>">
                        <div class="hpp-strip-header">
                            <div class="form-group hpp-strip-name">
                                <label for="recipe-name">Nama Resep</label>
                                <input id="recipe-name" type="text" name="name" maxlength="120" required value="<?= $escape($value('name')) ?>" placeholder="Nama resep...">
                            </div>
                            <div class="form-group hpp-strip-variant">
                                <label for="recipe-variant">Varian</label>
                                <input id="recipe-variant" type="text" name="variant" maxlength="40" value="<?= $escape($value('variant')) ?>" placeholder="Varian (opsional)">
                            </div>
                            <div class="form-group hpp-strip-yield">
                                <label for="recipe-yield">Jumlah Porsi / Kemasan</label>
                                <input id="recipe-yield" type="number" name="yield_jars" min="1" max="200" step="1" required value="<?= $escape($value('yield_jars', '1')) ?>" placeholder="1">
                            </div>
                            <div class="form-group hpp-strip-jar-ml">
                                <label for="recipe-jar-ml">Ukuran (ml/g)</label>
                                <input id="recipe-jar-ml" type="number" name="jar_ml" min="1" max="32767" step="1" value="<?= $escape($value('jar_ml')) ?>" placeholder="Opsional">
                            </div>
                            <div class="form-group hpp-strip-note">
                                <label for="recipe-note">Catatan Resep (opsional)</label>
                                <input id="recipe-note" type="text" name="note" maxlength="255" value="<?= $escape($value('note')) ?>" placeholder="Catatan internal resep...">
                            </div>
                        </div>
                    </section>

                    <section class="form-card hpp-materials-card">
                        <div class="hpp-editor-section-heading">
                            <h3>Bahan</h3>
                            <a href="hpp_ingredients.php" target="_blank" class="hpp-link-subtle" title="Buka Master Bahan di tab baru"><?= admin_icon('manage') ?> <span>Master Bahan</span></a>
                        </div>

                        <datalist id="master-ingredients-datalist">
                            <?php foreach ($ingredients as $ing): ?>
                                <option value="<?= $escape($ing['name']) ?>" data-id="<?= (int) $ing['id'] ?>" data-unit="<?= $escape($ing['base_unit']) ?>" data-unit-cost="<?= $escape($cleanDecimal($ing['unit_cost'], 2)) ?>">
                                    <?= $escape($ing['name']) ?> (<?= $escape($ing['base_unit']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </datalist>

                        <div class="hpp-quick-add-bar">
                            <div class="hpp-quick-add-input-wrap">
                                <input type="text" id="quick-ingredient-input" list="master-ingredients-datalist" placeholder="🔍 Ketik nama bahan master untuk tambah cepat (lalu tekan Enter)..." autocomplete="off">
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm btn-with-icon" id="btn-quick-add"><?= admin_icon('add') ?><span>Tambah Cepat</span></button>
                        </div>

                        <div class="hpp-table-header hpp-materials-header" aria-hidden="true">
                            <span>Bahan Baku</span>
                            <span>Jumlah Digunakan</span>
                            <span>Subtotal</span>
                            <span></span>
                        </div>

                        <div class="hpp-editor-rows" data-lines></div>
                        <p class="hpp-materials-footer-hint">
                            Bahan belum ada di daftar? Tambahkan bahan baru di <a href="hpp_ingredients.php" target="_blank">Master Bahan</a>.
                        </p>
                        <label class="hpp-refresh-snapshots" title="Perbarui snapshot harga bahan master sebelum menyimpan"<?= $recipe === null ? ' hidden' : '' ?>>
                            <input type="checkbox" name="refresh_master_snapshots" value="1" data-refresh-snapshots<?= $refreshSnapshots ? ' checked' : '' ?>>
                            <span>Gunakan harga master terbaru saat ini untuk resep ini</span>
                        </label>
                    </section>

                    <div class="hpp-editor-actions">
                        <button type="submit" class="btn btn-primary btn-with-icon"><?= admin_icon('save') ?>Simpan Resep</button>
                        <button type="button" class="btn btn-secondary btn-with-icon" id="btn-next-to-costs"><span>Lanjut: Biaya Operasional &rarr;</span></button>
                        <a href="hpp_recipes.php" class="btn btn-cancel btn-with-icon"><?= admin_icon('back') ?>Batal</a>
                    </div>
                    </div>

                    <div class="hpp-tab-pane" id="tab-pane-costs" data-tab-pane hidden>
                    <div class="alert hpp-editor-notice" role="note">
                        <?= admin_icon('info') ?>
                        <span>Kalkulasi dan penyimpanan resep tidak mengubah harga jual di website. Harga publik hanya berubah setelah Anda meninjau dan mengonfirmasi penerapan harga.</span>
                    </div>
                    <section class="form-card hpp-costs-card">
                        <div class="hpp-editor-section-heading">
                            <h3>Biaya Operasional</h3>
                            <button type="button" class="btn btn-cancel btn-sm btn-with-icon" data-add-cost><?= admin_icon('add') ?><span>Tambah Biaya</span></button>
                        </div>
                        <div class="hpp-table-header hpp-cost-table-header" aria-hidden="true">
                            <span>Nama Biaya</span>
                            <span>Jenis Perhitungan</span>
                            <span>Nilai</span>
                            <span>Subtotal Biaya</span>
                            <span></span>
                        </div>
                        <div class="hpp-editor-rows" data-costs></div>
                    </section>

                    <div class="hpp-editor-actions">
                        <button type="submit" class="btn btn-primary btn-with-icon"><?= admin_icon('save') ?>Simpan Resep</button>
                        <button type="button" class="btn btn-secondary btn-with-icon" id="btn-back-to-materials"><span>&larr; Bahan</span></button>
                        <button type="button" class="btn btn-secondary btn-with-icon" id="btn-next-to-calculation"><span>Lanjut: Hasil Kalkulasi &rarr;</span></button>
                        <a href="hpp_recipes.php" class="btn btn-cancel btn-with-icon"><?= admin_icon('back') ?>Batal</a>
                    </div>
                    </div>

                    <div class="hpp-tab-pane" id="tab-pane-calculation" data-tab-pane<?= $applyPreview === null ? ' hidden' : '' ?>>
                    <section class="form-card hpp-calculation-card" aria-live="polite">
                        <h3>Pratinjau Kalkulasi</h3>
                        <p class="hpp-help-text">Pratinjau diperbarui saat data berubah. Nilai tersimpan tetap dihitung dan divalidasi ulang di server.</p>
                        <div class="hpp-calculation-summary">
                            <div><span>Total bahan</span><strong data-total-material>Rp 0</strong></div>
                            <div><span>Biaya operasional</span><strong data-total-operational>Rp 0</strong></div>
                            <div><span>Total biaya aktual</span><strong data-total-actual>Rp 0</strong></div>
                            <div><span>HPP resep (dibulatkan ke atas)</span><strong data-hpp-recipe>Rp 0</strong></div>
                            <div><span>HPP per jar</span><strong data-hpp-per-jar>Rp 0</strong></div>
                        </div>
                        <div class="hpp-scenarios-heading">
                            <span>Skenario Markup &amp; Rekomendasi Harga</span>
                        </div>
                        <div class="hpp-scenarios-list">
                            <div class="hpp-scenario-item hpp-scenario-custom" id="custom-scenario-item">
                                <div class="hpp-scenario-custom-badge" title="Ketik persentase markup kustom manual">
                                    <span class="hpp-scenario-custom-sign">+</span>
                                    <input type="number" id="custom-markup-input" class="hpp-scenario-custom-input" min="0" max="10000" step="any" placeholder="60" value="60" aria-label="Persentase markup kustom">
                                    <span class="hpp-scenario-custom-pct">%</span>
                                </div>
                                <div class="hpp-scenario-info">
                                    <span class="hpp-scenario-price" id="custom-scenario-price">Rp 0</span>
                                    <span class="hpp-scenario-meta" id="custom-scenario-meta">Kustom manual</span>
                                </div>
                                <div class="hpp-scenario-action">
                                    <button type="button" class="btn btn-primary btn-sm" id="btn-use-custom-scenario" data-use-scenario-price="" disabled>Gunakan</button>
                                </div>
                            </div>
                            <div class="hpp-scenarios-presets" data-scenarios></div>
                        </div>
                        <?php if ($recipe === null): ?>
                            <div class="hpp-scenarios-hint">
                                <?= admin_icon('info') ?>
                                <span>Simpan resep terlebih dahulu untuk dapat menerapkan harga rekomendasi ini ke produk website.</span>
                            </div>
                        <?php endif; ?>
                        <p class="hpp-calculation-status" data-calculation-status role="status"></p>
                    </section>

                    <div class="hpp-editor-actions">
                        <button type="submit" class="btn btn-primary btn-with-icon"><?= admin_icon('save') ?>Simpan Resep</button>
                        <button type="button" class="btn btn-secondary btn-with-icon" id="btn-back-to-costs"><span>&larr; Kembali ke Biaya</span></button>
                        <a href="hpp_recipes.php" class="btn btn-cancel btn-with-icon"><?= admin_icon('back') ?>Batal</a>
                    </div>
                    </div>

                    </div>
                </form>

                <?php if ($recipe !== null): ?>
                    <section class="form-card hpp-public-price-card" data-pricing-section<?= $applyPreview === null ? ' hidden' : '' ?>>
                        <h3>Terapkan Harga Jual ke Website</h3>
                        <p class="hpp-help-text">
                            Penerapan memakai HPP resep tersimpan. Pilih varian dan harga final; perubahan baru dilakukan setelah Anda mengonfirmasi harga lama dan baru.
                        </p>
                        <form method="post" action="hpp_edit.php?id=<?= (int) $recipeId ?>" class="hpp-public-price-form" data-submit-lock>
                            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                            <input type="hidden" name="action" value="preview_apply_price">
                            <div class="admin-form-grid">
                                <div class="form-group">
                                    <label for="public-price-item">Varian produk</label>
                                    <select id="public-price-item" name="product_item_id" required>
                                        <option value="">Pilih varian produk</option>
                                        <?php foreach ($productItems as $productItem): ?>
                                            <?php
                                            $itemId = (int) $productItem['id'];
                                            $selectedItemId = $applyFormValues['product_item_id'] ?? $recipe['product_item_id'];
                                            $isSelectedItem = is_scalar($selectedItemId) && (string) $selectedItemId === (string) $itemId;
                                            ?>
                                            <option value="<?= $itemId ?>"<?= $isSelectedItem ? ' selected' : '' ?>>
                                                <?= $escape($productItem['product_name']) ?> — <?= $escape($productItem['item_name']) ?>
                                                (saat ini Rp <?= format_decimal(ceil((float) $productItem['price']), 0) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="final-public-price">Harga jual final (Rp)</label>
                                    <input
                                        id="final-public-price"
                                        type="number"
                                        name="final_price"
                                        min="1"
                                        max="99999999"
                                        step="1"
                                        inputmode="numeric"
                                        data-final-price
                                        value="<?= $escape($applyFormValues['final_price'] ?? '') ?>"
                                        required
                                    >
                                </div>
                            </div>
                            <div class="hpp-price-rounding-actions">
                                <button type="button" class="btn btn-cancel" data-round-price="500">Bulatkan ke atas ke Rp 500</button>
                                <button type="button" class="btn btn-cancel" data-round-price="1000">Bulatkan ke atas ke Rp 1.000</button>
                            </div>
                            <p class="hpp-help-text">Pembulatan hanya mengisi harga usulan di atas. Harga website tidak berubah sampai Anda meninjau dan mengonfirmasi langkah berikutnya.</p>
                            <button type="submit" class="btn btn-primary btn-with-icon" data-loading-label="Menyiapkan tinjauan..."><?= admin_icon('check') ?>Tinjau Perubahan Harga</button>
                        </form>
                    </section>
                <?php endif; ?>

                <?php if ($applyPreview !== null): ?>
                    <section class="form-card hpp-price-confirmation" data-pricing-section aria-labelledby="hpp-price-confirmation-title">
                        <h3 id="hpp-price-confirmation-title">Konfirmasi Penerapan Harga</h3>
                        <p>
                            Resep <strong><?= $escape($applyPreview['recipe_name']) ?></strong> akan dihubungkan ke
                            <strong><?= $escape($applyPreview['product_name']) ?> — <?= $escape($applyPreview['item_name']) ?></strong>.
                        </p>
                        <?php if (
                            $linkedProductItem !== null
                            && (int) $linkedProductItem['id'] !== (int) $applyPreview['product_item_id']
                        ): ?>
                            <p class="hpp-help-text">
                                Hubungan resep akan dipindahkan dari <?= $escape($linkedProductItem['product_name']) ?> —
                                <?= $escape($linkedProductItem['item_name']) ?>. Harga varian tersebut tidak berubah.
                            </p>
                        <?php endif; ?>
                        <dl class="hpp-price-comparison">
                            <div>
                                <dt>Harga lama</dt>
                                <dd>Rp <?= format_decimal(ceil((float) $applyPreview['old_price']), 0) ?></dd>
                            </div>
                            <div>
                                <dt>Harga baru</dt>
                                <dd>Rp <?= format_decimal(ceil((float) $applyPreview['new_price']), 0) ?></dd>
                            </div>
                            <div>
                                <dt>HPP per jar</dt>
                                <dd>Rp <?= format_decimal(ceil((float) $applyPreview['hpp_per_jar']), 0) ?></dd>
                            </div>
                        </dl>
                        <?php if ($applyPreview['below_hpp']): ?>
                            <div class="alert alert-error" role="alert">
                                Harga baru berada di bawah HPP per jar. Peringatan ini bersifat informatif; harga tetap hanya berubah jika Anda mengonfirmasi.
                            </div>
                        <?php endif; ?>
                        <form method="post" action="hpp_edit.php?id=<?= (int) $recipeId ?>" class="hpp-confirm-price-actions" data-submit-lock>
                            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                            <input type="hidden" name="action" value="apply_public_price">
                            <input type="hidden" name="product_item_id" value="<?= (int) $applyPreview['product_item_id'] ?>">
                            <input type="hidden" name="final_price" value="<?= (int) $applyPreview['new_price'] ?>">
                            <input type="hidden" name="expected_old_price" value="<?= $escape($applyPreview['old_price']) ?>">
                            <button type="submit" class="btn btn-primary btn-with-icon"><?= admin_icon('check') ?>Konfirmasi dan Pakai sebagai Harga Jual</button>
                            <a href="hpp_edit.php?id=<?= (int) $recipeId ?>" class="btn btn-cancel btn-with-icon"><?= admin_icon('back') ?>Batal</a>
                        </form>
                    </section>
                <?php endif; ?>

                <template id="hpp-line-template">
                    <div class="hpp-editor-row" data-line-row>
                        <input type="hidden" name="lines[__INDEX__][id]" data-line-id>
                        <input type="hidden" name="lines[__INDEX__][unit_cost]" data-line-unit-cost>
                        <div class="form-group hpp-cell-ingredient">
                            <label class="hpp-row-label">Bahan Baku</label>
                            <select name="lines[__INDEX__][ingredient_id]" data-ingredient-select>
                                <option value="">Item bebas</option>
                                <?php foreach ($ingredients as $ingredient): ?>
                                    <option value="<?= (int) $ingredient['id'] ?>" data-unit="<?= $escape($ingredient['base_unit']) ?>" data-unit-cost="<?= $escape($cleanDecimal($ingredient['unit_cost'], 2)) ?>"><?= $escape($ingredient['name']) ?> (<?= $escape($ingredient['base_unit']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="lines[__INDEX__][custom_name]" data-custom-name-hidden disabled>
                            <div class="hpp-custom-name-wrap" data-custom-group hidden>
                                <input type="text" name="lines[__INDEX__][custom_name]" maxlength="120" data-custom-name placeholder="Nama item bebas...">
                                <div class="hpp-custom-cost-wrap">
                                    <span class="hpp-custom-cost-label">Biaya satuan (Rp):</span>
                                    <input type="text" data-custom-cost-input inputmode="decimal" maxlength="16" placeholder="0">
                                </div>
                            </div>
                        </div>
                        <div class="form-group hpp-cell-qty">
                            <label class="hpp-row-label">Jumlah</label>
                            <div class="hpp-qty-input-group">
                                <input type="text" name="lines[__INDEX__][qty]" inputmode="decimal" maxlength="15" placeholder="0" data-line-qty required>
                                <span class="hpp-qty-unit-badge" data-unit-badge>satuan</span>
                            </div>
                        </div>
                        <div class="hpp-editor-row-total">
                            <span>Subtotal</span>
                            <strong data-line-total>Rp 0</strong>
                        </div>
                        <button type="button" class="btn btn-danger btn-sm btn-with-icon hpp-remove-row" data-remove-line aria-label="Hapus baris bahan" title="Hapus baris bahan"><?= admin_icon('delete') ?><span>Hapus</span></button>
                    </div>
                </template>
                <template id="hpp-cost-template">
                    <div class="hpp-editor-row hpp-cost-row" data-cost-row>
                        <div class="form-group hpp-cell-cost-label">
                            <label class="hpp-row-label">Nama biaya</label>
                            <input type="text" name="costs[__INDEX__][label]" maxlength="80" data-cost-label required placeholder="Nama biaya...">
                        </div>
                        <div class="form-group hpp-cell-cost-type">
                            <label class="hpp-row-label">Jenis perhitungan</label>
                            <select name="costs[__INDEX__][calc_type]" data-cost-type>
                                <option value="pct_bahan">Persentase dari total bahan (%)</option>
                                <option value="per_jar">Biaya per jar (Rp)</option>
                                <option value="flat">Biaya tetap per resep (Rp)</option>
                            </select>
                        </div>
                        <div class="form-group hpp-cell-cost-amount">
                            <label class="hpp-row-label">Nilai</label>
                            <input type="text" name="costs[__INDEX__][amount]" inputmode="decimal" maxlength="16" data-cost-amount required placeholder="Nilai...">
                        </div>
                        <div class="hpp-editor-row-total"><span>Subtotal</span><strong data-cost-total>Rp 0</strong></div>
                        <button type="button" class="btn btn-danger btn-sm btn-with-icon hpp-remove-row" data-remove-cost aria-label="Hapus biaya operasional" title="Hapus biaya operasional"><?= admin_icon('delete') ?><span>Hapus</span></button>
                    </div>
                </template>
                <script type="application/json" id="hpp-editor-data"><?= json_encode(
                    [
                        'lines' => $viewLines,
                        'costs' => $viewCosts,
                        'margins' => $markupScenarios,
                        'refreshSnapshots' => $refreshSnapshots,
                        'editing' => $recipeId !== null,
                    ],
                    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
                ) ?></script>
                <script src="assets/js/hpp_recipe_editor.js?v=<?= (int) filemtime(__DIR__ . '/assets/js/hpp_recipe_editor.js') . '-' . (int) filesize(__DIR__ . '/assets/js/hpp_recipe_editor.js') ?>" defer></script>
                <?php endif; ?>
            </main>
        </div>
    </div>
</body>
</html>
