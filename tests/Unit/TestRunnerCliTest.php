<?php
use PHPUnit\Framework\TestCase;

class TestRunnerCliTest extends TestCase
{
    private function runRunner(array $arguments): array
    {
        $command = array_merge(
            [PHP_BINARY, __DIR__ . '/../run_tests.php'],
            $arguments
        );
        $process = proc_open(
            $command,
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            dirname(__DIR__, 2)
        );
        if (!is_resource($process)) {
            $this->fail('Could not start the test runner process.');
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $status = proc_close($process);

        return [
            'status' => $status,
            'stdout' => $stdout,
            'stderr' => $stderr,
        ];
    }

    public function testListsAvailableSuitesWithoutRunningTests(): void
    {
        $result = $this->runRunner(['--list-suites']);

        $this->assertSame(0, $result['status']);
        $this->assertStringContainsString('HppCalcTest', $result['stdout']);
        $this->assertStringContainsString('TestRunnerCliTest', $result['stdout']);
        $this->assertStringNotContainsString('Total Test:', $result['stdout']);
    }

    public function testRunsOnlyRequestedSuite(): void
    {
        $result = $this->runRunner(['--suite=HppCalcTest']);

        $this->assertSame(0, $result['status'], $result['stderr'] . $result['stdout']);
        $this->assertStringContainsString('• HppCalcTest:', $result['stdout']);
        $this->assertStringNotContainsString('• SeoAndRoutingTest:', $result['stdout']);
        $this->assertStringContainsString('STATUS: OK', $result['stdout']);
    }

    public function testRunsMultipleRequestedSuites(): void
    {
        $result = $this->runRunner(['--suite=HppCalcTest,HppRecipeRepositoryTest']);

        $this->assertSame(0, $result['status'], $result['stderr'] . $result['stdout']);
        $this->assertStringContainsString('• HppCalcTest:', $result['stdout']);
        $this->assertStringContainsString('• HppRecipeRepositoryTest:', $result['stdout']);
        $this->assertStringNotContainsString('• SeoAndRoutingTest:', $result['stdout']);
    }

    public function testRejectsUnknownSuite(): void
    {
        $result = $this->runRunner(['--suite=MissingSuite']);

        $this->assertSame(2, $result['status']);
        $this->assertStringContainsString('Unknown suite(s): MissingSuite', $result['stderr']);
    }

    public function testRejectsEmptySuiteList(): void
    {
        $result = $this->runRunner(['--suite=HppCalcTest,']);

        $this->assertSame(2, $result['status']);
        $this->assertStringContainsString('--suite requires', $result['stderr']);
    }
}
