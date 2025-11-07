<?php

declare(strict_types=1);

namespace LaravelParallel\Exceptions;

use Throwable;

/**
 * Exception thrown when serialization or unserialization fails.
 *
 * This exception is used for errors related to closure serialization,
 * task serialization, or result deserialization.
 */
final class SerializationException extends ParallelException
{
    /**
     * Create an exception for closure serialization failure.
     */
    public static function closureNotSerializable(Throwable $previous): self
    {
        return new self(
            message: 'Closure is not serializable: '.$previous->getMessage(),
            previous: $previous,
        );
    }

    /**
     * Create an exception for context not available.
     */
    public static function contextNotAvailable(string $contextType): self
    {
        return new self(
            message: "Context '{$contextType}' is not available in the worker process.",
        );
    }

    /**
     * Create an exception for unserialization failure.
     */
    public static function unserializationFailed(Throwable $previous): self
    {
        return new self(
            message: 'Failed to unserialize data: '.$previous->getMessage(),
            previous: $previous,
        );
    }

    /**
     * Create an exception for unsupported variable type.
     */
    public static function unsupportedType(string $type): self
    {
        return new self(
            message: "Cannot serialize variable of type '{$type}'.",
        );
    }
}
