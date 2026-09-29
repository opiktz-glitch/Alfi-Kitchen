<?php
require 'seo_helpers.php';

// Samakan dengan versi statis agar crawler dapat Sitemap walau mod_rewrite mati.
// (Isi Sitemap tetap memakai domain resmi via site_url().)
header('Content-Type: text/plain; charset=UTF-8');
?>
User-agent: *
Disallow: /admin/
Disallow: /login.php
Disallow: /logs/
Disallow: /database/

Sitemap: <?= site_url('sitemap.xml') ?>

