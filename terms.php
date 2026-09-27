<?php include 'header.php'; ?>

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
        
        <p>Selamat datang di <strong>Alfi Kitchen</strong>. Dengan mengakses dan menggunakan situs web ini, Anda setuju untuk terikat oleh Syarat dan Ketentuan berikut. Jika Anda tidak menyetujui salah satu dari syarat ini, mohon untuk tidak menggunakan situs web kami.</p>

        <h3>1. Ketentuan Penggunaan</h3>
        <p>Anda setuju untuk menggunakan situs web ini hanya untuk tujuan yang sah, dan dengan cara yang tidak melanggar hak orang lain, atau membatasi maupun menghalangi penggunaan serta kenyamanan orang lain terhadap situs web ini.</p>

        <h3>2. Informasi Produk</h3>
        <p>Kami berusaha sebaik mungkin untuk menampilkan gambar, spesifikasi, dan detail produk seakurat mungkin. Namun, kami tidak menjamin bahwa deskripsi produk atau konten lain di situs ini sepenuhnya akurat, lengkap, dapat diandalkan, atau bebas dari kesalahan ketik. Warna produk mungkin sedikit berbeda tergantung pada resolusi layar perangkat Anda.</p>

        <h3>3. Harga dan Ketersediaan</h3>
        <ul>
            <li>Harga yang tercantum dapat berubah sewaktu-waktu tanpa pemberitahuan sebelumnya.</li>
            <li>Ketersediaan barang tidak selalu dijamin 100% pada saat Anda memesan. Jika barang habis, kami akan memberitahu Anda secepatnya.</li>
        </ul>

        <h3>4. Hak Kekayaan Intelektual</h3>
        <p>Semua konten yang terdapat di situs ini, termasuk namun tidak terbatas pada teks, grafis, logo, ikon tombol, gambar, dan klip video adalah milik <strong>Alfi Kitchen</strong> atau pemasok kontennya dan dilindungi oleh undang-undang hak cipta.</p>

        <h3>5. Penyangkalan (Disclaimer)</h3>
        <p>Situs web ini disediakan "sebagaimana adanya". Kami tidak memberikan jaminan apa pun, baik tersurat maupun tersirat, mengenai pengoperasian situs ini atau informasi, konten, maupun material yang termasuk di dalamnya.</p>

        <h3>6. Perubahan Syarat & Ketentuan</h3>
        <p>Kami memiliki hak penuh untuk mengubah, memodifikasi, menambah, atau menghapus bagian mana pun dari Syarat & Ketentuan ini kapan saja. Merupakan tanggung jawab Anda untuk secara berkala memeriksa halaman ini untuk melihat perubahan.</p>
        
        <div style="text-align:center; margin-top: 40px;">
            <a href="index.php" style="display:inline-block; padding: 12px 24px; background:var(--accent); color:white; text-decoration:none; border-radius:10px; font-weight:bold; box-shadow:0 4px 12px rgba(232,147,90,0.3);">Kembali ke Halaman Utama</a>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
