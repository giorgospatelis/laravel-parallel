<?php

declare(strict_types=1);

namespace LaravelParallel\Integrations\Telescope\Listeners;

use Illuminate\Events\Dispatcher;
use LaravelParallel\Events\TaskCompleted;
use LaravelParallel\Events\TaskFailed;
use LaravelParallel\Events\TaskStarted;
use LaravelParallel\Integrations\Telescope\Watchers\ParallelTaskWatcher;

class ParallelTelescopeEventSubscriber
{
    public function __construct(
        private readonly ParallelTaskWatcher $watcher
    ) {}

    public function handleTaskCompleted(TaskCompleted $event): void
    {
        $this->watcher->recordTaskCompleted($event);
    }

    public function handleTaskFailed(TaskFailed $event): void
    {
        $this->watcher->recordTaskFailed($event);
    }

    public function handleTaskStarted(TaskStarted $event): void
    {
        $this->watcher->recordTaskStarted($event);
    }

    /**
     * @return array<string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            TaskStarted::class => 'handleTaskStarted',
            TaskCompleted::class => 'handleTaskCompleted',
            TaskFailed::class => 'handleTaskFailed',
        ];
    }
}
