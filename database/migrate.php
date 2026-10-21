<?php
/**
 * CLI Migration Runner untuk Alfi Kitchen.
 *
 * Cara penggunaan:
 *   php database/migrate.php         -> Jalankan semua migrasi yang belum dieksekusi
 *   php database/migrate.php status  -> Lihat status dan riwayat migrasi database
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Akses ditolak: Skrip ini hanya dapat dijalankan melalui terminal (CLI).\n");
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/DatabaseMigrator.php';

$migrator = new DatabaseMigrator($pdo, __DIR__ . '/migrations');
$command = $argv[1] ?? 'migrate';

echo "========================================\n";
echo "  Alfi Kitchen Database Migrator\n";
echo "========================================\n\n";

if ($command === 'status') {
    $statusList = $migrator->getStatus();

    if (empty($statusList)) {
        echo "Belum ada file migrasi di folder database/migrations/.\n";
        exit(0);
    }

    echo sprintf("%-35s | %-10s | %-20s\n", "Versi Migrasi", "Status", "Waktu Diterapkan");
    echo str_repeat("-", 72) . "\n";

    foreach ($statusList as $item) {
        $statusStr = $item['status'] === 'applied' ? "✓ APPLIED" : "⏳ PENDING";
        $appliedAt = $item['applied_at'] ?? "-";
        echo sprintf("%-35s | %-10s | %-20s\n", $item['version'], $statusStr, $appliedAt);
    }
    echo "\n";
    exit(0);
}

if ($command === 'migrate') {
    $pending = $migrator->getPendingMigrations();

    if (empty($pending)) {
        echo "Database sudah berada pada versi terbaru. Tidak ada migrasi pending.\n";
        exit(0);
    }

    echo "Ditemukan " . count($pending) . " migrasi pending. Memulai eksekusi...\n\n";

    $result = $migrator->migrate();

    foreach ($result['executed'] as $file) {
        echo "  [BERHASIL] Eksekusi: $file\n";
    }

    if (!empty($result['errors'])) {
        echo "\n[GAGAL] Terjadi kesalahan:\n";
        foreach ($result['errors'] as $error) {
            echo "  ✗ $error\n";
        }
        exit(1);
    }

    echo "\nSemua migrasi berhasil diterapkan! Database kini up-to-date.\n";
    exit(0);
}

echo "Perintah tidak dikenal. Gunakan: 'php database/migrate.php' atau 'php database/migrate.php status'\n";
exit(1);
