<?php

declare(strict_types=1);

namespace LaravelParallel\Benchmarks;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\ParamProviders;
use PhpBench\Attributes\RetryThreshold;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;

/**
 * Break-even point analysis benchmarks.
 *
 * These benchmarks validate the performance claims made in the README:
 * - Setup overhead: ~2-5ms per execution
 * - Per-task overhead: ~0.5-1ms (serialization + IPC)
 * - Break-even point: 10 tasks × 100ms = 1 second total work
 *
 * README Example Scenarios (from lines 441-452):
 * 1. Small Workload: 5 tasks × 100ms = 500ms sequential
 * 2. Medium Workload: 50 tasks × 1s = 50s sequential
 * 3. Large Workload: 1000 tasks × 500ms = 500s sequential
 *
 * @BeforeMethods("setUp")
 */
#[BeforeMethods('setUp')]
class BreakEvenBench extends BaseBenchmark
{
    /**
     * Benchmark: Small workload (NOT recommended per README).
     *
     * README claim: 5 tasks × 100ms = 500ms sequential
     *               Parallel: ~150ms (3.2x speedup, 3% overhead)
     *
     * This tests whether parallelization overhead outweighs benefits
     * for small workloads.
     *
     * @Revs(10)
     * @Iterations(10)
     * @Warmup(2)
     */
    #[Revs(10)]
    #[Iterations(10)]
    #[Warmup(2)]
    #[RetryThreshold(10.0)]
    public function benchSmallWorkloadSequential(): void
    {
        $tasks = [];
        for ($i = 0; $i < 5; $i++) {
            $tasks[$i] = $this->createCpuTask(100);
        }

        $this->runSequential($tasks);
    }

    /**
     * @Revs(10)
     * @Iterations(10)
     * @Warmup(2)
     */
    #[Revs(10)]
    #[Iterations(10)]
    #[Warmup(2)]
    #[RetryThreshold(10.0)]
    public function benchSmallWorkloadParallel(): void
    {
        $tasks = [];
        for ($i = 0; $i < 5; $i++) {
            $tasks[$i] = $this->createCpuTask(100);
        }

        $cpuCount = $this->getCpuCount();
        $this->runParallel($tasks, $cpuCount);
    }

    /**
     * Benchmark: Medium workload (recommended per README).
     *
     * README claim: 50 tasks × 1s = 50s sequential
     *               Parallel (5 cores): ~10s (4.98x speedup, 0.3% overhead)
     *
     * This should show clear benefits of parallelization.
     *
     * @Revs(2)
     * @Iterations(5)
     * @Warmup(1)
     */
    #[Revs(2)]
    #[Iterations(5)]
    #[Warmup(1)]
    #[RetryThreshold(10.0)]
    public function benchMediumWorkloadSequential(): void
    {
        $tasks = [];
        for ($i = 0; $i < 50; $i++) {
            $tasks[$i] = $this->createCpuTask(1000); // 1 second per task
        }

        $this->runSequential($tasks);
    }

    /**
     * @Revs(2)
     * @Iterations(5)
     * @Warmup(1)
     */
    #[Revs(2)]
    #[Iterations(5)]
    #[Warmup(1)]
    #[RetryThreshold(10.0)]
    public function benchMediumWorkloadParallel(): void
    {
        $tasks = [];
        for ($i = 0; $i < 50; $i++) {
            $tasks[$i] = $this->createCpuTask(1000);
        }

        // Use actual CPU count or 5 for comparison
        $workers = min($this->getCpuCount(), 8);
        $this->runParallel($tasks, $workers);
    }

    /**
     * Benchmark: Break-even point (10 tasks × 100ms).
     *
     * README claim: Break-even at ~1 second total work.
     * This is the minimum workload where parallel makes sense.
     *
     * @Revs(10)
     * @Iterations(10)
     * @Warmup(2)
     */
    #[Revs(10)]
    #[Iterations(10)]
    #[Warmup(2)]
    #[RetryThreshold(10.0)]
    public function benchBreakEvenSequential(): void
    {
        $tasks = [];
        for ($i = 0; $i < 10; $i++) {
            $tasks[$i] = $this->createCpuTask(100);
        }

        $this->runSequential($tasks);
    }

    /**
     * @Revs(10)
     * @Iterations(10)
     * @Warmup(2)
     */
    #[Revs(10)]
    #[Iterations(10)]
    #[Warmup(2)]
    #[RetryThreshold(10.0)]
    public function benchBreakEvenParallel(): void
    {
        $tasks = [];
        for ($i = 0; $i < 10; $i++) {
            $tasks[$i] = $this->createCpuTask(100);
        }

        $cpuCount = $this->getCpuCount();
        $this->runParallel($tasks, $cpuCount);
    }

    /**
     * Benchmark: Below break-even (micro-tasks).
     *
     * Tasks too small to benefit from parallelization.
     * Sequential should be faster due to overhead.
     *
     * @Revs(20)
     * @Iterations(10)
     * @Warmup(2)
     */
    #[Revs(20)]
    #[Iterations(10)]
    #[Warmup(2)]
    #[RetryThreshold(5.0)]
    public function benchBelowBreakEvenSequential(): void
    {
        $tasks = [];
        for ($i = 0; $i < 10; $i++) {
            $tasks[$i] = $this->createCpuTask(10); // Only 10ms per task
        }

        $this->runSequential($tasks);
    }

    /**
     * @Revs(20)
     * @Iterations(10)
     * @Warmup(2)
     */
    #[Revs(20)]
    #[Iterations(10)]
    #[Warmup(2)]
    #[RetryThreshold(5.0)]
    public function benchBelowBreakEvenParallel(): void
    {
        $tasks = [];
        for ($i = 0; $i < 10; $i++) {
            $tasks[$i] = $this->createCpuTask(10);
        }

        $cpuCount = $this->getCpuCount();
        $this->runParallel($tasks, $cpuCount);
    }

    /**
     * Benchmark: Overhead measurement scenarios.
     *
     * Directly measure setup and per-task overhead to validate
     * the ~2-5ms setup and ~0.5-1ms per-task claims.
     *
     * @Revs(50)
     * @Iterations(10)
     * @Warmup(2)
     * @ParamProviders("provideOverheadScenarios")
     */
    #[Revs(50)]
    #[Iterations(10)]
    #[Warmup(2)]
    #[ParamProviders('provideOverheadScenarios')]
    #[RetryThreshold(5.0)]
    public function benchOverheadMeasurement(array $params): void
    {
        $taskCount = $params['tasks'];
        $tasks = [];

        // Minimal tasks to isolate overhead
        for ($i = 0; $i < $taskCount; $i++) {
            $tasks[$i] = fn () => $i;
        }

        $this->runParallel($tasks, 1);
    }

    /**
     * Benchmark: Task count vs performance relationship.
     *
     * Shows how overhead as percentage decreases with more/longer tasks.
     *
     * @Revs(5)
     * @Iterations(5)
     * @Warmup(1)
     * @ParamProviders("provideTaskCountScenarios")
     */
    #[Revs(5)]
    #[Iterations(5)]
    #[Warmup(1)]
    #[ParamProviders('provideTaskCountScenarios')]
    #[RetryThreshold(10.0)]
    public function benchTaskCountImpactSequential(array $params): void
    {
        $taskCount = $params['tasks'];
        $taskDuration = $params['duration'];
        $tasks = [];

        for ($i = 0; $i < $taskCount; $i++) {
            $tasks[$i] = $this->createCpuTask($taskDuration);
        }

        $this->runSequential($tasks);
    }

    /**
     * @Revs(5)
     * @Iterations(5)
     * @Warmup(1)
     * @ParamProviders("provideTaskCountScenarios")
     */
    #[Revs(5)]
    #[Iterations(5)]
    #[Warmup(1)]
    #[ParamProviders('provideTaskCountScenarios')]
    #[RetryThreshold(10.0)]
    public function benchTaskCountImpactParallel(array $params): void
    {
        $taskCount = $params['tasks'];
        $taskDuration = $params['duration'];
        $tasks = [];

        for ($i = 0; $i < $taskCount; $i++) {
            $tasks[$i] = $this->createCpuTask($taskDuration);
        }

        $cpuCount = $this->getCpuCount();
        $this->runParallel($tasks, $cpuCount);
    }

    /**
     * Provide overhead measurement scenarios.
     *
     * @return \Generator<string, array<string, int>>
     */
    public function provideOverheadScenarios(): \Generator
    {
        yield '1 minimal task' => ['tasks' => 1];
        yield '5 minimal tasks' => ['tasks' => 5];
        yield '10 minimal tasks' => ['tasks' => 10];
        yield '25 minimal tasks' => ['tasks' => 25];
    }

    /**
     * Provide task count scenarios for overhead analysis.
     *
     * @return \Generator<string, array<string, int>>
     */
    public function provideTaskCountScenarios(): \Generator
    {
        // Vary task counts and durations
        yield '5 tasks × 50ms' => ['tasks' => 5, 'duration' => 50];
        yield '10 tasks × 100ms' => ['tasks' => 10, 'duration' => 100];
        yield '20 tasks × 100ms' => ['tasks' => 20, 'duration' => 100];
        yield '50 tasks × 100ms' => ['tasks' => 50, 'duration' => 100];
        yield '10 tasks × 500ms' => ['tasks' => 10, 'duration' => 500];
    }
}
