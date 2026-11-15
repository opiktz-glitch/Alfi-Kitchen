<?php
/**
 * Helper untuk operasi metadata backup dan verifikasi skema migrasi database.
 */

declare(strict_types=1);

/**
 * Buat header metadata standar untuk file SQL backup Alfi Kitchen.
 *
 * @param string[] $appliedMigrations Daftar versi file migrasi yang sudah aktif.
 * @param string|null $timestamp Waktu backup (Y-m-d H:i:s).
 * @return string Blok komentar metadata SQL.
 */
function generate_backup_header(array $appliedMigrations, ?string $timestamp = null): string
{
    $timestamp = $timestamp ?? date('Y-m-d H:i:s');
    $normalized = array_values(array_filter(array_map('trim', $appliedMigrations)));
    natcasesort($normalized);
    $normalized = array_values($normalized);

    $latestVersion = !empty($normalized) ? end($normalized) : 'v00_none';
    $migrationsList = implode(',', $normalized);

    $header = "-- ====================================================\n";
    $header .= "-- ALFI_KITCHEN_DATABASE_BACKUP\n";
    $header .= "-- SCHEMA_VERSION: " . $latestVersion . "\n";
    $header .= "-- MIGRATIONS: " . $migrationsList . "\n";
    $header .= "-- EXPORTED_AT: " . $timestamp . "\n";
    $header .= "-- ====================================================";

    return $header;
}

/**
 * Ekstrak metadata skema migrasi dari isi file SQL backup.
 * Mendukung pembacaan header metadata resmi maupun fallback parser dari baris INSERT schema_migrations.
 *
 * @param string $sqlContent Isi file .sql yang diunggah.
 * @return array{
 *     is_valid: bool,
 *     schema_version: ?string,
 *     migrations: string[],
 *     exported_at: ?string,
 *     has_header: bool
 * }
 */
function extract_sql_backup_metadata(string $sqlContent): array
{
    $hasHeader = false;
    $schemaVersion = null;
    $migrations = [];
    $exportedAt = null;

    // 1. Cek Header Metadata Resmi
    if (preg_match('/--\s*ALFI_KITCHEN_(?:DATABASE_)?BACKUP/i', $sqlContent)) {
        $hasHeader = true;

        if (preg_match('/--\s*SCHEMA_VERSION:\s*([^\r\n]+)/i', $sqlContent, $matches)) {
            $schemaVersion = trim($matches[1]);
        }
        if (preg_match('/--\s*MIGRATIONS:\s*([^\r\n]+)/i', $sqlContent, $matches)) {
            $rawList = trim($matches[1]);
            if ($rawList !== '') {
                $migrations = array_values(array_filter(array_map('trim', explode(',', $rawList))));
            }
        }
        if (preg_match('/--\s*EXPORTED_AT:\s*([^\r\n]+)/i', $sqlContent, $matches)) {
            $exportedAt = trim($matches[1]);
        }
    }

    // 2. Fallback: Parse statement INSERT INTO schema_migrations jika header tidak ada / kosong
    if (empty($migrations)) {
        if (preg_match_all('/INSERT\s+INTO\s+[`"]?schema_migrations[`"]?\s*(?:\([^)]*\))?\s*VALUES\s*\((.*?)\);/is', $sqlContent, $insertMatches)) {
            foreach ($insertMatches[1] as $valuesRow) {
                // Ambil string pertama di dalam tanda petik tunggal 'vXX_...'
                if (preg_match("/'([^']+)'/", $valuesRow, $valMatch)) {
                    $ver = trim($valMatch[1]);
                    if ($ver !== '' && !in_array($ver, $migrations, true)) {
                        $migrations[] = $ver;
                    }
                }
            }
        }
    }

    natcasesort($migrations);
    $migrations = array_values($migrations);

    if ($schemaVersion === null && !empty($migrations)) {
        $schemaVersion = end($migrations);
    }

    return [
        'is_valid' => !empty($migrations),
        'schema_version' => $schemaVersion,
        'migrations' => $migrations,
        'exported_at' => $exportedAt,
        'has_header' => $hasHeader,
    ];
}

/**
 * Periksa kompatibilitas skema file backup dengan database sistem saat ini.
 *
 * @param string[] $backupMigrations Daftar migrasi di file backup.
 * @param string[] $systemMigrations Daftar migrasi aktif di sistem.
 * @return array{
 *     compatible: bool,
 *     reason: string,
 *     backup_latest: ?string,
 *     system_latest: ?string
 * }
 */
function verify_backup_schema_compatibility(array $backupMigrations, array $systemMigrations): array
{
    $bNorm = array_values(array_filter(array_map('trim', $backupMigrations)));
    $sNorm = array_values(array_filter(array_map('trim', $systemMigrations)));

    natcasesort($bNorm);
    $bNorm = array_values($bNorm);

    natcasesort($sNorm);
    $sNorm = array_values($sNorm);

    $bLatest = !empty($bNorm) ? end($bNorm) : null;
    $sLatest = !empty($sNorm) ? end($sNorm) : null;

    if (empty($bNorm)) {
        return [
            'compatible' => false,
            'reason' => 'File backup tidak memuat riwayat tabel schema_migrations resmi Alfi Kitchen.',
            'backup_latest' => null,
            'system_latest' => $sLatest,
        ];
    }

    if ($bNorm !== $sNorm) {
        $diffMissingInSystem = array_diff($bNorm, $sNorm);
        $diffMissingInBackup = array_diff($sNorm, $bNorm);

        $details = [];
        if (!empty($diffMissingInBackup)) {
            $details[] = 'Backup kekurangan migrasi: ' . implode(', ', $diffMissingInBackup);
        }
        if (!empty($diffMissingInSystem)) {
            $details[] = 'Backup memuat migrasi yang belum ada di sistem: ' . implode(', ', $diffMissingInSystem);
        }

        return [
            'compatible' => false,
            'reason' => 'Versi skema backup (' . ($bLatest ?? 'tidak diketahui') . ') berbeda dengan versi skema sistem saat ini (' . ($sLatest ?? 'tidak diketahui') . '). ' . implode('. ', $details),
            'backup_latest' => $bLatest,
            'system_latest' => $sLatest,
        ];
    }

    return [
        'compatible' => true,
        'reason' => 'Versi skema cocok (' . ($sLatest ?? 'ok') . ').',
        'backup_latest' => $bLatest,
        'system_latest' => $sLatest,
    ];
}

/**
 * Daftar tabel resmi Alfi Kitchen yang diizinkan dalam file backup.
 */
const ALLOWED_BACKUP_TABLES = [
    'users',
    'login_attempts',
    'products',
    'product_items',
    'hero_images',
    'settings',
    'schema_migrations',
    'hpp_ingredients',
    'hpp_recipes',
    'hpp_recipe_lines',
    'hpp_recipe_costs',
];

/**
 * Pisahkan script SQL dump menjadi query/statement mandiri secara aman.
 * Menghindari pemotongan keliru jika ada tanda titik koma (;) di dalam teks string kuotasi.
 *
 * @param string $sql Isi script SQL.
 * @return string[] Daftar statement SQL bersih.
 */
function split_sql_dump_statements(string $sql): array
{
    $statements = [];
    $current = '';
    $len = strlen($sql);
    $inSingleQuote = false;
    $inDoubleQuote = false;
    $inBacktick = false;
    $inLineComment = false;
    $inBlockComment = false;

    for ($i = 0; $i < $len; $i++) {
        $char = $sql[$i];
        $next = $i + 1 < $len ? $sql[$i + 1] : '';

        // Abaikan komentar sebaris (-- atau #)
        if ($inLineComment) {
            if ($char === "\n") {
                $inLineComment = false;
            }
            continue;
        }

        // Abaikan komentar blok (/* ... */)
        if ($inBlockComment) {
            if ($char === '*' && $next === '/') {
                $inBlockComment = false;
                $i++;
            }
            continue;
        }

        // Deteksi awal komentar jika sedang di luar string
        if (!$inSingleQuote && !$inDoubleQuote && !$inBacktick) {
            if ($char === '-' && $next === '-') {
                $inLineComment = true;
                $i++;
                continue;
            }
            if ($char === '#') {
                $inLineComment = true;
                continue;
            }
            if ($char === '/' && $next === '*') {
                $inBlockComment = true;
                $i++;
                continue;
            }
        }

        // Penanganan tanda kutip tunggal (')
        if ($char === "'" && !$inDoubleQuote && !$inBacktick) {
            if ($inSingleQuote) {
                if ($i > 0 && $sql[$i - 1] === '\\') {
                    // Kutip di-escape dengan backslash: \'
                } elseif ($next === "'") {
                    // Kutip ganda ANSI SQL: ''
                    $current .= "''";
                    $i++;
                    continue;
                } else {
                    $inSingleQuote = false;
                }
            } else {
                $inSingleQuote = true;
            }
            $current .= $char;
            continue;
        }

        // Penanganan tanda kutip ganda (")
        if ($char === '"' && !$inSingleQuote && !$inBacktick) {
            if ($inDoubleQuote) {
                if ($i > 0 && $sql[$i - 1] === '\\') {
                    // Kutip di-escape
                } else {
                    $inDoubleQuote = false;
                }
            } else {
                $inDoubleQuote = true;
            }
            $current .= $char;
            continue;
        }

        // Penanganan backtick (`)
        if ($char === '`' && !$inSingleQuote && !$inDoubleQuote) {
            $inBacktick = !$inBacktick;
            $current .= $char;
            continue;
        }

        // Pemisah query titik koma (;) hanya jika di luar string dan komentar
        if ($char === ';' && !$inSingleQuote && !$inDoubleQuote && !$inBacktick) {
            $stmt = trim($current);
            if ($stmt !== '') {
                $statements[] = $stmt;
            }
            $current = '';
            continue;
        }

        $current .= $char;
    }

    $last = trim($current);
    if ($last !== '') {
        $statements[] = $last;
    }

    return $statements;
}

/**
 * Validasi keamanan perintah SQL dump sebelum dieksekusi.
 *
 * @param string[] $statements Daftar statement SQL.
 * @param string[] $allowedTables Daftar tabel yang diizinkan.
 * @return array{safe: bool, reason: string}
 */
function validate_sql_dump_safety(array $statements, array $allowedTables = ALLOWED_BACKUP_TABLES): array
{
    if (empty($statements)) {
        return ['safe' => false, 'reason' => 'Tidak ada statement SQL yang ditemukan dalam file backup.'];
    }

    $allowedLookup = array_fill_keys(array_map('strtolower', $allowedTables), true);

    foreach ($statements as $stmt) {
        $trimmed = trim($stmt);
        if ($trimmed === '') {
            continue;
        }

        // 1. Blokir perintah berisiko tinggi / arbitrary privilege injection
        if (preg_match('/\b(CREATE\s+USER|GRANT\s+|REVOKE\s+|DROP\s+DATABASE|ALTER\s+USER|INTO\s+OUTFILE|INTO\s+DUMPFILE|SHUTDOWN)\b|\bLOAD_FILE\s*\(/i', $trimmed)) {
            return ['safe' => false, 'reason' => 'File backup memuat perintah berbahaya yang dilarang.'];
        }

        // 2. Izin khusus untuk perintah pengaturan konfigurasi safe
        if (preg_match('/^SET\s+(?:FOREIGN_KEY_CHECKS|foreign_key_checks)\s*=\s*[01]$/i', $trimmed)) {
            continue;
        }

        // 3. Periksa perintah DROP TABLE
        if (preg_match('/^DROP\s+TABLE(?:\s+IF\s+EXISTS)?\s+[`"]?([a-zA-Z0-9_]+)[`"]?$/i', $trimmed, $m)) {
            $tbl = strtolower($m[1]);
            if (!isset($allowedLookup[$tbl])) {
                return ['safe' => false, 'reason' => "Tabel '$tbl' pada perintah DROP TABLE tidak terdaftar dalam whitelist resmi Alfi Kitchen."];
            }
            continue;
        }

        // 4. Periksa perintah CREATE TABLE
        if (preg_match('/^CREATE\s+TABLE(?:\s+IF\s+NOT\s+EXISTS)?\s+[`"]?([a-zA-Z0-9_]+)[`"]?/i', $trimmed, $m)) {
            $tbl = strtolower($m[1]);
            if (!isset($allowedLookup[$tbl])) {
                return ['safe' => false, 'reason' => "Tabel '$tbl' pada perintah CREATE TABLE tidak terdaftar dalam whitelist resmi Alfi Kitchen."];
            }
            continue;
        }

        // 5. Periksa perintah INSERT INTO
        if (preg_match('/^INSERT\s+INTO\s+[`"]?([a-zA-Z0-9_]+)[`"]?/i', $trimmed, $m)) {
            $tbl = strtolower($m[1]);
            if (!isset($allowedLookup[$tbl])) {
                return ['safe' => false, 'reason' => "Tabel '$tbl' pada perintah INSERT INTO tidak terdaftar dalam whitelist resmi Alfi Kitchen."];
            }
            continue;
        }

        return ['safe' => false, 'reason' => 'Perintah SQL tidak dikenali atau tidak diizinkan: ' . substr($trimmed, 0, 50) . '...'];
    }

    return ['safe' => true, 'reason' => 'Semua perintah SQL valid dan aman.'];
}

/**
 * Eksekusi restore database dari daftar query yang sudah tervalidasi.
 *
 * @param PDO $pdo Koneksi PDO.
 * @param string[] $statements Daftar query yang akan dieksekusi.
 * @return int Jumlah statement yang berhasil dieksekusi.
 */
function execute_database_restore(PDO $pdo, array $statements): int
{
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $executed = 0;

    try {
        foreach ($statements as $stmt) {
            $trimmed = trim($stmt);
            if ($trimmed !== '') {
                $pdo->exec($trimmed);
                $executed++;
            }
        }
    } finally {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    return $executed;
}

/**
 * Daftar ekstensi gambar yang diizinkan untuk diekstrak ke folder uploads/.
 */
const ALLOWED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];

/**
 * Validasi keamanan satu entri file di dalam arsip ZIP untuk mencegah Zip Slip dan eksekusi skrip.
 *
 * @param string $entryName Nama entri di dalam file ZIP.
 * @param string $targetBaseDir Direktori dasar tujuan (uploads).
 * @param string[] $allowedExtensions Ekstensi file yang diizinkan.
 * @return array{safe: bool, reason: string, clean_path: ?string, target_path: ?string}
 */
function validate_zip_entry_safety(
    string $entryName,
    string $targetBaseDir,
    array $allowedExtensions = ALLOWED_IMAGE_EXTENSIONS
): array {
    // Normalisasi separator
    $normalized = str_replace('\\', '/', $entryName);
    $normalized = trim($normalized, '/');

    // Cek null byte
    if (str_contains($normalized, "\0")) {
        return ['safe' => false, 'reason' => 'Entri ZIP memuat karakter null byte.', 'clean_path' => null, 'target_path' => null];
    }

    // Cek path traversal
    $segments = explode('/', $normalized);
    foreach ($segments as $segment) {
        if ($segment === '..' || $segment === '.') {
            return ['safe' => false, 'reason' => 'Entri ZIP memuat segmen traversal terlarang (..).', 'clean_path' => null, 'target_path' => null];
        }
    }

    // Cek drive letter Windows (misal C:)
    if (preg_match('/^[a-zA-Z]:/', $normalized)) {
        return ['safe' => false, 'reason' => 'Entri ZIP memuat path absolut drive Windows.', 'clean_path' => null, 'target_path' => null];
    }

    $ext = strtolower(pathinfo($normalized, PATHINFO_EXTENSION));
    if (!in_array($ext, array_map('strtolower', $allowedExtensions), true)) {
        return ['safe' => false, 'reason' => "Ekstensi file '$ext' tidak diizinkan. Hanya file gambar yang dapat dipulihkan.", 'clean_path' => null, 'target_path' => null];
    }

    $realBase = realpath($targetBaseDir);
    if ($realBase === false) {
        return ['safe' => false, 'reason' => 'Direktori tujuan uploads tidak ditemukan.', 'clean_path' => null, 'target_path' => null];
    }

    $targetPath = $realBase . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized);

    // Normalisasi perbandingan path
    $realBaseNorm = str_replace('\\', '/', $realBase);
    $targetPathNorm = str_replace('\\', '/', $targetPath);

    if (!str_starts_with($targetPathNorm, $realBaseNorm . '/')) {
        return ['safe' => false, 'reason' => 'Target ekstraksi berada di luar direktori uploads.', 'clean_path' => null, 'target_path' => null];
    }

    return [
        'safe' => true,
        'reason' => 'Entri aman.',
        'clean_path' => $normalized,
        'target_path' => $targetPath,
    ];
}

/**
 * Ekstrak file gambar dari arsip ZIP secara aman dengan pembatasan Zip Bomb dan validasi Zip Slip.
 *
 * @param string $zipFilePath Path file ZIP fisik di server.
 * @param string $targetBaseDir Direktori dasar uploads.
 * @param int $maxFiles Batas maksimal jumlah file yang diekstrak.
 * @param int $maxTotalBytes Batas maksimal total ukuran dekompresi (default: 150 MB).
 * @return array{success: bool, extracted_count: int, skipped_count: int, error: ?string}
 */
function extract_images_archive(
    string $zipFilePath,
    string $targetBaseDir,
    int $maxFiles = 500,
    int $maxTotalBytes = 157286400
): array {
    if (!extension_loaded('zip')) {
        return ['success' => false, 'extracted_count' => 0, 'skipped_count' => 0, 'error' => 'Ekstensi PHP ZIP tidak aktif di server ini.'];
    }

    $zip = new ZipArchive();
    $openRes = $zip->open($zipFilePath);
    if ($openRes !== true) {
        return ['success' => false, 'extracted_count' => 0, 'skipped_count' => 0, 'error' => 'Gagal membuka file arsip ZIP (Kode: ' . $openRes . ').'];
    }

    $totalFiles = $zip->numFiles;
    if ($totalFiles > $maxFiles) {
        $zip->close();
        return ['success' => false, 'extracted_count' => 0, 'skipped_count' => 0, 'error' => "Jumlah file di dalam arsip ($totalFiles) melebihi batas maksimal ($maxFiles)."];
    }

    $extractedCount = 0;
    $skippedCount = 0;
    $totalExtractedBytes = 0;

    for ($i = 0; $i < $totalFiles; $i++) {
        $stat = $zip->statIndex($i);
        if ($stat === false) {
            continue;
        }

        $entryName = $stat['name'];
        // Jika entri berupa folder saja (diakhiri /), lewati
        if (str_ends_with(str_replace('\\', '/', $entryName), '/')) {
            continue;
        }

        $check = validate_zip_entry_safety($entryName, $targetBaseDir);
        if (!$check['safe']) {
            $skippedCount++;
            continue;
        }

        $uncompressedSize = (int) ($stat['size'] ?? 0);
        $totalExtractedBytes += $uncompressedSize;
        if ($totalExtractedBytes > $maxTotalBytes) {
            $zip->close();
            return [
                'success' => false,
                'extracted_count' => $extractedCount,
                'skipped_count' => $skippedCount,
                'error' => 'Ukuran dekompresi file melebihi batas aman (Zip Bomb prevention).',
            ];
        }

        $destPath = $check['target_path'];
        $destDir = dirname($destPath);
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        $stream = $zip->getStream($entryName);
        if ($stream === false) {
            $skippedCount++;
            continue;
        }

        $outFp = fopen($destPath, 'wb');
        if ($outFp === false) {
            fclose($stream);
            $skippedCount++;
            continue;
        }

        stream_copy_to_stream($stream, $outFp);
        fclose($stream);
        fclose($outFp);

        $extractedCount++;
    }

    $zip->close();

    return [
        'success' => true,
        'extracted_count' => $extractedCount,
        'skipped_count' => $skippedCount,
        'error' => null,
    ];
}

/**
 * Baca aktivitas restore terakhir dari log aktivitas admin.
 *
 * @param string|null $logPath Path kustom ke file log (opsional, untuk testing).
 * @param string|null $filterType Filter jenis restore: 'database', 'images', atau null untuk semua.
 * @return array{
 *     timestamp: string,
 *     formatted_date: string,
 *     action: string,
 *     username: string,
 *     type: string,
 *     status: string,
 *     file: string,
 *     schema: ?string,
 *     queries: ?int,
 *     extracted: ?int,
 *     skipped: ?int,
 *     error: ?string,
 *     summary: string,
 *     details: string
 * }|null Mengembalikan data restore terakhir atau null jika belum ada.
 */
function get_latest_restore_activity(?string $logPath = null, ?string $filterType = null): ?array
{
    $logFile = $logPath ?? (__DIR__ . '/../../logs/admin_activity.log');
    if (!is_file($logFile) || !is_readable($logFile)) {
        return null;
    }

    $lines = @file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false || empty($lines)) {
        return null;
    }

    // Cari dari baris paling akhir (terbaru)
    for ($i = count($lines) - 1; $i >= 0; $i--) {
        $line = trim($lines[$i]);
        if (!str_contains($line, 'action=RESTORE_')) {
            continue;
        }

        // Format log: Y-m-d H:i:s | ACTION | username=... | action=... | details=... | ip=...
        $pattern = '/^(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})\s*\|\s*ACTION\s*\|\s*username=([^|]+)\s*\|\s*action=(RESTORE_[A-Za-z0-9_]+)\s*\|\s*details=(.*?)(?:\s*\|\s*ip=(.*))?$/';
        if (!preg_match($pattern, $line, $matches)) {
            continue;
        }

        $rawDate = $matches[1];
        $username = trim($matches[2]);
        $action = trim($matches[3]);
        $detailsRaw = trim($matches[4]);

        $type = null;
        if (str_starts_with($action, 'RESTORE_DATABASE')) {
            $type = 'database';
        } elseif (str_starts_with($action, 'RESTORE_IMAGES')) {
            $type = 'images';
        } else {
            continue;
        }

        if ($filterType !== null && $type !== $filterType) {
            continue;
        }

        $status = str_ends_with($action, '_SUCCESS') ? 'success' : 'failed';

        // Parse key=value dari string details
        $detailsMap = [];
        $parts = explode(',', $detailsRaw);
        foreach ($parts as $part) {
            $kv = explode('=', trim($part), 2);
            if (count($kv) === 2) {
                $detailsMap[trim($kv[0])] = trim($kv[1]);
            }
        }

        $file = $detailsMap['file'] ?? '';
        $schema = $detailsMap['schema'] ?? null;
        $queries = isset($detailsMap['queries']) ? (int) $detailsMap['queries'] : null;
        $extracted = isset($detailsMap['extracted']) ? (int) $detailsMap['extracted'] : null;
        $skipped = isset($detailsMap['skipped']) ? (int) $detailsMap['skipped'] : null;
        $error = $detailsMap['error'] ?? $detailsMap['reason'] ?? null;

        $timeVal = strtotime($rawDate);
        $formattedDate = $timeVal ? date('d M Y, H:i', $timeVal) . ' WIB' : $rawDate;

        $summary = '';
        if ($type === 'database') {
            if ($status === 'success') {
                $summary = 'Berhasil dipulihkan (' . ($queries !== null ? $queries . ' kueri dieksekusi' : 'selesai') . ($schema ? ', skema ' . $schema : '') . ')';
            } else {
                $summary = 'Restore gagal: ' . ($error ?? 'Terjadi kesalahan');
            }
        } else {
            if ($status === 'success') {
                $summary = 'Berhasil dipulihkan (' . ($extracted ?? 0) . ' file diekstrak' . ($skipped ? ', ' . $skipped . ' diabaikan' : '') . ')';
            } else {
                $summary = 'Restore gagal: ' . ($error ?? 'Terjadi kesalahan');
            }
        }

        return [
            'timestamp' => $rawDate,
            'formatted_date' => $formattedDate,
            'action' => $action,
            'username' => $username,
            'type' => $type,
            'status' => $status,
            'file' => $file,
            'schema' => $schema,
            'queries' => $queries,
            'extracted' => $extracted,
            'skipped' => $skipped,
            'error' => $error,
            'summary' => $summary,
            'details' => $detailsRaw,
        ];
    }

    return null;
}
