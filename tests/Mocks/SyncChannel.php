<?php

declare(strict_types=1);

namespace LaravelParallel\Tests\Mocks;

use Amp\Sync\Channel;
use Closure;

/**
 * Synchronous mock implementation of Channel for testing.
 *
 * This is a minimal implementation that satisfies the Channel interface
 * but doesn't actually perform any inter-process communication.
 * It's used by SyncExecution to provide a channel to tasks.
 */
final class SyncChannel implements Channel
{
    /**
     * Close the channel.
     *
     * In synchronous execution, this is a no-op.
     */
    public function close(): void
    {
        // No-op for synchronous execution
    }

    /**
     * Check if the channel is closed.
     */
    public function isClosed(): bool
    {
        return false;
    }

    /**
     * Called when the channel is closed by the other party.
     *
     * @param  Closure(): void  $onClose
     */
    public function onClose(Closure $onClose): void
    {
        // No-op for synchronous execution
    }

    /**
     * Receive a value from the channel.
     *
     * In synchronous execution, there's no actual IPC, so this returns null.
     */
    public function receive(?\Amp\Cancellation $cancellation = null): mixed
    {
        return null;
    }

    /**
     * Send a value through the channel.
     *
     * In synchronous execution, this is a no-op.
     */
    public function send(mixed $data): void
    {
        // No-op for synchronous execution
    }
}
