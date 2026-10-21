<?php
http_response_code(404);
header('X-Robots-Tag: noindex, nofollow');

$pageTitle = '404 - Halaman Tidak Ditemukan | Alfi Kitchen';
$pageDescription = 'Maaf, halaman yang Anda cari tidak dapat ditemukan di Alfi Kitchen.';

include 'header.php';
?>

<style>
    header {
        position: relative !important;
        top: 0 !important;
        background: #fff8ef !important;
        padding-top: 20px !important;
        padding-bottom: 20px !important;
    }
    .error-wrapper {
        max-width: 650px;
        margin: 50px auto 90px;
        padding: 0 20px;
        text-align: center;
    }
    .error-card {
        background: white;
        padding: 50px 30px;
        border-radius: 24px;
        box-shadow: 0 15px 35px rgba(232, 147, 90, 0.08);
        border: 1px solid var(--line, #f0e6dc);
    }
    .error-badge {
        display: inline-block;
        font-size: 3.5rem;
        margin-bottom: 10px;
        animation: floatEmoji 3s ease-in-out infinite;
    }
    .error-code {
        font-size: 4rem;
        font-weight: 900;
        line-height: 1;
        margin: 0 0 15px;
        color: var(--primary, #e8935a);
        letter-spacing: -2px;
    }
    .error-title {
        font-size: 1.5rem;
        font-weight: 800;
        margin-bottom: 12px;
        color: var(--fg, #2c2523);
    }
    .error-desc {
        color: #7a6e69;
        font-size: 1rem;
        line-height: 1.6;
        margin: 0 auto 30px;
        max-width: 460px;
    }
    .error-actions {
        display: flex;
        justify-content: center;
        gap: 12px;
        flex-wrap: wrap;
    }
    .btn-home {
        background: var(--primary, #e8935a);
        color: white;
        padding: 12px 24px;
        border-radius: 999px;
        font-weight: 700;
        text-decoration: none;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .btn-home:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(232, 147, 90, 0.3);
    }
    .btn-wa {
        background: #25d366;
        color: white;
        padding: 12px 24px;
        border-radius: 999px;
        font-weight: 700;
        text-decoration: none;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .btn-wa:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(37, 211, 102, 0.3);
    }
    @keyframes floatEmoji {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-8px); }
    }
</style>

<main class="error-wrapper">
    <div class="error-card">
        <div class="error-badge">🍮🔍</div>
        <h1 class="error-code">404</h1>
        <h2 class="error-title">Ups! Halaman Tidak Ditemukan</h2>
        <p class="error-desc">
            Sepertinya menu atau halaman yang Anda cari sudah dipindahkan, diganti namanya, atau sedang tidak tersedia.
        </p>
        <div class="error-actions">
            <a href="<?= site_url('') ?>" class="btn-home">🏠 Kembali ke Beranda</a>
            <a href="<?= htmlspecialchars(whatsapp_url($whatsapp_number ?? null, 'Halo Alfi Kitchen, saya ingin menanyakan menu...')) ?>" target="_blank" rel="noopener noreferrer" class="btn-wa">💬 Tanya via WhatsApp</a>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>
