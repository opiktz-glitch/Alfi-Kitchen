<?php
use PHPUnit\Framework\TestCase;

/**
 * Regression test konsolidasi helper upload admin.
 *
 * Cakupan:
 * 1. Folder uploads harus resolve ke ROOT PROJECT (bukan admin/uploads).
 * 2. delete_uploaded_image() hanya boleh didefinisikan SATU KALI.
 * 3. Urutan require kedua helper tidak menyebabkan fatal redeclare.
 * 4. Penghapusan aman: file di dalam uploads/ terhapus, path traversal ditolak.
 * 5. Halaman admin tidak boleh menghapus gambar secara manual.
 */
class UploadHelperRefactorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../';
    private const DB_HELPER = __DIR__ . '/../../admin/helpers/db_helper.php';
    private const UPLOAD_HELPER = __DIR__ . '/../../admin/upload_helper.php';


    public function testDbHelperResolvesAbsPathToProjectRoot(): void
    {
        $content = file_get_contents(self::DB_HELPER);

        $this->assertStringContainsString(
            "define('ABSPATH', __DIR__ . '/../../')",
            $content,
            'ABSPATH harus resolve ke root project (dua level di atas admin/helpers)'
        );
        $this->assertStringNotContainsString(
            "define('ABSPATH', __DIR__ . '/../')",
            $content,
            'ABSPATH tidak boleh resolve ke folder admin/'
        );
    }

    public function testUploadsDirectoryResolvesToProjectRootUploads(): void
    {
        require_once self::DB_HELPER;

        $this->assertTrue(function_exists('uploads_directory'), 'uploads_directory() harus tersedia');

        $expected = realpath(self::PROJECT_ROOT . 'uploads');
        $actual = realpath(uploads_directory());

        $this->assertNotFalse($expected, 'Folder uploads harus ada di root project');
        $this->assertSame($expected, $actual, 'uploads_directory() harus menunjuk folder uploads di root project');
    }

    public function testUploadsDirectoryIsNotInsideAdminFolder(): void
    {
        require_once self::DB_HELPER;

        $adminReal = realpath(self::PROJECT_ROOT . 'admin');
        $uploadsReal = realpath(uploads_directory());

        $this->assertNotFalse($adminReal);
        $this->assertNotFalse($uploadsReal);
        $this->assertStringStartsNotWith(
            $adminReal . DIRECTORY_SEPARATOR,
            $uploadsReal . DIRECTORY_SEPARATOR,
            'Folder uploads tidak boleh berada di dalam admin/'
        );
    }

    public function testDeleteUploadedImageIsDefinedOnlyOnce(): void
    {
        $dbContent = file_get_contents(self::DB_HELPER);
        $uploadContent = file_get_contents(self::UPLOAD_HELPER);

        $this->assertMatchesRegularExpression(
            '/function\s+delete_uploaded_image\s*\(/',
            $dbContent,
            'db_helper.php harus mendefinisikan delete_uploaded_image()'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/function\s+delete_uploaded_image\s*\(/',
            $uploadContent,
            'upload_helper.php tidak boleh mendefinisikan ulang delete_uploaded_image() (fatal redeclare)'
        );
    }

    public function testUploadHelperLoadsDbHelper(): void
    {
        $content = file_get_contents(self::UPLOAD_HELPER);

        $this->assertStringContainsString(
            "require_once __DIR__ . '/helpers/db_helper.php'",
            $content,
            'upload_helper.php harus me-load db_helper.php'
        );
    }

    public function testUploadHelperUsesSharedUploadsDirectory(): void
    {
        $content = file_get_contents(self::UPLOAD_HELPER);

        $this->assertStringContainsString(
            '$uploadDirectory = uploads_directory();',
            $content,
            'upload_helper.php harus memakai uploads_directory()'
        );
        $this->assertStringNotContainsString(
            "dirname(__DIR__) . '/uploads'",
            $content,
            'upload_helper.php tidak boleh menghitung path uploads secara manual'
        );
    }


    public function testDeleteUploadedImageRemovesFileInsideUploadsFolder(): void
    {
        require_once self::DB_HELPER;

        $file = uploads_directory() . '/__phpunit_delete_test.jpg';
        file_put_contents($file, 'x');
        $this->assertFileExists($file);

        delete_uploaded_image('uploads/__phpunit_delete_test.jpg');

        $this->assertFileDoesNotExist($file, 'File di dalam uploads/ harus terhapus');
    }

    public function testDeleteUploadedImageRejectsPathTraversal(): void
    {
        require_once self::DB_HELPER;

        $outside = realpath(self::PROJECT_ROOT) . '/__phpunit_outside.jpg';
        file_put_contents($outside, 'x');

        try {
            delete_uploaded_image('uploads/../__phpunit_outside.jpg');
            delete_uploaded_image('uploads\\..\\__phpunit_outside.jpg');

            $this->assertFileExists($outside, 'Path traversal harus ditolak');
        } finally {
            if (file_exists($outside)) {
                unlink($outside);
            }
        }
    }

    public function testDeleteUploadedImageRejectsPathsOutsideUploadsFolder(): void
    {
        require_once self::DB_HELPER;

        $outside = realpath(self::PROJECT_ROOT) . '/__phpunit_root.jpg';
        file_put_contents($outside, 'x');

        try {
            delete_uploaded_image('__phpunit_root.jpg');

            $this->assertFileExists($outside, 'File di luar uploads/ tidak boleh dihapus');
        } finally {
            if (file_exists($outside)) {
                unlink($outside);
            }
        }
    }

    public function testDeleteUploadedImageIgnoresInvalidInput(): void
    {
        require_once self::DB_HELPER;

        delete_uploaded_image(null);
        delete_uploaded_image('');
        delete_uploaded_image([]);
        delete_uploaded_image(123);

        $this->assertTrue(true, 'Input invalid tidak boleh menyebabkan error');
    }

    /**
     * @dataProvider provideAdminPagesThatDeleteImages
     */
    public function testAdminPagesUseDeleteHelperInsteadOfManualUnlink(string $file): void
    {
        $path = self::PROJECT_ROOT . 'admin/' . $file;
        $this->assertFileExists($path, "File $file harus ada");

        $content = file_get_contents($path);

        $this->assertStringNotContainsString(
            "unlink('../'",
            $content,
            "$file tidak boleh menghapus gambar secara manual (gunakan delete_uploaded_image)"
        );
        $this->assertStringNotContainsString(
            "file_exists('../' .",
            $content,
            "$file tidak boleh mengecek path uploads secara relatif (gunakan delete_uploaded_image)"
        );
    }

    public static function provideAdminPagesThatDeleteImages(): array
    {
        return [
            ['index.php'],
            ['manage_hero.php'],
            ['manage_items.php'],
            ['edit_product.php'],
            ['edit_item.php'],
        ];
    }

    public function testRequiringBothHelpersInAnyOrderDoesNotRedeclare(): void
    {
        require_once self::UPLOAD_HELPER;
        require_once self::DB_HELPER;
        require_once self::UPLOAD_HELPER;

        foreach (['delete_uploaded_image', 'uploads_directory', 'store_uploaded_image', 'compress_uploaded_image'] as $fn) {
            $this->assertTrue(function_exists($fn), "Fungsi $fn harus tersedia setelah require helper");
        }
    }
}

