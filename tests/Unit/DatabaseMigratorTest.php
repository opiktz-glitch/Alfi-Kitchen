<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../includes/DatabaseMigrator.php';

class DatabaseMigratorTest extends TestCase
{
    private const MIGRATIONS_DIR = __DIR__ . '/../../database/migrations';

    public function testMigrationsDirectoryExists(): void
    {
        $this->assertDirectoryExists(self::MIGRATIONS_DIR, 'Folder database/migrations harus ada');
    }

    public function testInitialMigrationsExist(): void
    {
        $expectedFiles = [
            'v01_initial_schema.sql',
            'v02_create_login_attempts.sql',
            'v03_settings_utf8mb4.sql',
            'v04_add_product_slug.sql',
            'v05_hpp.sql',
        ];

        foreach ($expectedFiles as $file) {
            $path = self::MIGRATIONS_DIR . '/' . $file;
            $this->assertFileExists($path, "File migrasi $file harus ada");
            $content = file_get_contents($path);
            $this->assertNotEmpty(trim($content), "File migrasi $file tidak boleh kosong");
        }
    }

    public function testMigratorScansAndSortsMigrations(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $migrator = new DatabaseMigrator($pdo, self::MIGRATIONS_DIR);

        $available = $migrator->getAvailableMigrations();

        $this->assertGreaterThanOrEqual(5, count($available));
        $this->assertSame('v01_initial_schema.sql', $available[0]);
        $this->assertSame('v02_create_login_attempts.sql', $available[1]);
        $this->assertSame('v03_settings_utf8mb4.sql', $available[2]);
        $this->assertSame('v04_add_product_slug.sql', $available[3]);
        $this->assertSame('v05_hpp.sql', $available[4]);
    }

    public function testHppMigrationDefinesExpectedTablesAndProductPriceRelation(): void
    {
        $migration = file_get_contents(self::MIGRATIONS_DIR . '/v05_hpp.sql');

        foreach (['hpp_ingredients', 'hpp_recipes', 'hpp_recipe_lines', 'hpp_recipe_costs'] as $table) {
            $this->assertStringContainsString("CREATE TABLE IF NOT EXISTS `$table`", $migration);
        }

        $this->assertStringContainsString('`product_item_id` INT NULL', $migration);
        $this->assertStringContainsString('FOREIGN KEY (`product_item_id`) REFERENCES `product_items` (`id`) ON DELETE SET NULL', preg_replace('/\s+/', ' ', $migration));
        $this->assertStringContainsString('UNIQUE KEY `uq_hpp_recipe_item` (`product_item_id`)', $migration);
        $this->assertStringContainsString('ON DELETE RESTRICT', $migration);
        $this->assertStringContainsString('ON DELETE CASCADE', $migration);
        $this->assertStringContainsString("'hpp_default_costs'", $migration);
        $this->assertStringContainsString("'hpp_margins'", $migration);
        $this->assertStringContainsString("'hpp_round_step'", $migration);
    }

    public function testEnsureTrackingTableAndStatus(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $migrator = new DatabaseMigrator($pdo, self::MIGRATIONS_DIR);

        $migrator->ensureTrackingTable();

        $applied = $migrator->getAppliedMigrations();
        $this->assertIsArray($applied);
        $this->assertEmpty($applied);

        $pending = $migrator->getPendingMigrations();
        $this->assertGreaterThanOrEqual(5, count($pending));

        $statusList = $migrator->getStatus();
        $this->assertNotEmpty($statusList);
        $this->assertSame('pending', $statusList[0]['status']);
    }

    public function testMigratorAppliesMigrationsInSqlite(): void
    {
        $tempDir = sys_get_temp_dir() . '/alfi_test_migrations_' . uniqid();
        mkdir($tempDir);

        file_put_contents($tempDir . '/001_test_table.sql', "CREATE TABLE sample (id INT PRIMARY KEY, name TEXT);");
        file_put_contents($tempDir . '/002_insert_sample.sql', "INSERT INTO sample (id, name) VALUES (1, 'Alfi');");

        $pdo = new PDO('sqlite::memory:');
        $migrator = new DatabaseMigrator($pdo, $tempDir);

        $result = $migrator->migrate();

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['executed']);

        $applied = $migrator->getAppliedMigrations();
        $this->assertArrayHasKey('001_test_table.sql', $applied);
        $this->assertArrayHasKey('002_insert_sample.sql', $applied);

        $pending = $migrator->getPendingMigrations();
        $this->assertEmpty($pending);

        // Bersihkan temp dir
        @unlink($tempDir . '/001_test_table.sql');
        @unlink($tempDir . '/002_insert_sample.sql');
        @rmdir($tempDir);
    }
}
