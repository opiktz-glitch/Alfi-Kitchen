<?php
/**
 * Penyaji gambar on-the-fly (WebP + resize) untuk Alfi Kitchen.
 *
 * Keamanan: parameter 'file' berasal dari user, jadi WAJIB divalidasi.
 * File yang boleh disajikan hanya:
 *  1. Gambar di dalam folder uploads/ di root project (mis. 'uploads/abc.jpg')
 *  2. Gambar statis di root project (mis. 'hero.jpg', 'apple-touch-icon.png')
 *
 * Semua path di luar itu (termasuk file .php seperti config.php) DITOLAK.
 */

require_once __DIR__ . '/admin/helpers/db_helper.php';

$file = $_GET['file'] ?? '';
$w = (int)($_GET['w'] ?? $_GET['amp;w'] ?? 150);
$qParam = isset($_GET['q']) ? (int)$_GET['q'] : (isset($_GET['amp;q']) ? (int)$_GET['amp;q'] : 68);
$q = ($qParam >= 30 && $qParam <= 90) ? $qParam : 68;

$filePath = resolve_thumbnail_path(is_string($file) ? $file : '');

if ($filePath === null) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

// Hanya layani file gambar (dicek lewat isi file, bukan sekadar ekstensi).
$info = @getimagesize($filePath);
if (!$info) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

// Cache headers
$lastModified = filemtime($filePath);
header('Cache-Control: max-age=2592000, public'); // 30 days
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 2592000) . ' GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');

if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $lastModified) {
    header('HTTP/1.0 304 Not Modified');
    exit;
}

// Tanpa extension GD, kita tidak bisa resize/convert.
// Fallback aman: layani file gambar aslinya apa adanya (tetap tervalidasi path-nya).
if (!function_exists('imagecreatetruecolor') || !function_exists('imagewebp')) {
    $originalMime = $info['mime'] ?? mime_content_type($filePath);
    header('Content-Type: ' . $originalMime);
    readfile($filePath);
    exit;
}

$origW = $info[0];
$origH = $info[1];
$mime = $info['mime'];

$h = (int)floor($origH * ($w / $origW));

if ($origW <= $w) {
    // Keep original dimensions if smaller, but still convert to WebP
    $w = $origW;
    $h = $origH;
}

$im = null;
if ($mime == 'image/jpeg') $im = @imagecreatefromjpeg($filePath);
elseif ($mime == 'image/png') $im = @imagecreatefrompng($filePath);
elseif ($mime == 'image/webp') $im = @imagecreatefromwebp($filePath);

if (!$im) {
    header('Content-Type: ' . mime_content_type($filePath));
    readfile($filePath);
    exit;
}

$dst = imagecreatetruecolor($w, $h);
if ($mime == 'image/png' || $mime == 'image/webp') {
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
    imagefilledrectangle($dst, 0, 0, $w, $h, $transparent);
}

imagecopyresampled($dst, $im, 0, 0, 0, 0, $w, $h, $origW, $origH);

// Sajikan sebagai WebP jika browser mendukung, atau fallback ke JPEG/PNG untuk browser lawas
$acceptsWebp = !isset($_SERVER['HTTP_ACCEPT']) || strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false || strpos($_SERVER['HTTP_ACCEPT'], '*/*') !== false;

if ($acceptsWebp && function_exists('imagewebp')) {
    header('Content-Type: image/webp');
    imagewebp($dst, null, $q);
} elseif ($mime === 'image/png' && function_exists('imagepng')) {
    header('Content-Type: image/png');
    imagepng($dst, null, 8);
} elseif ($mime === 'image/jpeg' && function_exists('imagejpeg')) {
    header('Content-Type: image/jpeg');
    imagejpeg($dst, null, min(75, $q + 4));
} else {
    header('Content-Type: image/webp');
    imagewebp($dst, null, $q);
}

imagedestroy($im);
imagedestroy($dst);
?>
