<?php
function store_uploaded_image($file, $prefix = 'image', $required = false, $allowedMimeTypes = null, $fixedFilename = null) {
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $required
            ? ['path' => null, 'error' => 'Pilih file gambar untuk diunggah.']
            : ['path' => null, 'error' => null];
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['path' => null, 'error' => 'File gagal diunggah. Silakan coba lagi.'];
    }

    $temporaryPath = $file['tmp_name'] ?? '';
    $fileSize = is_uploaded_file($temporaryPath) ? filesize($temporaryPath) : false;
    if ($fileSize === false || $fileSize < 1 || $fileSize > 5 * 1024 * 1024) {
        return ['path' => null, 'error' => 'Ukuran gambar harus lebih dari 0 dan maksimal 5 MB.'];
    }

    $extensions = [
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/pjpeg' => 'jpg',
        'image/png' => 'png',
        'image/x-png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];
    $mimeTypes = $allowedMimeTypes ?? array_keys($extensions);

    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $fileInfo->file($temporaryPath);
    if (!$mimeType || !isset($extensions[$mimeType])) {
        $mimeType = is_array($file['type'] ?? null) ? '' : (string) ($file['type'] ?? '');
    }

    $imageInfo = @getimagesize($temporaryPath);
    if (
        !isset($extensions[$mimeType])
        || !in_array($mimeType, $mimeTypes, true)
        || !$imageInfo
        || !in_array(($imageInfo['mime'] ?? ''), $mimeTypes, true)
        || ($imageInfo[0] * $imageInfo[1]) > 40000000
    ) {
        return ['path' => null, 'error' => 'File harus berupa gambar JPEG, PNG, GIF, atau WEBP yang valid.'];
    }

    $extension = $extensions[$mimeType];
    $uploadDirectory = dirname(__DIR__) . '/uploads';
    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
        return ['path' => null, 'error' => 'Folder upload tidak dapat dibuat.'];
    }

    @chmod($uploadDirectory, 0755);
    if (!is_writable($uploadDirectory)) {
        return ['path' => null, 'error' => 'Folder uploads tidak dapat ditulis. Atur izin folder menjadi 0755 di hosting.'];
    }

    $prefix = preg_replace('/[^a-z0-9_-]/i', '', $prefix);
    $filename = $fixedFilename ?? (($prefix ? $prefix . '_' : '') . bin2hex(random_bytes(16)) . '.' . $extension);
    if (!preg_match('/^[a-z0-9_-]+\.' . preg_quote($extension, '/') . '$/i', $filename)) {
        return ['path' => null, 'error' => 'Nama file tujuan tidak valid.'];
    }

    $destination = $uploadDirectory . '/' . $filename;
    if (file_exists($destination)) {
        @unlink($destination);
    }

    if (!@move_uploaded_file($temporaryPath, $destination)) {
        return ['path' => null, 'error' => 'Gambar gagal disimpan di server.'];
    }

    @chmod($destination, 0644);

    // SEO/Performa: kompres otomatis gambar baru (hemat ~70% tanpa beda visual).
    // Aman: bila GD tidak tersedia / gambar kecil / GIF animasi, file asli dipakai.
    compress_uploaded_image($destination, $mimeType, $prefix);

    return ['path' => 'uploads/' . $filename, 'error' => null];
}

/**
 * Kompres gambar hasil upload di tempat (in-place).
 *
 * Aturan:
 * - JPEG (hero/produk) : max 1600px, quality 80 (~100-200KB)
 * - PNG logo           : max 512px + optimize (transparansi dipertahankan)
 * - PNG lain           : max 1000px + optimize
 * - GIF / WebP         : max 1600px saja, tanpa kompres ulang (jaga animasi)
 * - Gambar sudah kecil : dilewati (tidak dibesarkan / dikompres ulang)
 *
 * Selalu aman: gagal di titik mana pun = file asli tetap dipakai, tanpa error user.
 */
function compress_uploaded_image($destination, $mimeType, $prefix = '') {
    // 1. GD wajib ada (XAMPP lokal belum aktif -> lewati diam-diam, hosting aktif -> jalan)
    if (!extension_loaded('gd') || !function_exists('imagecreatefromstring')) {
        return;
    }

    $data = @file_get_contents($destination);
    if ($data === false || $data === '') {
        return;
    }

    // GIF: jangan sentuh sama sekali (risiko animasi rusak)
    if ($mimeType === 'image/gif') {
        return;
    }

    $src = @imagecreatefromstring($data);
    if ($src === false) {
        return; // bukan gambar valid -> biarkan validasi lain yang menolak
    }

    $width = imagesx($src);
    $height = imagesy($src);
    if ($width < 1 || $height < 1) {
        imagedestroy($src);
        return;
    }

    // 2. Tentukan batas dimensi
    $isLogo = (stripos((string) $prefix, 'logo') !== false)
        || (is_string($destination) && stripos(basename($destination), 'logo') === 0);
    if ($mimeType === 'image/png' && $isLogo) {
        $maxDim = 512;
    } elseif ($mimeType === 'image/png') {
        $maxDim = 1000;
    } else {
        $maxDim = 1600; // JPEG & WebP
    }

    // Sudah kecil -> tidak perlu apa-apa
    if ($width <= $maxDim && $height <= $maxDim) {
        // JPEG besar tetap layak dikompres ulang ringan (q80) bila >300KB
        if ($mimeType !== 'image/jpeg' && $mimeType !== 'image/pjpeg' && $mimeType !== 'image/jpg') {
            imagedestroy($src);
            return;
        }
        if (@filesize($destination) === false || @filesize($destination) <= 300 * 1024) {
            imagedestroy($src);
            return;
        }
    }

    // 3. Resize proporsional bila melebihi batas
    $img = $src;
    $scale = min($maxDim / $width, $maxDim / $height);
    if ($scale < 1) {
        $newW = (int) round($width * $scale);
        $newH = (int) round($height * $scale);
        $canvas = imagecreatetruecolor($newW, $newH);
        if ($canvas === false) {
            imagedestroy($src);
            return;
        }
        // Pertahankan transparansi PNG/WebP agar tidak jadi hitam
        if ($mimeType === 'image/png' || $mimeType === 'image/webp' || $mimeType === 'image/x-png') {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefilledrectangle($canvas, 0, 0, $newW, $newH, $transparent);
        }
        if (!imagecopyresampled($canvas, $src, 0, 0, 0, 0, $newW, $newH, $width, $height)) {
            imagedestroy($canvas);
            imagedestroy($src);
            return;
        }
        imagedestroy($src);
        $img = $canvas;
    }

    // 4. Simpan ke file sementara dulu, timpa hanya bila berhasil + lebih kecil
    $tmp = $destination . '.cmp';
    $ok = false;
    if ($mimeType === 'image/jpeg' || $mimeType === 'image/pjpeg' || $mimeType === 'image/jpg') {
        // progressive JPEG: tampil bertahap, LCP terasa lebih cepat
        if (function_exists('imageinterlace')) {
            imageinterlace($img, true);
        }
        $ok = @imagejpeg($img, $tmp, 80);
    } elseif ($mimeType === 'image/png' || $mimeType === 'image/x-png') {
        $ok = @imagepng($img, $tmp, 6);
    } elseif ($mimeType === 'image/webp') {
        $ok = function_exists('imagewebp') ? @imagewebp($img, $tmp, 80) : false;
    }
    imagedestroy($img);

    if ($ok && is_file($tmp) && filesize($tmp) > 0 && filesize($tmp) < filesize($destination)) {
        @rename($tmp, $destination);
        @chmod($destination, 0644);
    } else {
        @unlink($tmp); // hasil lebih besar / gagal -> pakai asli
    }
}

function delete_uploaded_image($relativePath) {
    if (!is_string($relativePath) || strpos($relativePath, 'uploads/') !== 0) {
        return;
    }

    $uploadDirectory = realpath(dirname(__DIR__) . '/uploads');
    $target = realpath(dirname(__DIR__) . '/' . $relativePath);
    if ($uploadDirectory && $target && strpos($target, $uploadDirectory . DIRECTORY_SEPARATOR) === 0 && is_file($target)) {
        unlink($target);
    }
}