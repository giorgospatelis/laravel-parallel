<?php

declare(strict_types=1);

namespace LaravelParallel\Exceptions;

use Throwable;

/**
 * Exception thrown when worker pool operations fail.
 *
 * This exception is used for worker pool specific errors such as
 * pool creation failures, worker crashes, or pool shutdown errors.
 */
final class WorkerPoolException extends ParallelException
{
    /**
     * Create an exception for pool initialization failure.
     */
    public static function initializationFailed(Throwable $previous): self
    {
        return new self(
            message: 'Failed to initialize worker pool: '.$previous->getMessage(),
            previous: $previous,
        );
    }

    /**
     * Create an exception for pool already shutdown.
     */
    public static function poolShutdown(): self
    {
        return new self(
            message: 'Worker pool has already been shut down.',
        );
    }

    /**
     * Create an exception for worker crash.
     */
    public static function workerCrashed(int $workerId, Throwable $previous): self
    {
        return new self(
            message: "Worker #{$workerId} crashed: ".$previous->getMessage(),
            previous: $previous,
        );
    }

    /**
     * Create an exception for worker limit exceeded.
     */
    public static function workerLimitExceeded(int $requested, int $maximum): self
    {
        return new self(
            message: "Worker limit exceeded. Requested {$requested} workers, but maximum is {$maximum}.",
        );
    }
}
