<?php
require 'config.php';
require 'seo_helpers.php';

header('Content-Type: application/xml; charset=UTF-8');

$urls = [
    ['loc' => site_url(''),            'priority' => '1.0', 'changefreq' => 'weekly'],
    ['loc' => site_url('about.php'),   'priority' => '0.6', 'changefreq' => 'monthly'],
    ['loc' => site_url('privacy.php'), 'priority' => '0.3', 'changefreq' => 'yearly'],
    ['loc' => site_url('terms.php'),   'priority' => '0.3', 'changefreq' => 'yearly'],
];

$stmt = $pdo->query("SELECT id, name, slug FROM products ORDER BY id ASC");
foreach ($stmt->fetchAll() as $p) {
    $urls[] = [
        'loc'        => site_url('produk/' . ensure_product_slug($pdo, $p)),
        'priority'   => '0.8',
        'changefreq' => 'weekly',
    ];
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as $u): ?>
  <url>
    <loc><?= htmlspecialchars($u['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') ?></loc>
    <changefreq><?= $u['changefreq'] ?></changefreq>
    <priority><?= $u['priority'] ?></priority>
  </url>
<?php endforeach; ?>
</urlset>
