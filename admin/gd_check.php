<?php
// Cek sekali: apakah GD aktif di server? (buka manual di browser, hapus setelah cek)
// URL: /admin/gd_check.php  (login tidak diperlukan, hanya info GD; hapus file setelah cek)
header('Content-Type: text/plain; charset=UTF-8');
echo 'GD loaded: ' . (extension_loaded('gd') ? 'YES' : 'NO') . PHP_EOL;
if (function_exists('gd_info')) {
    foreach (gd_info() as $k => $v) {
        echo $k . ': ' . (is_bool($v) ? ($v ? '1' : '0') : $v) . PHP_EOL;
    }
} else {
    echo "gd_info(): NOT AVAILABLE" . PHP_EOL;
}
echo 'imagecreatefromstring: ' . (function_exists('imagecreatefromstring') ? 'YES' : 'NO') . PHP_EOL;
echo 'imagejpeg: ' . (function_exists('imagejpeg') ? 'YES' : 'NO') . PHP_EOL;
echo 'imagepng: ' . (function_exists('imagepng') ? 'YES' : 'NO') . PHP_EOL;
echo 'imagewebp: ' . (function_exists('imagewebp') ? 'YES' : 'NO') . PHP_EOL;
