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
 * Core parallel execution benchmarks.
 *
 * These benchmarks measure the fundamental performance characteristics of
 * parallel execution compared to sequential execution. They validate the
 * basic overhead claims and demonstrate the performance benefits.
 *
 * @BeforeMethods("setUp")
 */
#[BeforeMethods('setUp')]
class CoreParallelBench extends BaseBenchmark
{
    /**
     * Benchmark setup overhead.
     *
     * This measures the time it takes to set up the parallel execution
     * environment (worker pool initialization). According to README, this
     * should be ~2-5ms per execution.
     *
     * @Revs(100)
     * @Iterations(10)
     * @Warmup(2)
     */
    #[Revs(100)]
    #[Iterations(10)]
    #[Warmup(2)]
    #[RetryThreshold(5.0)]
    public function benchSetupOverhead(): void
    {
        // Create a simple task that returns immediately
        $tasks = [
            'noop' => fn () => true,
        ];

        // Execute - this measures setup + minimal execution
        $this->runParallel($tasks, 1);
    }

    /**
     * Benchmark minimal task overhead (sequential).
     *
     * Establish a baseline for sequential execution of minimal tasks.
     * This helps us calculate the per-task overhead of parallelization.
     *
     * @Revs(50)
     * @Iterations(10)
     * @Warmup(2)
     * @ParamProviders("provideTaskCounts")
     */
    #[Revs(50)]
    #[Iterations(10)]
    #[Warmup(2)]
    #[ParamProviders('provideTaskCounts')]
    #[RetryThreshold(5.0)]
    public function benchSequentialMinimal(array $params): void
    {
        $taskCount = $params['tasks'];
        $tasks = [];

        for ($i = 0; $i < $taskCount; $i++) {
            $tasks[$i] = fn () => $i * 2;
        }

        $this->runSequential($tasks);
    }

    /**
     * Benchmark minimal task overhead (parallel).
     *
     * Measure the overhead of parallelizing minimal tasks. According to README,
     * per-task overhead should be ~0.5-1ms (serialization + IPC).
     *
     * @Revs(50)
     * @Iterations(10)
     * @Warmup(2)
     * @ParamProviders("provideTaskCounts")
     */
    #[Revs(50)]
    #[Iterations(10)]
    #[Warmup(2)]
    #[ParamProviders('provideTaskCounts')]
    #[RetryThreshold(5.0)]
    public function benchParallelMinimal(array $params): void
    {
        $taskCount = $params['tasks'];
        $tasks = [];

        for ($i = 0; $i < $taskCount; $i++) {
            $tasks[$i] = fn () => $i * 2;
        }

        $this->runParallel($tasks);
    }

    /**
     * Benchmark sequential execution with CPU work (100ms per task).
     *
     * This establishes the baseline for CPU-bound work that should benefit
     * from parallelization.
     *
     * @Revs(5)
     * @Iterations(5)
     * @Warmup(1)
     * @ParamProviders("provideTaskCounts")
     */
    #[Revs(5)]
    #[Iterations(5)]
    #[Warmup(1)]
    #[ParamProviders('provideTaskCounts')]
    #[RetryThreshold(10.0)]
    public function benchSequentialCpuWork(array $params): void
    {
        $taskCount = $params['tasks'];
        $tasks = [];

        for ($i = 0; $i < $taskCount; $i++) {
            $tasks[$i] = $this->createCpuTask(100);
        }

        $this->runSequential($tasks);
    }

    /**
     * Benchmark parallel execution with CPU work (100ms per task).
     *
     * This should demonstrate significant speedup compared to sequential
     * execution when using multiple workers.
     *
     * @Revs(5)
     * @Iterations(5)
     * @Warmup(1)
     * @ParamProviders("provideTaskCounts")
     */
    #[Revs(5)]
    #[Iterations(5)]
    #[Warmup(1)]
    #[ParamProviders('provideTaskCounts')]
    #[RetryThreshold(10.0)]
    public function benchParallelCpuWork(array $params): void
    {
        $taskCount = $params['tasks'];
        $cpuCount = $this->getCpuCount();
        $tasks = [];

        for ($i = 0; $i < $taskCount; $i++) {
            $tasks[$i] = $this->createCpuTask(100);
        }

        $this->runParallel($tasks, $cpuCount);
    }

    /**
     * Benchmark Parallel::map() with transformation (sequential baseline).
     *
     * Test the map() convenience method against sequential array_map.
     *
     * @Revs(10)
     * @Iterations(5)
     * @Warmup(1)
     */
    #[Revs(10)]
    #[Iterations(5)]
    #[Warmup(1)]
    #[RetryThreshold(5.0)]
    public function benchSequentialMap(): void
    {
        $items = range(1, 50);

        array_map(function ($item) {
            return $this->cpuWork(50);
        }, $items);
    }

    /**
     * Benchmark Parallel::map() with transformation (parallel).
     *
     * Test the map() convenience method with parallel execution.
     *
     * @Revs(10)
     * @Iterations(5)
     * @Warmup(1)
     */
    #[Revs(10)]
    #[Iterations(5)]
    #[Warmup(1)]
    #[RetryThreshold(5.0)]
    public function benchParallelMap(): void
    {
        $items = range(1, 50);
        $cpuCount = $this->getCpuCount();

        \LaravelParallel\Facades\Parallel::workers($cpuCount)
            ->map($items, function ($item) {
                return $this->cpuWork(50);
            });
    }

    /**
     * Benchmark error handling overhead (sequential).
     *
     * Measure the baseline cost of handling errors in sequential execution.
     *
     * @Revs(20)
     * @Iterations(5)
     * @Warmup(1)
     */
    #[Revs(20)]
    #[Iterations(5)]
    #[Warmup(1)]
    #[RetryThreshold(5.0)]
    public function benchSequentialWithErrors(): void
    {
        $tasks = [
            'success1' => fn () => 'ok',
            'fail1' => fn () => throw new \Exception('Error'),
            'success2' => fn () => 'ok',
            'fail2' => fn () => throw new \Exception('Error'),
        ];

        try {
            $this->runSequential($tasks);
        } catch (\Exception $e) {
            // Expected - some tasks fail
        }
    }

    /**
     * Benchmark error handling overhead (parallel).
     *
     * Measure how parallel execution handles task failures. Parallel should
     * isolate failures without stopping other tasks.
     *
     * @Revs(20)
     * @Iterations(5)
     * @Warmup(1)
     */
    #[Revs(20)]
    #[Iterations(5)]
    #[Warmup(1)]
    #[RetryThreshold(5.0)]
    public function benchParallelWithErrors(): void
    {
        $tasks = [
            'success1' => fn () => 'ok',
            'fail1' => fn () => throw new \Exception('Error'),
            'success2' => fn () => 'ok',
            'fail2' => fn () => throw new \Exception('Error'),
        ];

        // Parallel execution continues despite failures
        $this->runParallel($tasks);
    }

    /**
     * Provide different task counts for parameterized benchmarks.
     *
     * @return \Generator<string, array<string, int>>
     */
    public function provideTaskCounts(): \Generator
    {
        // Test with different batch sizes
        yield '5 tasks' => ['tasks' => 5];
        yield '10 tasks' => ['tasks' => 10];
        yield '25 tasks' => ['tasks' => 25];
        yield '50 tasks' => ['tasks' => 50];
    }
}
