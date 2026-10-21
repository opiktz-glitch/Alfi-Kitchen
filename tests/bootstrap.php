<?php
/**
 * Bootstrap & Lightweight Native Test Runner untuk Alfi Kitchen.
 *
 * Standar ini memungkinkan pengujian unit berbasis TestCase (kompatibel PHPUnit)
 * dijalankan langsung menggunakan PHP Native tanpa ketergantungan Composer / PHPUnit eksternal.
 * Jika suatu saat PHPUnit diinstal, suite ini tetap 100% kompatibel.
 */

namespace PHPUnit\Framework;

if (!class_exists('PHPUnit\Framework\TestCase', false)) {
    class AssertionFailedError extends \RuntimeException {}

    abstract class TestCase
    {
        public static function setUpBeforeClass(): void {}
        public static function tearDownAfterClass(): void {}
        protected function setUp(): void {}
        protected function tearDown(): void {}

        public function fail(string $message = ''): void
        {
            throw new AssertionFailedError($message ?: 'Assertion failed');
        }

        public function assertTrue($condition, string $message = ''): void
        {
            if ($condition !== true) {
                $this->fail($message ?: "Failed asserting that " . var_export($condition, true) . " is true.");
            }
        }

        public function assertFalse($condition, string $message = ''): void
        {
            if ($condition !== false) {
                $this->fail($message ?: "Failed asserting that " . var_export($condition, true) . " is false.");
            }
        }

        public function assertSame($expected, $actual, string $message = ''): void
        {
            if ($expected !== $actual) {
                $this->fail($message ?: "Failed asserting that " . var_export($actual, true) . " is identical to " . var_export($expected, true) . ".");
            }
        }

        public function assertEquals($expected, $actual, string $message = ''): void
        {
            if ($expected != $actual) {
                $this->fail($message ?: "Failed asserting that " . var_export($actual, true) . " matches expected " . var_export($expected, true) . ".");
            }
        }

        public function assertNotSame($expected, $actual, string $message = ''): void
        {
            if ($expected === $actual) {
                $this->fail($message ?: "Failed asserting that values are not identical.");
            }
        }

        public function assertNull($actual, string $message = ''): void
        {
            if ($actual !== null) {
                $this->fail($message ?: "Failed asserting that " . var_export($actual, true) . " is null.");
            }
        }

        public function assertNotNull($actual, string $message = ''): void
        {
            if ($actual === null) {
                $this->fail($message ?: "Failed asserting that value is not null.");
            }
        }

        public function assertNotFalse($actual, string $message = ''): void
        {
            if ($actual === false) {
                $this->fail($message ?: "Failed asserting that value is not false.");
            }
        }

        public function assertEmpty($actual, string $message = ''): void
        {
            if (!empty($actual)) {
                $this->fail($message ?: "Failed asserting that value is empty.");
            }
        }

        public function assertNotEmpty($actual, string $message = ''): void
        {
            if (empty($actual)) {
                $this->fail($message ?: "Failed asserting that value is not empty.");
            }
        }

        public function assertCount(int $expectedCount, $countable, string $message = ''): void
        {
            $actualCount = is_countable($countable) ? count($countable) : 0;
            if ($actualCount !== $expectedCount) {
                $this->fail($message ?: "Failed asserting that count $actualCount matches expected $expectedCount.");
            }
        }

        public function assertGreaterThan($expected, $actual, string $message = ''): void
        {
            if ($actual <= $expected) {
                $this->fail($message ?: "Failed asserting that $actual is greater than $expected.");
            }
        }

        public function assertGreaterThanOrEqual($expected, $actual, string $message = ''): void
        {
            if ($actual < $expected) {
                $this->fail($message ?: "Failed asserting that $actual is greater than or equal to $expected.");
            }
        }

        public function assertIsArray($actual, string $message = ''): void
        {
            if (!is_array($actual)) {
                $this->fail($message ?: "Failed asserting that " . gettype($actual) . " is an array.");
            }
        }

        public function assertIsString($actual, string $message = ''): void
        {
            if (!is_string($actual)) {
                $this->fail($message ?: "Failed asserting that " . gettype($actual) . " is a string.");
            }
        }

        public function assertFileExists(string $filename, string $message = ''): void
        {
            if (!is_file($filename)) {
                $this->fail($message ?: "Failed asserting that file '$filename' exists.");
            }
        }

        public function assertFileDoesNotExist(string $filename, string $message = ''): void
        {
            if (file_exists($filename)) {
                $this->fail($message ?: "Failed asserting that file '$filename' does not exist.");
            }
        }

        public function assertDirectoryExists(string $directory, string $message = ''): void
        {
            if (!is_dir($directory)) {
                $this->fail($message ?: "Failed asserting that directory '$directory' exists.");
            }
        }

        public function assertStringContainsString(string $needle, string $haystack, string $message = ''): void
        {
            if (strpos($haystack, $needle) === false) {
                $this->fail($message ?: "Failed asserting that string contains '$needle'.");
            }
        }

        public function assertStringNotContainsString(string $needle, string $haystack, string $message = ''): void
        {
            if (strpos($haystack, $needle) !== false) {
                $this->fail($message ?: "Failed asserting that string does not contain '$needle'.");
            }
        }

        public function assertStringStartsWith(string $prefix, string $string, string $message = ''): void
        {
            if (substr($string, 0, strlen($prefix)) !== $prefix) {
                $this->fail($message ?: "Failed asserting that '$string' starts with '$prefix'.");
            }
        }

        public function assertStringStartsNotWith(string $prefix, string $string, string $message = ''): void
        {
            if (substr($string, 0, strlen($prefix)) === $prefix) {
                $this->fail($message ?: "Failed asserting that string does not start with '$prefix'.");
            }
        }

        public function assertMatchesRegularExpression(string $pattern, string $string, string $message = ''): void
        {
            if (!preg_match($pattern, $string)) {
                $this->fail($message ?: "Failed asserting that '$string' matches pattern '$pattern'.");
            }
        }

        public function assertDoesNotMatchRegularExpression(string $pattern, string $string, string $message = ''): void
        {
            if (preg_match($pattern, $string)) {
                $this->fail($message ?: "Failed asserting that '$string' does not match pattern '$pattern'.");
            }
        }

        public function assertArrayHasKey($key, $array, string $message = ''): void
        {
            if (!is_array($array) || !array_key_exists($key, $array)) {
                $this->fail($message ?: "Failed asserting that array has key '$key'.");
            }
        }
    }
}

namespace AlfiKitchen\Test;

/**
 * Runner sederhana untuk mengeksekusi kelas-kelas TestCase di tests/Unit/.
 */
class SimpleRunner
{
    private int $totalTests = 0;
    private int $passedTests = 0;
    private int $failedTests = 0;
    private array $failures = [];

    public function runSuite(array $testClasses): bool
    {
        echo "====================================================\n";
        echo "  Alfi Kitchen - Unit Test Suite (Native Standard)\n";
        echo "====================================================\n\n";

        $startTime = microtime(true);

        foreach ($testClasses as $className) {
            $this->runClass($className);
        }

        $duration = round(microtime(true) - $startTime, 3);

        echo "----------------------------------------------------\n";
        echo "Ringkasan Hasil Pengujian:\n";
        echo "  Waktu: {$duration}s\n";
        echo "  Total Test: {$this->totalTests}\n";
        echo "  Lulus     : {$this->passedTests}\n";
        echo "  Gagal     : {$this->failedTests}\n";

        if ($this->failedTests > 0) {
            echo "\nDetail Kegagalan:\n";
            foreach ($this->failures as $i => $fail) {
                echo "  " . ($i + 1) . ") {$fail['class']}::{$fail['method']}\n";
                echo "     " . $fail['message'] . "\n";
            }
            echo "\nSTATUS: FAILED\n";
            return false;
        }

        echo "\nSTATUS: OK (Semua unit test penting lulus!)\n";
        echo "====================================================\n";
        return true;
    }

    private function runClass(string $className): void
    {
        if (!class_exists($className)) {
            echo "  [ERROR] Class $className tidak ditemukan.\n\n";
            $this->failedTests++;
            $this->failures[] = ['class' => $className, 'method' => 'N/A', 'message' => 'Class tidak ditemukan'];
            return;
        }

        $reflector = new \ReflectionClass($className);
        if (!$reflector->isSubclassOf(\PHPUnit\Framework\TestCase::class)) {
            return;
        }

        $shortName = $reflector->getShortName();
        echo "• $shortName:\n";

        $className::setUpBeforeClass();

        $methods = $reflector->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            $methodName = $method->getName();
            if (!str_starts_with($methodName, 'test')) {
                continue;
            }

            $doc = (string)$method->getDocComment();
            $dataProvider = null;
            if (preg_match('/@dataProvider\s+([a-zA-Z0-9_]+)/', $doc, $matches)) {
                $dataProvider = $matches[1];
            }

            if ($dataProvider && method_exists($className, $dataProvider)) {
                $data = $className::$dataProvider();
                foreach ($data as $datasetKey => $dataset) {
                    $args = is_array($dataset) ? $dataset : [$dataset];
                    $testLabel = "{$methodName} [" . (is_string($datasetKey) ? $datasetKey : "#$datasetKey") . "]";
                    $this->executeSingleTest($className, $methodName, $args, $testLabel);
                }
            } else {
                $this->executeSingleTest($className, $methodName, [], $methodName);
            }
        }

        $className::tearDownAfterClass();
        echo "\n";
    }

    private function executeSingleTest(string $className, string $methodName, array $args, string $label): void
    {
        $this->totalTests++;
        $instance = new $className();

        $refClass = new \ReflectionClass($instance);
        $setUp = $refClass->getMethod('setUp');
        $setUp->setAccessible(true);
        $tearDown = $refClass->getMethod('tearDown');
        $tearDown->setAccessible(true);

        try {
            $setUp->invoke($instance);
            $instance->$methodName(...$args);
            $tearDown->invoke($instance);

            echo "    ✓ $label\n";
            $this->passedTests++;
        } catch (\Throwable $e) {
            try {
                $tearDown->invoke($instance);
            } catch (\Throwable $ignored) {}

            echo "    ✗ $label\n";
            echo "      " . $e->getMessage() . "\n";
            $this->failedTests++;
            $this->failures[] = [
                'class' => $className,
                'method' => $label,
                'message' => $e->getMessage() . " (" . basename($e->getFile()) . ":" . $e->getLine() . ")"
            ];
        }
    }
}