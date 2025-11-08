<?php

declare(strict_types=1);

namespace LaravelParallel\Results;

/**
 * Immutable metrics for parallel execution.
 *
 * This value object encapsulates execution metrics such as timing,
 * success/failure counts, and performance statistics.
 */
final readonly class ExecutionMetrics
{
    /**
     * Create a new ExecutionMetrics instance.
     *
     * @param  int  $totalTasks  Total number of tasks executed
     * @param  int  $successfulTasks  Number of tasks that succeeded
     * @param  int  $failedTasks  Number of tasks that failed
     * @param  float  $totalExecutionTime  Total execution time in seconds
     * @param  float  $averageExecutionTime  Average execution time per task in seconds
     * @param  float  $minExecutionTime  Minimum execution time in seconds
     * @param  float  $maxExecutionTime  Maximum execution time in seconds
     */
    public function __construct(
        public int $totalTasks,
        public int $successfulTasks,
        public int $failedTasks,
        public float $totalExecutionTime,
        public float $averageExecutionTime,
        public float $minExecutionTime,
        public float $maxExecutionTime,
    ) {
    }

    /**
     * Create metrics from a result collection.
     */
    public static function fromResults(ResultCollection $results): self
    {
        /** @var array<float> $executionTimes */
        $executionTimes = $results->map(fn ($r) => $r->getExecutionTime())->all();

        $minTime = 0.0;
        $maxTime = 0.0;
        if (! empty($executionTimes)) {
            /** @var float $minValue */
            $minValue = min($executionTimes);
            /** @var float $maxValue */
            $maxValue = max($executionTimes);
            $minTime = $minValue;
            $maxTime = $maxValue;
        }

        return new self(
            totalTasks: $results->count(),
            successfulTasks: $results->successful()->count(),
            failedTasks: $results->failed()->count(),
            totalExecutionTime: $results->totalExecutionTime(),
            averageExecutionTime: $results->averageExecutionTime(),
            minExecutionTime: $minTime,
            maxExecutionTime: $maxTime,
        );
    }

    /**
     * Get the failure rate as a percentage (0-100).
     */
    public function failureRate(): float
    {
        if ($this->totalTasks === 0) {
            return 0.0;
        }

        return ($this->failedTasks / $this->totalTasks) * 100;
    }

    /**
     * Get the success rate as a percentage (0-100).
     */
    public function successRate(): float
    {
        if ($this->totalTasks === 0) {
            return 0.0;
        }

        return ($this->successfulTasks / $this->totalTasks) * 100;
    }

    /**
     * Convert metrics to an array.
     *
     * @return array<string, int|float>
     */
    public function toArray(): array
    {
        return [
            'total_tasks' => $this->totalTasks,
            'successful_tasks' => $this->successfulTasks,
            'failed_tasks' => $this->failedTasks,
            'success_rate' => $this->successRate(),
            'failure_rate' => $this->failureRate(),
            'total_execution_time' => $this->totalExecutionTime,
            'average_execution_time' => $this->averageExecutionTime,
            'min_execution_time' => $this->minExecutionTime,
            'max_execution_time' => $this->maxExecutionTime,
        ];
    }
}
