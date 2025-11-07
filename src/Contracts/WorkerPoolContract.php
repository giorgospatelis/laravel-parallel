<?php

declare(strict_types=1);

namespace LaravelParallel\Contracts;

use Amp\Parallel\Worker\Execution;
use Amp\Parallel\Worker\Task;

/**
 * Contract for managing worker pools in parallel processing.
 *
 * This interface defines the contract for creating, managing, and shutting down
 * worker pools that execute tasks in parallel.
 */
interface WorkerPoolContract
{
    /**
     * Get the number of workers in this pool.
     */
    public function getWorkerCount(): int;

    /**
     * Check if the pool is currently running.
     */
    public function isRunning(): bool;

    /**
     * Shutdown the worker pool gracefully.
     *
     * Waits for all pending tasks to complete before shutting down workers.
     */
    public function shutdown(): void;

    /**
     * Submit a task to the worker pool for execution.
     *
     * @template TReceive
     * @template TSend
     * @template TResult
     *
     * @param  Task<TReceive, TSend, TResult>  $task  The task to execute
     * @return Execution<TReceive, TSend, TResult> The execution handle
     */
    public function submit(Task $task): Execution;
}
