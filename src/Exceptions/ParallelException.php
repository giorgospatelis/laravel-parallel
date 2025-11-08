<?php

declare(strict_types=1);

namespace LaravelParallel\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Exception thrown when parallel processing encounters an error.
 *
 * This exception serves as the base exception for all parallel processing
 * related errors, including validation failures, worker pool errors,
 * and task execution failures.
 */
class ParallelException extends RuntimeException
{
    /**
     * Create a new ParallelException instance.
     *
     * @param  string  $message  The exception message
     * @param  int  $code  The exception code
     * @param  Throwable|null  $previous  The previous exception for exception chaining
     */
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create an exception for CPU core detection failure.
     */
    public static function cpuDetectionFailed(): self
    {
        return new self(
            message: 'Failed to detect CPU core count. Please specify worker count manually.',
        );
    }

    /**
     * Create an exception for empty closure array.
     */
    public static function emptyClosures(): self
    {
        return new self(
            message: 'No closures provided for parallel execution.',
        );
    }

    /**
     * Create an exception for invalid closure validation.
     *
     * @param  string  $key  The key of the invalid closure
     */
    public static function invalidClosure(string $key): self
    {
        return new self(
            message: "Invalid closure provided at key '{$key}'. Expected a callable.",
        );
    }

    /**
     * Create an exception for invalid timeout.
     *
     * @param  float  $timeout  The invalid timeout value
     */
    public static function invalidTimeout(float $timeout): self
    {
        return new self(
            message: "Invalid timeout: {$timeout}. Must be greater than 0.",
        );
    }

    /**
     * Create an exception for invalid worker count.
     *
     * @param  int  $count  The invalid worker count
     * @param  int  $max  The maximum allowed workers
     */
    public static function invalidWorkerCount(int $count, int $max): self
    {
        return new self(
            message: "Invalid worker count: {$count}. Must be between 1 and {$max}.",
        );
    }

    /**
     * Create an exception for task execution failure.
     *
     * @param  string|int  $key  The task key
     * @param  Throwable  $previous  The underlying exception
     */
    public static function taskExecutionFailed(string|int $key, Throwable $previous): self
    {
        return new self(
            message: "Task at key '{$key}' failed: ".$previous->getMessage(),
            previous: $previous,
        );
    }

    /**
     * Create an exception when too many tasks are submitted.
     *
     * This helps prevent resource exhaustion and DoS attacks by limiting
     * the number of tasks that can be processed in a single batch.
     *
     * @param  int  $count  The number of tasks submitted
     * @param  int  $max  The maximum allowed tasks
     */
    public static function tooManyTasks(int $count, int $max): self
    {
        return new self(
            message: sprintf(
                'Too many tasks submitted: %d tasks exceeds the limit of %d. '.
                'Consider enabling auto-chunking (parallel.auto_chunk) or reducing batch size.',
                $count,
                $max
            ),
        );
    }

    /**
     * Create an exception for worker pool creation failure.
     *
     * @param  Throwable  $previous  The underlying exception
     */
    public static function workerPoolCreationFailed(Throwable $previous): self
    {
        return new self(
            message: 'Failed to create worker pool: '.$previous->getMessage(),
            previous: $previous,
        );
    }
}
