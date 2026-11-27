<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../includes/visit_tracker.php';

class VisitTrackerTest extends TestCase
{
    private function createDatabase(bool $withTables = true): PDO
    {
        $pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        if ($withTables) {
            $pdo->exec(
                'CREATE TABLE site_visit_stats ('
                . 'id INTEGER PRIMARY KEY AUTOINCREMENT, '
                . 'visit_date TEXT NOT NULL, '
                . 'page_key TEXT NOT NULL, '
                . 'page_label TEXT NOT NULL, '
                . 'views_count INTEGER NOT NULL DEFAULT 0, '
                . 'unique_count INTEGER NOT NULL DEFAULT 0, '
                . 'UNIQUE(visit_date, page_key))'
            );
            $pdo->exec(
                'CREATE TABLE site_visit_uniques ('
                . 'visit_date TEXT NOT NULL, '
                . 'page_key TEXT NOT NULL, '
                . 'visitor_hash TEXT NOT NULL, '
                . 'PRIMARY KEY (visit_date, page_key, visitor_hash))'
            );
        }
        return $pdo;
    }

    public function testGracefullySkipsWhenTablesDoNotExist(): void
    {
        $pdo = $this->createDatabase(false);
        $recorded = record_site_visit($pdo, 'home', 'Beranda', [
            'allow_cli' => true,
            'ignore_admin_check' => true,
            'server' => [
                'REQUEST_METHOD' => 'GET',
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_USER_AGENT' => 'Mozilla/5.0',
            ],
        ]);
        $this->assertFalse($recorded);

        $summary = get_site_visit_summary($pdo);
        $this->assertFalse($summary['ready']);
        $this->assertSame(0, $summary['today']['views']);
    }

    public function testRecordsViewsAndDeduplicatesDailyUniques(): void
    {
        $pdo = $this->createDatabase(true);
        $visitorA = [
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '203.0.113.10',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Linux; Android 14)',
        ];
        $visitorB = [
            'REQUEST_METHOD' => 'GET',
            'REMOTE_ADDR' => '203.0.113.20',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0)',
        ];

        // Visitor A visits home twice and product:1 once on 2026-11-27
        $this->assertTrue(record_site_visit($pdo, 'home', 'Beranda', [
            'allow_cli' => true,
            'ignore_admin_check' => true,
            'date' => '2026-11-27',
            'server' => $visitorA,
        ]));
        $this->assertTrue(record_site_visit($pdo, 'home', 'Beranda', [
            'allow_cli' => true,
            'ignore_admin_check' => true,
            'date' => '2026-11-27',
            'server' => $visitorA,
        ]));
        $this->assertTrue(record_site_visit($pdo, 'product:1', 'Kategori: Puding', [
            'allow_cli' => true,
            'ignore_admin_check' => true,
            'date' => '2026-11-27',
            'server' => $visitorA,
        ]));

        // Visitor B visits home once on 2026-11-27, plus an earlier visit 5 days ago
        $this->assertTrue(record_site_visit($pdo, 'home', 'Beranda', [
            'allow_cli' => true,
            'ignore_admin_check' => true,
            'date' => '2026-11-27',
            'server' => $visitorB,
        ]));
        $this->assertTrue(record_site_visit($pdo, 'about', 'Tentang Kami', [
            'allow_cli' => true,
            'ignore_admin_check' => true,
            'date' => '2026-11-22',
            'server' => $visitorB,
        ]));

        // Bot must be ignored
        $this->assertFalse(record_site_visit($pdo, 'home', 'Beranda', [
            'allow_cli' => true,
            'ignore_admin_check' => true,
            'date' => '2026-11-27',
            'server' => [
                'REQUEST_METHOD' => 'GET',
                'REMOTE_ADDR' => '66.249.66.1',
                'HTTP_USER_AGENT' => 'Mozilla/5.0 (compatible; Googlebot/2.1)',
            ],
        ]));

        $summary = get_site_visit_summary($pdo, '2026-11-27');
        $this->assertTrue($summary['ready']);
        $this->assertSame(4, $summary['today']['views']);
        $this->assertSame(2, $summary['today']['uniques']);
        $this->assertSame(5, $summary['last_7_days']['views']);
        $this->assertSame(3, $summary['last_7_days']['uniques']);
        $this->assertSame('home', $summary['top_pages'][0]['page_key']);
        $this->assertSame(3, $summary['top_pages'][0]['views']);
        $this->assertSame(2, $summary['top_pages'][0]['uniques']);
    }
}
