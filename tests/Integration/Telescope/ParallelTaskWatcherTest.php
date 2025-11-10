<?php

declare(strict_types=1);

use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use LaravelParallel\Events\TaskCompleted;
use LaravelParallel\Events\TaskFailed;
use LaravelParallel\Events\TaskStarted;
use LaravelParallel\Integrations\Telescope\Watchers\ParallelTaskWatcher;

beforeEach(function () {
    if (! class_exists(Telescope::class)) {
        $this->markTestSkipped('Telescope is not installed');
    }

    // Reset config for each test
    config(['parallel.telescope.enabled' => true]);
    config(['parallel.telescope.record_tasks' => true]);

    $this->watcher = new ParallelTaskWatcher([]);
});

describe('ParallelTaskWatcher', function () {
    it('records task started event', function () {
        $entries = [];

        // Mock Telescope's recordEntry method
        Telescope::shouldReceive('recordEntry')
            ->once()
            ->withArgs(function (IncomingEntry $entry) use (&$entries) {
                $entries[] = $entry;

                return true;
            });

        $event = new TaskStarted('task_1', microtime(true));

        $this->watcher->recordTaskStarted($event);

        expect($entries)->toHaveCount(1);
        expect($entries[0]->type)->toBe('parallel_task');
        expect($entries[0]->content['task_key'])->toBe('task_1');
        expect($entries[0]->content['status'])->toBe('started');
    });

    it('records task completed event', function () {
        $entries = [];

        Telescope::shouldReceive('recordEntry')
            ->twice()
            ->withArgs(function (IncomingEntry $entry) use (&$entries) {
                $entries[] = $entry;

                return true;
            });

        // Start task first
        $startedAt = microtime(true);
        $startEvent = new TaskStarted('task_2', $startedAt);
        $this->watcher->recordTaskStarted($startEvent);

        // Complete task
        $completedEvent = new TaskCompleted('task_2', 'success_result', 1.5);
        $this->watcher->recordTaskCompleted($completedEvent);

        expect($entries)->toHaveCount(2);

        $completedEntry = $entries[1];
        expect($completedEntry->type)->toBe('parallel_task');
        expect($completedEntry->content['task_key'])->toBe('task_2');
        expect($completedEntry->content['status'])->toBe('completed');
        expect($completedEntry->content['execution_time'])->toBe(1.5);
        expect($completedEntry->content['result'])->toBe('success_result');
    });

    it('records task failed event', function () {
        $entries = [];

        Telescope::shouldReceive('recordEntry')
            ->twice()
            ->withArgs(function (IncomingEntry $entry) use (&$entries) {
                $entries[] = $entry;

                return true;
            });

        // Start task first
        $startedAt = microtime(true);
        $startEvent = new TaskStarted('task_3', $startedAt);
        $this->watcher->recordTaskStarted($startEvent);

        // Fail task
        $exception = new RuntimeException('Task execution failed');
        $failedEvent = new TaskFailed('task_3', $exception, 0.8);
        $this->watcher->recordTaskFailed($failedEvent);

        expect($entries)->toHaveCount(2);

        $failedEntry = $entries[1];
        expect($failedEntry->type)->toBe('parallel_task');
        expect($failedEntry->content['task_key'])->toBe('task_3');
        expect($failedEntry->content['status'])->toBe('failed');
        expect($failedEntry->content['execution_time'])->toBe(0.8);
        expect($failedEntry->content['exception']['class'])->toBe(RuntimeException::class);
        expect($failedEntry->content['exception']['message'])->toBe('Task execution failed');
    });

    it('includes tags for filtering', function () {
        $entry = null;

        Telescope::shouldReceive('recordEntry')
            ->once()
            ->withArgs(function (IncomingEntry $capturedEntry) use (&$entry) {
                $entry = $capturedEntry;

                return true;
            });

        $event = new TaskStarted('image_processing', microtime(true));
        $this->watcher->recordTaskStarted($event);

        expect($entry)->not->toBeNull();
        expect($entry->tags)->toContain('parallel');
        expect($entry->tags)->toContain('parallel:started');
        expect($entry->tags)->toContain('task:image_processing');
    });

    it('adds different tags for completed tasks', function () {
        $entries = [];

        Telescope::shouldReceive('recordEntry')
            ->twice()
            ->withArgs(function (IncomingEntry $entry) use (&$entries) {
                $entries[] = $entry;

                return true;
            });

        $startEvent = new TaskStarted('data_import', microtime(true));
        $this->watcher->recordTaskStarted($startEvent);

        $completedEvent = new TaskCompleted('data_import', ['rows' => 100], 2.5);
        $this->watcher->recordTaskCompleted($completedEvent);

        $completedEntry = $entries[1];
        expect($completedEntry->tags)->toContain('parallel');
        expect($completedEntry->tags)->toContain('parallel:completed');
        expect($completedEntry->tags)->toContain('task:data_import');
    });

    it('adds exception tags for failed tasks', function () {
        $entries = [];

        Telescope::shouldReceive('recordEntry')
            ->twice()
            ->withArgs(function (IncomingEntry $entry) use (&$entries) {
                $entries[] = $entry;

                return true;
            });

        $startEvent = new TaskStarted('api_call', microtime(true));
        $this->watcher->recordTaskStarted($startEvent);

        $exception = new InvalidArgumentException('Invalid API response');
        $failedEvent = new TaskFailed('api_call', $exception, 0.3);
        $this->watcher->recordTaskFailed($failedEvent);

        $failedEntry = $entries[1];
        expect($failedEntry->tags)->toContain('parallel');
        expect($failedEntry->tags)->toContain('parallel:failed');
        expect($failedEntry->tags)->toContain('task:api_call');
        expect($failedEntry->tags)->toContain('exception:InvalidArgumentException');
    });
});

describe('ParallelTaskWatcher configuration', function () {
    it('respects enabled configuration', function () {
        config(['parallel.telescope.enabled' => false]);

        $watcher = new ParallelTaskWatcher([]);

        Telescope::shouldReceive('recordEntry')->never();

        $event = new TaskStarted('task_1', microtime(true));
        $watcher->recordTaskStarted($event);
    });

    it('respects record_tasks configuration', function () {
        config(['parallel.telescope.record_tasks' => false]);

        $watcher = new ParallelTaskWatcher([]);

        Telescope::shouldReceive('recordEntry')->never();

        $event = new TaskStarted('task_1', microtime(true));
        $watcher->recordTaskStarted($event);
    });

    it('records when both configurations are enabled', function () {
        config(['parallel.telescope.enabled' => true]);
        config(['parallel.telescope.record_tasks' => true]);

        $watcher = new ParallelTaskWatcher([]);

        Telescope::shouldReceive('recordEntry')->once();

        $event = new TaskStarted('task_1', microtime(true));
        $watcher->recordTaskStarted($event);
    });
});

describe('ParallelTaskWatcher result formatting', function () {
    it('truncates long string results', function () {
        $entry = null;

        Telescope::shouldReceive('recordEntry')
            ->twice()
            ->withArgs(function (IncomingEntry $capturedEntry) use (&$entry) {
                $entry = $capturedEntry;

                return true;
            });

        $startEvent = new TaskStarted('long_task', microtime(true));
        $this->watcher->recordTaskStarted($startEvent);

        // Create a string longer than 1000 characters
        $longResult = str_repeat('a', 1500);
        $completedEvent = new TaskCompleted('long_task', $longResult, 1.0);
        $this->watcher->recordTaskCompleted($completedEvent);

        expect($entry->content['result'])->toBeString();
        expect(mb_strlen($entry->content['result']))->toBeLessThan(1100); // 1000 + "... (truncated)"
        expect($entry->content['result'])->toContain('(truncated)');
    });

    it('truncates large array results', function () {
        $entry = null;

        Telescope::shouldReceive('recordEntry')
            ->twice()
            ->withArgs(function (IncomingEntry $capturedEntry) use (&$entry) {
                $entry = $capturedEntry;

                return true;
            });

        $startEvent = new TaskStarted('array_task', microtime(true));
        $this->watcher->recordTaskStarted($startEvent);

        // Create an array with more than 100 elements
        $largeArray = range(1, 150);
        $completedEvent = new TaskCompleted('array_task', $largeArray, 1.0);
        $this->watcher->recordTaskCompleted($completedEvent);

        expect($entry->content['result'])->toBeArray();
        expect(count($entry->content['result']))->toBeLessThanOrEqual(101); // 100 + "..." key
        expect($entry->content['result'])->toHaveKey('...');
    });

    it('formats object results', function () {
        $entry = null;

        Telescope::shouldReceive('recordEntry')
            ->twice()
            ->withArgs(function (IncomingEntry $capturedEntry) use (&$entry) {
                $entry = $capturedEntry;

                return true;
            });

        $startEvent = new TaskStarted('object_task', microtime(true));
        $this->watcher->recordTaskStarted($startEvent);

        $objectResult = new stdClass;
        $objectResult->foo = 'bar';
        $completedEvent = new TaskCompleted('object_task', $objectResult, 1.0);
        $this->watcher->recordTaskCompleted($completedEvent);

        expect($entry->content['result'])->toBeArray();
        expect($entry->content['result'])->toHaveKey('class');
        expect($entry->content['result']['class'])->toBe(stdClass::class);
    });

    it('handles primitive results correctly', function () {
        $entry = null;

        Telescope::shouldReceive('recordEntry')
            ->twice()
            ->withArgs(function (IncomingEntry $capturedEntry) use (&$entry) {
                $entry = $capturedEntry;

                return true;
            });

        $startEvent = new TaskStarted('primitive_task', microtime(true));
        $this->watcher->recordTaskStarted($startEvent);

        $completedEvent = new TaskCompleted('primitive_task', 42, 1.0);
        $this->watcher->recordTaskCompleted($completedEvent);

        expect($entry->content['result'])->toBe(42);
    });
});

describe('ParallelTaskWatcher exception formatting', function () {
    it('includes full exception details', function () {
        $entries = [];

        Telescope::shouldReceive('recordEntry')
            ->twice()
            ->withArgs(function (IncomingEntry $entry) use (&$entries) {
                $entries[] = $entry;

                return true;
            });

        $startEvent = new TaskStarted('exception_task', microtime(true));
        $this->watcher->recordTaskStarted($startEvent);

        $exception = new RuntimeException('Critical error', 500);
        $failedEvent = new TaskFailed('exception_task', $exception, 0.5);
        $this->watcher->recordTaskFailed($failedEvent);

        $failedEntry = $entries[1];
        $exceptionData = $failedEntry->content['exception'];

        expect($exceptionData)->toBeArray();
        expect($exceptionData['class'])->toBe(RuntimeException::class);
        expect($exceptionData['message'])->toBe('Critical error');
        expect($exceptionData['code'])->toBe(500);
        expect($exceptionData['file'])->toBeString();
        expect($exceptionData['line'])->toBeInt();
        expect($exceptionData['trace'])->toBeArray();
    });

    it('limits stack trace to 10 frames', function () {
        $entries = [];

        Telescope::shouldReceive('recordEntry')
            ->twice()
            ->withArgs(function (IncomingEntry $entry) use (&$entries) {
                $entries[] = $entry;

                return true;
            });

        $startEvent = new TaskStarted('deep_stack', microtime(true));
        $this->watcher->recordTaskStarted($startEvent);

        // Create exception with deep call stack
        $exception = new RuntimeException('Deep stack error');
        $failedEvent = new TaskFailed('deep_stack', $exception, 0.5);
        $this->watcher->recordTaskFailed($failedEvent);

        $failedEntry = $entries[1];
        $trace = $failedEntry->content['exception']['trace'];

        expect($trace)->toBeArray();
        expect(count($trace))->toBeLessThanOrEqual(10);
    });
});

describe('ParallelTaskWatcher task lifecycle', function () {
    it('tracks complete task lifecycle', function () {
        $entries = [];

        Telescope::shouldReceive('recordEntry')
            ->times(3)
            ->withArgs(function (IncomingEntry $entry) use (&$entries) {
                $entries[] = $entry;

                return true;
            });

        $startedAt = microtime(true);

        // Start
        $this->watcher->recordTaskStarted(new TaskStarted('lifecycle_task', $startedAt));

        // Simulate some work
        usleep(10000); // 10ms

        // Complete
        $this->watcher->recordTaskCompleted(new TaskCompleted('lifecycle_task', 'done', 0.01));

        // Start another
        $this->watcher->recordTaskStarted(new TaskStarted('lifecycle_task_2', microtime(true)));

        expect($entries)->toHaveCount(3);
        expect($entries[0]->content['status'])->toBe('started');
        expect($entries[1]->content['status'])->toBe('completed');
        expect($entries[2]->content['status'])->toBe('started');
    });

    it('handles concurrent tasks', function () {
        $entries = [];

        Telescope::shouldReceive('recordEntry')
            ->times(4)
            ->withArgs(function (IncomingEntry $entry) use (&$entries) {
                $entries[] = $entry;

                return true;
            });

        // Start multiple tasks
        $this->watcher->recordTaskStarted(new TaskStarted('concurrent_1', microtime(true)));
        $this->watcher->recordTaskStarted(new TaskStarted('concurrent_2', microtime(true)));

        // Complete in different order
        $this->watcher->recordTaskCompleted(new TaskCompleted('concurrent_2', 'result_2', 1.0));
        $this->watcher->recordTaskCompleted(new TaskCompleted('concurrent_1', 'result_1', 1.5));

        expect($entries)->toHaveCount(4);
        expect($entries[0]->content['task_key'])->toBe('concurrent_1');
        expect($entries[1]->content['task_key'])->toBe('concurrent_2');
        expect($entries[2]->content['task_key'])->toBe('concurrent_2');
        expect($entries[3]->content['task_key'])->toBe('concurrent_1');
    });
});

describe('ParallelTaskWatcher performance', function () {
    it('has minimal overhead for recording', function () {
        Telescope::shouldReceive('recordEntry')->times(100);

        $startTime = microtime(true);

        for ($i = 0; $i < 100; $i++) {
            $this->watcher->recordTaskStarted(new TaskStarted("task_{$i}", microtime(true)));
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000; // Convert to ms

        expect($totalTime)->toBeLessThan(50); // Less than 50ms for 100 recordings
    });
});
