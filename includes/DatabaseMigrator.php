<?php
/**
 * Database Migration Manager untuk Alfi Kitchen.
 *
 * Mengelola riwayat dan eksekusi migrasi database berversi agar
 * database lokal (XAMPP) dan hosting (InfinityFree) selalu sinkron.
 */

class DatabaseMigrator
{
    private PDO $pdo;
    private string $migrationsDir;

    public function __construct(PDO $pdo, ?string $migrationsDir = null)
    {
        $this->pdo = $pdo;
        $this->migrationsDir = $migrationsDir ?? __DIR__ . '/../database/migrations';
    }

    /**
     * Pastikan tabel tracking schema_migrations ada di database.
     */
    public function ensureTrackingTable(): void
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $options = $driver === 'mysql' ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';

        $sql = "CREATE TABLE IF NOT EXISTS schema_migrations (
            version VARCHAR(190) NOT NULL,
            applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (version)
        ){$options};";

        $this->pdo->exec($sql);
    }

    /**
     * Dapatkan semua nama file migrasi yang tersedia di folder migrations, urut alfabet.
     *
     * @return string[]
     */
    public function getAvailableMigrations(): array
    {
        if (!is_dir($this->migrationsDir)) {
            return [];
        }

        $files = scandir($this->migrationsDir);
        if ($files === false) {
            return [];
        }

        $migrations = [];
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'sql' && preg_match('/^(v[0-9]+|[0-9]+)_.*\.sql$/i', $file)) {
                $migrations[] = $file;
            }
        }

        natcasesort($migrations);
        return array_values($migrations);
    }

    /**
     * Dapatkan daftar versi migrasi yang sudah pernah diterapkan di database.
     *
     * @return array<string, string> [version => applied_at]
     */
    public function getAppliedMigrations(): array
    {
        $this->ensureTrackingTable();

        $stmt = $this->pdo->query("SELECT version, applied_at FROM schema_migrations ORDER BY version ASC");
        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[$row['version']] = (string) $row['applied_at'];
        }

        return $results;
    }

    /**
     * Dapatkan daftar migrasi yang belum dijalankan (pending).
     *
     * @return string[]
     */
    public function getPendingMigrations(): array
    {
        $available = $this->getAvailableMigrations();
        $applied = $this->getAppliedMigrations();

        return array_values(array_filter($available, function ($file) use ($applied) {
            return !isset($applied[$file]);
        }));
    }

    /**
     * Dapatkan ringkasan status semua migrasi (versi, status, tanggal eksekusi).
     *
     * @return array[]
     */
    public function getStatus(): array
    {
        $available = $this->getAvailableMigrations();
        $applied = $this->getAppliedMigrations();

        $statusList = [];
        foreach ($available as $file) {
            $isApplied = isset($applied[$file]);
            $statusList[] = [
                'version' => $file,
                'status' => $isApplied ? 'applied' : 'pending',
                'applied_at' => $isApplied ? $applied[$file] : null,
            ];
        }

        return $statusList;
    }

    /**
     * Jalankan seluruh migrasi yang berstatus pending.
     *
     * @return array [success => bool, executed => string[], errors => string[]]
     */
    public function migrate(): array
    {
        $this->ensureTrackingTable();
        $pending = $this->getPendingMigrations();

        $executed = [];
        $errors = [];

        foreach ($pending as $file) {
            $filePath = $this->migrationsDir . '/' . $file;
            $sqlContent = file_get_contents($filePath);

            if ($sqlContent === false) {
                $errors[] = "Gagal membaca file migrasi: $file";
                break;
            }

            try {
                $this->executeSqlStatements($sqlContent);

                // Catat ke tabel tracking (CURRENT_TIMESTAMP adalah ANSI SQL kompatibel MySQL & SQLite)
                $stmt = $this->pdo->prepare("INSERT INTO schema_migrations (version, applied_at) VALUES (?, CURRENT_TIMESTAMP)");
                $stmt->execute([$file]);

                $executed[] = $file;
            } catch (Throwable $e) {
                $errors[] = "Error pada migrasi [$file]: " . $e->getMessage();
                break; // Hentikan eksekusi jika terjadi error
            }
        }

        return [
            'success' => empty($errors),
            'executed' => $executed,
            'errors' => $errors,
        ];
    }

    /**
     * Eksekusi blok script SQL yang mungkin berisi beberapa query terpisah titik koma.
     */
    private function executeSqlStatements(string $sql): void
    {
        // Bersihkan komentar baris SQL (-- ...)
        $lines = explode("\n", $sql);
        $cleanLines = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed !== '' && strpos($trimmed, '--') !== 0 && strpos($trimmed, '#') !== 0) {
                $cleanLines[] = $line;
            }
        }

        $cleanSql = implode("\n", $cleanLines);

        // Pisahkan per query berdasarkan titik koma di akhir baris / statement
        $queries = array_filter(array_map('trim', explode(';', $cleanSql)));

        foreach ($queries as $query) {
            if ($query !== '') {
                try {
                    $this->pdo->exec($query);
                } catch (PDOException $e) {
                    // 1060: Duplicate column name (mis. kolom sudah ada)
                    // 1061: Duplicate key name (mis. index sudah ada)
                    // 1050: Table already exists
                    $errorCode = (int) ($e->errorInfo[1] ?? 0);
                    if (in_array($errorCode, [1050, 1060, 1061], true)) {
                        continue;
                    }
                    throw $e;
                }
            }
        }
    }
}
