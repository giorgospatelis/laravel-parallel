<?php

declare(strict_types=1);

namespace LaravelParallel\Support;

use LaravelParallel\Exceptions\ParallelException;

/**
 * Validates tasks before parallel execution.
 *
 * This class provides validation utilities to ensure tasks meet the requirements
 * for parallel execution, such as being callable and properly formatted.
 */
final class TaskValidator
{
    /**
     * Perform all standard validations for task execution.
     *
     * @param  array<string|int, mixed>  $tasks  Tasks to validate
     *
     * @throws ParallelException If validation fails
     */
    public function validateAll(array $tasks): void
    {
        $this->validateNotEmpty($tasks);
        $this->validateCallables($tasks);
        $this->validateTaskCount(count($tasks));
    }

    /**
     * Validate that all array elements are callable.
     *
     * @param  array<string|int, mixed>  $tasks  Array to validate
     *
     * @throws ParallelException If any element is not callable
     */
    public function validateCallables(array $tasks): void
    {
        foreach ($tasks as $key => $task) {
            if (! is_callable($task)) {
                throw ParallelException::invalidClosure((string) $key);
            }
        }
    }

    /**
     * Validate that the tasks array is not empty.
     *
     * @param  array<string|int, mixed>  $tasks  Array to validate
     *
     * @throws ParallelException If the array is empty
     */
    public function validateNotEmpty(array $tasks): void
    {
        if (empty($tasks)) {
            throw ParallelException::emptyClosures();
        }
    }

    /**
     * Validate that task count doesn't exceed configured limits.
     *
     * This validation protects against resource exhaustion and DoS attacks
     * by limiting the number of tasks that can be submitted in a single batch.
     *
     * @param  int  $taskCount  Number of tasks to validate
     *
     * @throws ParallelException If the task count exceeds limits and auto-chunking is disabled
     */
    public function validateTaskCount(int $taskCount): void
    {
        $maxTasks = config('parallel.max_tasks_per_batch', 10000);

        // Allow 0 to disable limit
        if ($maxTasks <= 0) {
            return;
        }

        $autoChunk = config('parallel.auto_chunk', true);

        if ($taskCount > $maxTasks && ! $autoChunk) {
            throw ParallelException::tooManyTasks($taskCount, $maxTasks);
        }
    }

    /**
     * Validate that a timeout value is positive.
     *
     * @param  float  $timeout  The timeout to validate
     *
     * @throws ParallelException If the timeout is invalid
     */
    public function validateTimeout(float $timeout): void
    {
        if ($timeout <= 0) {
            throw ParallelException::invalidTimeout($timeout);
        }
    }

    /**
     * Validate that a worker count is within acceptable bounds.
     *
     * @param  int  $count  The worker count to validate
     * @param  int  $min  Minimum allowed workers
     * @param  int  $max  Maximum allowed workers
     *
     * @throws ParallelException If the count is out of bounds
     */
    public function validateWorkerCount(int $count, int $min = 1, int $max = 128): void
    {
        if ($count < $min || $count > $max) {
            throw ParallelException::invalidWorkerCount($count, $max);
        }
    }
}
