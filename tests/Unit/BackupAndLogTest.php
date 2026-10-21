<?php
use PHPUnit\Framework\TestCase;

/**
 * Test proteksi cooldown rate-limit pada endpoint backup dan rotasi log admin.
 */
class BackupAndLogTest extends TestCase
{
    private const AUTH_FILE = __DIR__ . '/../../admin/auth.php';
    private const EXPORT_DB_FILE = __DIR__ . '/../../admin/export_db.php';
    private const EXPORT_IMAGES_FILE = __DIR__ . '/../../admin/export_images.php';
    private const ACTIVITY_LOG_FILE = __DIR__ . '/../../admin/activity_log.php';

    protected function setUp(): void
    {
        require_once self::AUTH_FILE;
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
}
