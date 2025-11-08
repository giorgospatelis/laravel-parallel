<?php

declare(strict_types=1);

namespace LaravelParallel\Contracts;

/**
 * Contract for executing parallel tasks.
 *
 * This interface defines the contract for executors that coordinate
 * the execution of multiple tasks in parallel and collect their results.
 */
interface ExecutorContract
{
    /**
     * Execute an array of tasks in parallel.
     *
     * @param  array<string|int, callable>  $tasks  Array of tasks to execute
     * @return array<string|int, ResultContract> Results indexed by task keys
     *
     * @throws \LaravelParallel\Exceptions\ParallelException If execution fails
     */
    public function execute(array $tasks): array;

    /**
     * Get the configured timeout in seconds.
     */
    public function getTimeout(): ?float;

    /**
     * Set the maximum execution timeout in seconds.
     *
     * @param  float  $seconds  Timeout in seconds (must be > 0)
     */
    public function setTimeout(float $seconds): self;

    /**
     * Set the number of worker processes.
     *
     * @param  int  $count  The number of workers
     */
    public function setWorkerCount(int $count): self;
}
