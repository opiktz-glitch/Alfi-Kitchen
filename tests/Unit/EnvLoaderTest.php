<?php
use PHPUnit\Framework\TestCase;

/**
 * Test sistem pemisahan konfigurasi lingkungan (.env loader).
 */
class EnvLoaderTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../';
    private const LOADER_FILE = __DIR__ . '/../../includes/env_loader.php';
    private const ENV_EXAMPLE = __DIR__ . '/../../.env.example';
    private const CONFIG_EXAMPLE = __DIR__ . '/../../config.example.php';
    private const GITIGNORE_FILE = __DIR__ . '/../../.gitignore';

    protected function setUp(): void
    {
        require_once self::LOADER_FILE;
    }

    public function testEnvLoaderFileExists(): void
    {
        $this->assertFileExists(self::LOADER_FILE, 'File includes/env_loader.php harus ada');
    }

    public function testExampleFilesExist(): void
    {
        $this->assertFileExists(self::ENV_EXAMPLE, 'File template .env.example harus ada');
        $this->assertFileExists(self::CONFIG_EXAMPLE, 'File template config.example.php harus ada');
    }

    public function testGitIgnoreProtectsEnvFile(): void
    {
        $this->assertFileExists(self::GITIGNORE_FILE, 'File .gitignore harus ada');
        $content = file_get_contents(self::GITIGNORE_FILE);
        $this->assertStringContainsString('.env', $content, '.env harus terdaftar di .gitignore');
    }

    public function testEnvLoaderParsesTempEnvFile(): void
    {
        $tempFile = sys_get_temp_dir() . '/test_env_' . uniqid() . '.env';
        $envData = implode("\n", [
            '# Komentar pengujian',
            'TEST_ALFI_HOST=127.0.0.1',
            'TEST_ALFI_PORT=3306',
            'TEST_ALFI_QUOTED_DOUBLE="quoted value"',
            'TEST_ALFI_QUOTED_SINGLE=\'single quoted\'',
            'TEST_ALFI_EMPTY=',
            '',
        ]);

        file_put_contents($tempFile, $envData);

        try {
            $result = load_env($tempFile);
            $this->assertTrue($result, 'load_env harus mengembalikan true untuk file valid');

            $this->assertSame('127.0.0.1', env('TEST_ALFI_HOST'));
            $this->assertSame('3306', env('TEST_ALFI_PORT'));
            $this->assertSame('quoted value', env('TEST_ALFI_QUOTED_DOUBLE'));
            $this->assertSame('single quoted', env('TEST_ALFI_QUOTED_SINGLE'));
            $this->assertSame('', env('TEST_ALFI_EMPTY'));
        } finally {
            @unlink($tempFile);
        }
    }

    public function testEnvHelperReturnsFallbackWhenKeyNotFound(): void
    {
        $randomKey = 'RANDOM_NOT_EXIST_' . uniqid();
        $this->assertSame('my_fallback', env($randomKey, 'my_fallback'));
        $this->assertNull(env($randomKey));
    }
}
