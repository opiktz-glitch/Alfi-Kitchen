<?php
$src = __DIR__ . '/uploads/logo.png';
if (file_exists($src)) {
    $info = getimagesize($src);
    if ($info && $info[0] > 250) {
        $im = @imagecreatefrompng($src);
        if ($im) {
            $dst = imagecreatetruecolor(250, 250);
            
            // Pertahankan transparansi PNG
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            
            // Resize
            imagecopyresampled($dst, $im, 0, 0, 0, 0, 250, 250, $info[0], $info[1]);
            
            // Timpa gambar lama dengan kompresi maksimal (9 untuk PNG)
            imagepng($dst, $src, 9);
            
            imagedestroy($im);
            imagedestroy($dst);
            echo "Sukses: logo.png berhasil di-resize menjadi 250x250!";
        } else {
            echo "Gagal: Library GD (imagecreatefrompng) mungkin tidak aktif di hosting.";
        }
    } else {
        echo "Abaikan: Gambar sudah berukuran kecil atau format tidak dikenali.";
    }
} else {
    echo "Error: File uploads/logo.png tidak ditemukan.";
}
?>
