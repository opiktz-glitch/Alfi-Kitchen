<?php
use PHPUnit\Framework\TestCase;

/**
 * Regression test keamanan thumb.php (anti Local File Inclusion / LFI).
 *
 * Cakupan:
 * 1. resolve_project_path() menolak traversal, path absolut, dan null byte.
 * 2. resolve_thumbnail_path() hanya mengizinkan gambar di uploads/ + whitelist root.
 * 3. thumb.php memakai validator bersama (bukan path mentah dari user).
 * 4. thumb.php mengembalikan 404 untuk payload serangan.
 */
class ThumbSecurityTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../';
    private const DB_HELPER = __DIR__ . '/../../admin/helpers/db_helper.php';
    private const THUMB = __DIR__ . '/../../thumb.php';

    protected function setUp(): void
    {
        require_once self::DB_HELPER;
    }

    public function testThumbPhpHasValidSyntax(): void
    {
        $output = [];
        $exitCode = 0;
        exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg(self::THUMB) . ' 2>&1', $output, $exitCode);

        $this->assertSame(0, $exitCode, "Syntax error di thumb.php:\n" . implode("\n", $output));
    }

    public function testThumbPhpUsesSharedPathValidator(): void
    {
        $content = file_get_contents(self::THUMB);

        $this->assertStringContainsString(
            'resolve_thumbnail_path(',
            $content,
            'thumb.php harus memakai resolve_thumbnail_path()'
        );
        $this->assertStringContainsString(
            "require_once __DIR__ . '/admin/helpers/db_helper.php'",
            $content,
            'thumb.php harus me-require db_helper.php'
        );
        $this->assertStringNotContainsString(
            "file_exists(__DIR__ . '/' . \$file)",
            $content,
            'thumb.php tidak boleh memproses path mentah dari user'
        );
    }

    public function testThumbPhpValidatesFileIsActuallyAnImage(): void
    {
        $content = file_get_contents(self::THUMB);

        $this->assertStringContainsString('getimagesize', $content);
        $this->assertStringContainsString('404 Not Found', $content);
    }

    /**
     * @dataProvider maliciousPathProvider
     */
    public function testResolveProjectPathRejectsMaliciousPaths(string $path): void
    {
        $this->assertNull(
            resolve_project_path($path),
            "resolve_project_path('$path') harus mengembalikan null"
        );
    }

    public static function maliciousPathProvider(): array
    {
        return [
            'parent traversal'      => ['../config.php'],
            'hidden traversal'     => ['uploads/../config.php'],
            'windows traversal'     => ['..\\config.php'],
            'deep traversal'        => ['../../../../etc/passwd'],
            'unix absolute path'    => ['/etc/passwd'],
            'windows absolute path' => ['C:/Windows/win.ini'],
            'null byte'             => ["config.php\0.txt"],
            'empty string'          => [''],
        ];
    }

    /**
     * @dataProvider nonImagePathProvider
     */
    public function testResolveThumbnailPathRejectsNonImageLocations(string $path): void
    {
        $this->assertNull(
            resolve_thumbnail_path($path),
            "resolve_thumbnail_path('$path') harus mengembalikan null"
        );
    }

    public static function nonImagePathProvider(): array
    {
        return [
            'config file'      => ['config.php'],
            'index file'       => ['index.php'],
            'thumb itself'     => ['thumb.php'],
            'db helper'        => ['admin/helpers/db_helper.php'],
            'test runner'      => ['tests/run_tests.php'],
            'htaccess'         => ['.htaccess'],
            'traversal'        => ['uploads/../config.php'],
            'outside uploads'  => ['../uploads/logo.png'],
        ];
    }

    public function testResolveThumbnailPathAllowsUploadsImage(): void
    {
        $sample = uploads_directory() . '/__phpunit_thumb_test.png';

        // PNG 1x1 yang valid.
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        );
        file_put_contents($sample, $png);

        try {
            $resolved = resolve_thumbnail_path('uploads/__phpunit_thumb_test.png');

            $this->assertNotNull($resolved, 'Gambar di uploads/ harus diizinkan');
            $this->assertSame(realpath($sample), $resolved);
        } finally {
            @unlink($sample);
        }
    }

    public function testResolveThumbnailPathAllowsWhitelistedRootImages(): void
    {
        foreach (thumbnail_allowed_root_files() as $rootImage) {
            if (!is_file(self::PROJECT_ROOT . $rootImage)) {
                continue;
            }

            $this->assertNotNull(
                resolve_thumbnail_path($rootImage),
                "Gambar root '$rootImage' harus diizinkan"
            );
        }
    }

    public function testResolveProjectPathRejectsFilesOutsideProjectRoot(): void
    {
        $outside = sys_get_temp_dir() . '/alfi_phpunit_outside.txt';
        file_put_contents($outside, 'secret');

        try {
            $this->assertNull(
                resolve_project_path($outside),
                'Path absolut di luar root project harus ditolak'
            );
        } finally {
            @unlink($outside);
        }
    }
}
