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
    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0777, true) && !is_dir($uploadDirectory)) {
        return ['path' => null, 'error' => 'Folder upload tidak dapat dibuat.'];
    }

    if (!is_writable($uploadDirectory)) {
        @chmod($uploadDirectory, 0777);
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

    return ['path' => 'uploads/' . $filename, 'error' => null];
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