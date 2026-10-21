<?php
use PHPUnit\Framework\TestCase;

/**
 * Test sintaks PHP pada semua file yang diubah.
 * Menjalankan php -l (lint) untuk memastikan tidak ada syntax error.
 */
class SyntaxTest extends TestCase
{
    private const PHP_BINARY = 'C:\xampp\php\php.exe';
    private const PROJECT_ROOT = __DIR__ . '/../../';

    public function testAllAdminPhpFilesHaveValidSyntax(): void
    {
        $adminDir = self::PROJECT_ROOT . 'admin';
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($adminDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        $failed = [];
        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $lint = shell_exec(sprintf('"%s" -l "%s" 2>&1', self::PHP_BINARY, $file->getRealPath()));
            if (strpos($lint, 'No syntax errors') === false) {
                $failed[] = $file->getBasename() . ': ' . trim($lint);
            }
        }

        $this->assertEmpty($failed, 'Beberapa file PHP memiliki syntax error: ' . implode("\n", $failed));
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