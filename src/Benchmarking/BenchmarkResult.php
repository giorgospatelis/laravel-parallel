<?php

declare(strict_types=1);

namespace LaravelParallel\Benchmarking;

/**
 * Immutable value object representing benchmark execution results.
 *
 * Contains performance metrics from a benchmark scenario execution.
 */
final readonly class BenchmarkResult
{
    /**
     * Create a new benchmark result.
     *
     * @param  string  $scenarioName  Name of the benchmark scenario
     * @param  string  $category  Scenario category (cpu-bound, io-bound, etc.)
     * @param  int  $iterations  Number of tasks executed
     * @param  int  $workerCount  Number of parallel workers used
     * @param  float  $totalTime  Total execution time in seconds
     * @param  float  $averageTimePerTask  Average time per task in seconds
     * @param  float  $throughput  Tasks processed per second
     * @param  int  $memoryPeakBytes  Peak memory usage in bytes
     */
    public function __construct(
        public string $scenarioName,
        public string $category,
        public int $iterations,
        public int $workerCount,
        public float $totalTime,
        public float $averageTimePerTask,
        public float $throughput,
        public int $memoryPeakBytes,
    ) {}

    /**
     * Get formatted average time per task.
     *
     * @return string Formatted time string
     */
    public function getFormattedAverageTime(): string
    {
        return number_format($this->averageTimePerTask * 1000, 2).' ms';
    }

    /**
     * Get formatted memory usage.
     *
     * @return string Formatted memory string
     */
    public function getFormattedMemory(): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->memoryPeakBytes;
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return number_format($bytes, 2).' '.$units[$unitIndex];
    }

    /**
     * Get formatted throughput.
     *
     * @return string Formatted throughput string
     */
    public function getFormattedThroughput(): string
    {
        return number_format($this->throughput, 2).' tasks/sec';
    }

    /**
     * Get formatted total time.
     *
     * @return string Formatted time string
     */
    public function getFormattedTotalTime(): string
    {
        return number_format($this->totalTime, 4).' seconds';
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'scenario' => $this->scenarioName,
            'category' => $this->category,
            'iterations' => $this->iterations,
            'workers' => $this->workerCount,
            'total_time' => $this->totalTime,
            'average_time' => $this->averageTimePerTask,
            'throughput' => $this->throughput,
            'memory_peak' => $this->memoryPeakBytes,
        ];
    }
}
