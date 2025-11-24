<?php

declare(strict_types=1);

namespace LaravelParallel\Benchmarking;

use LaravelParallel\Contracts\BenchmarkScenario;
use LaravelParallel\Facades\Parallel;
use LaravelParallel\Support\CpuDetector;

/**
 * Orchestrates benchmark scenario execution.
 *
 * Runs benchmark scenarios using the Parallel facade and collects
 * performance metrics for analysis.
 */
final readonly class BenchmarkRunner
{
    /**
     * Create a new BenchmarkRunner instance.
     *
     * @param  CpuDetector  $cpuDetector  CPU detection service
     */
    public function __construct(
        private CpuDetector $cpuDetector
    ) {
    }
    /**
     * Run a single benchmark scenario.
     *
     * @param  BenchmarkScenario  $scenario  The scenario to benchmark
     * @param  int|null  $workerCount  Number of workers (null = auto-detect)
     * @param  int  $iterations  Number of tasks to execute
     * @return BenchmarkResult The benchmark results
     */
    public function run(
        BenchmarkScenario $scenario,
        ?int $workerCount = null,
        int $iterations = 100
    ): BenchmarkResult {
        $tasks = $scenario->getTasks($iterations);

        $memoryBefore = memory_get_peak_usage(true);
        $startTime = microtime(true);

        // Execute tasks in parallel using the Parallel facade
        $actualWorkerCount = $workerCount ?? $this->cpuDetector->detect();
        $parallelManager = Parallel::workers($actualWorkerCount);
        $results = $parallelManager->run($tasks);

        $endTime = microtime(true);
        $memoryAfter = memory_get_peak_usage(true);

        $totalTime = $endTime - $startTime;
        $averageTimePerTask = $totalTime / $iterations;
        $throughput = $iterations / $totalTime;
        $memoryPeak = $memoryAfter - $memoryBefore;

        return new BenchmarkResult(
            scenarioName: $scenario->getName(),
            category: $scenario->getCategory(),
            iterations: $iterations,
            workerCount: $actualWorkerCount,
            totalTime: $totalTime,
            averageTimePerTask: $averageTimePerTask,
            throughput: $throughput,
            memoryPeakBytes: max(0, $memoryPeak), // Ensure non-negative
        );
    }

    /**
     * Run multiple benchmark scenarios.
     *
     * @param  array<BenchmarkScenario>  $scenarios  Scenarios to benchmark
     * @param  int|null  $workerCount  Number of workers (null = auto-detect)
     * @param  int  $iterations  Number of tasks per scenario
     * @return array<BenchmarkResult> Array of benchmark results
     */
    public function runMultiple(
        array $scenarios,
        ?int $workerCount = null,
        int $iterations = 100
    ): array {
        $results = [];

        foreach ($scenarios as $scenario) {
            $results[] = $this->run($scenario, $workerCount, $iterations);
        }

        return $results;
    }
}
