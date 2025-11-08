<?php

declare(strict_types=1);

namespace LaravelParallel\Tests\Mocks;

use Amp\Cancellation;
use Amp\Parallel\Worker\Execution;
use Amp\Parallel\Worker\Task;
use Amp\Parallel\Worker\Worker;
use Error;
use Throwable;

/**
 * Synchronous mock implementation of Worker for testing.
 *
 * This class executes tasks synchronously in the main process rather than
 * in a separate worker process.
 */
final class SyncWorker implements Worker
{
    private bool $isRunning = true;

    /**
     * Check if the worker is idle.
     *
     * For synchronous execution, the worker is always idle when not executing.
     */
    public function isIdle(): bool
    {
        return $this->isRunning;
    }

    /**
     * Check if the worker is running.
     */
    public function isRunning(): bool
    {
        return $this->isRunning;
    }

    /**
     * Kill the worker immediately.
     */
    public function kill(): void
    {
        $this->isRunning = false;
    }

    /**
     * Shutdown the worker gracefully.
     */
    public function shutdown(): void
    {
        $this->isRunning = false;
    }

    /**
     * Submit a task for synchronous execution.
     *
     * @template TReceive
     * @template TSend
     * @template TResult
     *
     * @param  Task<TReceive, TSend, TResult>  $task
     * @return Execution<TReceive, TSend, TResult>
     */
    public function submit(Task $task, ?Cancellation $cancellation = null): Execution
    {
        if (! $this->isRunning) {
            throw new Error('Cannot submit tasks to a shutdown worker');
        }

        // Create a channel for communication
        $channel = new SyncChannel();

        // Create a cancellation token if provided, or use a no-op one
        if ($cancellation === null) {
            $cancellation = new \Amp\DeferredCancellation();
            $cancellation = $cancellation->getCancellation();
        }

        // Execute the task synchronously and capture the result or exception
        try {
            $result = $task->run($channel, $cancellation);
            $future = \Amp\Future::complete($result);
        } catch (Throwable $e) {
            $future = \Amp\Future::error($e);
        }

        // Return an Execution instance with the completed future
        return new Execution($task, $channel, $future);
    }
}
