<?php

declare(strict_types=1);

namespace LaravelParallel\Exceptions;

/**
 * Exception thrown when a task execution times out.
 *
 * This exception is thrown when a parallel task exceeds the configured
 * maximum execution time.
 */
final class TimeoutException extends ParallelException
{
    /**
     * Create an exception for pool operation timeout.
     */
    public static function poolOperationTimedOut(string $operation, float $timeout): self
    {
        return new self(
            message: "Pool operation '{$operation}' timed out after {$timeout} seconds.",
        );
    }

    /**
     * Create an exception for task execution timeout.
     */
    public static function taskTimedOut(string|int $taskKey, float $timeout): self
    {
        return new self(
            message: "Task '{$taskKey}' timed out after {$timeout} seconds.",
        );
    }
}
