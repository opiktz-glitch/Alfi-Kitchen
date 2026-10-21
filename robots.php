<?php
require_once __DIR__ . '/includes/seo_helpers.php';

// Samakan dengan versi statis agar crawler dapat Sitemap walau mod_rewrite mati.
// (Isi Sitemap tetap memakai domain resmi via site_url().)
// Catatan: Tidak menggunakan Disallow di robots.txt. Pengindeksan area admin
// dan sistem dicegah melalui header HTTP X-Robots-Tag di .htaccess
// (pendekatan modern yang direkomendasikan Google).
header('Content-Type: text/plain; charset=UTF-8');
?>
User-agent: *

Sitemap: <?= site_url('sitemap.xml') ?>

