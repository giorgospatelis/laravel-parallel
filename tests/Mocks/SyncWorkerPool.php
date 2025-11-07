<?php

declare(strict_types=1);

namespace LaravelParallel\Tests\Mocks;

use Amp\Cancellation;
use Amp\Parallel\Worker\Execution;
use Amp\Parallel\Worker\Task;
use Amp\Parallel\Worker\WorkerPool;

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
    ) {}

    /**
     * Get the worker limit (simulated worker count).
     *
     * @return int
     */
    public function getLimit(): int
    {
        return $this->workerLimit;
    }

    /**
     * Get the number of idle workers.
     *
     * For synchronous execution, we always return the full limit
     * since no workers are actually busy.
     *
     * @return int
     */
    public function getIdleWorkerCount(): int
    {
        return $this->isShutdown ? 0 : $this->workerLimit;
    }

    /**
     * Get the number of workers in the pool.
     *
     * @return int
     */
    public function getWorkerCount(): int
    {
        return $this->isShutdown ? 0 : $this->workerLimit;
    }

    /**
     * Check if the pool is running.
     *
     * @return bool
     */
    public function isRunning(): bool
    {
        return !$this->isShutdown;
    }

    /**
     * Check if the pool is idle (all workers available).
     *
     * @return bool
     */
    public function isIdle(): bool
    {
        return !$this->isShutdown;
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
     * @param Task<TReceive, TSend, TResult> $task
     * @param Cancellation|null $cancellation
     * @return Execution<TReceive, TSend, TResult>
     *
     * @throws \Error if the pool has been shutdown
     */
    public function submit(Task $task, ?Cancellation $cancellation = null): Execution
    {
        if ($this->isShutdown) {
            throw new \Error('Cannot submit tasks to a shutdown worker pool');
        }

        // Get a worker and submit the task to it
        $worker = $this->getWorker();

        return $worker->submit($task, $cancellation);
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
     * Get a worker from the pool.
     *
     * For synchronous execution, we don't actually have workers,
     * so this returns a mock worker.
     *
     * @return \Amp\Parallel\Worker\Worker
     */
    public function getWorker(): \Amp\Parallel\Worker\Worker
    {
        if ($this->isShutdown) {
            throw new \Error('Cannot get worker from a shutdown pool');
        }

        return new SyncWorker();
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
}
