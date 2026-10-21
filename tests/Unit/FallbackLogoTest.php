<?php
use PHPUnit\Framework\TestCase;

/**
 * Test fallback logo: base64 diekstrak dari template ke file terpisah.
 * Tidak membutuhkan database.
 */
class FallbackLogoTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../';
    private const FALLBACK_FILE = __DIR__ . '/../../includes/fallback_logo.php';
    private const HEADER_FILE = __DIR__ . '/../../header.php';
    private const HOME_VIEW_FILE = __DIR__ . '/../../views/home.view.php';

    public function testFallbackLogoFileExists(): void
    {
        $this->assertFileExists(self::FALLBACK_FILE, 'File includes/fallback_logo.php harus ada');
    }

    public function testFallbackLogoReturnsValidPngDataUri(): void
    {
        $logo = require self::FALLBACK_FILE;

        $this->assertIsString($logo);
        $this->assertStringStartsWith('data:image/png;base64,', $logo);

        $decoded = base64_decode(substr($logo, strlen('data:image/png;base64,')), true);
        $this->assertNotFalse($decoded, 'Base64 fallback logo harus bisa di-decode');
        $this->assertSame("\x89PNG\r\n\x1a\n", substr($decoded, 0, 8), 'Fallback logo harus berupa PNG');
        $this->assertGreaterThan(1000, strlen($decoded), 'Fallback logo terpotong');
    }

    public function testFallbackLogoIsIdempotent(): void
    {
        $first = require self::FALLBACK_FILE;
        $second = require self::FALLBACK_FILE;

        $this->assertSame($first, $second, 'Fallback logo harus sama saat di-require berulang');
        $this->assertTrue(defined('ALFI_FALLBACK_LOGO'), 'Constant guard harus terdefinisi');
    }

    /**
     * @dataProvider provideLogoTemplates
     */
    public function testTemplateHasNoInlineBase64(string $path): void
    {
        $this->assertFileExists($path);
        $this->assertStringNotContainsString(
            'data:image/png;base64,',
            file_get_contents($path),
            'Template tidak boleh lagi memuat base64 logo secara inline'
        );
    }

    /**
     * @dataProvider provideLogoTemplates
     */
    public function testTemplateUsesFallbackLogoVariable(string $path): void
    {
        $content = file_get_contents($path);
        $this->assertStringContainsString('fallback_logo.php', $content);
        $this->assertStringContainsString('$fallbackLogo', $content);
    }

    public function testHeaderRequiresFallbackFromProjectRoot(): void
    {
        $this->assertStringContainsString(
            "require __DIR__ . '/includes/fallback_logo.php'",
            file_get_contents(self::HEADER_FILE)
        );
    }

    public function testHomeViewRequiresFallbackFromProjectRoot(): void
    {
        $this->assertStringContainsString(
            "require __DIR__ . '/../includes/fallback_logo.php'",
            file_get_contents(self::HOME_VIEW_FILE)
        );
    }

    public static function provideLogoTemplates(): array
    {
        return [
            [self::HEADER_FILE],
            [self::HOME_VIEW_FILE],
        ];
    }
}