<?php
$pageTitle = 'Syarat & Ketentuan Pemesanan | Alfi Kitchen';
$pageDescription = 'Informasi pemesanan Alfi Kitchen melalui WhatsApp, konfirmasi produk, dan pengiriman langsung ke alamat tujuan.';
include 'header.php';
?>

<!-- Override Header positioning untuk halaman non-hero -->
<style>
    header {
        position: relative !important;
        top: 0 !important;
        background: #fff8ef !important;
        padding-top: 20px !important;
        padding-bottom: 20px !important;
    }
    .page-wrapper {
        max-width: 800px;
        margin: 40px auto 80px;
        padding: 0 20px;
    }
    .page-title {
        font-size: 2rem;
        font-weight: 800;
        margin-bottom: 30px;
        color: var(--fg);
        text-align: center;
    }
    .content-box {
        background: white;
        padding: 40px;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(232, 147, 90, 0.05);
        border: 1px solid var(--line);
        line-height: 1.8;
        color: var(--fg);
    }
    .content-box h3 {
        color: var(--accent);
        margin-top: 30px;
        margin-bottom: 10px;
        font-size: 1.3rem;
    }
    .content-box p {
        color: var(--muted);
        margin-bottom: 15px;
    }
    .content-box ul {
        color: var(--muted);
        margin-bottom: 20px;
        padding-left: 20px;
    }
    .content-box li {
        margin-bottom: 8px;
    }
</style>

<div class="page-wrapper">
    <h1 class="page-title">Syarat & Ketentuan Umum</h1>
    
    <div class="content-box">
        <p><strong>Terakhir Diperbarui:</strong> <?php echo date("d F Y"); ?></p>
        
        <p>Situs <strong>Alfi Kitchen</strong> menampilkan katalog produk. Dengan menggunakan situs ini atau memesan melalui WhatsApp, Anda menyetujui ketentuan berikut.</p>

        <h3>1. Ketentuan Penggunaan</h3>
        <p>Gunakan situs ini untuk melihat informasi produk dan menghubungi Alfi Kitchen secara wajar. Pemesanan hanya diterima melalui WhatsApp; situs ini tidak menyediakan checkout atau pembayaran online.</p>

        <h3>2. Informasi Produk</h3>
        <p>Foto dan deskripsi produk ditampilkan sebagai informasi katalog. Tampilan dapat sedikit berbeda dari produk sebenarnya. Jika ada pertanyaan tentang komposisi atau detail produk, konfirmasikan melalui WhatsApp sebelum memesan.</p>

        <h3>3. Pemesanan dan Pengiriman</h3>
        <ul>
            <li>Kirim pilihan produk dan jumlah pesanan melalui WhatsApp untuk konfirmasi ketersediaan dan harga.</li>
            <li>Pengiriman dilakukan langsung ke alamat tujuan (door-to-door) setelah alamat dan pesanan disepakati.</li>
            <li>Jangkauan, jadwal, biaya pengiriman, dan metode pembayaran dikonfirmasi melalui WhatsApp sebelum pesanan disepakati.</li>
        </ul>

        <h3>4. Hak Kekayaan Intelektual</h3>
        <p>Semua konten yang terdapat di situs ini, termasuk namun tidak terbatas pada teks, grafis, logo, ikon tombol, gambar, dan klip video adalah milik <strong>Alfi Kitchen</strong> atau pemasok kontennya dan dilindungi oleh undang-undang hak cipta.</p>

        <h3>5. Perubahan Informasi</h3>
        <p>Informasi katalog, harga, dan ketersediaan dapat berubah. Konfirmasi terbaru untuk pesanan berlaku berdasarkan kesepakatan melalui WhatsApp.</p>

        <h3>6. Perubahan Syarat & Ketentuan</h3>
        <p>Alfi Kitchen dapat memperbarui syarat ini jika layanan berubah. Tanggal pembaruan terbaru tercantum di bagian atas halaman. Jika Anda memiliki pertanyaan, silakan hubungi kami melalui WhatsApp.</p>
        
        <div style="text-align:center; margin-top: 40px;">
            <a href="index.php" style="display:inline-block; padding: 12px 24px; background:var(--accent); color:white; text-decoration:none; border-radius:10px; font-weight:bold; box-shadow:0 4px 12px rgba(232,147,90,0.3);">Kembali ke Halaman Utama</a>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
