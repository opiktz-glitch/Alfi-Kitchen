<?php
use PHPUnit\Framework\TestCase;

/**
 * Test refactoring admin: CSS terpusat, auth_check, csrf_check.
 * Tidak membutuhkan database.
 */
class AdminRefactorTest extends TestCase
{
    private const ADMIN_DIR = __DIR__ . '/../../admin';
    private const CSS_FILE = __DIR__ . '/../../admin/assets/css/admin.css';
    private const AUTH_CHECK_FILE = __DIR__ . '/../../admin/auth_check.php';
    private const CSRF_CHECK_FILE = __DIR__ . '/../../admin/csrf_check.php';

    public function testAdminCssFileExists(): void
    {
        $this->assertFileExists(self::CSS_FILE, 'File admin.css harus ada');
    }

    public function testAuthCheckFileExists(): void
    {
        $this->assertFileExists(self::AUTH_CHECK_FILE, 'File auth_check.php harus ada');
    }

    public function testCsrfCheckFileExists(): void
    {
        $this->assertFileExists(self::CSRF_CHECK_FILE, 'File csrf_check.php harus ada');
    }

    public function testAdminCssContainsRequiredClasses(): void
    {
        $css = file_get_contents(self::CSS_FILE);
        $this->assertStringContainsString('.admin-header', $css);
        $this->assertStringContainsString('.admin-container', $css);
        $this->assertStringContainsString('.form-card', $css);
        $this->assertStringContainsString('.form-group', $css);
        $this->assertStringContainsString('.form-control', $css);
        $this->assertStringContainsString('.btn-primary', $css);
        $this->assertStringContainsString('.btn-cancel', $css);
        $this->assertStringContainsString('.btn-danger', $css);
        $this->assertStringContainsString('.btn-secondary', $css);
        $this->assertStringContainsString('.alert', $css);
        $this->assertStringContainsString('.alert-error', $css);
        $this->assertStringContainsString('.alert-success', $css);
        $this->assertStringContainsString('.table-responsive', $css);
        $this->assertStringContainsString('.header-action', $css);
        $this->assertStringContainsString('.thumb', $css);
        $this->assertStringContainsString('.preview-img', $css);
    }

    public function testAuthCheckRedirectsWhenNotLoggedIn(): void
    {
        $content = file_get_contents(self::AUTH_CHECK_FILE);
        $this->assertStringContainsString("admin_logged_in", $content);
        $this->assertStringContainsString("../login.php", $content);
        $this->assertStringContainsString("auth.php", $content);
    }

    public function testCsrfCheckVerifiesPostToken(): void
    {
        $content = file_get_contents(self::CSRF_CHECK_FILE);
        $this->assertStringContainsString("REQUEST_METHOD", $content);
        $this->assertStringContainsString("verify_csrf_token", $content);
        $this->assertStringContainsString("csrf_token", $content);
        $this->assertStringContainsString("403", $content);
    }

    /**
     * @dataProvider provideAdminFilesWithAuthCheck
     */
    public function testAdminFilesUseAuthCheck(string $file): void
    {
        $path = self::ADMIN_DIR . '/' . $file;
        $this->assertFileExists($path, "File $file harus ada");
        $content = file_get_contents($path);
        $this->assertStringContainsString("require 'auth_check.php'", $content, "File $file harus menggunakan auth_check.php");
        $this->assertStringNotContainsString("if (!isset(\$_SESSION['admin_logged_in'])", $content, "File $file tidak boleh punya blok login duplikat");
    }

    /**
     * @dataProvider provideAdminFilesWithCsrfCheck
     */
    public function testAdminFilesUseCsrfCheck(string $file): void
    {
        $path = self::ADMIN_DIR . '/' . $file;
        $this->assertFileExists($path, "File $file harus ada");
        $content = file_get_contents($path);
        $this->assertStringContainsString("require 'csrf_check.php'", $content, "File $file harus menggunakan csrf_check.php");
    }

    public static function provideAdminFilesWithAuthCheck(): array
    {
        return [
            ['add_product.php'],
            ['edit_product.php'],
            ['manage_hero.php'],
            ['manage_items.php'],
            ['settings.php'],
            ['activity_log.php'],
            ['edit_item.php'],
            ['index.php'],
            ['export_db.php'],
            ['export_images.php'],
        ];
    }

    public static function provideAdminFilesWithCsrfCheck(): array
    {
        return [
            ['add_product.php'],
            ['edit_product.php'],
            ['manage_hero.php'],
            ['manage_items.php'],
            ['settings.php'],
            ['edit_item.php'],
            ['index.php'],
            ['activity_log.php'],
        ];
    }

    /**
     * @dataProvider provideAdminFilesWithSitePreview
     */
    public function testAdminPagesIncludeSitePreviewLink(string $file): void
    {
        $path = self::ADMIN_DIR . '/' . $file;
        $this->assertFileExists($path, "File $file harus ada");
        $content = file_get_contents($path);
        $this->assertStringContainsString('Lihat Website ↗', $content, "File $file harus memiliki tombol navigasi Lihat Website ↗");
        $this->assertStringContainsString('btn-site-preview', $content, "File $file harus menggunakan kelas btn-site-preview");
    }

    public static function provideAdminFilesWithSitePreview(): array
    {
        return [
            ['index.php'],
            ['manage_hero.php'],
            ['settings.php'],
            ['activity_log.php'],
            ['manage_items.php'],
            ['add_product.php'],
            ['edit_product.php'],
            ['edit_item.php'],
        ];
    }
}