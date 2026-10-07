<?php
/**
 * Skrip untuk mengoptimasi dan me-resize semua gambar di dalam folder uploads/
 */
$dir = __DIR__ . '/uploads/';
$max_width = 1200; // Maksimal lebar gambar, disesuaikan agar tidak pecah di layar besar
$quality_jpeg = 75; // Kualitas kompresi JPG (0-100)
$compression_png = 9; // Kompresi maksimal PNG (0-9)

$files = scandir($dir);
$count = 0;
$success = 0;

echo "<h2>Hasil Optimasi Gambar:</h2><ul>";

foreach ($files as $file) {
    if ($file === '.' || $file === '..' || is_dir($dir . $file)) continue;

    $filePath = $dir . $file;
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

    if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
        $info = @getimagesize($filePath);
        if (!$info) continue;

        $width = $info[0];
        $height = $info[1];
        $mime = $info['mime'];
        $count++;

        // Jika gambar sangat besar (lebih dari max_width), kita perkecil
        // Atau jika hanya mau dikompres ulang walau ukurannya kecil, kita proses juga.
        $new_width = $width;
        $new_height = $height;

        if ($width > $max_width) {
            $new_width = $max_width;
            $new_height = floor($height * ($max_width / $width));
        }

        $im = null;
        if ($mime == 'image/jpeg') {
            $im = @imagecreatefromjpeg($filePath);
        } elseif ($mime == 'image/png') {
            $im = @imagecreatefrompng($filePath);
        }

        if ($im) {
            $dst = imagecreatetruecolor((int)$new_width, (int)$new_height);

            // Handle transparency for PNG
            if ($mime == 'image/png') {
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
                $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
                imagefilledrectangle($dst, 0, 0, $new_width, $new_height, $transparent);
            }

            // Resize & copy
            imagecopyresampled($dst, $im, 0, 0, 0, 0, (int)$new_width, (int)$new_height, $width, $height);

            // Save back to original file
            $saved = false;
            if ($mime == 'image/jpeg') {
                $saved = imagejpeg($dst, $filePath, $quality_jpeg);
            } elseif ($mime == 'image/png') {
                $saved = imagepng($dst, $filePath, $compression_png);
            }

            if ($saved) {
                $status = ($width > $max_width) ? "Di-resize ke {$new_width}px & dikompres" : "Dikompres ulang";
                echo "<li>✅ <b>{$file}</b>: {$status}</li>";
                $success++;
            } else {
                echo "<li>❌ <b>{$file}</b>: Gagal disimpan</li>";
            }

            imagedestroy($im);
            imagedestroy($dst);
        } else {
            echo "<li>⚠️ <b>{$file}</b>: Gagal diproses (Library tidak mendukung)</li>";
        }
    }
}

echo "</ul><br><b>Selesai!</b> {$success} dari {$count} gambar berhasil dioptimasi.";
?>
