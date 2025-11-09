<?php

declare(strict_types=1);

namespace LaravelParallel\Integrations\Horizon\Listeners;

use Illuminate\Events\Dispatcher;
use LaravelParallel\Events\TaskCompleted;
use LaravelParallel\Events\TaskFailed;
use LaravelParallel\Events\TaskStarted;
use LaravelParallel\Integrations\Horizon\HorizonMetricsBridge;

class ParallelHorizonEventSubscriber
{
    public function __construct(
        private readonly HorizonMetricsBridge $bridge
    ) {}

    public function handleTaskCompleted(TaskCompleted $event): void
    {
        $this->bridge->handleTaskCompleted($event);
    }

    public function handleTaskFailed(TaskFailed $event): void
    {
        $this->bridge->handleTaskFailed($event);
    }

    public function handleTaskStarted(TaskStarted $event): void
    {
        $this->bridge->handleTaskStarted($event);
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
