<?php
/**
 * Test Helper Functions untuk Alfi Kitchen
 */

if (!function_exists('resolve_css_imports')) {
    /**
     * Membaca berkas CSS dan menggabungkan isi berkas yang di-@import secara rekursif.
     */
    function resolve_css_imports(string $filePath): string
    {
        if (!file_exists($filePath)) {
            return '';
        }
        $content = file_get_contents($filePath);
        if (preg_match_all('/@import\s+[\'"]([^\'"]+)[\'"]\s*;/', $content, $matches)) {
            $baseDir = dirname($filePath);
            $resolved = $content;
            foreach ($matches[1] as $importFile) {
                $subFile = $baseDir . '/' . $importFile;
                if (file_exists($subFile)) {
                    $resolved .= "\n" . resolve_css_imports($subFile);
                }
            }
            return $resolved;
        }
        return $content;
    }
}
