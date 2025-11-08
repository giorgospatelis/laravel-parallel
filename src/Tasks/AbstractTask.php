<?php

declare(strict_types=1);

namespace LaravelParallel\Tasks;

use LaravelParallel\Contracts\TaskContract;

/**
 * Base class for parallel tasks.
 *
 * This abstract class provides common functionality for tasks that can be
 * executed in parallel worker processes, including unique identification
 * and serialization checking.
 *
 * @template TReceive
 * @template TSend
 * @template TResult
 *
 * @implements TaskContract<TReceive, TSend, TResult>
 */
abstract class AbstractTask implements TaskContract
{
    /**
     * Unique identifier for this task instance (lazy-loaded).
     */
    private ?string $id = null;

    public function __construct()
    {
        // ID is now lazy-loaded on first access
    }

    /**
     * Ensure ID is generated before serialization if it was accessed.
     */
    final public function __serialize(): array
    {
        return [
            'id' => $this->id, // Preserve the ID if it was set
        ];
    }

    /**
     * Restore ID after unserialization.
     *
     * @param  array<string, mixed>  $data
     */
    final public function __unserialize(array $data): void
    {
        $this->id = $data['id'] ?? null;
    }

    /**
     * Get a unique identifier for this task.
     *
     * The ID is lazy-loaded to avoid overhead when not needed.
     */
    final public function getId(): string
    {
        if ($this->id === null) {
            $this->id = $this->generateId();
        }

        return $this->id;
    }

    /**
     * Check if the task is serializable.
     *
     * By default, assumes the task is serializable. Override if custom
     * serialization checks are needed.
     */
    final public function isSerializable(): bool
    {
        return true;
    }

    /**
     * Generate a unique identifier for the task.
     *
     * Override this method to provide custom ID generation logic.
     */
    protected function generateId(): string
    {
        return uniqid('task_', true);
    }
}
