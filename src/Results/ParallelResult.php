<?php

declare(strict_types=1);

namespace LaravelParallel\Results;

use LaravelParallel\Contracts\ResultContract;
use Throwable;

/**
 * Represents the result of a parallel task execution.
 *
 * This class encapsulates the outcome of a task executed in a parallel worker,
 * including the value (on success), exception (on failure), execution time,
 * and metadata about the execution.
 */
final class ParallelResult implements ResultContract
{
    /**
     * Create a new ParallelResult instance.
     *
     * @param  mixed  $value  The successful result value, or null if task failed
     * @param  Throwable|null  $exception  The exception that occurred, or null if task succeeded
     * @param  float  $executionTime  The time taken to execute the task in seconds
     * @param  bool  $isSuccess  Whether the task completed successfully
     */
    public function __construct(
        private readonly mixed $value = null,
        private readonly ?Throwable $exception = null,
        private readonly float $executionTime = 0.0,
        private readonly bool $isSuccess = true,
    ) {
    }

    /**
     * Create a failed result with an exception.
     *
     * @param  Throwable  $exception  The exception that occurred
     * @param  float  $executionTime  The execution time in seconds
     */
    public static function failure(Throwable $exception, float $executionTime = 0.0): self
    {
        return new self(
            value: null,
            exception: $exception,
            executionTime: $executionTime,
            isSuccess: false,
        );
    }

    /**
     * Create a successful result with a value.
     *
     * @param  mixed  $value  The result value
     * @param  float  $executionTime  The execution time in seconds
     */
    public static function success(mixed $value, float $executionTime = 0.0): self
    {
        return new self(
            value: $value,
            exception: null,
            executionTime: $executionTime,
            isSuccess: true,
        );
    }

    /**
     * Get the exception that occurred during execution.
     */
    public function getException(): ?Throwable
    {
        return $this->exception;
    }

    /**
     * Get the execution time in seconds.
     */
    public function getExecutionTime(): float
    {
        return $this->executionTime;
    }

    /**
     * Get the result value.
     *
     * @return mixed The result value
     *
     * @throws Throwable If the task failed
     */
    public function getValue(): mixed
    {
        if (! $this->isSuccess && $this->exception !== null) {
            throw $this->exception;
        }

        return $this->value;
    }

    /**
     * Get the result value or a default if the task failed.
     *
     * @param  mixed  $default  The default value to return on failure
     */
    public function getValueOr(mixed $default): mixed
    {
        return $this->isSuccess ? $this->value : $default;
    }

    /**
     * Execute a callback if the task failed.
     *
     * @param  callable  $callback  Function to execute with the exception
     */
    public function ifFailure(callable $callback): self
    {
        if (! $this->isSuccess) {
            $callback($this->exception);
        }

        return $this;
    }

    /**
     * Execute a callback if the task succeeded.
     *
     * @param  callable  $callback  Function to execute with the value
     */
    public function ifSuccess(callable $callback): self
    {
        if ($this->isSuccess) {
            $callback($this->value);
        }

        return $this;
    }

    /**
     * Check if the task failed.
     */
    public function isFailure(): bool
    {
        return ! $this->isSuccess;
    }

    /**
     * Check if the task completed successfully.
     */
    public function isSuccess(): bool
    {
        return $this->isSuccess;
    }
}
