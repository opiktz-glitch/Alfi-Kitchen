<?php
require 'seo_helpers.php';

header('Content-Type: text/plain; charset=UTF-8');
?>
User-agent: *
Disallow: /admin/
Disallow: /login.php
Disallow: /logs/
Disallow: /database/

Sitemap: <?= site_url('sitemap.xml') ?>

