<?php

declare(strict_types=1);

use Illuminate\Events\Dispatcher;
use Laravel\Telescope\Telescope;
use LaravelParallel\Events\TaskCompleted;
use LaravelParallel\Events\TaskFailed;
use LaravelParallel\Events\TaskStarted;
use LaravelParallel\Integrations\Telescope\Listeners\ParallelTelescopeEventSubscriber;
use LaravelParallel\Integrations\Telescope\Watchers\ParallelTaskWatcher;

beforeEach(function () {
    if (! class_exists(Telescope::class)) {
        $this->markTestSkipped('Telescope is not installed');
    }

    $this->watcher = Mockery::mock(ParallelTaskWatcher::class);
    $this->subscriber = new ParallelTelescopeEventSubscriber($this->watcher);
});

describe('ParallelTelescopeEventSubscriber', function () {
    it('subscribes to all parallel task events', function () {
        $dispatcher = Mockery::mock(Dispatcher::class);

        $subscriptions = $this->subscriber->subscribe($dispatcher);

        expect($subscriptions)->toBeArray();
        expect($subscriptions)->toHaveKey(TaskStarted::class);
        expect($subscriptions)->toHaveKey(TaskCompleted::class);
        expect($subscriptions)->toHaveKey(TaskFailed::class);
    });

    it('maps events to correct handler methods', function () {
        $dispatcher = Mockery::mock(Dispatcher::class);

        $subscriptions = $this->subscriber->subscribe($dispatcher);

        expect($subscriptions[TaskStarted::class])->toBe('handleTaskStarted');
        expect($subscriptions[TaskCompleted::class])->toBe('handleTaskCompleted');
        expect($subscriptions[TaskFailed::class])->toBe('handleTaskFailed');
    });
});

describe('ParallelTelescopeEventSubscriber event handling', function () {
    it('handles TaskStarted event', function () {
        $event = new TaskStarted('task_1', microtime(true));

        $this->watcher->shouldReceive('recordTaskStarted')
            ->once()
            ->with($event);

        $this->subscriber->handleTaskStarted($event);
    });

    it('handles TaskCompleted event', function () {
        $event = new TaskCompleted('task_2', 'result', 1.234);

        $this->watcher->shouldReceive('recordTaskCompleted')
            ->once()
            ->with($event);

        $this->subscriber->handleTaskCompleted($event);
    });

    it('handles TaskFailed event', function () {
        $exception = new RuntimeException('Task error');
        $event = new TaskFailed('task_3', $exception, 0.5);

        $this->watcher->shouldReceive('recordTaskFailed')
            ->once()
            ->with($event);

        $this->subscriber->handleTaskFailed($event);
    });

    it('forwards multiple events to watcher', function () {
        $startEvent = new TaskStarted('multi_task', microtime(true));
        $completedEvent = new TaskCompleted('multi_task', 'done', 2.0);

        $this->watcher->shouldReceive('recordTaskStarted')
            ->once()
            ->with($startEvent);

        $this->watcher->shouldReceive('recordTaskCompleted')
            ->once()
            ->with($completedEvent);

        $this->subscriber->handleTaskStarted($startEvent);
        $this->subscriber->handleTaskCompleted($completedEvent);
    });
});

describe('ParallelTelescopeEventSubscriber integration', function () {
    it('integrates with Laravel event dispatcher', function () {
        $watcher = new ParallelTaskWatcher([]);
        $subscriber = new ParallelTelescopeEventSubscriber($watcher);

        // Register subscriber with event dispatcher
        $dispatcher = app(Dispatcher::class);
        $dispatcher->subscribe($subscriber);

        // Mock Telescope to verify recording
        Telescope::shouldReceive('recordEntry')->once();

        // Dispatch event
        event(new TaskStarted('integration_test', microtime(true)));

        // Verify event was handled
        $listeners = $dispatcher->getListeners(TaskStarted::class);
        expect($listeners)->not->toBeEmpty();
    });

    it('handles events dispatched through Laravel event system', function () {
        config(['parallel.telescope.enabled' => true]);
        config(['parallel.telescope.record_tasks' => true]);

        $watcher = new ParallelTaskWatcher([]);
        $subscriber = new ParallelTelescopeEventSubscriber($watcher);

        $dispatcher = app(Dispatcher::class);
        $dispatcher->subscribe($subscriber);

        Telescope::shouldReceive('recordEntry')->times(3);

        // Dispatch multiple events
        event(new TaskStarted('event_1', microtime(true)));
        event(new TaskCompleted('event_1', 'result', 1.0));
        event(new TaskStarted('event_2', microtime(true)));
    });

    it('works with event helper function', function () {
        config(['parallel.telescope.enabled' => true]);
        config(['parallel.telescope.record_tasks' => true]);

        $watcher = new ParallelTaskWatcher([]);
        $subscriber = new ParallelTelescopeEventSubscriber($watcher);

        app(Dispatcher::class)->subscribe($subscriber);

        Telescope::shouldReceive('recordEntry')->once();

        event(new TaskStarted('helper_test', microtime(true)));
    });
});

describe('ParallelTelescopeEventSubscriber error handling', function () {
    it('propagates exceptions from watcher', function () {
        $event = new TaskStarted('error_task', microtime(true));

        $this->watcher->shouldReceive('recordTaskStarted')
            ->once()
            ->andThrow(new RuntimeException('Recording failed'));

        expect(fn () => $this->subscriber->handleTaskStarted($event))
            ->toThrow(RuntimeException::class, 'Recording failed');
    });

    it('handles watcher returning without error', function () {
        $event = new TaskCompleted('normal_task', 'result', 1.0);

        $this->watcher->shouldReceive('recordTaskCompleted')
            ->once()
            ->andReturn();

        expect(fn () => $this->subscriber->handleTaskCompleted($event))
            ->not->toThrow(Exception::class);
    });
});

describe('ParallelTelescopeEventSubscriber dependency injection', function () {
    it('requires ParallelTaskWatcher dependency', function () {
        expect(fn () => new ParallelTelescopeEventSubscriber($this->watcher))
            ->not->toThrow(TypeError::class);
    });

    it('can be resolved from Laravel container', function () {
        // Bind watcher to container
        app()->singleton(ParallelTaskWatcher::class, function () {
            return new ParallelTaskWatcher([]);
        });

        $subscriber = app(ParallelTelescopeEventSubscriber::class);

        expect($subscriber)->toBeInstanceOf(ParallelTelescopeEventSubscriber::class);
    });

    it('receives watcher instance via constructor', function () {
        $watcher = new ParallelTaskWatcher([]);
        $subscriber = new ParallelTelescopeEventSubscriber($watcher);

        expect($subscriber)->toBeInstanceOf(ParallelTelescopeEventSubscriber::class);
    });
});

describe('ParallelTelescopeEventSubscriber with different event data', function () {
    it('handles numeric task keys', function () {
        $event = new TaskStarted(0, microtime(true));

        $this->watcher->shouldReceive('recordTaskStarted')
            ->once()
            ->withArgs(function ($receivedEvent) {
                return $receivedEvent->taskKey === 0;
            });

        $this->subscriber->handleTaskStarted($event);
    });

    it('handles string task keys', function () {
        $event = new TaskStarted('string_key', microtime(true));

        $this->watcher->shouldReceive('recordTaskStarted')
            ->once()
            ->withArgs(function ($receivedEvent) {
                return $receivedEvent->taskKey === 'string_key';
            });

        $this->subscriber->handleTaskStarted($event);
    });

    it('handles various result types', function () {
        $results = [
            'string' => 'text result',
            'array' => ['data' => 'value'],
            'int' => 42,
            'float' => 3.14,
            'bool' => true,
            'null' => null,
        ];

        foreach ($results as $type => $result) {
            $event = new TaskCompleted("task_{$type}", $result, 1.0);

            $this->watcher->shouldReceive('recordTaskCompleted')
                ->once()
                ->with($event);

            $this->subscriber->handleTaskCompleted($event);
        }
    });

    it('handles different exception types', function () {
        $exceptions = [
            new RuntimeException('Runtime error'),
            new InvalidArgumentException('Invalid argument'),
            new LogicException('Logic error'),
            new Exception('Generic error'),
        ];

        foreach ($exceptions as $index => $exception) {
            $event = new TaskFailed("exception_task_{$index}", $exception, 0.5);

            $this->watcher->shouldReceive('recordTaskFailed')
                ->once()
                ->with($event);

            $this->subscriber->handleTaskFailed($event);
        }
    });
});

describe('ParallelTelescopeEventSubscriber performance', function () {
    it('handles high volume of events efficiently', function () {
        $eventCount = 100;

        $this->watcher->shouldReceive('recordTaskStarted')->times($eventCount);

        $startTime = microtime(true);

        for ($i = 0; $i < $eventCount; $i++) {
            $event = new TaskStarted("perf_task_{$i}", microtime(true));
            $this->subscriber->handleTaskStarted($event);
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000; // Convert to ms

        // Should process 100 events in less than 10ms (excluding actual recording)
        expect($totalTime)->toBeLessThan(10);
    });

    it('has minimal overhead per event', function () {
        $this->watcher->shouldReceive('recordTaskCompleted')->once();

        $startTime = microtime(true);

        $event = new TaskCompleted('overhead_test', 'result', 1.0);
        $this->subscriber->handleTaskCompleted($event);

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to ms

        // Should handle a single event in less than 1ms
        expect($executionTime)->toBeLessThan(1);
    });
});

describe('ParallelTelescopeEventSubscriber subscription format', function () {
    it('returns array with event class as key', function () {
        $subscriptions = $this->subscriber->subscribe(app(Dispatcher::class));

        expect($subscriptions)->toBeArray();

        foreach (array_keys($subscriptions) as $eventClass) {
            expect(class_exists($eventClass))->toBeTrue();
        }
    });

    it('returns method names as values', function () {
        $subscriptions = $this->subscriber->subscribe(app(Dispatcher::class));

        foreach ($subscriptions as $method) {
            expect($method)->toBeString();
            expect(method_exists($this->subscriber, $method))->toBeTrue();
        }
    });

    it('subscribes to exactly three events', function () {
        $subscriptions = $this->subscriber->subscribe(app(Dispatcher::class));

        expect(count($subscriptions))->toBe(3);
    });
});
