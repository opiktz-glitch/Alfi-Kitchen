<?php
require 'config.php';

require_once __DIR__ . '/seo_helpers.php';

// Tampilkan halaman 404 yang benar (status HTTP 404 + noindex) agar tidak dianggap soft-404
function product_not_found() {
    global $pdo; // dipakai oleh footer.php (scope fungsi tidak otomatis melihat $pdo)
    http_response_code(404);
    header('X-Robots-Tag: noindex, nofollow');
    $pageTitle = 'Produk Tidak Ditemukan | Alfi Kitchen';
    $pageDescription = 'Produk yang Anda cari tidak ditemukan. Lihat pilihan puding, dessert, dan salad buah dari Alfi Kitchen.';
    $noindex = true;
    include 'header.php';
    echo '<style>header { display: none !important; }</style>';
    echo '<main style="padding: 80px 6vw; min-height: 60vh; text-align: center;">';
    echo '<h1 style="font-size: clamp(1.8rem, 4vw, 2.6rem); margin: 0 0 16px;">Produk tidak ditemukan</h1>';
    echo '<p style="color: var(--muted); margin: 0 0 28px;">Produk yang Anda cari mungkin sudah dihapus atau alamatnya salah.</p>';
    echo '<a href="index.php#menu" style="display: inline-block; background: var(--accent); color: #fff; padding: 14px 36px; border-radius: 30px; text-decoration: none; font-weight: 600;">Lihat Semua Produk</a>';
    echo '</main>';
    include 'footer.php';
    exit;
}

$slugParam = isset($_GET['slug']) ? (string) $_GET['slug'] : '';
$idParam = $_GET['id'] ?? null;
$id = ($idParam !== null && $idParam !== '')
    ? filter_var($idParam, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
    : null;

if ($slugParam === '' && ($id === false || $id === null)) {
    product_not_found();
}

if ($slugParam !== '') {
    // URL utama: /produk/{slug}
    $stmt = $pdo->prepare("SELECT * FROM products WHERE slug = ?");
    $stmt->execute([$slugParam]);
    $main_product = $stmt->fetch();

    if ($main_product) {
        // $id tidak di-set di jalur slug — pakai id hasil query agar daftar item tampil
        $id = (int) $main_product['id'];
        // Kalau diakses lewat URL lama product.php?slug=..., pindahkan ke /produk/{slug}.
        // Deteksi pakai REQUEST_URI (bukan SCRIPT_NAME): saat URL pretty di-rewrite internal
        // ke product.php, SCRIPT_NAME ikut berubah menjadi /product.php sehingga kondisi
        // basename(SCRIPT_NAME) ikut cocok dan menyebabkan redirect loop 301 tanpa ujung.
        $reqUri = $_SERVER['REQUEST_URI'] ?? '';
        $onPrettyUrl = preg_match('#/produk/#', strtok($reqUri, '?') ?: '') === 1;
        if (!$onPrettyUrl) {
            header('Location: ' . site_url('produk/' . $main_product['slug']), true, 301);
            exit;
        }
    } else {
        product_not_found();
    }
} else {
    // URL lama product.php?id=N -> redirect permanen (301) ke URL slug
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $main_product = $stmt->fetch();

    if(!$main_product) {
        product_not_found();
    }

    $targetSlug = ensure_product_slug($pdo, $main_product);
    header('Location: ' . site_url('produk/' . $targetSlug), true, 301);
    exit;
}

$pageTitle = $main_product['name'] . ' | Alfi Kitchen';
$pageDescription = 'Lihat pilihan ' . $main_product['name'] . ' dari Alfi Kitchen. Tanyakan ketersediaan, harga, dan pengiriman langsung melalui WhatsApp.';

// Mengambil produk untuk ditampilkan di kolom
$stmt3 = $pdo->prepare("SELECT * FROM product_items WHERE product_id = ? ORDER BY id DESC");
$stmt3->execute([$id]);
$related_products = $stmt3->fetchAll();

// SEO: canonical, gambar share, dan structured data
$canonicalPath = 'produk/' . ensure_product_slug($pdo, $main_product);
if (!empty($main_product['image'])) {
    $pageImage = $main_product['image'];
} elseif (!empty($related_products[0]['image'])) {
    $pageImage = $related_products[0]['image'];
}

$itemListElements = [];
foreach ($related_products as $i => $rp) {
    $productLd = [
        '@type' => 'Product',
        'name' => $rp['name'],
        'description' => !empty($rp['description']) ? $rp['description'] : $main_product['name'] . ' dari Alfi Kitchen',
        'brand' => ['@type' => 'Brand', 'name' => 'Alfi Kitchen'],
        'offers' => [
            '@type' => 'Offer',
            'price' => (string) (int) $rp['price'],
            'priceCurrency' => 'IDR',
            'availability' => 'https://schema.org/InStock',
            'url' => site_url($canonicalPath),
        ],
    ];
    if (!empty($rp['image'])) {
        $productLd['image'] = preg_match('#^https?://#i', $rp['image']) ? $rp['image'] : site_url($rp['image']);
    }
    $itemListElements[] = ['@type' => 'ListItem', 'position' => $i + 1, 'item' => $productLd];
}

$jsonLd = [
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda', 'item' => site_url('')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $main_product['name'], 'item' => site_url($canonicalPath)],
            ],
        ],
        [
            '@type' => 'ItemList',
            'name' => $main_product['name'],
            'itemListElement' => $itemListElements,
        ],
    ],
];

include 'header.php';
?>
<style>
  /* Sembunyikan sisa header karena tidak terpakai */
  header { display: none !important; }
</style>

<!-- Logo Melayang Fixed -->
<a href="index.php" class="floating-logo" title="Kembali ke Beranda" style="position: fixed; z-index: 999;">
    <img src="<?= $logo_src ?>" alt="Alfi Kitchen Logo">
</a>

<main style="padding: 10px 6vw 40px; min-height: 70vh; max-width: 1200px; margin: 0 auto;">
  
  <!-- Judul dari halaman utama -->
  <h1 style="text-align:center; font-size: clamp(2rem, 4vw, 3rem); margin: 0 0 30px; color: var(--fg); font-weight: 800;">
      <?= htmlspecialchars($main_product['name']) ?>
  </h1>
  
  <!-- Baris Produk (Responsive, center, maksimal 3 per baris) -->
  <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 30px; margin-bottom: 60px;">
    
    <?php if(empty($related_products)): ?>
        <p style="color: var(--muted); text-align: center; width: 100%;">Belum ada jenis produk yang ditambahkan.</p>
    <?php endif; ?>

    <?php foreach($related_products as $rp): ?>
    <div style="width: calc(33.333% - 20px); min-width: 280px; max-width: 350px; background: var(--card); border: 1px solid var(--line); border-radius: 20px; padding: 24px; display: flex; flex-direction: column; box-shadow: 0 10px 30px rgba(0,0,0,0.03); transition: transform 0.2s;">
        
        <!-- Gambar Produk -->
        <?php if(!empty($rp['image'])): ?>
            <img src="<?= htmlspecialchars($rp['image']) ?>" alt="<?= htmlspecialchars($rp['name']) ?>" width="350" height="220" loading="lazy" decoding="async" style="width: 100%; height: 220px; object-fit: contain; border-radius: 12px; margin-bottom: 20px;">
        <?php else: ?>
            <div style="width: 100%; height: 220px; border-radius: 12px; background: var(--accent); margin-bottom: 20px;"></div>
        <?php endif; ?>
        
        <!-- Judul Kolom (Nama Produk) -->
        <h3 style="margin: 0 0 12px; font-size: 1.3rem; font-weight: 700; color: var(--fg); text-align: center;">
            <?= htmlspecialchars($rp['name']) ?>
        </h3>
        
        <!-- Deskripsi -->
        <p style="color: var(--muted); font-size: 0.95rem; line-height: 1.6; flex-grow: 1; margin: 0 0 20px; text-align: center;">
            <?= nl2br(htmlspecialchars($rp['description'] ?? 'Puding lezat dan lembut yang dirancang secara khusus untuk memberikan kepuasan maksimal di setiap gigitan.')) ?>
        </p>
        
        <!-- Harga di Bawah -->
        <div style="font-weight: 800; font-size: 1.25rem; color: var(--accent); margin-top: auto; border-top: 1px solid var(--line); padding-top: 16px; text-align: center;">
            Rp <?= number_format($rp['price'], 0, ',', '.') ?>
        </div>
        
    </div>
    <?php endforeach; ?>
    
  </div>

  <!-- Tombol kembali di paling bawah di luar kolom -->
  <div style="text-align: center;">
      <a href="index.php" style="display: inline-block; background: var(--accent); color: #fff; padding: 14px 36px; border-radius: 30px; text-decoration: none; font-weight: 600; font-size: 1.1rem; box-shadow: 0 4px 12px rgba(232, 147, 90, 0.3); transition: transform 0.2s;">
          Kembali ke Beranda
      </a>
  </div>
  
</main>
<?php include 'footer.php'; ?>
