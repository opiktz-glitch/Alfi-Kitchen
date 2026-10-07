<?php
require 'config.php';
require 'seo_helpers.php';

header('Content-Type: application/xml; charset=UTF-8');

$urls = [
    ['loc' => site_url(''),            'priority' => '1.0', 'changefreq' => 'weekly', 'lastmod' => date('c')],
    ['loc' => site_url('about.php'),   'priority' => '0.6', 'changefreq' => 'monthly'],
    ['loc' => site_url('privacy.php'), 'priority' => '0.3', 'changefreq' => 'yearly'],
    ['loc' => site_url('terms.php'),   'priority' => '0.3', 'changefreq' => 'yearly'],
];

$stmt = $pdo->query("SELECT id, name, slug, image, created_at FROM products ORDER BY id ASC");
foreach ($stmt->fetchAll() as $p) {
    $imgLoc = null;
    if (!empty($p['image'])) {
        // Expose the high-res WebP version to Googlebot
        $imgLoc = site_url('thumb.php?file=' . urlencode($p['image']) . '&w=800');
    }

    $urls[] = [
        'loc'        => site_url('produk/' . ensure_product_slug($pdo, $p)),
        'priority'   => '0.8',
        'changefreq' => 'weekly',
        'lastmod'    => date('c', strtotime($p['created_at'])),
        'image'      => $imgLoc,
        'title'      => $p['name']
    ];
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
<?php foreach ($urls as $u): ?>
  <url>
    <loc><?= htmlspecialchars($u['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') ?></loc>
    <?php if (!empty($u['lastmod'])): ?><lastmod><?= htmlspecialchars($u['lastmod'], ENT_XML1, 'UTF-8') ?></lastmod><?php endif; ?>
    <changefreq><?= htmlspecialchars($u['changefreq'], ENT_XML1, 'UTF-8') ?></changefreq>
    <priority><?= htmlspecialchars($u['priority'], ENT_XML1, 'UTF-8') ?></priority>
    <?php if (!empty($u['image'])): ?>
    <image:image>
      <image:loc><?= htmlspecialchars($u['image'], ENT_XML1 | ENT_QUOTES, 'UTF-8') ?></image:loc>
      <image:title><?= htmlspecialchars($u['title'], ENT_XML1 | ENT_QUOTES, 'UTF-8') ?></image:title>
    </image:image>
    <?php endif; ?>
  </url>
<?php endforeach; ?>
</urlset>
