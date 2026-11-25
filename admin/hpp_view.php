<?php
require 'auth_check.php';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/helpers/hpp_recipe_repository.php';
require_once __DIR__ . '/auth.php';

$escape = static function ($value): string {
    return htmlspecialchars(is_scalar($value) ? (string) $value : '', ENT_QUOTES, 'UTF-8');
};

$recipeId = (int) ($_GET['id'] ?? 0);
if ($recipeId < 1) {
    set_admin_flash('ID resep tidak valid.', 'error');
    header('Location: hpp_recipes.php');
    exit;
}

$recipe = hpp_get_recipe($pdo, $recipeId);
if (!$recipe) {
    set_admin_flash('Resep HPP tidak ditemukan.', 'error');
    header('Location: hpp_recipes.php');
    exit;
}

$isEmbedded = !empty($_GET['embedded']);
$recipeTitle = trim($recipe['name'] . ($recipe['variant'] !== '' ? ' — ' . $recipe['variant'] : ''));
$yieldJars = max(1, (int) ($recipe['yield_jars'] ?? 1));
$yieldNote = 'Perkiraan harga untuk 1 resep jadi ' . $yieldJars . ' kemasan' . (!empty($recipe['notes']) ? ' (' . $recipe['notes'] . ')' : '');

// Hitung total bahan baku dari baris snapshot
$materialTotal = 0;
$formattedLines = [];
foreach ($recipe['lines'] as $line) {
    $qty = (float) $line['qty'];
    $unitCost = (float) $line['unit_cost'];
    $subtotal = round($qty * $unitCost);
    $materialTotal += $subtotal;

    $baseUnit = strtolower(trim($line['base_unit'] ?? ''));
    if ($baseUnit === 'g') {
        $unitDisplay = 'gram';
    } elseif ($baseUnit === 'ml') {
        $unitDisplay = 'ml';
    } elseif ($baseUnit === 'pcs') {
        $unitDisplay = 'pcs';
    } else {
        $unitDisplay = $baseUnit ?: 'satuan';
    }

    // Tentukan harga beli kemasan acuan
    $packPrice = null;
    if (isset($line['pack_price']) && $line['pack_price'] > 0) {
        $packPrice = (float) $line['pack_price'];
    } elseif (!empty($line['pack_size']) && (float) $line['pack_size'] > 0) {
        $packPrice = round($unitCost * (float) $line['pack_size']);
    } else {
        $packPrice = round($unitCost);
    }

    $formattedLines[] = [
        'name' => $line['ingredient_name'] ?: ($line['custom_name'] ?: 'Bahan Tanpa Nama'),
        'qty' => $qty,
        'unit' => $unitDisplay,
        'pack_price' => $packPrice,
        'subtotal' => $subtotal,
    ];
}

// Hitung biaya operasional
$operationalTotal = 0;
$formattedCosts = [];
foreach ($recipe['costs'] as $cost) {
    $calcType = $cost['calc_type'] ?? 'pct_bahan';
    $amount = (float) ($cost['amount'] ?? 0);
    $costSubtotal = 0;
    $detail = '';

    if ($calcType === 'pct_bahan') {
        $costSubtotal = round($materialTotal * ($amount / 100));
        $detail = format_decimal($amount, 2) . '%';
    } elseif ($calcType === 'per_jar') {
        $costSubtotal = round($yieldJars * $amount);
        $detail = $yieldJars . ' kemasan @ Rp ' . format_decimal($amount, 0);
    } else {
        $costSubtotal = round($amount);
        $detail = 'Flat';
    }

    $operationalTotal += $costSubtotal;
    $formattedCosts[] = [
        'label' => $cost['label'] ?? 'Operasional',
        'detail' => $detail,
        'subtotal' => $costSubtotal,
    ];
}

$grandTotal = $materialTotal + $operationalTotal;
$roundedTotal = (int) ($recipe['hpp_recipe'] ?? 0);
if ($roundedTotal <= 0) {
    $step = max(1, (int) ($recipe['rounding_step'] ?? 1000));
    $roundedTotal = (int) (ceil($grandTotal / $step) * $step);
}
$costPerJar = (int) ($recipe['hpp_per_jar'] ?? 0);
if ($costPerJar <= 0) {
    $costPerJar = (int) ceil($roundedTotal / $yieldJars);
}

// Skenario Margin Standar (50%, 75%, 100%, 200%)
$marginPercentages = [50, 75, 100, 200];
$targetMargin = (float) ($recipe['target_margin_percent'] ?? 0);
if ($targetMargin > 0 && !in_array((int) round($targetMargin), $marginPercentages, true)) {
    $marginPercentages[] = round($targetMargin, 1);
    sort($marginPercentages);
}

$scenarios = [];
foreach ($marginPercentages as $pct) {
    $profit = round($roundedTotal * ($pct / 100));
    $batchPrice = $roundedTotal + $profit;
    $jarPrice = round($batchPrice / $yieldJars);
    $scenarios[] = [
        'percent' => $pct,
        'profit' => $profit,
        'batch_price' => $batchPrice,
        'jar_price' => $jarPrice,
        'is_target' => (abs($pct - $targetMargin) < 0.01),
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title>Lembar Resep HPP — <?= $escape($recipeTitle) ?></title>
    <link rel="icon" href="../favicon.ico" sizes="16x16 32x32 48x48">
    <link rel="stylesheet" href="assets/css/admin.css?v=<?= filemtime(__DIR__ . '/assets/css/admin.css') ?>">
</head>
<body class="admin-body <?= $isEmbedded ? 'hpp-view-embedded' : '' ?>">
    <div class="hpp-view-container">
        <?php if (!$isEmbedded): ?>
            <header class="hpp-view-topbar no-print">
                <div class="hpp-view-back">
                    <a href="hpp_recipes.php" class="btn btn-secondary btn-with-icon"><?= admin_icon('back') ?>Kembali ke Daftar Resep</a>
                </div>
                <div class="hpp-view-topbar-actions">
                    <a href="../" target="_blank" rel="noopener noreferrer" class="btn-site-preview"><?= admin_icon('external') ?>Lihat Website</a>
                    <a href="hpp_edit.php?id=<?= $recipeId ?>" class="btn btn-secondary btn-with-icon"><?= admin_icon('edit') ?>Edit Resep</a>
                    <button type="button" class="btn btn-primary btn-with-icon" onclick="window.print()"><?= admin_icon('print') ?>Cetak / Simpan PDF</button>
                </div>
            </header>
        <?php else: ?>
            <div class="hpp-view-topbar hpp-view-topbar-embedded no-print">
                <span class="hpp-view-embedded-label"><?= admin_icon('calculator') ?> Rincian Resep HPP</span>
                <div class="hpp-view-topbar-actions">
                    <a href="hpp_view.php?id=<?= $recipeId ?>" target="_blank" class="btn btn-secondary btn-with-icon" title="Buka di tab baru"><?= admin_icon('external') ?>Buka Tab Baru</a>
                    <button type="button" class="btn btn-primary btn-with-icon" onclick="window.print()"><?= admin_icon('print') ?>Cetak PDF</button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Lembar Resep Fisik Bergaya Kartu Contoh PDF -->
        <article class="hpp-recipe-sheet" id="hpp-recipe-print-area">
            <header class="hpp-sheet-banner">
                <div class="hpp-sheet-badge">HPP Resep Dapur</div>
                <h1 class="hpp-sheet-title"><?= $escape($recipe['name']) ?></h1>
                <?php if ($recipe['variant'] !== ''): ?>
                    <h2 class="hpp-sheet-variant"><?= $escape($recipe['variant']) ?></h2>
                <?php endif; ?>
                <p class="hpp-sheet-subtitle"><?= $escape($yieldNote) ?></p>
            </header>

            <div class="hpp-sheet-body">
                <!-- Bagian 1: Rincian Bahan Baku -->
                <div class="hpp-sheet-section-header">
                    <h3 class="hpp-sheet-section-title">Bahan Baku</h3>
                </div>
                <div class="table-responsive">
                    <table class="hpp-sheet-table">
                        <thead>
                            <tr>
                                <th>Keterangan</th>
                                <th class="num-col col-qty">
                                    <span class="desktop-only">Jumlah</span>
                                    <span class="mobile-only">Takaran</span>
                                </th>
                                <th class="center-col col-unit desktop-only">Satuan</th>
                                <th class="num-col col-pack-price">Harga (Rp)</th>
                                <th class="num-col col-subtotal">Total (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($formattedLines as $line): ?>
                                <tr>
                                    <td><?= $escape($line['name']) ?></td>
                                    <td class="num-col col-qty">
                                        <span class="qty-num"><?= format_decimal($line['qty'], 2) ?></span>
                                        <span class="qty-unit-inline mobile-only"><?= $escape($line['unit']) ?></span>
                                    </td>
                                    <td class="center-col col-unit desktop-only"><?= $escape($line['unit']) ?></td>
                                    <td class="num-col col-pack-price"><?= format_decimal($line['pack_price'], 0) ?></td>
                                    <td class="num-col col-subtotal"><strong><?= format_decimal($line['subtotal'], 0) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="row-subtotal">
                                <td colspan="4" class="desktop-only">Total Bahan Baku</td>
                                <td colspan="3" class="mobile-only">Total Bahan Baku</td>
                                <td class="num-col col-subtotal">Rp <?= format_decimal($materialTotal, 0) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Bagian 2: Rincian Operasional -->
                <div class="hpp-sheet-section-header">
                    <h3 class="hpp-sheet-section-title">Operasional</h3>
                </div>
                <div class="table-responsive">
                    <table class="hpp-sheet-table">
                        <thead>
                            <tr>
                                <th colspan="3" class="desktop-only">Keterangan</th>
                                <th colspan="2" class="mobile-only">Keterangan</th>
                                <th class="center-col">Perhitungan</th>
                                <th class="num-col">Total (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($formattedCosts as $cost): ?>
                                <tr>
                                    <td colspan="3" class="desktop-only"><?= $escape($cost['label']) ?></td>
                                    <td colspan="2" class="mobile-only"><?= $escape($cost['label']) ?></td>
                                    <td class="center-col"><?= $escape($cost['detail']) ?></td>
                                    <td class="num-col"><strong><?= format_decimal($cost['subtotal'], 0) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="row-subtotal">
                                <td colspan="4" class="desktop-only">Total Operasional</td>
                                <td colspan="3" class="mobile-only">Total Operasional</td>
                                <td class="num-col">Rp <?= format_decimal($operationalTotal, 0) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Bagian 3: Ringkasan Total HPP & Pembulatan -->
                <div class="hpp-sheet-summary-box">
                    <table class="hpp-sheet-table hpp-sheet-summary-table">
                        <tbody>
                            <tr>
                                <td><strong>Total Bahan Baku &amp; Operasional</strong></td>
                                <td class="num-col">Rp <?= format_decimal($grandTotal, 0) ?></td>
                            </tr>
                            <tr class="row-total">
                                <td><strong>Pembulatan (Harga 1 Resep)</strong></td>
                                <td class="num-col">Rp <?= format_decimal($roundedTotal, 0) ?></td>
                            </tr>
                            <tr class="row-highlight">
                                <td><strong>Harga per kemasan (1 resep = <?= $yieldJars ?> kemasan)</strong></td>
                                <td class="num-col">Rp <?= format_decimal($costPerJar, 0) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Bagian 4: Simulasi Rencana Margin & Harga Jual -->
                <div class="hpp-sheet-section-header">
                    <h3 class="hpp-sheet-section-title">Rencana Margin &amp; Rekomendasi Harga Jual</h3>
                </div>
                <div class="table-responsive">
                    <table class="hpp-sheet-table hpp-sheet-margin-table">
                        <thead>
                            <tr>
                                <th>Rencana Margin</th>
                                <th class="num-col">Perkiraan Laba (Rp)</th>
                                <th class="num-col">Harga Jual 1 Resep (Rp)</th>
                                <th class="num-col th-jar-price">Harga / Kemasan (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($scenarios as $sc): ?>
                                <tr class="<?= $sc['is_target'] ? 'row-selected-margin' : '' ?>">
                                    <td>
                                        <strong><?= $sc['percent'] ?>%</strong>
                                        <?php if ($sc['is_target']): ?>
                                            <span class="hpp-target-tag">Target Toko</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="num-col"><?= format_decimal($sc['profit'], 0) ?></td>
                                    <td class="num-col"><?= format_decimal($sc['batch_price'], 0) ?></td>
                                    <td class="num-col hpp-jar-price-cell">
                                        <strong>Rp <?= format_decimal($sc['jar_price'], 0) ?></strong>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </article>

        <?php if (!$isEmbedded): ?>
            <!-- Bilah Aksi Melayang Khusus Mobile (Sticky Bottom Bar) -->
            <aside class="hpp-mobile-bottom-bar no-print" aria-label="Aksi Cepat Lembar Resep">
                <div class="hpp-mobile-bottom-inner">
                    <a href="hpp_recipes.php" class="btn btn-secondary hpp-mobile-btn-back" title="Kembali ke Daftar Resep"><?= admin_icon('back') ?><span>Daftar</span></a>
                    <a href="hpp_edit.php?id=<?= $recipeId ?>" class="btn btn-secondary hpp-mobile-btn-edit" title="Edit Resep"><?= admin_icon('edit') ?><span>Edit</span></a>
                    <button type="button" class="btn btn-primary hpp-mobile-btn-print" onclick="window.print()"><?= admin_icon('print') ?>Cetak / Simpan PDF</button>
                </div>
                <div class="hpp-mobile-print-tip">
                    <span>💡 Di HP: Pada menu printer, pilih <strong>"Simpan sebagai PDF"</strong></span>
                </div>
            </aside>
        <?php endif; ?>
    </div>
</body>
</html>
