<?php

declare(strict_types=1);

namespace LaravelParallel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Throwable;

/**
 * Event dispatched when a parallel task fails.
 */
final class TaskFailed
{
    use Dispatchable;

    public function __construct(
        public readonly string|int $taskKey,
        public readonly Throwable $exception,
        public readonly float $executionTime,
    ) {}
}
