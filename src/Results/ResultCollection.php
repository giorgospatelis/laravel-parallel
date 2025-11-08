<?php

declare(strict_types=1);

namespace LaravelParallel\Results;

use Illuminate\Support\Collection;
use LaravelParallel\Contracts\ResultContract;
use Throwable;

/**
 * Collection of parallel execution results.
 *
 * This class extends Laravel's Collection with parallel-specific methods
 * for working with task results, such as filtering successful/failed tasks
 * and extracting values.
 *
 * @extends Collection<array-key, ResultContract>
 */
final class ResultCollection extends Collection
{
    /**
     * Check if all results were successful.
     */
    public function allSuccessful(): bool
    {
        return $this->every(fn (ResultContract $result) => $result->isSuccess());
    }

    /**
     * Check if any results failed.
     */
    public function anyFailed(): bool
    {
        return $this->contains(fn (ResultContract $result) => $result->isFailure());
    }

    /**
     * Get the average execution time across all results.
     */
    public function averageExecutionTime(): float
    {
        if ($this->isEmpty()) {
            return 0.0;
        }

        return $this->totalExecutionTime() / $this->count();
    }

    /**
     * Get all exceptions from failed results.
     *
     * @return \Illuminate\Support\Collection<int|string, \Throwable>
     */
    public function exceptions(): \Illuminate\Support\Collection
    {
        /** @var \Illuminate\Support\Collection<int|string, \Throwable> $exceptions */
        $exceptions = $this->failed()
            ->map(fn (ResultContract $result) => $result->getException())
            ->filter();

        return $exceptions;
    }

    /**
     * Get all failed results.
     *
     * @return self<array-key, ResultContract>
     */
    public function failed(): self
    {
        return $this->filter(fn (ResultContract $result) => $result->isFailure());
    }

    /**
     * Get all successful results.
     *
     * @return self<array-key, ResultContract>
     */
    public function successful(): self
    {
        return $this->filter(fn (ResultContract $result) => $result->isSuccess());
    }

    /**
     * Get the total execution time across all results.
     */
    public function totalExecutionTime(): float
    {
        return $this->sum(fn (ResultContract $result) => $result->getExecutionTime());
    }

    /**
     * Get all result values (throws on any failure).
     *
     * @return Collection<array-key, mixed>
     *
     * @throws Throwable If any result failed
     */
    public function values(): Collection
    {
        return $this->map(fn (ResultContract $result) => $result->getValue());
    }

    /**
     * Get all successful values, using defaults for failures.
     *
     * @param  mixed  $default  Default value for failed results
     * @return Collection<array-key, mixed>
     */
    public function valuesOr(mixed $default = null): Collection
    {
        return $this->map(fn (ResultContract $result) => $result->getValueOr($default));
    }
}
