<?php
$file = $_GET['file'] ?? '';
$w = (int)($_GET['w'] ?? 150);

if (!$file || !file_exists(__DIR__ . '/' . $file)) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

$filePath = __DIR__ . '/' . $file;
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

$info = @getimagesize($filePath);
if (!$info) {
    header('Content-Type: ' . mime_content_type($filePath));
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

// Serve as WebP if supported, else original type
header('Content-Type: image/webp');
imagewebp($dst, null, 80);

imagedestroy($im);
imagedestroy($dst);
?>
