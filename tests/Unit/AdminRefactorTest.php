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

    public function testAdminVisualStyleUsesCompactTypographyAndControls(): void
    {
        $css = file_get_contents(self::CSS_FILE);

        $this->assertStringContainsString('font-size: 14px;', $css);
        $this->assertStringContainsString('font-size: 1.45rem;', $css);
        $this->assertStringContainsString('min-height: 34px;', $css);
        $this->assertStringContainsString('padding: 6px 11px;', $css);
        $this->assertStringContainsString('min-height: 38px;', $css);
        $this->assertStringContainsString('background: var(--card);', $css);
    }

    public function testAdminFormsUseAvailableWidthAndStackOnMobile(): void
    {
        $css = file_get_contents(self::CSS_FILE);
        $addProduct = file_get_contents(self::ADMIN_DIR . '/add_product.php');
        $editItem = file_get_contents(self::ADMIN_DIR . '/edit_item.php');
        $manageItems = file_get_contents(self::ADMIN_DIR . '/manage_items.php');
        $settings = file_get_contents(self::ADMIN_DIR . '/settings.php');

        $this->assertStringContainsString('max-width: 1240px;', $css);
        $this->assertStringContainsString('grid-template-columns: repeat(2, minmax(0, 1fr));', $css);
        $this->assertStringContainsString('grid-template-columns: 1fr;', $css);
        $this->assertStringContainsString('class="admin-form-grid"', $addProduct);
        $this->assertStringContainsString('class="admin-form-grid"', $editItem);
        $this->assertStringContainsString('class="admin-form-grid"', $manageItems);
        $this->assertStringContainsString('class="form-card settings-card"', $settings);
        $this->assertStringContainsString('class="settings-grid"', $settings);
    }

    public function testDashboardSeparatesSecondaryNavigationFromPrimaryAction(): void
    {
        $dashboard = file_get_contents(self::ADMIN_DIR . '/index.php');
        $css = file_get_contents(self::CSS_FILE);

        $this->assertStringContainsString('<nav class="admin-nav"', $dashboard);
        $this->assertStringContainsString('Kelola Slider</a>', $dashboard);
        $this->assertStringContainsString('Pengaturan</a>', $dashboard);
        $this->assertStringContainsString('Log Aktivitas</a>', $dashboard);
        $this->assertStringContainsString('class="btn btn-primary">+ Tambah Kategori</a>', $dashboard);
        $this->assertStringContainsString('.admin-nav', $css);
    }

    public function testDashboardPrioritizesVariantManagementAndSeparatesDelete(): void
    {
        $dashboard = file_get_contents(self::ADMIN_DIR . '/index.php');
        $css = file_get_contents(self::CSS_FILE);

        $this->assertStringContainsString('aria-label="Kelola varian dan harga untuk', $dashboard);
        $this->assertStringContainsString('aria-label="Edit kategori', $dashboard);
        $this->assertStringContainsString('class="category-actions-delete"', $dashboard);
        $this->assertStringContainsString('aria-label="Hapus kategori', $dashboard);
        $this->assertStringContainsString('class="btn btn-primary category-icon-button"', $dashboard);
        $this->assertStringContainsString("admin_icon('manage')", $dashboard);
        $this->assertStringContainsString('.category-icon-button', $css);
        $this->assertStringContainsString('.category-actions-delete', $css);
        $this->assertStringContainsString('background: transparent', $css);
    }

    public function testDashboardShowsConsistentCategoryThumbnailsAndVariantCounts(): void
    {
        $dashboard = file_get_contents(self::ADMIN_DIR . '/index.php');
        $css = file_get_contents(self::CSS_FILE);

        $this->assertStringContainsString('AS item_count', $dashboard);
        $this->assertStringContainsString('class="category-thumb"', $dashboard);
        $this->assertStringContainsString('class="category-thumb category-thumb-placeholder"', $dashboard);
        $this->assertStringContainsString('number_format((int) $p[\'item_count\'])', $dashboard);
        $this->assertStringContainsString('.category-thumb', $css);
        $this->assertStringContainsString('.category-count', $css);
    }

    public function testDashboardUsesStackedCategoryCardsOnMobile(): void
    {
        $dashboard = file_get_contents(self::ADMIN_DIR . '/index.php');
        $css = file_get_contents(self::CSS_FILE);

        $this->assertStringContainsString('data-label="Kategori"', $dashboard);
        $this->assertStringContainsString('data-label="Jumlah varian"', $dashboard);
        $this->assertStringContainsString('data-label="Aksi"', $dashboard);
        $this->assertStringContainsString('.category-table table {', $css);
        $this->assertStringContainsString('min-width: 0;', $css);
        $this->assertStringContainsString('.category-table tbody tr {', $css);
        $this->assertStringContainsString('.category-actions-main {', $css);
        $this->assertStringContainsString('display: flex;', $css);
    }

    public function testDashboardSupportsSearchEmptyStatesAndNamedDeleteConfirmation(): void
    {
        $dashboard = file_get_contents(self::ADMIN_DIR . '/index.php');
        $css = file_get_contents(self::CSS_FILE);

        $this->assertStringContainsString('name="search"', $dashboard);
        $this->assertStringContainsString('LIKE ? ESCAPE', $dashboard);
        $this->assertStringContainsString('JSON_HEX_APOS | JSON_HEX_QUOT', $dashboard);
        $this->assertStringContainsString('beserta seluruh variannya?', $dashboard);
        $this->assertStringContainsString('Belum ada kategori produk', $dashboard);
        $this->assertStringContainsString('category-empty', $css);
    }

    public function testAdminFlashMessagesAreEscapedAndConsumedOnce(): void
    {
        $auth = file_get_contents(self::ADMIN_DIR . '/auth.php');
        $flashTemplate = file_get_contents(self::ADMIN_DIR . '/flash_message.php');
        $dashboard = file_get_contents(self::ADMIN_DIR . '/index.php');
        $this->assertStringContainsString('function set_admin_flash', $auth);
        $this->assertStringContainsString('function consume_admin_flash', $auth);
        $this->assertStringContainsString('unset($_SESSION[\'admin_flash\'])', $auth);
        $this->assertStringContainsString('htmlspecialchars($adminFlash[\'message\']', $flashTemplate);
        $this->assertStringContainsString('flash_message.php', $dashboard);
    }

    public function testProductAndVariantMutationsSetSuccessFeedback(): void
    {
        $addProduct = file_get_contents(self::ADMIN_DIR . '/add_product.php');
        $editProduct = file_get_contents(self::ADMIN_DIR . '/edit_product.php');
        $manageItems = file_get_contents(self::ADMIN_DIR . '/manage_items.php');
        $editItem = file_get_contents(self::ADMIN_DIR . '/edit_item.php');

        $this->assertStringContainsString('set_admin_flash(\'Kategori "', $addProduct);
        $this->assertStringContainsString('set_admin_flash(\'Kategori "', $editProduct);
        $this->assertStringContainsString('set_admin_flash(\'Varian "', $manageItems);
        $this->assertStringContainsString('set_admin_flash(\'Varian "', $editItem);
        $this->assertStringContainsString('include __DIR__ . \'/flash_message.php\'', $manageItems);
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
        $this->assertStringContainsString('Lihat Website ↗', $content, "File $file harus memiliki tautan teks Lihat Website");
        $this->assertStringContainsString('btn-site-preview', $content, "File $file harus menggunakan kelas btn-site-preview");
    }

    public function testAdminIconOnlyControlsHaveAccessibleNames(): void
    {
        foreach (self::provideAdminFilesWithSitePreview() as [$file]) {
            $content = file_get_contents(self::ADMIN_DIR . '/' . $file);
            $insideTable = false;
            foreach (explode("\n", $content) as $line) {
                if (str_contains($line, '<table')) {
                    $insideTable = true;
                }

                if (str_contains($line, 'admin_icon(')) {
                    $this->assertTrue($insideTable, "Ikon aksi di $file hanya boleh digunakan dalam tabel");
                }

                if (!str_contains($line, 'btn-icon-only') && !str_contains($line, 'category-icon-button')) {
                    if (str_contains($line, '</table>')) {
                        $insideTable = false;
                    }
                    continue;
                }

                $this->assertStringContainsString('aria-label=', $line, "Kontrol ikon di $file harus memiliki aria-label");
                $this->assertStringContainsString('title=', $line, "Kontrol ikon di $file harus memiliki title");

                if (str_contains($line, '</table>')) {
                    $insideTable = false;
                }
            }
        }
    }

    public function testAdminIconHelperUsesAccessibleInlineSvg(): void
    {
        $auth = file_get_contents(self::ADMIN_DIR . '/auth.php');
        $this->assertStringContainsString('function admin_icon(string $name): string', $auth);
        $this->assertStringContainsString('class="admin-icon" viewBox="0 0 24 24" aria-hidden="true"', $auth);
        $this->assertStringContainsString('throw new InvalidArgumentException(\'Ikon admin tidak dikenal.\')', $auth);
        $this->assertStringContainsString('.btn-icon-only', file_get_contents(self::CSS_FILE));
        $this->assertStringContainsString("admin_icon('manage')", file_get_contents(self::ADMIN_DIR . '/index.php'));
        $this->assertStringContainsString("admin_icon('edit')", file_get_contents(self::ADMIN_DIR . '/manage_items.php'));
        $this->assertStringContainsString("admin_icon('delete')", file_get_contents(self::ADMIN_DIR . '/manage_hero.php'));
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