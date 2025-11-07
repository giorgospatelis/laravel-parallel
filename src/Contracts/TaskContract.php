<?php

declare(strict_types=1);

namespace LaravelParallel\Contracts;

use Amp\Parallel\Worker\Task;

/**
 * Contract for parallel tasks that can be executed in worker processes.
 *
 * This interface defines the contract for tasks that can be serialized
 * and executed in parallel worker processes. Implementations should be
 * serializable and contain all necessary data for execution.
 *
 * @template TReceive
 * @template TSend
 * @template TResult
 *
 * @extends Task<TReceive, TSend, TResult>
 */
interface TaskContract extends Task
{
    /**
     * Get a unique identifier for this task.
     *
     * This can be used for tracking, logging, or debugging purposes.
     */
    public function getId(): string;

    /**
     * Check if the task is serializable.
     *
     * Returns true if the task can be safely serialized and sent to a worker.
     */
    public function isSerializable(): bool;
}
