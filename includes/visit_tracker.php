<?php
/**
 * Pencatat & pembaca statistik kunjungan sederhana untuk Alfi Kitchen.
 * Menyimpan rekap harian tanpa menyimpan IP mentah pengunjung.
 */

if (!function_exists('visit_tracker_is_bot_request')) {
    function visit_tracker_is_bot_request(?array $server = null): bool
    {
        $srv = $server ?? $_SERVER;
        $method = strtoupper((string) ($srv['REQUEST_METHOD'] ?? 'GET'));
        if ($method !== 'GET') {
            return true;
        }

        $purpose = strtolower((string) ($srv['HTTP_PURPOSE'] ?? $srv['HTTP_X_PURPOSE'] ?? $srv['HTTP_SEC_PURPOSE'] ?? ''));
        if ($purpose !== '' && str_contains($purpose, 'prefetch')) {
            return true;
        }

        $ua = trim((string) ($srv['HTTP_USER_AGENT'] ?? ''));
        if ($ua === '') {
            return true;
        }

        return preg_match(
            '/(bot|crawl|spider|slurp|mediapartners|facebookexternalhit|whatsapp|telegrambot|discordbot|curl|wget|python-requests|go-http-client|headless|lighthouse|pagespeed|gtmetrix|uptime|pingdom|ahrefs|semrush|mj12bot)/i',
            $ua
        ) === 1;
    }
}

if (!function_exists('visit_tracker_is_admin_session')) {
    function visit_tracker_is_admin_session(): bool
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return !empty($_SESSION['admin_logged_in']);
        }
        $sessionName = session_name();
        if ($sessionName !== '' && isset($_COOKIE[$sessionName])) {
            @session_start(['read_and_close' => true]);
            return !empty($_SESSION['admin_logged_in']);
        }
        return false;
    }
}

if (!function_exists('visit_tracker_tables_ready')) {
    function visit_tracker_tables_ready(PDO $pdo): bool
    {
        try {
            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $rows = $pdo->query(
                    "SELECT name FROM sqlite_master WHERE type = 'table' AND name IN ('site_visit_stats', 'site_visit_uniques')"
                )->fetchAll(PDO::FETCH_COLUMN);
                return count($rows) === 2;
            }
            $rows = $pdo->query("SHOW TABLES LIKE 'site_visit_%'")->fetchAll(PDO::FETCH_COLUMN);
            return in_array('site_visit_stats', $rows, true) && in_array('site_visit_uniques', $rows, true);
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('visit_tracker_insert_unique')) {
    function visit_tracker_insert_unique(PDO $pdo, string $date, string $pageKey, string $visitorHash): bool
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $sql = $driver === 'sqlite'
            ? 'INSERT OR IGNORE INTO site_visit_uniques (visit_date, page_key, visitor_hash) VALUES (?, ?, ?)'
            : 'INSERT IGNORE INTO site_visit_uniques (visit_date, page_key, visitor_hash) VALUES (?, ?, ?)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$date, $pageKey, $visitorHash]);
        return $stmt->rowCount() > 0;
    }
}

if (!function_exists('visit_tracker_upsert_stat')) {
    function visit_tracker_upsert_stat(PDO $pdo, string $date, string $pageKey, string $pageLabel, int $viewsDelta, int $uniqueDelta): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmt = $pdo->prepare(
                'INSERT INTO site_visit_stats (visit_date, page_key, page_label, views_count, unique_count) '
                . 'VALUES (?, ?, ?, ?, ?) '
                . 'ON CONFLICT(visit_date, page_key) DO UPDATE SET '
                . 'page_label = excluded.page_label, '
                . 'views_count = views_count + excluded.views_count, '
                . 'unique_count = unique_count + excluded.unique_count'
            );
            $stmt->execute([$date, $pageKey, $pageLabel, $viewsDelta, $uniqueDelta]);
            return;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO site_visit_stats (visit_date, page_key, page_label, views_count, unique_count) '
            . 'VALUES (?, ?, ?, ?, ?) '
            . 'ON DUPLICATE KEY UPDATE '
            . 'page_label = VALUES(page_label), '
            . 'views_count = views_count + VALUES(views_count), '
            . 'unique_count = unique_count + VALUES(unique_count)'
        );
        $stmt->execute([$date, $pageKey, $pageLabel, $viewsDelta, $uniqueDelta]);
    }
}

if (!function_exists('record_site_visit')) {
    function record_site_visit(PDO $pdo, string $pageKey, string $pageLabel, array $options = []): bool
    {
        if (empty($options['allow_cli']) && PHP_SAPI === 'cli') {
            return false;
        }
        $server = $options['server'] ?? $_SERVER;
        if (visit_tracker_is_bot_request($server)) {
            return false;
        }
        if (empty($options['ignore_admin_check']) && visit_tracker_is_admin_session()) {
            return false;
        }
        if (!visit_tracker_tables_ready($pdo)) {
            return false;
        }

        $cleanKey = substr(trim($pageKey), 0, 120);
        $cleanLabel = substr(trim($pageLabel), 0, 160);
        if ($cleanKey === '' || $cleanKey === '__site__') {
            return false;
        }
        if ($cleanLabel === '') {
            $cleanLabel = $cleanKey;
        }

        $today = (string) ($options['date'] ?? date('Y-m-d'));
        $visitorHash = hash(
            'sha256',
            $today . '|' . ($server['REMOTE_ADDR'] ?? '') . '|' . ($server['HTTP_USER_AGENT'] ?? '')
        );

        try {
            $isNewPageUnique = visit_tracker_insert_unique($pdo, $today, $cleanKey, $visitorHash);
            visit_tracker_upsert_stat($pdo, $today, $cleanKey, $cleanLabel, 1, $isNewPageUnique ? 1 : 0);

            $isNewSiteUnique = visit_tracker_insert_unique($pdo, $today, '__site__', $visitorHash);
            visit_tracker_upsert_stat($pdo, $today, '__site__', 'Total Pengunjung', 0, $isNewSiteUnique ? 1 : 0);

            $cutoff = date('Y-m-d', strtotime($today . ' -2 days'));
            $pruneStmt = $pdo->prepare('DELETE FROM site_visit_uniques WHERE visit_date < ?');
            $pruneStmt->execute([$cutoff]);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('get_site_visit_summary')) {
    function get_site_visit_summary(PDO $pdo, ?string $today = null): array
    {
        $empty = [
            'ready' => false,
            'today' => ['views' => 0, 'uniques' => 0],
            'last_7_days' => ['views' => 0, 'uniques' => 0],
            'last_30_days' => ['views' => 0, 'uniques' => 0],
            'top_pages' => [],
        ];

        if (!visit_tracker_tables_ready($pdo)) {
            return $empty;
        }

        $todayDate = $today ?? date('Y-m-d');
        $date7 = date('Y-m-d', strtotime($todayDate . ' -6 days'));
        $date30 = date('Y-m-d', strtotime($todayDate . ' -29 days'));

        $rangeStats = static function (string $fromDate) use ($pdo, $todayDate): array {
            $viewsStmt = $pdo->prepare(
                "SELECT COALESCE(SUM(views_count), 0) FROM site_visit_stats WHERE page_key <> '__site__' AND visit_date BETWEEN ? AND ?"
            );
            $viewsStmt->execute([$fromDate, $todayDate]);
            $views = (int) $viewsStmt->fetchColumn();

            $uniquesStmt = $pdo->prepare(
                "SELECT COALESCE(SUM(unique_count), 0) FROM site_visit_stats WHERE page_key = '__site__' AND visit_date BETWEEN ? AND ?"
            );
            $uniquesStmt->execute([$fromDate, $todayDate]);
            $uniques = (int) $uniquesStmt->fetchColumn();

            return ['views' => $views, 'uniques' => $uniques];
        };

        $topStmt = $pdo->prepare(
            "SELECT page_key, MAX(page_label) AS page_label, SUM(views_count) AS views, SUM(unique_count) AS uniques "
            . "FROM site_visit_stats "
            . "WHERE page_key <> '__site__' AND visit_date BETWEEN ? AND ? "
            . "GROUP BY page_key "
            . "ORDER BY views DESC, uniques DESC "
            . "LIMIT 5"
        );
        $topStmt->execute([$date30, $todayDate]);
        $topPages = [];
        foreach ($topStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $topPages[] = [
                'page_key' => (string) $row['page_key'],
                'page_label' => (string) $row['page_label'],
                'views' => (int) $row['views'],
                'uniques' => (int) $row['uniques'],
            ];
        }

        return [
            'ready' => true,
            'today' => $rangeStats($todayDate),
            'last_7_days' => $rangeStats($date7),
            'last_30_days' => $rangeStats($date30),
            'top_pages' => $topPages,
        ];
    }
}
