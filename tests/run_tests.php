<?php
/**
 * Test runner sederhana untuk refactoring Alfi Kitchen Admin.
 * Jalankan dengan: C:\xampp\php\php.exe tests\run_tests.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$passed = 0;
$failed = 0;
$errors = [];

function test(string $name, callable $fn): void {
    global $passed, $failed, $errors;
    try {
        $fn();
        echo "  ✓ $name\n";
        $passed++;
    } catch (\Throwable $e) {
        echo "  ✗ $name\n";
        echo "    " . $e->getMessage() . "\n";
        $failed++;
        $errors[] = "$name: " . $e->getMessage();
    }
}

function assertFileExists(string $file, string $msg = ''): void {
    if (!is_file($file)) {
        throw new \Exception($msg ?: "File tidak ditemukan: $file");
    }
}

function assertStringContains(string $haystack, string $needle, string $msg = ''): void {
    if (strpos($haystack, $needle) === false) {
        throw new \Exception($msg ?: "Tidak menemukan '$needle' dalam teks");
    }
}

function assertStringNotContains(string $haystack, string $needle, string $msg = ''): void {
    if (strpos($haystack, $needle) !== false) {
        throw new \Exception($msg ?: "Tidak boleh menemukan '$needle' dalam teks");
    }
}

function phpLint(string $file): array {
    $output = [];
    $returnVar = 0;
    exec('"C:\xampp\php\php.exe" -l "' . $file . '" 2>&1', $output, $returnVar);
    return ['output' => implode("\n", $output), 'passed' => $returnVar === 0];
}

echo "========================================\n";
echo "  Alfi Kitchen Admin Refactor Tests\n";
echo "========================================\n\n";

// ============================================================================
// Langkah 1: CSS admin terpusat
// ============================================================================
echo "--- Langkah 1: Admin CSS Terpusat ---\n";

$cssFile = __DIR__ . '/../admin/assets/css/admin.css';
$css = file_get_contents($cssFile);

test('admin.css file exists', function () use ($cssFile) {
    assertFileExists($cssFile, 'File admin.css harus ada');
});

test('admin.css contains admin-header class', function () use ($css) {
    assertStringContains($css, '.admin-header', 'CSS harus memiliki .admin-header');
});

test('admin.css contains admin-container class', function () use ($css) {
    assertStringContains($css, '.admin-container', 'CSS harus memiliki .admin-container');
});

test('admin.css contains form-card class', function () use ($css) {
    assertStringContains($css, '.form-card', 'CSS harus memiliki .form-card');
});

test('admin.css contains form-group class', function () use ($css) {
    assertStringContains($css, '.form-group', 'CSS harus memiliki .form-group');
});

test('admin.css contains form-control class', function () use ($css) {
    assertStringContains($css, '.form-control', 'CSS harus memiliki .form-control');
});

test('admin.css contains btn-primary class', function () use ($css) {
    assertStringContains($css, '.btn-primary', 'CSS harus memiliki .btn-primary');
});

test('admin.css contains btn-cancel class', function () use ($css) {
    assertStringContains($css, '.btn-cancel', 'CSS harus memiliki .btn-cancel');
});

test('admin.css contains btn-danger class', function () use ($css) {
    assertStringContains($css, '.btn-danger', 'CSS harus memiliki .btn-danger');
});

test('admin.css contains btn-secondary class', function () use ($css) {
    assertStringContains($css, '.btn-secondary', 'CSS harus memiliki .btn-secondary');
});

test('admin.css contains alert class', function () use ($css) {
    assertStringContains($css, '.alert', 'CSS harus memiliki .alert');
});

test('admin.css contains alert-error class', function () use ($css) {
    assertStringContains($css, '.alert-error', 'CSS harus memiliki .alert-error');
});

test('admin.css contains alert-success class', function () use ($css) {
    assertStringContains($css, '.alert-success', 'CSS harus memiliki .alert-success');
});

test('admin.css contains table-responsive class', function () use ($css) {
    assertStringContains($css, '.table-responsive', 'CSS harus memiliki .table-responsive');
});

test('admin.css contains header-action class', function () use ($css) {
    assertStringContains($css, '.header-action', 'CSS harus memiliki .header-action');
});

test('admin.css contains thumb class', function () use ($css) {
    assertStringContains($css, '.thumb', 'CSS harus memiliki .thumb');
});

test('admin.css contains preview-img class', function () use ($css) {
    assertStringContains($css, '.preview-img', 'CSS harus memiliki .preview-img');
});

test('admin.css contains :root variables', function () use ($css) {
    assertStringContains($css, ':root', 'CSS harus memiliki variabel :root');
});

echo "\n";

// ============================================================================
// Langkah 2: auth_check.php
// ============================================================================
echo "--- Langkah 2: auth_check.php ---\n";

$authFile = __DIR__ . '/../admin/auth_check.php';
$authContent = file_get_contents($authFile);

test('auth_check.php file exists', function () use ($authFile) {
    assertFileExists($authFile, 'File auth_check.php harus ada');
});

test('auth_check.php contains session check', function () use ($authContent) {
    assertStringContains($authContent, "admin_logged_in", 'auth_check harus memeriksa admin_logged_in');
});

test('auth_check.php redirects to login', function () use ($authContent) {
    assertStringContains($authContent, "../login.php", 'auth_check harus redirect ke ../login.php');
});

test('auth_check.php requires auth.php', function () use ($authContent) {
    assertStringContains($authContent, "auth.php", 'auth_check harus require auth.php');
});

test('auth_check.php has valid PHP syntax', function () use ($authFile) {
    $result = phpLint($authFile);
    if (!$result['passed']) {
        throw new \Exception("Syntax error: " . $result['output']);
    }
});

echo "\n";

// ============================================================================
// Langkah 3: csrf_check.php
// ============================================================================
echo "--- Langkah 3: csrf_check.php ---\n";

$csrfFile = __DIR__ . '/../admin/csrf_check.php';
$csrfContent = file_get_contents($csrfFile);

test('csrf_check.php file exists', function () use ($csrfFile) {
    assertFileExists($csrfFile, 'File csrf_check.php harus ada');
});

test('csrf_check.php verifies POST request', function () use ($csrfContent) {
    assertStringContains($csrfContent, "REQUEST_METHOD", 'csrf_check harus memeriksa REQUEST_METHOD');
});

test('csrf_check.php uses verify_csrf_token', function () use ($csrfContent) {
    assertStringContains($csrfContent, 'verify_csrf_token', 'csrf_check harus menggunakan verify_csrf_token');
});

test('csrf_check.php checks csrf_token', function () use ($csrfContent) {
    assertStringContains($csrfContent, 'csrf_token', 'csrf_check harus memeriksa csrf_token');
});

test('csrf_check.php returns 403 on invalid', function () use ($csrfContent) {
    assertStringContains($csrfContent, '403', 'csrf_check harus mengembalikan HTTP 403');
});

test('csrf_check.php has valid PHP syntax', function () use ($csrfFile) {
    $result = phpLint($csrfFile);
    if (!$result['passed']) {
        throw new \Exception("Syntax error: " . $result['output']);
    }
});

echo "\n";

// ============================================================================
// Admin files use auth_check.php
// ============================================================================
echo "--- Admin files use auth_check.php ---\n";

$authAdminFiles = [
    'add_product.php',
    'edit_product.php',
    'manage_hero.php',
    'manage_items.php',
    'settings.php',
    'activity_log.php',
    'edit_item.php',
    'index.php',
    'export_db.php',
    'export_images.php',
];

foreach ($authAdminFiles as $file) {
    $path = __DIR__ . '/../admin/' . $file;
    $content = file_get_contents($path);

    test("$file uses auth_check.php", function () use ($path, $content, $file) {
        assertFileExists($path, "File $file harus ada");
        assertStringContains($content, "require 'auth_check.php'", "$file harus menggunakan auth_check.php");
        assertStringNotContains($content, "if (!isset(\$_SESSION['admin_logged_in'])", "$file tidak boleh punya blok login duplikat");
    });

    test("$file has valid PHP syntax", function () use ($path) {
        $result = phpLint($path);
        if (!$result['passed']) {
            throw new \Exception("Syntax error: " . $result['output']);
        }
    });
}

echo "\n";

// ============================================================================
// Admin files use csrf_check.php
// ============================================================================
echo "--- Admin files use csrf_check.php ---\n";

$csrfAdminFiles = [
    'add_product.php',
    'edit_product.php',
    'manage_hero.php',
    'manage_items.php',
    'settings.php',
    'edit_item.php',
    'index.php',
];

foreach ($csrfAdminFiles as $file) {
    $path = __DIR__ . '/../admin/' . $file;
    $content = file_get_contents($path);

    test("$file uses csrf_check.php", function () use ($path, $content, $file) {
        assertFileExists($path, "File $file harus ada");
        assertStringContains($content, "require 'csrf_check.php'", "$file harus menggunakan csrf_check.php");
    });
}

echo "\n";

// ============================================================================
// Ringkasan
echo "\n";

// ============================================================================
// Langkah 4: db_helper.php
// ============================================================================
echo "--- Langkah 4: Database Helper ---\n";

$dbHelperFile = __DIR__ . '/../admin/helpers/db_helper.php';

test('db_helper.php file exists', function () use ($dbHelperFile) {
    assertFileExists($dbHelperFile, 'File db_helper.php harus ada');
});

test('db_helper.php contains get_setting function', function () use ($dbHelperFile) {
    $content = file_get_contents($dbHelperFile);
    assertStringContains($content, 'function get_setting', 'db_helper harus memiliki fungsi get_setting');
});

test('db_helper.php contains get_whatsapp_number function', function () use ($dbHelperFile) {
    $content = file_get_contents($dbHelperFile);
    assertStringContains($content, 'function get_whatsapp_number', 'db_helper harus memiliki fungsi get_whatsapp_number');
});

test('db_helper.php contains get_home_settings function', function () use ($dbHelperFile) {
    $content = file_get_contents($dbHelperFile);
    assertStringContains($content, 'function get_home_settings', 'db_helper harus memiliki fungsi get_home_settings');
});

test('db_helper.php contains get_products function', function () use ($dbHelperFile) {
    $content = file_get_contents($dbHelperFile);
    assertStringContains($content, 'function get_products', 'db_helper harus memiliki fungsi get_products');
});

test('db_helper.php contains get_product_by_id function', function () use ($dbHelperFile) {
    $content = file_get_contents($dbHelperFile);
    assertStringContains($content, 'function get_product_by_id', 'db_helper harus memiliki fungsi get_product_by_id');
});

test('db_helper.php contains get_product_by_slug function', function () use ($dbHelperFile) {
    $content = file_get_contents($dbHelperFile);
    assertStringContains($content, 'function get_product_by_slug', 'db_helper harus memiliki fungsi get_product_by_slug');
});

test('db_helper.php contains get_hero_images function', function () use ($dbHelperFile) {
    $content = file_get_contents($dbHelperFile);
    assertStringContains($content, 'function get_hero_images', 'db_helper harus memiliki fungsi get_hero_images');
});

test('db_helper.php contains get_product_items function', function () use ($dbHelperFile) {
    $content = file_get_contents($dbHelperFile);
    assertStringContains($content, 'function get_product_items', 'db_helper harus memiliki fungsi get_product_items');
});

test('db_helper.php contains delete_uploaded_image function', function () use ($dbHelperFile) {
    $content = file_get_contents($dbHelperFile);
    assertStringContains($content, 'function delete_uploaded_image', 'db_helper harus memiliki fungsi delete_uploaded_image');
});

test('db_helper.php has valid PHP syntax', function () use ($dbHelperFile) {
    $result = phpLint($dbHelperFile);
    if (!$result['passed']) {
        throw new \Exception("Syntax error: " . $result['output']);
    }
});

echo "\n";

// ============================================================================
// Langkah 5: Fallback logo base64 diekstrak ke file terpisah
// ============================================================================
echo "--- Langkah 5: Fallback Logo Terpusat ---\n";

$fallbackLogoFile = __DIR__ . '/../includes/fallback_logo.php';

test('includes/fallback_logo.php file exists', function () use ($fallbackLogoFile) {
    assertFileExists($fallbackLogoFile, 'File includes/fallback_logo.php harus ada');
});

test('includes/fallback_logo.php has valid PHP syntax', function () use ($fallbackLogoFile) {
    $result = phpLint($fallbackLogoFile);
    if (!$result['passed']) {
        throw new \Exception("Syntax error: " . $result['output']);
    }
});

test('fallback_logo.php returns a valid PNG data URI', function () use ($fallbackLogoFile) {
    $logo = require $fallbackLogoFile;

    if (!is_string($logo)) {
        throw new \Exception('fallback_logo.php harus mengembalikan string');
    }
    if (strpos($logo, 'data:image/png;base64,') !== 0) {
        throw new \Exception('fallback logo harus berupa data URI PNG');
    }

    $decoded = base64_decode(substr($logo, strlen('data:image/png;base64,')), true);
    if ($decoded === false) {
        throw new \Exception('Base64 fallback logo tidak valid');
    }
    if (strncmp($decoded, "\x89PNG\r\n\x1a\n", 8) !== 0) {
        throw new \Exception('Fallback logo bukan file PNG yang valid');
    }
    if (strlen($decoded) < 1000) {
        throw new \Exception('Fallback logo terpotong (ukuran tidak wajar)');
    }
});

test('fallback_logo.php is idempotent when required twice', function () use ($fallbackLogoFile) {
    $first = require $fallbackLogoFile;
    $second = require $fallbackLogoFile;

    if ($first !== $second) {
        throw new \Exception('Fallback logo harus sama saat di-require berulang');
    }
});

test('fallback_logo.php uses constant guard', function () use ($fallbackLogoFile) {
    $content = file_get_contents($fallbackLogoFile);
    assertStringContains($content, "defined('ALFI_FALLBACK_LOGO')", 'fallback_logo harus memakai constant guard');
});

// Template tidak boleh lagi memuat base64 inline
$logoTemplates = [
    'header.php'          => __DIR__ . '/../header.php',
    'views/home.view.php' => __DIR__ . '/../views/home.view.php',
];

foreach ($logoTemplates as $label => $path) {
    test("$label has no inline base64 logo", function () use ($path, $label) {
        $content = file_get_contents($path);
        assertStringNotContains(
            $content,
            'data:image/png;base64,',
            "$label tidak boleh lagi memuat base64 logo secara inline"
        );
    });

    test("$label requires fallback_logo.php", function () use ($path, $label) {
        $content = file_get_contents($path);
        assertStringContains(
            $content,
            'fallback_logo.php',
            "$label harus mengambil logo fallback dari includes/fallback_logo.php"
        );
        assertStringContains(
            $content,
            '$fallbackLogo',
            "$label harus memakai variabel \$fallbackLogo"
        );
    });

    test("$label has valid PHP syntax", function () use ($path) {
        $result = phpLint($path);
        if (!$result['passed']) {
            throw new \Exception("Syntax error: " . $result['output']);
        }
    });
}

test('header.php requires fallback_logo from project root', function () use ($logoTemplates) {
    $content = file_get_contents($logoTemplates['header.php']);
    assertStringContains(
        $content,
        "require __DIR__ . '/includes/fallback_logo.php'",
        'header.php harus me-require includes/fallback_logo.php'
    );
});

test('views/home.view.php requires fallback_logo from project root', function () use ($logoTemplates) {
    $content = file_get_contents($logoTemplates['views/home.view.php']);
    assertStringContains(
        $content,
        "require __DIR__ . '/../includes/fallback_logo.php'",
        'views/home.view.php harus me-require ../includes/fallback_logo.php'
    );
});


// ============================================================================
// Langkah 6: Konsolidasi helper upload (path uploads + duplikasi fungsi)
// ============================================================================
echo "--- Langkah 6: Upload Helper Terpusat ---\n";

$uploadHelperFile = __DIR__ . '/../admin/upload_helper.php';

test('upload_helper.php has valid PHP syntax', function () use ($uploadHelperFile) {
    $result = phpLint($uploadHelperFile);
    if (!$result['passed']) {
        throw new \Exception("Syntax error: " . $result['output']);
    }
});

test('db_helper.php resolves ABSPATH to project root', function () use ($dbHelperFile) {
    $content = file_get_contents($dbHelperFile);
    assertStringContains(
        $content,
        "define('ABSPATH', __DIR__ . '/../../')",
        'db_helper harus resolve ABSPATH ke root project (dua level ke atas)'
    );
    assertStringNotContains(
        $content,
        "define('ABSPATH', __DIR__ . '/../')",
        'ABSPATH tidak boleh resolve ke folder admin/'
    );
});

test('db_helper.php exposes uploads_directory()', function () use ($dbHelperFile) {
    $content = file_get_contents($dbHelperFile);
    assertStringContains($content, 'function uploads_directory', 'db_helper harus punya fungsi uploads_directory');
});

test('uploads_directory() points to root project uploads folder', function () use ($dbHelperFile) {
    require_once $dbHelperFile;

    if (!function_exists('uploads_directory')) {
        throw new \Exception('Fungsi uploads_directory() tidak tersedia');
    }

    $expected = realpath(__DIR__ . '/../uploads');
    $actual = realpath(uploads_directory());

    if ($expected === false) {
        throw new \Exception('Folder uploads tidak ditemukan di root project');
    }
    if ($actual !== $expected) {
        throw new \Exception("uploads_directory() harus = $expected, tapi dapat $actual");
    }
});

test('uploads_directory() is NOT inside admin folder', function () use ($dbHelperFile) {
    require_once $dbHelperFile;

    $adminReal = realpath(__DIR__ . '/../admin');
    $uploadsReal = realpath(uploads_directory());

    if ($uploadsReal === false || $adminReal === false) {
        throw new \Exception('Path admin/ atau uploads/ tidak dapat di-resolve');
    }
    if (strpos($uploadsReal . DIRECTORY_SEPARATOR, $adminReal . DIRECTORY_SEPARATOR) === 0) {
        throw new \Exception('uploads_directory() tidak boleh berada di dalam admin/');
    }
});

test('delete_uploaded_image is defined only in db_helper.php', function () use ($dbHelperFile, $uploadHelperFile) {
    $dbContent = file_get_contents($dbHelperFile);
    $uploadContent = file_get_contents($uploadHelperFile);

    assertStringContains($dbContent, 'function delete_uploaded_image', 'db_helper harus punya delete_uploaded_image');
    assertStringNotContains(
        $uploadContent,
        'function delete_uploaded_image',
        'upload_helper tidak boleh mendefinisikan ulang delete_uploaded_image (fatal redeclare)'
    );
});

test('upload_helper.php loads db_helper.php', function () use ($uploadHelperFile) {
    $content = file_get_contents($uploadHelperFile);
    assertStringContains(
        $content,
        "require_once __DIR__ . '/helpers/db_helper.php'",
        'upload_helper harus me-load db_helper agar delete_uploaded_image selalu tersedia'
    );
});


test('delete_uploaded_image removes a file inside uploads folder', function () use ($dbHelperFile) {
    require_once $dbHelperFile;

    $file = uploads_directory() . '/__test_delete_me.jpg';
    file_put_contents($file, 'x');

    delete_uploaded_image('uploads/__test_delete_me.jpg');

    if (file_exists($file)) {
        @unlink($file);
        throw new \Exception('File di dalam uploads/ harus terhapus');
    }
});

test('delete_uploaded_image rejects path traversal', function () use ($dbHelperFile) {
    require_once $dbHelperFile;

    $outside = realpath(__DIR__ . '/..') . '/__test_traversal.jpg';
    file_put_contents($outside, 'x');

    try {
        delete_uploaded_image('uploads/../__test_traversal.jpg');
        delete_uploaded_image('uploads\\..\\__test_traversal.jpg');

        if (!file_exists($outside)) {
            throw new \Exception('Path traversal harus ditolak, file luar tidak boleh terhapus');
        }
    } finally {
        if (file_exists($outside)) {
            @unlink($outside);
        }
    }
});

test('delete_uploaded_image rejects paths outside uploads folder', function () use ($dbHelperFile) {
    require_once $dbHelperFile;

    $outside = realpath(__DIR__ . '/..') . '/__test_root.jpg';
    file_put_contents($outside, 'x');

    try {
        delete_uploaded_image('__test_root.jpg');
        if (!file_exists($outside)) {
            throw new \Exception('File di luar uploads/ tidak boleh dihapus');
        }
    } finally {
        if (file_exists($outside)) {
            @unlink($outside);
        }
    }
});

test('delete_uploaded_image ignores invalid input without error', function () use ($dbHelperFile) {
    require_once $dbHelperFile;

    delete_uploaded_image(null);
    delete_uploaded_image('');
    delete_uploaded_image([]);
    delete_uploaded_image(123);
});

// Halaman admin harus memakai helper, bukan unlink manual
$deletePages = ['index.php', 'manage_hero.php', 'manage_items.php', 'edit_product.php', 'edit_item.php'];

foreach ($deletePages as $page) {
    $pagePath = __DIR__ . '/../admin/' . $page;

    test("$page uses delete_uploaded_image instead of manual unlink", function () use ($pagePath, $page) {
        assertFileExists($pagePath, "File $page harus ada");
        $content = file_get_contents($pagePath);

        assertStringNotContains($content, "unlink('../'", "$page tidak boleh unlink manual");
        assertStringNotContains($content, "file_exists('../' .", "$page tidak boleh cek path uploads relatif");
    });
}

echo "\n";


test('requiring both helpers does not trigger redeclare error', function () use ($uploadHelperFile, $dbHelperFile) {
    require_once $uploadHelperFile;
    require_once $dbHelperFile;
    require_once $uploadHelperFile;

    foreach (['delete_uploaded_image', 'uploads_directory', 'store_uploaded_image', 'compress_uploaded_image'] as $fn) {
        if (!function_exists($fn)) {
            throw new \Exception("Fungsi $fn harus tersedia setelah require helper");
        }
    }
});

test('upload_helper.php uses shared uploads_directory()', function () use ($uploadHelperFile) {
    $content = file_get_contents($uploadHelperFile);
    assertStringContains($content, '$uploadDirectory = uploads_directory();', 'upload_helper harus memakai uploads_directory()');
    assertStringNotContains($content, "dirname(__DIR__) . '/uploads'", 'upload_helper tidak boleh menghitung path uploads manual');
});


echo "\n";

// ============================================================================
// Langkah 7: Keamanan thumb.php (anti LFI / path traversal)
// ============================================================================
echo "--- Langkah 7: Keamanan thumb.php (Anti LFI) ---\n";

$thumbFile = __DIR__ . '/../thumb.php';
$dbHelperFile = __DIR__ . '/../admin/helpers/db_helper.php';

test('thumb.php file exists', function () use ($thumbFile) {
    assertFileExists($thumbFile, 'File thumb.php harus ada');
});

test('thumb.php has valid PHP syntax', function () use ($thumbFile) {
    $result = phpLint($thumbFile);
    if (!$result['passed']) {
        throw new \Exception("Syntax error di thumb.php:\n" . $result['output']);
    }
});

test('thumb.php uses the shared path resolver', function () use ($thumbFile) {
    $content = file_get_contents($thumbFile);
    assertStringContains($content, 'resolve_thumbnail_path(', 'thumb.php harus memakai resolve_thumbnail_path()');
    assertStringContains($content, "admin/helpers/db_helper.php", 'thumb.php harus me-require db_helper.php');
});

test('thumb.php does not read raw user path directly', function () use ($thumbFile) {
    $content = file_get_contents($thumbFile);
    assertStringNotContains($content, "file_exists(__DIR__ . '/' . \$file)", 'thumb.php tidak boleh memakai path mentah dari user');
});

test('thumb.php rejects path traversal and non-image files', function () use ($thumbFile) {
    $content = file_get_contents($thumbFile);
    assertStringContains($content, 'getimagesize', 'thumb.php harus memvalidasi file sebagai gambar');
    assertStringContains($content, '404 Not Found', 'thumb.php harus mengembalikan 404 untuk request invalid');
});

test('resolve_project_path rejects traversal, absolute paths and null bytes', function () use ($dbHelperFile) {
    require_once $dbHelperFile;

    $invalid = [
        '../config.php',         // traversal ke parent
        'uploads/../config.php', // traversal tersembunyi
        '..\\config.php',        // traversal backslash windows
        '../../../../etc/passwd',
        '/etc/passwd',           // path absolut unix
        'C:/Windows/win.ini',    // path absolut windows
        "config.php\0.txt",      // null byte
        '',                      // kosong
    ];

    foreach ($invalid as $path) {
        $result = resolve_project_path($path);
        if ($result !== null) {
            throw new \Exception("resolve_project_path('$path') seharusnya null, tetapi mengembalikan: $result");
        }
    }
});

test('resolve_project_path resolves a valid uploads image', function () use ($dbHelperFile) {
    require_once $dbHelperFile;

    $sample = uploads_directory() . '/__lfi_test_sample.png';

    // PNG 1x1 yang valid.
    $png = base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
    );
    file_put_contents($sample, $png);

    try {
        $resolved = resolve_project_path('uploads/__lfi_test_sample.png');
        if ($resolved === null) {
            throw new \Exception('resolve_project_path harus bisa me-resolve gambar valid di uploads/');
        }
        if (realpath($sample) !== $resolved) {
            throw new \Exception("Path hasil resolve tidak sama dengan file asli: $resolved");
        }
    } finally {
        @unlink($sample);
    }
});

test('resolve_project_path rejects files outside project root', function () use ($dbHelperFile) {
    require_once $dbHelperFile;

    $outside = sys_get_temp_dir() . '/alfi_outside_test.txt';
    file_put_contents($outside, 'secret');

    try {
        $result = resolve_project_path($outside);
        if ($result !== null) {
            throw new \Exception("Path absolut di luar root harus ditolak, tetapi mengembalikan: $result");
        }
    } finally {
        @unlink($outside);
    }
});

test('resolve_thumbnail_path only allows uploads images and whitelisted root images', function () use ($dbHelperFile) {
    require_once $dbHelperFile;

    // File PHP di root: resolve_project_path bisa resolve-nya,
    // resolve_thumbnail_path HARUS menolaknya (bukan lokasi gambar resmi).
    $rejected = [
        'config.php',
        'index.php',
        'thumb.php',
        'admin/helpers/db_helper.php',
        'tests/run_tests.php',
        '.htaccess',
        'uploads/../config.php',
        '../uploads/logo.png',
    ];

    foreach ($rejected as $path) {
        $result = resolve_thumbnail_path($path);
        if ($result !== null) {
            throw new \Exception("resolve_thumbnail_path('$path') seharusnya null, tetapi mengembalikan: $result");
        }
    }
});

test('resolve_thumbnail_path allows valid uploads image and whitelisted root image', function () use ($dbHelperFile) {
    require_once $dbHelperFile;

    $sample = uploads_directory() . '/__thumb_whitelist_test.png';
    $png = base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
    );
    file_put_contents($sample, $png);

    try {
        $resolved = resolve_thumbnail_path('uploads/__thumb_whitelist_test.png');
        if ($resolved === null) {
            throw new \Exception('resolve_thumbnail_path harus mengizinkan gambar di uploads/');
        }
    } finally {
        @unlink($sample);
    }

    // Gambar statis root yang ada di whitelist harus diizinkan.
    foreach (thumbnail_allowed_root_files() as $rootImage) {
        if (!is_file(__DIR__ . '/../' . $rootImage)) {
            continue;
        }
        $resolved = resolve_thumbnail_path($rootImage);
        if ($resolved === null) {
            throw new \Exception("resolve_thumbnail_path('$rootImage') harus diizinkan");
        }
    }
});

/**
 * Jalankan thumb.php di proses terpisah dengan ?file=... lalu cek http_response_code().
 */
function runThumbProbe(string $payload): array
{
    $php = PHP_BINARY;
    $projectRoot = realpath(__DIR__ . '/..');

    $runner = $projectRoot . '/__tmp_thumb_probe.php';
    file_put_contents($runner, '<?php
$_GET["file"] = $argv[1];
register_shutdown_function(function () {
    fwrite(STDERR, "\n<<<CODE=" . http_response_code() . ">>>");
});
include ' . var_export($projectRoot . '/thumb.php', true) . ';
');

    try {
        $output = [];
        $exitCode = 0;
        exec(
            escapeshellarg($php) . ' ' . escapeshellarg($runner) . ' ' . escapeshellarg($payload) . ' 2>&1',
            $output,
            $exitCode
        );
        $text = implode("\n", $output);

        $httpCode = 0;
        if (preg_match('/<<<CODE=(\d+)>>>/', $text, $m)) {
            $httpCode = (int) $m[1];
        }

        return ['code' => $httpCode, 'body' => $text];
    } finally {
        @unlink($runner);
    }
}

test('thumb.php returns 404 for config.php and traversal attempts', function () {
    $attackCases = [
        'config.php',
        '../config.php',
        'uploads/../config.php',
        '..\\config.php',
        'C:/Windows/win.ini',
    ];

    foreach ($attackCases as $payload) {
        $result = runThumbProbe($payload);

        if ($result['code'] !== 404) {
            throw new \Exception(
                "thumb.php harus return 404 untuk '$payload', tetapi HTTP {$result['code']}. Output: {$result['body']}"
            );
        }

        // Pastikan isi file sensitif tidak bocor ke response.
        foreach (['dbname=', '$password', 'PDO('] as $secret) {
            if (strpos($result['body'], $secret) !== false) {
                throw new \Exception("Respons thumb.php untuk '$payload' membocorkan '$secret'");
            }
        }
    }
});

test('thumb.php serves a valid uploads image', function () {
    $projectRoot = realpath(__DIR__ . '/..');
    $uploadsDir = $projectRoot . '/uploads';

    if (!is_dir($uploadsDir)) {
        return; // Tidak ada folder uploads di environment ini.
    }

    // Cari satu gambar yang benar-benar ada di uploads/.
    $sample = null;
    foreach (glob($uploadsDir . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [] as $candidate) {
        if (is_file($candidate) && @getimagesize($candidate)) {
            $sample = $candidate;
            break;
        }
    }

    if ($sample === null) {
        return; // Tidak ada gambar uji di uploads/.
    }

    $relative = 'uploads/' . basename($sample);
    $result = runThumbProbe($relative);

    if ($result['code'] === 404) {
        throw new \Exception("thumb.php harus bisa menyajikan gambar valid '$relative', tetapi return 404");
    }
});

echo "  Hasil: $passed lulus, $failed gagal\n";
echo "========================================\n";

if ($failed > 0) {
    echo "\n--- Error detail ---\n";
    foreach ($errors as $e) {
        echo "  ✗ $e\n";
    }
    exit(1);
}

echo "\nSemua test lulus!\n";
exit(0);