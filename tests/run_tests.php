<?php
/**
 * Test Runner Alfi Kitchen
 *
 * Menjalankan unit test suite sesuai alur perubahan aktif:
 * 1. Syntax integrity (Admin files, DatabaseMigrator, migrate.php, thumb.php, fallback_logo.php)
 * 2. Admin Refactor & Security Guard (admin.css, auth_check.php, csrf_check.php)
 * 3. Thumb Security (Anti-LFI & Whitelist Uploads)
 * 4. Upload Helper (Root path resolution, anti-traversal delete, single declaration)
 * 5. Database Migrator & Versioning (Format v01..v05, state tracking, SQLite execution)
 * 6. Fallback Logo Optimization (Pemisahan base64 payload dari view)
 * 7. HPP Calculator (Unit cost snapshots, operating costs, rounding, price scenarios)
 *
 * Jalankan semua tes: C:\xampp\php\php.exe tests\run_tests.php
 * Pilih suite:        C:\xampp\php\php.exe tests\run_tests.php --suite=HppCalcTest
 * Daftar suite:       C:\xampp\php\php.exe tests\run_tests.php --list-suites
 */

$suiteFiles = [
    'SyntaxTest' => 'SyntaxTest.php',
    'AdminRefactorTest' => 'AdminRefactorTest.php',
    'ThumbSecurityTest' => 'ThumbSecurityTest.php',
    'UploadHelperRefactorTest' => 'UploadHelperRefactorTest.php',
    'DatabaseMigratorTest' => 'DatabaseMigratorTest.php',
    'FallbackLogoTest' => 'FallbackLogoTest.php',
    'EnvLoaderTest' => 'EnvLoaderTest.php',
    'BackupAndLogTest' => 'BackupAndLogTest.php',
    'SeoAndRoutingTest' => 'SeoAndRoutingTest.php',
    'HppCalcTest' => 'HppCalcTest.php',
    'HppIngredientTest' => 'HppIngredientTest.php',
    'HppIngredientPageTest' => 'HppIngredientPageTest.php',
    'HppRecipeRepositoryTest' => 'HppRecipeRepositoryTest.php',
    'HppRecipeEditorPageTest' => 'HppRecipeEditorPageTest.php',
    'HppRecipeListPageTest' => 'HppRecipeListPageTest.php',
    'DatabaseRestoreTest' => 'DatabaseRestoreTest.php',
    'TestRunnerCliTest' => 'TestRunnerCliTest.php',
];

$printUsage = static function (): void {
    echo "Usage:\n";
    echo "  php tests/run_tests.php\n";
    echo "  php tests/run_tests.php --suite=HppCalcTest,HppRecipeRepositoryTest\n";
    echo "  php tests/run_tests.php --list-suites\n";
};

$arguments = $argv;
array_shift($arguments);
$selectedSuites = null;
foreach ($arguments as $argument) {
    if ($argument === '--help' || $argument === '-h') {
        $printUsage();
        exit(0);
    }
    if ($argument === '--list-suites') {
        if (count($arguments) !== 1) {
            fwrite(STDERR, "--list-suites cannot be combined with other options.\n");
            $printUsage();
            exit(2);
        }
        echo implode(PHP_EOL, array_keys($suiteFiles)) . PHP_EOL;
        exit(0);
    }
    if (strpos($argument, '--suite=') === 0 && $selectedSuites === null) {
        $requested = substr($argument, strlen('--suite='));
        $names = explode(',', $requested);
        if ($requested === '' || in_array('', $names, true)) {
            fwrite(STDERR, "--suite requires one or more comma-separated suite names.\n");
            $printUsage();
            exit(2);
        }
        $selectedSuites = array_values(array_unique($names));
        continue;
    }

    fwrite(STDERR, "Unknown or duplicate option: $argument\n");
    $printUsage();
    exit(2);
}

if ($selectedSuites !== null) {
    $unknownSuites = array_values(array_diff($selectedSuites, array_keys($suiteFiles)));
    if ($unknownSuites !== []) {
        fwrite(STDERR, 'Unknown suite(s): ' . implode(', ', $unknownSuites) . PHP_EOL);
        fwrite(STDERR, 'Use --list-suites to see available suite names.' . PHP_EOL);
        exit(2);
    }
    $suiteNames = $selectedSuites;
} else {
    $suiteNames = array_keys($suiteFiles);
}

require_once __DIR__ . '/bootstrap.php';
$testClasses = [];
foreach ($suiteNames as $suiteName) {
    require_once __DIR__ . '/Unit/' . $suiteFiles[$suiteName];
    $testClasses[] = '\\' . $suiteName;
}

$runner = new \AlfiKitchen\Test\SimpleRunner();
$success = $runner->runSuite($testClasses);

exit($success ? 0 : 1);