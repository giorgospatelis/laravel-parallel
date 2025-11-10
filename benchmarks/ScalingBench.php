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
 * Worker scaling benchmarks.
 *
 * These benchmarks analyze how performance scales with different worker counts.
 * They help determine optimal worker configurations for different workload types.
 *
 * Key questions answered:
 * - How does performance scale from 1 to N workers?
 * - What's the optimal worker count for CPU vs I/O tasks?
 * - At what point does adding workers provide diminishing returns?
 * - Is there overhead from having too many workers?
 *
 * @BeforeMethods("setUp")
 */
#[BeforeMethods('setUp')]
class ScalingBench extends BaseBenchmark
{
    /**
     * Benchmark CPU-bound tasks with varying worker counts.
     *
     * This tests Amdahl's Law in practice - showing how speedup scales
     * with the number of parallel workers for CPU-intensive work.
     *
     * Expected: Near-linear speedup up to CPU count, then diminishing returns.
     *
     * @Revs(3)
     * @Iterations(5)
     * @Warmup(1)
     * @ParamProviders("provideWorkerCounts")
     */
    #[Revs(3)]
    #[Iterations(5)]
    #[Warmup(1)]
    #[ParamProviders('provideWorkerCounts')]
    #[RetryThreshold(10.0)]
    public function benchCpuBoundScaling(array $params): void
    {
        $workerCount = $params['workers'];
        $taskCount = 20; // Fixed task count
        $tasks = [];

        // Create CPU-intensive tasks (100ms each)
        for ($i = 0; $i < $taskCount; $i++) {
            $tasks[$i] = $this->createCpuTask(100);
        }

        $this->runParallel($tasks, $workerCount);
    }

    /**
     * Benchmark I/O-bound tasks with varying worker counts.
     *
     * I/O tasks may benefit from worker counts exceeding CPU count
     * since threads spend time waiting, not computing.
     *
     * Expected: Benefits from workers > CPU count for I/O operations.
     *
     * @Revs(5)
     * @Iterations(5)
     * @Warmup(1)
     * @ParamProviders("provideWorkerCounts")
     */
    #[Revs(5)]
    #[Iterations(5)]
    #[Warmup(1)]
    #[ParamProviders('provideWorkerCounts')]
    #[RetryThreshold(10.0)]
    public function benchIoBoundScaling(array $params): void
    {
        $workerCount = $params['workers'];
        $taskCount = 20;
        $tasks = [];

        // Create I/O-bound tasks (simulated with sleep)
        for ($i = 0; $i < $taskCount; $i++) {
            $tasks[$i] = $this->createIoTask(50);
        }

        $this->runParallel($tasks, $workerCount);
    }

    /**
     * Benchmark many small tasks with varying worker counts.
     *
     * Tests how worker count affects overhead when tasks are numerous
     * but individually quick.
     *
     * Expected: Optimal performance around CPU count, overhead with too many workers.
     *
     * @Revs(5)
     * @Iterations(5)
     * @Warmup(1)
     * @ParamProviders("provideWorkerCounts")
     */
    #[Revs(5)]
    #[Iterations(5)]
    #[Warmup(1)]
    #[ParamProviders('provideWorkerCounts')]
    #[RetryThreshold(10.0)]
    public function benchManySmallTasksScaling(array $params): void
    {
        $workerCount = $params['workers'];
        $taskCount = 100; // Many tasks
        $tasks = [];

        // Small CPU tasks (25ms each)
        for ($i = 0; $i < $taskCount; $i++) {
            $tasks[$i] = $this->createCpuTask(25);
        }

        $this->runParallel($tasks, $workerCount);
    }

    /**
     * Benchmark few large tasks with varying worker counts.
     *
     * Tests whether large tasks benefit differently from worker scaling
     * compared to many small tasks.
     *
     * Expected: Diminishing returns after task count matches worker count.
     *
     * @Revs(3)
     * @Iterations(5)
     * @Warmup(1)
     * @ParamProviders("provideWorkerCounts")
     */
    #[Revs(3)]
    #[Iterations(5)]
    #[Warmup(1)]
    #[ParamProviders('provideWorkerCounts')]
    #[RetryThreshold(10.0)]
    public function benchFewLargeTasksScaling(array $params): void
    {
        $workerCount = $params['workers'];
        $taskCount = 8; // Few tasks
        $tasks = [];

        // Large CPU tasks (500ms each)
        for ($i = 0; $i < $taskCount; $i++) {
            $tasks[$i] = $this->createCpuTask(500);
        }

        $this->runParallel($tasks, $workerCount);
    }

    /**
     * Benchmark mixed CPU and I/O workload scaling.
     *
     * Real-world workloads often combine CPU and I/O operations.
     * This tests optimal worker count for mixed scenarios.
     *
     * Expected: Optimal around 1.5-2x CPU count due to mixed nature.
     *
     * @Revs(3)
     * @Iterations(5)
     * @Warmup(1)
     * @ParamProviders("provideWorkerCounts")
     */
    #[Revs(3)]
    #[Iterations(5)]
    #[Warmup(1)]
    #[ParamProviders('provideWorkerCounts')]
    #[RetryThreshold(10.0)]
    public function benchMixedWorkloadScaling(array $params): void
    {
        $workerCount = $params['workers'];
        $tasks = [];

        // Alternate between CPU and I/O tasks
        for ($i = 0; $i < 20; $i++) {
            if ($i % 2 === 0) {
                $tasks[$i] = $this->createCpuTask(100);
            } else {
                $tasks[$i] = $this->createIoTask(50);
            }
        }

        $this->runParallel($tasks, $workerCount);
    }

    /**
     * Benchmark hash computation scaling (pure CPU).
     *
     * Hash calculations are purely CPU-bound and should scale
     * linearly with worker count up to CPU core count.
     *
     * Expected: Perfect scaling up to core count.
     *
     * @Revs(5)
     * @Iterations(5)
     * @Warmup(1)
     * @ParamProviders("provideWorkerCounts")
     */
    #[Revs(5)]
    #[Iterations(5)]
    #[Warmup(1)]
    #[ParamProviders('provideWorkerCounts')]
    #[RetryThreshold(10.0)]
    public function benchHashComputationScaling(array $params): void
    {
        $workerCount = $params['workers'];
        $taskCount = 16;
        $tasks = [];

        // Create hash computation tasks
        for ($i = 0; $i < $taskCount; $i++) {
            $data = str_repeat("data-{$i}", 1000);
            $tasks[$i] = $this->createHashTask($data, 5000);
        }

        $this->runParallel($tasks, $workerCount);
    }

    /**
     * Benchmark speedup ratio for different worker counts.
     *
     * Measures actual speedup achieved vs sequential execution.
     * This directly validates parallelization efficiency.
     *
     * Expected speedup formula: speedup = tasks / (tasks/workers + overhead)
     *
     * @Revs(3)
     * @Iterations(5)
     * @Warmup(1)
     * @ParamProviders("provideSpeedupScenarios")
     */
    #[Revs(3)]
    #[Iterations(5)]
    #[Warmup(1)]
    #[ParamProviders('provideSpeedupScenarios')]
    #[RetryThreshold(10.0)]
    public function benchSpeedupRatio(array $params): void
    {
        $workers = $params['workers'];
        $tasks = $params['tasks'];
        $taskDuration = $params['duration'];

        $taskArray = [];
        for ($i = 0; $i < $tasks; $i++) {
            $taskArray[$i] = $this->createCpuTask($taskDuration);
        }

        $this->runParallel($taskArray, $workers);
    }

    /**
     * Provide different worker counts for scaling tests.
     *
     * @return \Generator<string, array<string, int>>
     */
    public function provideWorkerCounts(): \Generator
    {
        yield '1 worker' => ['workers' => 1];
        yield '2 workers' => ['workers' => 2];
        yield '4 workers' => ['workers' => 4];
        yield '8 workers' => ['workers' => 8];
        yield '16 workers' => ['workers' => 16];
    }

    /**
     * Provide scenarios for speedup ratio analysis.
     *
     * @return \Generator<string, array<string, int>>
     */
    public function provideSpeedupScenarios(): \Generator
    {
        // Vary workers and task counts to measure efficiency
        yield '4 workers, 16 tasks, 100ms' => [
            'workers' => 4,
            'tasks' => 16,
            'duration' => 100,
        ];

        yield '8 workers, 32 tasks, 100ms' => [
            'workers' => 8,
            'tasks' => 32,
            'duration' => 100,
        ];

        yield '4 workers, 8 tasks, 200ms' => [
            'workers' => 4,
            'tasks' => 8,
            'duration' => 200,
        ];

        yield '8 workers, 16 tasks, 200ms' => [
            'workers' => 8,
            'tasks' => 16,
            'duration' => 200,
        ];
    }
}
