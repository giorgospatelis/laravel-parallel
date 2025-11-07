<?php

declare(strict_types=1);

namespace LaravelParallel\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Event dispatched when a parallel task completes successfully.
 */
final class TaskCompleted
{
    use Dispatchable;

    public function __construct(
        public readonly string|int $taskKey,
        public readonly mixed $result,
        public readonly float $executionTime,
    ) {}
}
