<?php
require_once __DIR__ . '/includes/bootstrap.php';

require_once __DIR__ . '/includes/seo_helpers.php';

// Tampilkan halaman 404 yang benar (status HTTP 404 + noindex) agar tidak dianggap soft-404
function product_not_found() {
    global $pdo; // dipakai oleh footer.php (scope fungsi tidak otomatis melihat $pdo)
    http_response_code(404);
    header('X-Robots-Tag: noindex, nofollow');
    $pageTitle = 'Produk Tidak Ditemukan | Alfi Kitchen';
    $pageDescription = 'Produk yang Anda cari tidak ditemukan. Lihat pilihan puding, dessert, dan salad buah dari Alfi Kitchen.';
    $noindex = true;
    include 'header.php';
    // Logo melayang (konsisten dengan halaman produk) agar pengunjung 404 mudah kembali ke beranda
    echo '<a href="index.php" class="floating-logo" title="Kembali ke Beranda" style="position: fixed; z-index: 999;"><img src="' . htmlspecialchars($logo_src) . '" alt="Alfi Kitchen Logo"></a>';
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

// Mengambil produk untuk ditampilkan di kolom (dipindah ke atas agar bisa dipakai description)
$stmt3 = $pdo->prepare("SELECT * FROM product_items WHERE product_id = ? ORDER BY id DESC");
$stmt3->execute([$id]);
$related_products = $stmt3->fetchAll();

$wa_footer_stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'whatsapp_number'");
$wa_footer_stmt->execute();
$wa_footer_row = $wa_footer_stmt->fetch();
$wa_footer_number = $wa_footer_row ? $wa_footer_row['setting_value'] : '';

$pageStylesheet = 'assets/css/product.css';
$pageDescription = build_product_description($main_product['name'], $related_products);

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
<!-- Logo Melayang Fixed -->
<a href="index.php" class="floating-logo" title="Kembali ke Beranda" style="position: fixed; z-index: 999;">
    <img src="<?= $logo_src ?>" alt="Alfi Kitchen Logo">
</a>

<main class="product-page">
  
  <!-- Judul dari halaman utama -->
  <h1 class="product-page-title">
      <?= htmlspecialchars($main_product['name']) ?>
  </h1>
  
  <!-- Baris Produk (Responsive, center, maksimal 3 per baris) -->
  <div class="product-variants">
    
    <?php if(empty($related_products)): ?>
        <p class="product-empty">Belum ada jenis produk yang ditambahkan.</p>
    <?php endif; ?>

    <?php foreach($related_products as $rp): ?>
    <article class="product-variant">
        
        <!-- Gambar Produk -->
        <?php if(!empty($rp['image'])): ?>
            <img class="product-variant-image" src="<?= htmlspecialchars($rp['image']) ?>" alt="<?= htmlspecialchars($rp['name']) ?>" width="350" height="220" loading="lazy" decoding="async">
        <?php else: ?>
            <div class="product-variant-image product-variant-image-placeholder" aria-hidden="true"></div>
        <?php endif; ?>
        
        <!-- Judul Kolom (Nama Produk) -->
        <h2 class="product-variant-title">
            <?= htmlspecialchars($rp['name']) ?>
        </h2>
        
        <!-- Deskripsi -->
        <p class="product-variant-description">
            <?= nl2br(htmlspecialchars($rp['description'] ?? 'Puding lezat dan lembut yang dirancang secara khusus untuk memberikan kepuasan maksimal di setiap gigitan.')) ?>
        </p>
        
        <!-- Harga di Bawah -->
        <p class="product-variant-price">
            Rp <?= number_format($rp['price'], 0, ',', '.') ?>
        </p>

        <?php if ($wa_footer_number): ?>
            <?php
            $waMessage = sprintf(
                'Halo Alfi Kitchen, saya ingin memesan %s dari kategori %s.',
                trim((string) $rp['name']),
                trim((string) $main_product['name'])
            );
            $waProductUrl = whatsapp_url($wa_footer_number, $waMessage);
            ?>
            <a class="product-order-button" href="<?= htmlspecialchars($waProductUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">
                Pesan varian ini via WhatsApp
            </a>
        <?php endif; ?>
        
    </article>
    <?php endforeach; ?>
    
  </div>

  <!-- Tombol kembali di paling bawah di luar kolom -->
  <div class="product-back-link-wrap">
      <a href="index.php" class="product-back-link">
          Kembali ke Beranda
      </a>
  </div>
  
</main>
<?php include 'footer.php'; ?>
