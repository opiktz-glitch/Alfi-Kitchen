<?php
$crumbs = [
    'index.php' => [['Dashboard']],
    'add_product.php' => [['Dashboard', 'index.php'], ['Tambah Kategori']],
    'edit_product.php' => [['Dashboard', 'index.php'], ['Edit Kategori']],
    'manage_items.php' => [['Dashboard', 'index.php'], ['Kelola Varian']],
    'edit_item.php' => [['Dashboard', 'index.php'], ['Kelola Varian', null], ['Edit Varian']],
    'manage_hero.php' => [['Dashboard', 'index.php'], ['Kelola Slider']],
    'settings.php' => [['Dashboard', 'index.php'], ['Pengaturan']],
    'activity_log.php' => [['Dashboard', 'index.php'], ['Log Aktivitas']],
];
$page = basename($_SERVER['PHP_SELF'] ?? '');
$trail = $crumbs[$page] ?? [['Dashboard', 'index.php']];
?>
<nav class="breadcrumb" aria-label="Breadcrumb">
    <?php foreach ($trail as $i => $c): ?>
        <?php if ($i > 0): ?><span class="breadcrumb-sep">›</span><?php endif; ?>
        <?php if (!empty($c[1])): ?>
            <a href="<?= htmlspecialchars($c[1], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($c[0], ENT_QUOTES, 'UTF-8') ?></a>
        <?php else: ?>
            <span><?= htmlspecialchars($c[0], ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>
