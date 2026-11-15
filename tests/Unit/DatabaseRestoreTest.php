<?php
use PHPUnit\Framework\TestCase;

/**
 * Test keamanan dan mekanisme Restore Database Alfi Kitchen.
 */
class DatabaseRestoreTest extends TestCase
{
    private const RESTORE_DB_FILE = __DIR__ . '/../../admin/restore_db.php';
    private const RESTORE_IMAGES_FILE = __DIR__ . '/../../admin/restore_images.php';
    private const SETTINGS_FILE = __DIR__ . '/../../admin/settings.php';
    private const BACKUP_HELPER_FILE = __DIR__ . '/../../admin/helpers/backup_helper.php';
    private const DB_HELPER_FILE = __DIR__ . '/../../admin/helpers/db_helper.php';

    protected function setUp(): void
    {
        require_once self::BACKUP_HELPER_FILE;
        require_once self::DB_HELPER_FILE;
    }

    public function testRestoreScriptContainsSecurityAndIntegrityChecks(): void
    {
        $content = file_get_contents(self::RESTORE_DB_FILE);

        $this->assertStringContainsString("require 'auth_check.php'", $content);
        $this->assertStringContainsString("require 'csrf_check.php'", $content);
        $this->assertStringContainsString('extract_sql_backup_metadata', $content);
        $this->assertStringContainsString('verify_backup_schema_compatibility', $content);
        $this->assertStringContainsString('validate_sql_dump_safety', $content);
        $this->assertStringContainsString('execute_database_restore', $content);
        $this->assertStringContainsString('log_admin_action', $content);
        $this->assertStringContainsString('set_admin_flash', $content);
    }

    public function testSplitSqlDumpStatementsPreservesSemicolonsInsideStrings(): void
    {
        $sql = "SET FOREIGN_KEY_CHECKS = 0;\n"
            . "-- Komentar sebaris dengan tanda titik koma; abaikan ini\n"
            . "DROP TABLE IF EXISTS `settings`;\n"
            . "CREATE TABLE `settings` (`key` varchar(50), `val` text);\n"
            . "INSERT INTO `settings` VALUES ('home_desc', 'Puding buah lezat; segar, manis, dan nikmat');\n"
            . "INSERT INTO `settings` VALUES ('note', 'Catatan: ukuran 500ml; cocok untuk acara');\n"
            . "SET FOREIGN_KEY_CHECKS = 1;\n";

        $statements = split_sql_dump_statements($sql);

        $this->assertCount(6, $statements);
        $this->assertSame('SET FOREIGN_KEY_CHECKS = 0', $statements[0]);
        $this->assertSame('DROP TABLE IF EXISTS `settings`', $statements[1]);
        $this->assertSame('CREATE TABLE `settings` (`key` varchar(50), `val` text)', $statements[2]);
        $this->assertStringContainsString("Puding buah lezat; segar, manis, dan nikmat", $statements[3]);
        $this->assertStringContainsString("Catatan: ukuran 500ml; cocok untuk acara", $statements[4]);
        $this->assertSame('SET FOREIGN_KEY_CHECKS = 1', $statements[5]);
    }

    public function testValidateSqlDumpSafetyAllowsWhitelistedTables(): void
    {
        $statements = [
            'SET FOREIGN_KEY_CHECKS = 0',
            'DROP TABLE IF EXISTS `users`',
            'DROP TABLE IF EXISTS `products`',
            'DROP TABLE IF EXISTS `product_items`',
            'DROP TABLE IF EXISTS `hpp_recipes`',
            'CREATE TABLE `users` (`id` int, `username` varchar(50))',
            "INSERT INTO `users` VALUES (1, 'admin')",
            'SET FOREIGN_KEY_CHECKS = 1',
        ];

        $check = validate_sql_dump_safety($statements);
        $this->assertTrue($check['safe'], 'Semua statement pada tabel whitelist harus diizinkan');
    }

    public function testValidateSqlDumpSafetyBlocksUnknownTables(): void
    {
        $statements = [
            'SET FOREIGN_KEY_CHECKS = 0',
            'DROP TABLE IF EXISTS `unknown_table_exploit`',
        ];

        $check = validate_sql_dump_safety($statements);
        $this->assertFalse($check['safe'], 'Tabel di luar whitelist harus ditolak');
        $this->assertStringContainsString('unknown_table_exploit', $check['reason']);
    }

    public function testValidateSqlDumpSafetyBlocksDangerousCommands(): void
    {
        $dangerousPayloads = [
            'CREATE USER evil_user@localhost IDENTIFIED BY "pass"',
            'GRANT ALL PRIVILEGES ON *.* TO evil_user@localhost',
            'DROP DATABASE alfi_kitchen',
            "SELECT * FROM users INTO OUTFILE '/var/www/html/shell.php'",
            "SELECT LOAD_FILE('/etc/passwd')",
            'SHUTDOWN',
        ];

        foreach ($dangerousPayloads as $payload) {
            $check = validate_sql_dump_safety([$payload]);
            $this->assertFalse($check['safe'], 'Perintah berbahaya harus diblokir: ' . $payload);
            $this->assertStringContainsString('dilarang', $check['reason']);
        }
    }

    public function testRestoreImagesScriptContainsSecurityAndUploadsGuard(): void
    {
        $content = file_get_contents(self::RESTORE_IMAGES_FILE);

        $this->assertStringContainsString("require 'auth_check.php'", $content);
        $this->assertStringContainsString("require 'csrf_check.php'", $content);
        $this->assertStringContainsString('uploads_directory()', $content);
        $this->assertStringContainsString('extract_images_archive', $content);
        $this->assertStringContainsString('log_admin_action', $content);
        $this->assertStringContainsString('set_admin_flash', $content);
    }

    public function testValidateZipEntrySafetyAllowsSafeImagePaths(): void
    {
        $uploadsDir = uploads_directory();
        $safeEntries = [
            'hero/slider1.png',
            'products/dessert_box.jpg',
            'logo.webp',
            'items/cup_jelly.jpeg',
            'banner.svg',
        ];

        foreach ($safeEntries as $entry) {
            $check = validate_zip_entry_safety($entry, $uploadsDir);
            $this->assertTrue($check['safe'], "Entri gambar aman harus diizinkan: $entry");
            $this->assertNotNull($check['clean_path']);
            $this->assertNotNull($check['target_path']);
        }
    }

    public function testValidateZipEntrySafetyBlocksZipSlipAndDangerousFiles(): void
    {
        $uploadsDir = uploads_directory();
        $dangerousEntries = [
            '../../index.php',
            '../.env',
            'hero/../../config.php',
            'shell.php',
            '.htaccess',
            'script.sh',
            'malicious.exe',
            'products/exploit.phtml',
            "evil.png\0.php",
            'C:/Windows/System32/calc.exe',
        ];

        foreach ($dangerousEntries as $entry) {
            $check = validate_zip_entry_safety($entry, $uploadsDir);
            $this->assertFalse($check['safe'], "Entri berbahaya harus ditolak: $entry");
            $this->assertNull($check['target_path']);
        }
    }

    public function testSettingsPageIntegratesDatabaseAndImagesRestoreForms(): void
    {
        $settings = file_get_contents(self::SETTINGS_FILE);

        $this->assertStringContainsString('action="restore_db.php"', $settings);
        $this->assertStringContainsString('action="restore_images.php"', $settings);
        $this->assertStringContainsString('name="backup_file"', $settings);
        $this->assertStringContainsString('name="images_zip"', $settings);
        $this->assertStringContainsString('accept=".sql"', $settings);
        $this->assertStringContainsString('accept=".zip,application/zip"', $settings);
        $this->assertStringContainsString('enctype="multipart/form-data"', $settings);
        $this->assertStringContainsString('Restore Database (.sql)', $settings);
        $this->assertStringContainsString('Restore Gambar (.zip)', $settings);
    }
}
