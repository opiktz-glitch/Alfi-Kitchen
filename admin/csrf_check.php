<?php
/**
 * Verifikasi otomatis token CSRF untuk request POST.
 * Di-include di setiap halaman admin yang memiliki form POST.
 * Jika token tidak valid, hentikan eksekusi dengan HTTP 403.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('Token CSRF tidak valid.');
    }
}