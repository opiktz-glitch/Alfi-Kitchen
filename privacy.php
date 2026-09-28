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
    <h1 class="page-title">Kebijakan Privasi</h1>
    
    <div class="content-box">
        <p><strong>Terakhir Diperbarui:</strong> <?php echo date("d F Y"); ?></p>
        
        <p><strong>Alfi Kitchen</strong> menggunakan situs ini sebagai katalog produk. Pemesanan dan komunikasi pelanggan dilakukan melalui WhatsApp, bukan melalui formulir checkout di situs.</p>

        <h3>1. Informasi yang Kami Kumpulkan</h3>
        <p>Saat menghubungi dan memesan melalui WhatsApp, Anda dapat memberikan informasi yang diperlukan untuk memproses pesanan, seperti:</p>
        <ul>
            <li>Nama dan nomor kontak.</li>
            <li>Produk, jumlah pesanan, dan alamat tujuan pengiriman door-to-door.</li>
            <li>Informasi lain yang Anda sertakan dalam percakapan pemesanan.</li>
        </ul>

        <h3>2. Bagaimana Informasi Digunakan</h3>
        <p>Informasi yang Anda sampaikan digunakan untuk:</p>
        <ul>
            <li>Menjawab pertanyaan dan mengonfirmasi detail pesanan.</li>
            <li>Memeriksa ketersediaan produk serta menyepakati biaya dan jadwal pengiriman.</li>
            <li>Mengantarkan pesanan ke alamat tujuan yang disepakati.</li>
        </ul>

        <h3>3. Penggunaan WhatsApp</h3>
        <p>Pesan pemesanan dikirim melalui WhatsApp, layanan yang dikelola pihak ketiga. Penggunaan dan pemrosesan data oleh WhatsApp mengikuti kebijakan privasi mereka. Hindari mengirimkan informasi yang tidak diperlukan untuk pesanan Anda.</p>

        <h3>4. Keamanan</h3>
        <p>Kami membatasi penggunaan informasi pesanan untuk komunikasi dan pemenuhan pesanan. Untuk keamanan operasional, sistem juga mencatat aktivitas panel admin, termasuk alamat IP admin yang mengaksesnya.</p>

        <h3>5. Tautan dan Perubahan Kebijakan</h3>
        <p>Tautan WhatsApp akan membawa Anda ke layanan pihak ketiga. Kebijakan ini dapat diperbarui jika cara layanan atau pengelolaan informasi berubah; tanggal pembaruan tercantum di bagian atas halaman.</p>

        <h3>6. Hubungi Kami</h3>
        <p>Untuk pertanyaan tentang privasi atau penggunaan informasi pesanan, hubungi Alfi Kitchen melalui WhatsApp yang tersedia di situs ini.</p>
        
        <div style="text-align:center; margin-top: 40px;">
            <a href="index.php" style="display:inline-block; padding: 12px 24px; background:var(--accent); color:white; text-decoration:none; border-radius:10px; font-weight:bold; box-shadow:0 4px 12px rgba(232,147,90,0.3);">Kembali ke Halaman Utama</a>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
