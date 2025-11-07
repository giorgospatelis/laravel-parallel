<?php

declare(strict_types=1);

namespace LaravelParallel\Core;

use InvalidArgumentException;
use LaravelParallel\Contracts\ExecutorContract;
use LaravelParallel\Results\ParallelResult;
use LaravelParallel\Support\TaskValidator;

/**
 * Manages parallel task execution using amphp/parallel.
 *
 * This class provides a fluent, Laravel-friendly API for executing closures
 * in parallel across multiple worker processes. It delegates the actual execution
 * to the Executor while providing a clean, chainable interface.
 *
 * Example usage:
 * ```php
 * $results = Parallel::workers(4)
 *     ->timeout(30)
 *     ->run([
 *         'task1' => fn() => expensive_operation(),
 *         'task2' => fn() => another_operation(),
 *     ]);
 * ```
 */
final class ParallelManager
{
    private ?int $workerCount = null;

    public function __construct(
        private readonly ExecutorContract $executor,
        private readonly TaskValidator $validator,
    ) {}

    /**
     * Create a new ParallelManager instance.
     *
     * This method provides a static factory for fluent API usage.
     */
    public static function make(ExecutorContract $executor, TaskValidator $validator): self
    {
        return new self($executor, $validator);
    }

    /**
     * Get the configured worker count.
     */
    public function getWorkerCount(): ?int
    {
        return $this->workerCount;
    }

    /**
     * Execute a parallel map operation over an iterable.
     *
     * This method applies a callback to each item in the iterable in parallel,
     * returning an array of results. This is equivalent to array_map but executed
     * across multiple worker processes.
     *
     * Example:
     * ```php
     * $results = Parallel::map([1, 2, 3], fn($n) => $n * 2);
     * // Returns: [ParallelResult(2), ParallelResult(4), ParallelResult(6)]
     * ```
     *
     * @param  iterable<mixed>  $items  Items to process
     * @param  callable  $callback  Function to apply to each item
     * @return array<int|string, ParallelResult> Array of results
     *
     * @throws \LaravelParallel\Exceptions\ParallelException If execution fails
     */
    public function map(iterable $items, callable $callback): array
    {
        $closures = [];

        foreach ($items as $key => $item) {
            $stringKey = is_string($key) || is_int($key) ? (string) $key : '';
            $closures[$stringKey] = fn () => $callback($item);
        }

        return $this->run($closures);
    }

    /**
     * Execute closures in parallel and return results.
     *
     * This method submits all closures to the worker pool, waits for completion,
     * and collects results. Failed tasks are captured as ParallelResult objects
     * with exceptions rather than being thrown immediately.
     *
     * @param  array<string|int, callable>  $closures  Associative array of closures to execute
     * @return array<string|int, ParallelResult> Results indexed by original keys
     *
     * @throws \LaravelParallel\Exceptions\ParallelException If validation fails or pool creation fails
     */
    public function run(array $closures): array
    {
        return $this->executor->execute($closures);
    }

    /**
     * Set the maximum execution timeout.
     *
     * @param  float  $seconds  Timeout in seconds (must be > 0)
     *
     * @throws \LaravelParallel\Exceptions\ParallelException If timeout is invalid
     */
    public function timeout(float $seconds): self
    {
        $this->validator->validateTimeout($seconds);
        $this->executor->setTimeout($seconds);

        return $this;
    }

    /**
     * Set the number of worker processes.
     *
     * @param  int  $count  The number of workers (must be >= 1 and <= max_workers config)
     *
     * @throws \LaravelParallel\Exceptions\ParallelException If worker count is invalid
     * @throws InvalidArgumentException If configuration is invalid
     */
    public function workers(int $count): self
    {
        $maxWorkers = config('parallel.max_workers', 128);
        if (! is_int($maxWorkers)) {
            throw new InvalidArgumentException(
                'Configuration "parallel.max_workers" must be an integer, got: '.gettype($maxWorkers)
            );
        }

        $this->validator->validateWorkerCount($count, 1, $maxWorkers);
        $this->workerCount = $count;
        $this->executor->setWorkerCount($count);

        return $this;
    }
}
