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
        
        <p>Selamat datang di <strong>Alfi Kitchen</strong>. Privasi Anda adalah prioritas kami. Halaman Kebijakan Privasi ini menjelaskan bagaimana kami mengumpulkan, menggunakan, dan melindungi informasi pribadi Anda saat Anda mengunjungi situs web kami.</p>

        <h3>1. Informasi yang Kami Kumpulkan</h3>
        <p>Kami hanya mengumpulkan informasi yang Anda berikan secara sukarela kepada kami, seperti:</p>
        <ul>
            <li>Nama dan detail kontak (jika Anda menghubungi kami secara langsung atau melalui form).</li>
            <li>Data analitik anonim (seperti jenis browser dan waktu kunjungan) untuk membantu kami meningkatkan pengalaman pengguna.</li>
        </ul>

        <h3>2. Bagaimana Kami Menggunakan Informasi Anda</h3>
        <p>Informasi yang kami kumpulkan digunakan secara eksklusif untuk:</p>
        <ul>
            <li>Meningkatkan kualitas layanan dan produk di Alfi Kitchen.</li>
            <li>Menjawab pertanyaan, masukan, atau keluhan dari pelanggan.</li>
            <li>Menganalisis performa website untuk memberikan pengalaman yang lebih baik.</li>
        </ul>

        <h3>3. Perlindungan Data</h3>
        <p>Kami menerapkan langkah-langke keamanan teknis dan organisasional yang wajar untuk melindungi informasi pribadi Anda dari akses, perubahan, pengungkapan, atau penghancuran yang tidak sah. Kami <strong>tidak pernah</strong> menjual atau menyewakan data pribadi Anda kepada pihak ketiga.</p>

        <h3>4. Tautan ke Situs Web Pihak Ketiga</h3>
        <p>Situs web kami mungkin berisi tautan ke situs web lain. Kami tidak bertanggung jawab atas praktik privasi atau konten dari situs-situs tersebut. Kami menyarankan Anda untuk membaca kebijakan privasi mereka sebelum memberikan informasi apa pun.</p>

        <h3>5. Perubahan pada Kebijakan Privasi</h3>
        <p>Alfi Kitchen berhak untuk memperbarui Kebijakan Privasi ini sewaktu-waktu tanpa pemberitahuan sebelumnya. Setiap perubahan akan diumumkan di halaman ini bersama dengan tanggal pembaruan di bagian atas halaman.</p>

        <h3>6. Hubungi Kami</h3>
        <p>Jika Anda memiliki pertanyaan lebih lanjut mengenai Kebijakan Privasi ini atau cara kami menangani data Anda, silakan hubungi kami melalui kontak resmi Alfi Kitchen.</p>
        
        <div style="text-align:center; margin-top: 40px;">
            <a href="index.php" style="display:inline-block; padding: 12px 24px; background:var(--accent); color:white; text-decoration:none; border-radius:10px; font-weight:bold; box-shadow:0 4px 12px rgba(232,147,90,0.3);">Kembali ke Halaman Utama</a>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
