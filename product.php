<?php
require 'config.php';

$id = $_GET['id'] ?? null;
if(!$id) {
    die("Produk tidak ditemukan.");
}

// Mengambil data item yang diklik dari halaman utama untuk dijadikan Judul
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$main_product = $stmt->fetch();

if(!$main_product) {
    die("Produk tidak ditemukan.");
}

$pageTitle = $main_product['name'] . ' | Alfi Kitchen';
$pageDescription = 'Lihat pilihan ' . $main_product['name'] . ' dari Alfi Kitchen. Tanyakan ketersediaan, harga, dan pengiriman langsung melalui WhatsApp.';

// Mengambil produk untuk ditampilkan di kolom
$stmt3 = $pdo->prepare("SELECT * FROM product_items WHERE product_id = ? ORDER BY id DESC");
$stmt3->execute([$id]);
$related_products = $stmt3->fetchAll();

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

<div style="padding: 10px 6vw 40px; min-height: 70vh; max-width: 1200px; margin: 0 auto;">
  
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
            <img src="<?= htmlspecialchars($rp['image']) ?>" alt="<?= htmlspecialchars($rp['name']) ?>" style="width: 100%; height: 220px; object-fit: contain; border-radius: 12px; margin-bottom: 20px;">
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
  
</div>
<?php include 'footer.php'; ?>
