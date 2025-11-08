<?php

declare(strict_types=1);

namespace LaravelParallel\Tests\Mocks;

use Amp\Cancellation;
use Amp\Parallel\Worker\Execution;
use Amp\Parallel\Worker\Task;
use Amp\Parallel\Worker\WorkerPool;
use Error;

/**
 * Synchronous mock implementation of WorkerPool for testing.
 *
 * This class executes tasks synchronously in the main process rather than
 * distributing them to worker processes. This allows tests to run without
 * spawning real processes, making them compatible with code coverage tools
 * like PCOV which hang when trying to collect coverage from forked processes.
 *
 * The mock is designed to be a drop-in replacement for AMPHP's ContextWorkerPool,
 * implementing the same WorkerPool interface but with synchronous execution.
 */
final class SyncWorkerPool implements WorkerPool
{
    private bool $isShutdown = false;

    public function __construct(
        private readonly int $workerLimit,
    ) {
    }

    /**
     * Get the number of idle workers.
     *
     * For synchronous execution, we always return the full limit
     * since no workers are actually busy.
     */
    public function getIdleWorkerCount(): int
    {
        return $this->isShutdown ? 0 : $this->workerLimit;
    }

    /**
     * Get the worker limit (simulated worker count).
     */
    public function getLimit(): int
    {
        return $this->workerLimit;
    }

    /**
     * Get a worker from the pool.
     *
     * For synchronous execution, we don't actually have workers,
     * so this returns a mock worker.
     */
    public function getWorker(): \Amp\Parallel\Worker\Worker
    {
        if ($this->isShutdown) {
            throw new Error('Cannot get worker from a shutdown pool');
        }

        return new SyncWorker();
    }

    /**
     * Get the number of workers in the pool.
     */
    public function getWorkerCount(): int
    {
        return $this->isShutdown ? 0 : $this->workerLimit;
    }

    /**
     * Check if the pool is idle (all workers available).
     */
    public function isIdle(): bool
    {
        return ! $this->isShutdown;
    }

    /**
     * Check if the pool is running.
     */
    public function isRunning(): bool
    {
        return ! $this->isShutdown;
    }

    /**
     * Kill the pool immediately.
     *
     * For synchronous execution, this is the same as shutdown.
     */
    public function kill(): void
    {
        $this->isShutdown = true;
    }

    /**
     * Shutdown the worker pool.
     *
     * For synchronous execution, this just marks the pool as shutdown.
     * No actual processes to terminate.
     */
    public function shutdown(): void
    {
        $this->isShutdown = true;
    }

    /**
     * Submit a task for synchronous execution.
     *
     * Instead of submitting to a worker process, this executes the task
     * immediately in the current process and returns an Execution.
     *
     * @template TReceive
     * @template TSend
     * @template TResult
     *
     * @param  Task<TReceive, TSend, TResult>  $task
     * @return Execution<TReceive, TSend, TResult>
     *
     * @throws Error if the pool has been shutdown
     */
    public function submit(Task $task, ?Cancellation $cancellation = null): Execution
    {
        if ($this->isShutdown) {
            throw new Error('Cannot submit tasks to a shutdown worker pool');
        }

        // Get a worker and submit the task to it
        $worker = $this->getWorker();

        return $worker->submit($task, $cancellation);
    }
}
