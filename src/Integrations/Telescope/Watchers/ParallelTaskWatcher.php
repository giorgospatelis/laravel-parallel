<?php

declare(strict_types=1);

namespace LaravelParallel\Integrations\Telescope\Watchers;

use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\Watchers\Watcher;
use LaravelParallel\Events\TaskCompleted;
use LaravelParallel\Events\TaskFailed;
use LaravelParallel\Events\TaskStarted;

/**
 * Watcher for recording parallel task execution in Laravel Telescope.
 *
 * This watcher captures parallel task lifecycle events and records them
 * in Telescope for debugging and performance profiling.
 */
class ParallelTaskWatcher extends Watcher
{
    /**
     * Maximum number of active tasks to track (prevents memory leaks).
     */
    private const MAX_ACTIVE_TASKS = 1000;

    /**
     * Maximum time (in seconds) to keep a task in active tracking (5 minutes).
     */
    private const TASK_TTL_SECONDS = 300;

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $activeTasks = [];

    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
    }

    /**
     * Record a task completed event.
     */
    public function recordTaskCompleted(TaskCompleted $event): void
    {
        if (! $this->shouldRecord()) {
            return;
        }

        $taskKey = (string) $event->taskKey;
        $startData = $this->activeTasks[$taskKey] ?? null;

        $entry = IncomingEntry::make([
            'type' => 'parallel_task',
            'content' => [
                'task_key' => $event->taskKey,
                'status' => 'completed',
                'execution_time' => $event->executionTime,
                'result' => $this->formatResult($event->result),
                'started_at' => $startData['started_at'] ?? null,
                'completed_at' => microtime(true),
            ],
        ]);

        // Add tags for filtering
        $entry->tags([
            'parallel',
            'parallel:completed',
            'task:'.$taskKey,
        ]);

        Telescope::recordEntry($entry);

        // Clean up active task data
        unset($this->activeTasks[$taskKey]);
    }

    /**
     * Record a task failed event.
     */
    public function recordTaskFailed(TaskFailed $event): void
    {
        if (! $this->shouldRecord()) {
            return;
        }

        $taskKey = (string) $event->taskKey;
        $startData = $this->activeTasks[$taskKey] ?? null;

        $entry = IncomingEntry::make([
            'type' => 'parallel_task',
            'content' => [
                'task_key' => $event->taskKey,
                'status' => 'failed',
                'execution_time' => $event->executionTime,
                'exception' => [
                    'class' => $event->exception::class,
                    'message' => $event->exception->getMessage(),
                    'code' => $event->exception->getCode(),
                    'file' => $event->exception->getFile(),
                    'line' => $event->exception->getLine(),
                    'trace' => $this->formatStackTrace($event->exception->getTrace()),
                ],
                'started_at' => $startData['started_at'] ?? null,
                'failed_at' => microtime(true),
            ],
        ]);

        // Add tags for filtering
        $entry->tags([
            'parallel',
            'parallel:failed',
            'task:'.$taskKey,
            'exception:'.class_basename($event->exception),
        ]);

        Telescope::recordEntry($entry);

        // Clean up active task data
        unset($this->activeTasks[$taskKey]);
    }

    /**
     * Record a task started event.
     */
    public function recordTaskStarted(TaskStarted $event): void
    {
        if (! $this->shouldRecord()) {
            return;
        }
        $this->cleanupStaleTasks();

        $taskKey = (string) $event->taskKey;

        // Store start time for later reference
        $this->activeTasks[$taskKey] = [
            'started_at' => $event->startedAt,
            'task_key' => $event->taskKey,
        ];

        $entry = IncomingEntry::make([
            'type' => 'parallel_task',
            'content' => [
                'task_key' => $event->taskKey,
                'status' => 'started',
                'started_at' => $event->startedAt,
            ],
        ]);

        // Add tags for filtering
        $entry->tags([
            'parallel',
            'parallel:started',
            'task:'.$taskKey,
        ]);

        Telescope::recordEntry($entry);
    }

    /**
     * Register the watcher's event listeners.
     *
     * This is the standard Telescope watcher pattern for registering
     * event listeners via Laravel's event dispatcher.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     */
    public function register($app): void
    {
        $app['events']->listen(TaskStarted::class, [$this, 'recordTaskStarted']);
        $app['events']->listen(TaskCompleted::class, [$this, 'recordTaskCompleted']);
        $app['events']->listen(TaskFailed::class, [$this, 'recordTaskFailed']);
    }

    private function cleanupStaleTasks(): void
    {
        $now = microtime(true);

        // Remove tasks that have exceeded the TTL
        foreach ($this->activeTasks as $key => $data) {
            if (($now - $data['started_at']) > self::TASK_TTL_SECONDS) {
                unset($this->activeTasks[$key]);
            }
        }

        // If we still exceed the maximum, remove oldest tasks
        if (count($this->activeTasks) >= self::MAX_ACTIVE_TASKS) {
            // Keep only the most recent tasks
            $this->activeTasks = array_slice(
                $this->activeTasks,
                -self::MAX_ACTIVE_TASKS + 100, // Leave room for new tasks
                null,
                true
            );
        }
    }

    /**
     * Format the result for storage.
     */
    private function formatResult(mixed $result): mixed
    {
        // Limit result size to prevent excessive storage
        if (is_string($result) && mb_strlen($result) > 1000) {
            return mb_substr($result, 0, 1000).'... (truncated)';
        }

        if (is_array($result) && count($result) > 100) {
            return array_slice($result, 0, 100) + ['...' => '(truncated)'];
        }

        // For objects, store class name only
        if (is_object($result)) {
            return [
                'class' => $result::class,
                'value' => method_exists($result, '__toString')
                    ? (string) $result
                    : '(object)',
            ];
        }

        return $result;
    }

    /**
     * Format stack trace for storage.
     *
     * @param  array<int, array<string, mixed>>  $trace
     * @return array<int, array<string, mixed>>
     */
    private function formatStackTrace(array $trace): array
    {
        // Limit stack trace to first 10 frames
        $trace = array_slice($trace, 0, 10);

        return array_map(function (array $frame): array {
            return [
                'file' => $frame['file'] ?? null,
                'line' => $frame['line'] ?? null,
                'function' => $frame['function'] ?? null,
                'class' => $frame['class'] ?? null,
            ];
        }, $trace);
    }

    /**
     * Determine if tasks should be recorded.
     *
     * Note: Most config checks are done at service provider registration.
     * This provides an additional runtime check for configuration changes.
     */
    private function shouldRecord(): bool
    {
        // Allow runtime configuration changes to disable recording
        return config('parallel.telescope.enabled', true)
            && config('parallel.telescope.record_tasks', true);
    }
}
