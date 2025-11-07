<?php

declare(strict_types=1);

namespace LaravelParallel\Exceptions;

use Throwable;

/**
 * Exception thrown when a task encounters an error.
 *
 * This exception is used for task-specific errors such as serialization
 * failures, execution errors, or invalid task configurations.
 */
final class TaskException extends ParallelException
{
    /**
     * Create an exception for invalid task type.
     */
    public static function invalidTaskType(string $expectedType, string $actualType): self
    {
        return new self(
            message: "Invalid task type. Expected '{$expectedType}', got '{$actualType}'.",
        );
    }

    /**
     * Create an exception for task serialization failure.
     */
    public static function serializationFailed(string $taskId, Throwable $previous): self
    {
        return new self(
            message: "Failed to serialize task '{$taskId}': ".$previous->getMessage(),
            previous: $previous,
        );
    }

    /**
     * Create an exception for task not found.
     */
    public static function taskNotFound(string $taskId): self
    {
        return new self(
            message: "Task with ID '{$taskId}' not found.",
        );
    }
}
