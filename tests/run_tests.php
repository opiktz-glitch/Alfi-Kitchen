<?php
/**
 * Test Runner Alfi Kitchen
 *
 * Menjalankan unit test suite sesuai alur perubahan aktif:
 * 1. Syntax integrity (Admin files, DatabaseMigrator, migrate.php, thumb.php, fallback_logo.php)
 * 2. Admin Refactor & Security Guard (admin.css, auth_check.php, csrf_check.php)
 * 3. Thumb Security (Anti-LFI & Whitelist Uploads)
 * 4. Upload Helper (Root path resolution, anti-traversal delete, single declaration)
 * 5. Database Migrator & Versioning (Format v01..v04, state tracking, SQLite execution)
 * 6. Fallback Logo Optimization (Pemisahan base64 payload dari view)
 *
 * Jalankan dengan: C:\xampp\php\php.exe tests\run_tests.php
 */

require_once __DIR__ . '/bootstrap.php';

// Load unit test classes yang sesuai dengan alur perubahan
require_once __DIR__ . '/Unit/SyntaxTest.php';
require_once __DIR__ . '/Unit/AdminRefactorTest.php';
require_once __DIR__ . '/Unit/ThumbSecurityTest.php';
require_once __DIR__ . '/Unit/UploadHelperRefactorTest.php';
require_once __DIR__ . '/Unit/DatabaseMigratorTest.php';
require_once __DIR__ . '/Unit/FallbackLogoTest.php';
require_once __DIR__ . '/Unit/EnvLoaderTest.php';
require_once __DIR__ . '/Unit/BackupAndLogTest.php';
require_once __DIR__ . '/Unit/SeoAndRoutingTest.php';

$runner = new \AlfiKitchen\Test\SimpleRunner();

$testClasses = [
    \SyntaxTest::class,
    \AdminRefactorTest::class,
    \ThumbSecurityTest::class,
    \UploadHelperRefactorTest::class,
    \DatabaseMigratorTest::class,
    \FallbackLogoTest::class,
    \EnvLoaderTest::class,
    \BackupAndLogTest::class,
    \SeoAndRoutingTest::class,
];

$success = $runner->runSuite($testClasses);

exit($success ? 0 : 1);