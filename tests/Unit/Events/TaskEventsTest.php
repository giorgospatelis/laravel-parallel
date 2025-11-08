<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use LaravelParallel\Events\TaskCompleted;
use LaravelParallel\Events\TaskFailed;
use LaravelParallel\Events\TaskStarted;

describe('TaskStarted', function () {
    it('can be instantiated with string key', function () {
        $event = new TaskStarted('task_1', 1234567890.5);

        expect($event->taskKey)->toBe('task_1')
            ->and($event->startedAt)->toBe(1234567890.5);
    });

    it('can be instantiated with integer key', function () {
        $event = new TaskStarted(42, 1234567890.0);

        expect($event->taskKey)->toBe(42)
            ->and($event->startedAt)->toBe(1234567890.0);
    });

    it('has readonly properties', function () {
        $event = new TaskStarted('task_1', 1234567890.5);

        $event->taskKey = 'modified';
    })->throws(Error::class);

    it('can be dispatched', function () {
        Event::fake();

        TaskStarted::dispatch('task_1', microtime(true));

        Event::assertDispatched(TaskStarted::class);
    });

    it('dispatches with correct data', function () {
        Event::fake();

        $taskKey = 'test_task';
        $startedAt = microtime(true);

        TaskStarted::dispatch($taskKey, $startedAt);

        Event::assertDispatched(function (TaskStarted $event) use ($taskKey, $startedAt) {
            return $event->taskKey === $taskKey
                && $event->startedAt === $startedAt;
        });
    });
});

describe('TaskCompleted', function () {
    it('can be instantiated with string key', function () {
        $event = new TaskCompleted('task_1', 'result', 1.5);

        expect($event->taskKey)->toBe('task_1')
            ->and($event->result)->toBe('result')
            ->and($event->executionTime)->toBe(1.5);
    });

    it('can be instantiated with integer key', function () {
        $event = new TaskCompleted(42, ['data' => 'value'], 2.3);

        expect($event->taskKey)->toBe(42)
            ->and($event->result)->toBe(['data' => 'value'])
            ->and($event->executionTime)->toBe(2.3);
    });

    it('can store any result type', function () {
        $results = [
            'string' => 'test',
            'integer' => 123,
            'float' => 45.6,
            'array' => [1, 2, 3],
            'object' => new stdClass,
            'null' => null,
            'boolean' => true,
        ];

        foreach ($results as $type => $result) {
            $event = new TaskCompleted('task', $result, 1.0);
            expect($event->result)->toBe($result);
        }
    });

    it('has readonly properties', function () {
        $event = new TaskCompleted('task_1', 'result', 1.5);

        $event->result = 'modified';
    })->throws(Error::class);

    it('can be dispatched', function () {
        Event::fake();

        TaskCompleted::dispatch('task_1', 'result', 1.5);

        Event::assertDispatched(TaskCompleted::class);
    });

    it('dispatches with correct data', function () {
        Event::fake();

        $taskKey = 'test_task';
        $result = 'test_result';
        $executionTime = 1.5;

        TaskCompleted::dispatch($taskKey, $result, $executionTime);

        Event::assertDispatched(function (TaskCompleted $event) use ($taskKey, $result, $executionTime) {
            return $event->taskKey === $taskKey
                && $event->result === $result
                && $event->executionTime === $executionTime;
        });
    });
});

describe('TaskFailed', function () {
    it('can be instantiated with string key', function () {
        $exception = new RuntimeException('Task failed');
        $event = new TaskFailed('task_1', $exception, 1.5);

        expect($event->taskKey)->toBe('task_1')
            ->and($event->exception)->toBe($exception)
            ->and($event->executionTime)->toBe(1.5);
    });

    it('can be instantiated with integer key', function () {
        $exception = new InvalidArgumentException('Invalid input');
        $event = new TaskFailed(42, $exception, 2.3);

        expect($event->taskKey)->toBe(42)
            ->and($event->exception)->toBe($exception)
            ->and($event->executionTime)->toBe(2.3);
    });

    it('stores exception with message and trace', function () {
        $exception = new RuntimeException('Test error message', 123);
        $event = new TaskFailed('task_1', $exception, 1.0);

        expect($event->exception->getMessage())->toBe('Test error message')
            ->and($event->exception->getCode())->toBe(123)
            ->and($event->exception->getTrace())->toBeArray();
    });

    it('can store different exception types', function () {
        $exceptions = [
            new RuntimeException('Runtime error'),
            new InvalidArgumentException('Invalid argument'),
            new LogicException('Logic error'),
            new Exception('Generic exception'),
        ];

        foreach ($exceptions as $exception) {
            $event = new TaskFailed('task', $exception, 1.0);
            expect($event->exception)->toBe($exception);
        }
    });

    it('has readonly properties', function () {
        $exception = new RuntimeException('Task failed');
        $event = new TaskFailed('task_1', $exception, 1.5);

        $event->exception = new RuntimeException('Modified');
    })->throws(Error::class);

    it('can be dispatched', function () {
        Event::fake();

        $exception = new RuntimeException('Task failed');
        TaskFailed::dispatch('task_1', $exception, 1.5);

        Event::assertDispatched(TaskFailed::class);
    });

    it('dispatches with correct data', function () {
        Event::fake();

        $taskKey = 'test_task';
        $exception = new RuntimeException('Test error');
        $executionTime = 1.5;

        TaskFailed::dispatch($taskKey, $exception, $executionTime);

        Event::assertDispatched(function (TaskFailed $event) use ($taskKey, $exception, $executionTime) {
            return $event->taskKey === $taskKey
                && $event->exception === $exception
                && $event->executionTime === $executionTime;
        });
    });
});

describe('Event integration', function () {
    it('can dispatch all event types in sequence', function () {
        Event::fake();

        $taskKey = 'task_1';
        $startedAt = microtime(true);
        $result = 'success';
        $executionTime = 1.5;

        TaskStarted::dispatch($taskKey, $startedAt);
        TaskCompleted::dispatch($taskKey, $result, $executionTime);

        Event::assertDispatched(TaskStarted::class);
        Event::assertDispatched(TaskCompleted::class);
    });

    it('can handle failed task flow', function () {
        Event::fake();

        $taskKey = 'task_1';
        $startedAt = microtime(true);
        $exception = new RuntimeException('Failed');
        $executionTime = 0.5;

        TaskStarted::dispatch($taskKey, $startedAt);
        TaskFailed::dispatch($taskKey, $exception, $executionTime);

        Event::assertDispatched(TaskStarted::class);
        Event::assertDispatched(TaskFailed::class);
        Event::assertNotDispatched(TaskCompleted::class);
    });

    it('can dispatch multiple tasks', function () {
        Event::fake();

        for ($i = 1; $i <= 5; $i++) {
            TaskStarted::dispatch("task_{$i}", microtime(true));
        }

        Event::assertDispatchedTimes(TaskStarted::class, 5);
    });

    it('can listen to events', function () {
        $called = false;

        Event::listen(TaskCompleted::class, function ($event) use (&$called) {
            $called = true;
        });

        TaskCompleted::dispatch('task_1', 'result', 1.0);

        expect($called)->toBeTrue();
    });
});
