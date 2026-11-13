<?php
require 'auth_check.php';
require 'csrf_check.php';

$search = trim((string) ($_GET['search'] ?? ''));
if ($search !== '') {
    header('Location: products.php?' . http_build_query(['search' => $search]));
    exit;
}

header('Location: dashboard.php');
exit;
