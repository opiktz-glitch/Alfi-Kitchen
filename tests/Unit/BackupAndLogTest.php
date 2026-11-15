<?php
use PHPUnit\Framework\TestCase;

/**
 * Test proteksi cooldown rate-limit pada endpoint backup dan rotasi log admin.
 */
class BackupAndLogTest extends TestCase
{
    private const AUTH_FILE = __DIR__ . '/../../admin/auth.php';
    private const BACKUP_HELPER_FILE = __DIR__ . '/../../admin/helpers/backup_helper.php';
    private const EXPORT_DB_FILE = __DIR__ . '/../../admin/export_db.php';
    private const EXPORT_IMAGES_FILE = __DIR__ . '/../../admin/export_images.php';
    private const ACTIVITY_LOG_FILE = __DIR__ . '/../../admin/activity_log.php';

    protected function setUp(): void
    {
        require_once self::AUTH_FILE;
        require_once self::BACKUP_HELPER_FILE;
    }

    public function testBackupCooldownEnforcesDelay(): void
    {
        $testType = 'unit_test_' . uniqid();

        // Panggilan pertama harus diizinkan (null)
        $wait1 = check_backup_cooldown($testType, 30);
        $this->assertNull($wait1, 'Panggilan pertama backup harus diizinkan tanpa jeda');

        // Panggilan kedua langsung harus menghasilkan nilai sisa detik
        $wait2 = check_backup_cooldown($testType, 30);
        $this->assertNotNull($wait2, 'Panggilan berulang harus terkena cooldown');
        $this->assertGreaterThan(0, $wait2);
        $this->assertGreaterThanOrEqual(25, $wait2);
    }

    public function testExportScriptsIncludeCooldownGuard(): void
    {
        $dbContent = file_get_contents(self::EXPORT_DB_FILE);
        $this->assertStringContainsString('check_backup_cooldown', $dbContent, 'export_db.php harus memiliki proteksi cooldown');
        $this->assertStringContainsString('backup_cooldown', $dbContent);

        $imgContent = file_get_contents(self::EXPORT_IMAGES_FILE);
        $this->assertStringContainsString('check_backup_cooldown', $imgContent, 'export_images.php harus memiliki proteksi cooldown');
        $this->assertStringContainsString('backup_cooldown', $imgContent);
    }

    public function testDatabaseBackupIncludesHppAndMigrationTables(): void
    {
        $dbContent = file_get_contents(self::EXPORT_DB_FILE);
        foreach ([
            'schema_migrations',
            'hpp_ingredients',
            'hpp_recipes',
            'hpp_recipe_lines',
            'hpp_recipe_costs',
        ] as $table) {
            $this->assertStringContainsString(
                "'" . $table . "'",
                $dbContent,
                'Backup database harus mencakup tabel ' . $table
            );
        }
    }

    public function testActivityLogHasClearActionAndCsrf(): void
    {
        $content = file_get_contents(self::ACTIVITY_LOG_FILE);
        $this->assertStringContainsString("require 'csrf_check.php'", $content, 'activity_log.php harus dilindungi csrf_check');
        $this->assertStringContainsString('clear_admin_activity_log', $content);
        $this->assertStringContainsString('clear_log', $content);
    }

    public function testClearAdminActivityLogEmptiesFile(): void
    {
        $logFile = __DIR__ . '/../../logs/admin_activity.log';
        log_admin_action('UNIT_TEST_LOG', 'timestamp=' . time());
        $this->assertFileExists($logFile);

        $result = clear_admin_activity_log();
        $this->assertTrue($result, 'clear_admin_activity_log harus mengembalikan true');

        $content = file_get_contents($logFile);
        $this->assertStringContainsString('CLEAR_ACTIVITY_LOG', $content, 'Log harus memuat catatan CLEAR_ACTIVITY_LOG');
    }

    public function testGenerateAndExtractBackupHeaderMetadata(): void
    {
        $migrations = [
            'v01_initial_schema.sql',
            'v02_create_login_attempts.sql',
            'v03_settings_utf8mb4.sql',
            'v04_add_product_slug.sql',
            'v05_hpp.sql',
        ];
        $time = '2026-11-15 07:30:00';
        $header = generate_backup_header($migrations, $time);

        $this->assertStringContainsString('ALFI_KITCHEN_DATABASE_BACKUP', $header);
        $this->assertStringContainsString('SCHEMA_VERSION: v05_hpp.sql', $header);
        $this->assertStringContainsString('MIGRATIONS: v01_initial_schema.sql,v02_create_login_attempts.sql,v03_settings_utf8mb4.sql,v04_add_product_slug.sql,v05_hpp.sql', $header);
        $this->assertStringContainsString('EXPORTED_AT: 2026-11-15 07:30:00', $header);

        $parsed = extract_sql_backup_metadata($header);
        $this->assertTrue($parsed['is_valid']);
        $this->assertTrue($parsed['has_header']);
        $this->assertSame('v05_hpp.sql', $parsed['schema_version']);
        $this->assertSame($migrations, $parsed['migrations']);
        $this->assertSame('2026-11-15 07:30:00', $parsed['exported_at']);
    }

    public function testExtractMetadataFallbackFromInsertStatements(): void
    {
        $sql = "-- Export legacy tanpa header khusus\n"
            . "CREATE TABLE `schema_migrations` (`version` varchar(190));\n"
            . "INSERT INTO `schema_migrations` VALUES('v01_initial_schema.sql', '2026-10-21 09:27:00');\n"
            . "INSERT INTO `schema_migrations` VALUES('v02_create_login_attempts.sql', '2026-10-21 09:28:00');\n"
            . "INSERT INTO `schema_migrations` VALUES('v03_settings_utf8mb4.sql', '2026-10-21 09:28:00');\n"
            . "INSERT INTO `schema_migrations` VALUES('v04_add_product_slug.sql', '2026-10-21 09:28:00');\n"
            . "INSERT INTO `schema_migrations` VALUES('v05_hpp.sql', '2026-11-13 17:49:00');\n";

        $parsed = extract_sql_backup_metadata($sql);
        $this->assertTrue($parsed['is_valid']);
        $this->assertFalse($parsed['has_header']);
        $this->assertSame('v05_hpp.sql', $parsed['schema_version']);
        $this->assertCount(5, $parsed['migrations']);
        $this->assertSame('v01_initial_schema.sql', $parsed['migrations'][0]);
        $this->assertSame('v05_hpp.sql', $parsed['migrations'][4]);
    }

    public function testVerifyBackupSchemaCompatibilityRejectsMismatch(): void
    {
        $system = ['v01_initial_schema.sql', 'v02_create_login_attempts.sql', 'v03_settings_utf8mb4.sql', 'v04_add_product_slug.sql', 'v05_hpp.sql'];

        // Skenario 1: Backup versi lama (v04)
        $oldBackup = ['v01_initial_schema.sql', 'v02_create_login_attempts.sql', 'v03_settings_utf8mb4.sql', 'v04_add_product_slug.sql'];
        $checkOld = verify_backup_schema_compatibility($oldBackup, $system);
        $this->assertFalse($checkOld['compatible'], 'Backup versi lama harus ditolak');
        $this->assertStringContainsString('berbeda dengan versi skema sistem', $checkOld['reason']);
        $this->assertStringContainsString('v05_hpp.sql', $checkOld['reason']);

        // Skenario 2: Backup kosong / bukan file Alfi Kitchen
        $emptyBackup = [];
        $checkEmpty = verify_backup_schema_compatibility($emptyBackup, $system);
        $this->assertFalse($checkEmpty['compatible'], 'Backup tanpa tabel migrasi harus ditolak');

        // Skenario 3: Versi identik
        $checkMatch = verify_backup_schema_compatibility($system, $system);
        $this->assertTrue($checkMatch['compatible'], 'Backup versi skema sama harus diterima');
        $this->assertSame('v05_hpp.sql', $checkMatch['system_latest']);
    }

    public function testExportDbFileIncludesMigratorAndHeaderGeneration(): void
    {
        $content = file_get_contents(self::EXPORT_DB_FILE);
        $this->assertStringContainsString("require_once __DIR__ . '/helpers/backup_helper.php'", $content);
        $this->assertStringContainsString('DatabaseMigrator', $content);
        $this->assertStringContainsString('generate_backup_header', $content);
    }
}
