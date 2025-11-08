<?php

declare(strict_types=1);

namespace LaravelParallel\Workers;

use Amp\Parallel\Worker\Execution;
use Amp\Parallel\Worker\Task;
use Amp\Parallel\Worker\WorkerPool;
use LaravelParallel\Contracts\WorkerPoolContract;

/**
 * Manages worker pool lifecycle and task submission.
 *
 * This class wraps amphp's WorkerPool and implements our contract,
 * providing additional lifecycle management and tracking capabilities.
 */
final class WorkerPoolManager implements WorkerPoolContract
{
    private bool $isRunning = true;

    public function __construct(
        private readonly WorkerPool $pool,
        private readonly int $workerCount,
    ) {
    }

    /**
     * Get the number of workers in this pool.
     */
    public function getWorkerCount(): int
    {
        return $this->workerCount;
    }

    /**
     * Check if the pool is currently running.
     */
    public function isRunning(): bool
    {
        return $this->isRunning;
    }

    /**
     * Shutdown the worker pool gracefully.
     *
     * Waits for all pending tasks to complete before shutting down workers.
     */
    public function shutdown(): void
    {
        if ($this->isRunning) {
            $this->pool->shutdown();
            $this->isRunning = false;
        }
    }

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
    public function submit(Task $task): Execution
    {
        return $this->pool->submit($task);
    }
}
