<?php

declare(strict_types=1);

namespace LaravelParallel\Benchmarks;

use PhpBench\Attributes\Assert;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\RetryThreshold;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;

/**
 * Performance regression detection benchmarks.
 *
 * These benchmarks serve as canaries for performance regressions across releases.
 * They use PHPBench's @Assert annotations to fail if performance degrades beyond
 * acceptable thresholds.
 *
 * Use these benchmarks to:
 * 1. Establish baseline performance for each release
 * 2. Detect performance regressions in CI/CD
 * 3. Monitor critical path performance over time
 *
 * Run with baseline comparison:
 *   composer bench:baseline  # Store baseline
 *   composer bench:compare   # Compare against baseline
 *
 * @BeforeMethods("setUp")
 */
#[BeforeMethods('setUp')]
class RegressionBench extends BaseBenchmark
{
    /**
     * Critical Path: Minimal task execution.
     *
     * This is the fastest possible parallel execution path.
     * Any regression here indicates core overhead issues.
     *
     * Assertion: Should complete in < 10ms (target: ~5ms)
     *
     * @Revs(100)
     * @Iterations(10)
     * @Warmup(2)
     * @Assert("mode(variant.time.avg) < 10000000")
     */
    #[Revs(100)]
    #[Iterations(10)]
    #[Warmup(2)]
    #[RetryThreshold(5.0)]
    #[Assert('mode(variant.time.avg) < 10000000')] // < 10ms in nanoseconds
    public function benchCriticalPathMinimalExecution(): void
    {
        $tasks = ['task' => fn () => true];
        $this->runParallel($tasks, 1);
    }

    /**
     * Critical Path: Standard workload (10 tasks × 100ms).
     *
     * This represents the break-even point scenario. Performance here
     * should remain stable across releases.
     *
     * Assertion: Parallel should be faster than 1 second (ideal: ~400ms on 4 cores)
     *
     * @Revs(5)
     * @Iterations(10)
     * @Warmup(2)
     */
    #[Revs(5)]
    #[Iterations(10)]
    #[Warmup(2)]
    #[RetryThreshold(10.0)]
    public function benchCriticalPathStandardWorkload(): void
    {
        $tasks = [];
        for ($i = 0; $i < 10; $i++) {
            $tasks[$i] = $this->createCpuTask(100);
        }

        $cpuCount = $this->getCpuCount();
        $this->runParallel($tasks, $cpuCount);
    }

    /**
     * Regression Guard: Worker pool initialization.
     *
     * Monitors the overhead of creating worker pools.
     * Regressions here affect every parallel execution.
     *
     * @Revs(50)
     * @Iterations(10)
     * @Warmup(2)
     */
    #[Revs(50)]
    #[Iterations(10)]
    #[Warmup(2)]
    #[RetryThreshold(5.0)]
    public function benchRegressionWorkerPoolInit(): void
    {
        // Minimal execution to measure setup overhead
        $tasks = ['init' => fn () => 1];
        $this->runParallel($tasks, 1);
    }

    /**
     * Regression Guard: Serialization overhead.
     *
     * Monitors closure serialization performance.
     * Regressions indicate issues with SerializableClosure.
     *
     * @Revs(50)
     * @Iterations(10)
     * @Warmup(2)
     */
    #[Revs(50)]
    #[Iterations(10)]
    #[Warmup(2)]
    #[RetryThreshold(5.0)]
    public function benchRegressionSerializationOverhead(): void
    {
        $tasks = [];
        for ($i = 0; $i < 20; $i++) {
            $value = $i;
            $tasks[$i] = fn () => $value * 2;
        }

        $this->runParallel($tasks, 2);
    }

    /**
     * Regression Guard: Result collection.
     *
     * Monitors performance of collecting and processing results.
     *
     * @Revs(20)
     * @Iterations(10)
     * @Warmup(2)
     */
    #[Revs(20)]
    #[Iterations(10)]
    #[Warmup(2)]
    #[RetryThreshold(5.0)]
    public function benchRegressionResultCollection(): void
    {
        $tasks = [];
        for ($i = 0; $i < 50; $i++) {
            $tasks[$i] = fn () => range(1, 100); // Return arrays
        }

        $results = $this->runParallel($tasks, 4);

        // Access results to ensure they're collected
        $count = count($results);
    }

    /**
     * Regression Guard: Error handling.
     *
     * Monitors performance of error handling in parallel execution.
     *
     * @Revs(20)
     * @Iterations(10)
     * @Warmup(2)
     */
    #[Revs(20)]
    #[Iterations(10)]
    #[Warmup(2)]
    #[RetryThreshold(5.0)]
    public function benchRegressionErrorHandling(): void
    {
        $tasks = [
            'success1' => fn () => 'ok',
            'fail1' => fn () => throw new \Exception('Test error'),
            'success2' => fn () => 'ok',
            'fail2' => fn () => throw new \Exception('Test error'),
            'success3' => fn () => 'ok',
        ];

        $results = $this->runParallel($tasks, 2);
    }

    /**
     * Regression Guard: Map operation.
     *
     * Monitors Parallel::map() performance.
     *
     * @Revs(10)
     * @Iterations(10)
     * @Warmup(2)
     */
    #[Revs(10)]
    #[Iterations(10)]
    #[Warmup(2)]
    #[RetryThreshold(5.0)]
    public function benchRegressionMapOperation(): void
    {
        $items = range(1, 30);
        $cpuCount = $this->getCpuCount();

        \LaravelParallel\Facades\Parallel::workers($cpuCount)
            ->map($items, fn ($item) => $item * 2);
    }

    /**
     * Regression Guard: Hash computation (CPU-bound baseline).
     *
     * Establishes CPU-bound performance baseline.
     * Useful for comparing across different hardware.
     *
     * @Revs(5)
     * @Iterations(10)
     * @Warmup(2)
     */
    #[Revs(5)]
    #[Iterations(10)]
    #[Warmup(2)]
    #[RetryThreshold(10.0)]
    public function benchRegressionCpuBoundBaseline(): void
    {
        $tasks = [];
        for ($i = 0; $i < 16; $i++) {
            $data = "data-{$i}";
            $tasks[$i] = $this->createHashTask($data, 1000);
        }

        $cpuCount = $this->getCpuCount();
        $this->runParallel($tasks, $cpuCount);
    }

    /**
     * Regression Guard: Multiple worker counts.
     *
     * Monitors scaling efficiency across different worker counts.
     *
     * @Revs(3)
     * @Iterations(10)
     * @Warmup(1)
     */
    #[Revs(3)]
    #[Iterations(10)]
    #[Warmup(1)]
    #[RetryThreshold(10.0)]
    public function benchRegressionScaling2Workers(): void
    {
        $tasks = [];
        for ($i = 0; $i < 16; $i++) {
            $tasks[$i] = $this->createCpuTask(100);
        }

        $this->runParallel($tasks, 2);
    }

    /**
     * @Revs(3)
     * @Iterations(10)
     * @Warmup(1)
     */
    #[Revs(3)]
    #[Iterations(10)]
    #[Warmup(1)]
    #[RetryThreshold(10.0)]
    public function benchRegressionScaling4Workers(): void
    {
        $tasks = [];
        for ($i = 0; $i < 16; $i++) {
            $tasks[$i] = $this->createCpuTask(100);
        }

        $this->runParallel($tasks, 4);
    }

    /**
     * @Revs(3)
     * @Iterations(10)
     * @Warmup(1)
     */
    #[Revs(3)]
    #[Iterations(10)]
    #[Warmup(1)]
    #[RetryThreshold(10.0)]
    public function benchRegressionScaling8Workers(): void
    {
        $tasks = [];
        for ($i = 0; $i < 16; $i++) {
            $tasks[$i] = $this->createCpuTask(100);
        }

        $this->runParallel($tasks, 8);
    }

    /**
     * Regression Guard: Memory efficiency.
     *
     * Monitors memory usage during parallel execution.
     * Large increases indicate memory leaks or inefficiencies.
     *
     * @Revs(10)
     * @Iterations(5)
     * @Warmup(1)
     */
    #[Revs(10)]
    #[Iterations(5)]
    #[Warmup(1)]
    #[RetryThreshold(5.0)]
    public function benchRegressionMemoryEfficiency(): void
    {
        $tasks = [];
        for ($i = 0; $i < 100; $i++) {
            $tasks[$i] = fn () => range(1, 1000);
        }

        $this->runParallel($tasks, 4);

        // Force cleanup
        gc_collect_cycles();
    }

    /**
     * Regression Guard: Payload size impact.
     *
     * Monitors performance with different payload sizes.
     * Helps detect serialization regressions.
     *
     * @Revs(10)
     * @Iterations(10)
     * @Warmup(2)
     */
    #[Revs(10)]
    #[Iterations(10)]
    #[Warmup(2)]
    #[RetryThreshold(5.0)]
    public function benchRegressionSmallPayload(): void
    {
        $tasks = [];
        for ($i = 0; $i < 10; $i++) {
            $data = ['id' => $i, 'name' => "item-{$i}"];
            $tasks[$i] = fn () => $data;
        }

        $this->runParallel($tasks, 2);
    }

    /**
     * @Revs(10)
     * @Iterations(10)
     * @Warmup(2)
     */
    #[Revs(10)]
    #[Iterations(10)]
    #[Warmup(2)]
    #[RetryThreshold(5.0)]
    public function benchRegressionMediumPayload(): void
    {
        $tasks = [];
        for ($i = 0; $i < 10; $i++) {
            $data = $this->generatePayload(10240); // 10KB
            $tasks[$i] = fn () => $data;
        }

        $this->runParallel($tasks, 2);
    }

    /**
     * Stability Test: Long-running execution.
     *
     * Tests stability and performance consistency over longer executions.
     *
     * @Revs(2)
     * @Iterations(5)
     * @Warmup(1)
     */
    #[Revs(2)]
    #[Iterations(5)]
    #[Warmup(1)]
    #[RetryThreshold(15.0)]
    public function benchStabilityLongRunning(): void
    {
        $tasks = [];
        for ($i = 0; $i < 100; $i++) {
            $tasks[$i] = $this->createCpuTask(50);
        }

        $cpuCount = $this->getCpuCount();
        $this->runParallel($tasks, $cpuCount);
    }

    /**
     * Stability Test: Many small tasks.
     *
     * Tests performance with high task counts.
     *
     * @Revs(3)
     * @Iterations(5)
     * @Warmup(1)
     */
    #[Revs(3)]
    #[Iterations(5)]
    #[Warmup(1)]
    #[RetryThreshold(10.0)]
    public function benchStabilityManyTasks(): void
    {
        $tasks = [];
        for ($i = 0; $i < 200; $i++) {
            $tasks[$i] = $this->createCpuTask(10);
        }

        $cpuCount = $this->getCpuCount();
        $this->runParallel($tasks, $cpuCount);
    }
}
