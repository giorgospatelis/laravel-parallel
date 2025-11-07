<?php

declare(strict_types=1);

namespace LaravelParallel\Contracts;

use Throwable;

/**
 * Contract for representing the result of a parallel task execution.
 *
 * This interface defines the contract for task results, which encapsulate
 * both successful values and failure exceptions, along with execution metadata.
 */
interface ResultContract
{
    /**
     * Get the exception that occurred during execution.
     */
    public function getException(): ?Throwable;

    /**
     * Get the execution time in seconds.
     */
    public function getExecutionTime(): float;

    /**
     * Get the result value.
     *
     * @throws Throwable If the task failed
     */
    public function getValue(): mixed;

    /**
     * Get the result value or a default if the task failed.
     *
     * @param  mixed  $default  The default value to return on failure
     */
    public function getValueOr(mixed $default): mixed;

    /**
     * Execute a callback if the task failed.
     *
     * @param  callable  $callback  Function to execute with the exception
     */
    public function ifFailure(callable $callback): self;

    /**
     * Execute a callback if the task succeeded.
     *
     * @param  callable  $callback  Function to execute with the value
     */
    public function ifSuccess(callable $callback): self;

    /**
     * Check if the task failed.
     */
    public function isFailure(): bool;

    /**
     * Check if the task completed successfully.
     */
    public function isSuccess(): bool;
}
