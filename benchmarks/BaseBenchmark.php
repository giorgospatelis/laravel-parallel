<?php

declare(strict_types=1);

namespace LaravelParallel\Benchmarks;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Config;
use LaravelParallel\Facades\Parallel;
use RuntimeException;

/**
 * Base benchmark class providing common utilities and setup for all benchmarks.
 *
 * This class provides access to Laravel's service container, configuration, and
 * the Parallel facade. Unlike test classes, it does NOT extend Orchestra TestCase
 * because PHPBench serializes benchmark classes in worker processes, and TestCase
 * has non-serializable constructor dependencies.
 *
 * Instead, Laravel is bootstrapped via benchmarks/bootstrap.php which creates
 * a minimal application instance that benchmarks can access.
 *
 * IMPORTANT: Do NOT extend TestCase or any PHPUnit classes. PHPBench has its own
 * execution model that requires benchmark classes to be serializable.
 */
abstract class BaseBenchmark
{
    /**
     * Set up the benchmark environment before each benchmark method.
     *
     * This method can be called by PHPBench using @BeforeMethods annotation.
     * It ensures:
     * - Real worker pools are used (not mocks)
     * - Events are disabled (they add overhead)
     * - Logging is disabled (it impacts performance)
     * - Horizon is disabled (we're not testing monitoring overhead)
     *
     * Note: Unlike TestCase::setUp(), this method should not call parent::setUp()
     * because there is no parent class with setup logic.
     */
    public function setUp(): void
    {
        // Ensure we're using real worker pools, not mocks
        putenv('PARALLEL_USE_MOCK_POOLS=false');

        // Disable all overhead-adding features for accurate benchmarks
        Config::set('parallel.events.enabled', false);
        Config::set('parallel.logging.enabled', false);
        Config::set('parallel.horizon.enabled', false);
    }

    /**
     * Get the Laravel application instance.
     *
     * The application is bootstrapped in benchmarks/bootstrap.php and stored
     * globally for benchmarks to access.
     */
    protected function app(): Application
    {
        if (! isset($GLOBALS['__laravel_app'])) {
            throw new RuntimeException(
                'Laravel application not bootstrapped. Ensure phpbench.json uses benchmarks/bootstrap.php'
            );
        }

        return $GLOBALS['__laravel_app'];
    }

    /**
     * Simulate CPU-bound work by performing calculations.
     *
     * This creates realistic CPU-bound tasks for benchmarking.
     * The work is deterministic and produces consistent overhead.
     *
     * @param  int  $durationMs  Target duration in milliseconds
     * @return int Result of calculations (prevents optimization)
     */
    protected function cpuWork(int $durationMs): int
    {
        $start = microtime(true);
        $result = 0;
        $targetSeconds = $durationMs / 1000;

        // Perform calculations until target duration is reached
        while ((microtime(true) - $start) < $targetSeconds) {
            $result += array_sum(range(1, 100));
            $result = $result % 1000000; // Prevent overflow
        }

        return $result;
    }

    /**
     * Create a task that processes array data (CPU-bound).
     *
     * @param  array<mixed>  $data
     */
    protected function createArrayProcessingTask(array $data): callable
    {
        return function () use ($data): array {
            // Simulate data transformation operations
            $result = array_map(fn ($item) => $item * 2, $data);
            $result = array_filter($result, fn ($item) => $item > 0);
            sort($result);

            return $result;
        };
    }

    /**
     * Create a task that performs CPU work.
     */
    protected function createCpuTask(int $durationMs): callable
    {
        return function () use ($durationMs): int {
            $start = microtime(true);
            $result = 0;
            $targetSeconds = $durationMs / 1000;

            while ((microtime(true) - $start) < $targetSeconds) {
                $result += array_sum(range(1, 100));
                $result = $result % 1000000;
            }

            return $result;
        };
    }

    /**
     * Create a task that performs hash calculations (CPU-bound).
     */
    protected function createHashTask(string $data, int $iterations = 1000): callable
    {
        return function () use ($data, $iterations): string {
            $result = $data;
            for ($i = 0; $i < $iterations; $i++) {
                $result = hash('sha256', $result);
            }

            return $result;
        };
    }

    /**
     * Create a task that performs I/O work.
     */
    protected function createIoTask(int $durationMs): callable
    {
        return function () use ($durationMs): bool {
            usleep($durationMs * 1000);

            return true;
        };
    }

    /**
     * Generate test data of specific size for serialization benchmarks.
     *
     * @param  int  $sizeBytes  Approximate size in bytes
     * @return array<string, mixed>
     */
    protected function generatePayload(int $sizeBytes): array
    {
        // Generate string data to approximate the desired size
        $charCount = (int) ($sizeBytes / 4); // Rough estimate for array overhead
        $data = str_repeat('x', $charCount);

        return [
            'data' => $data,
            'timestamp' => microtime(true),
            'metadata' => [
                'size' => $sizeBytes,
                'generated_at' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * Get the number of CPU cores available.
     */
    protected function getCpuCount(): int
    {
        // Try to detect CPU count from system
        if (PHP_OS_FAMILY === 'Windows') {
            $output = shell_exec('wmic cpu get NumberOfCores');

            return $output ? (int) preg_replace('/[^0-9]/', '', $output) : 4;
        }

        if (is_readable('/proc/cpuinfo')) {
            $cpuinfo = file_get_contents('/proc/cpuinfo');
            if ($cpuinfo !== false) {
                preg_match_all('/^processor/m', $cpuinfo, $matches);

                return count($matches[0]) ?: 4;
            }
        }

        // Default fallback
        return 4;
    }

    /**
     * Simulate I/O-bound work with sleep.
     *
     * This creates realistic I/O-bound tasks (like waiting for API responses).
     * Note: I/O tasks may not benefit from parallelization as much as CPU tasks.
     *
     * @param  int  $durationMs  Duration in milliseconds
     */
    protected function ioWork(int $durationMs): void
    {
        usleep($durationMs * 1000);
    }

    /**
     * Execute tasks in parallel using the Parallel facade.
     *
     * @param  array<string|int, callable>  $tasks
     * @return array<string|int, mixed>
     */
    protected function runParallel(array $tasks, ?int $workers = null): array
    {
        if ($workers !== null) {
            return Parallel::workers($workers)->run($tasks);
        }

        return Parallel::run($tasks);
    }

    /**
     * Execute tasks sequentially for comparison baseline.
     *
     * @param  array<string|int, callable>  $tasks
     * @return array<string|int, mixed>
     */
    protected function runSequential(array $tasks): array
    {
        $results = [];

        foreach ($tasks as $key => $task) {
            $results[$key] = $task();
        }

        return $results;
    }
}
