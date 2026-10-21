<?php
use PHPUnit\Framework\TestCase;

/**
 * Test sintaks PHP pada file-file yang terdampak perubahan / refactor.
 * Menjalankan php -l (lint) untuk memastikan tidak ada syntax error.
 */
class SyntaxTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../';

    private function getPhpBinary(): string
    {
        if (defined('PHP_BINARY') && is_file(PHP_BINARY)) {
            return PHP_BINARY;
        }
        return 'C:\xampp\php\php.exe';
    }

    public function testAllAdminPhpFilesHaveValidSyntax(): void
    {
        $adminDir = self::PROJECT_ROOT . 'admin';
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($adminDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        $failed = [];
        $php = $this->getPhpBinary();
        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $lint = shell_exec(sprintf('"%s" -l "%s" 2>&1', $php, $file->getRealPath()));
            if (strpos($lint, 'No syntax errors') === false) {
                $failed[] = $file->getBasename() . ': ' . trim($lint);
            }
        }

        $this->assertEmpty($failed, 'Beberapa file PHP admin memiliki syntax error: ' . implode("\n", $failed));
    }

    public function testCoreModifiedFilesHaveValidSyntax(): void
    {
        $coreFiles = [
            'includes/DatabaseMigrator.php',
            'database/migrate.php',
            'includes/fallback_logo.php',
            'includes/env_loader.php',
            'config.example.php',
            'thumb.php',
        ];

        $php = $this->getPhpBinary();
        foreach ($coreFiles as $rel) {
            $path = self::PROJECT_ROOT . $rel;
            $this->assertFileExists($path, "File $rel harus ada");
            $lint = shell_exec(sprintf('"%s" -l "%s" 2>&1', $php, $path));
            $this->assertStringContainsString('No syntax errors', (string)$lint, "Syntax error di $rel: " . trim((string)$lint));
        }
    }

    public function testAdminCssIsValidCss(): void
    {
        $cssFile = self::PROJECT_ROOT . 'admin/assets/css/admin.css';
        $this->assertFileExists($cssFile, 'File admin.css harus ada');
        $css = file_get_contents($cssFile);
        $this->assertStringContainsString(':root', $css, 'CSS harus memiliki variabel :root');
        $this->assertStringContainsString('body', $css, 'CSS harus memiliki style body');
        $this->assertNotEmpty($css, 'CSS tidak boleh kosong');
    }
}