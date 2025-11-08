<?php

declare(strict_types=1);

namespace LaravelParallel\Core;

use Amp\Parallel\Worker\WorkerPool;
use Closure;
use Illuminate\Support\Facades\Log;
use LaravelParallel\Contracts\ExecutorContract;
use LaravelParallel\Results\ParallelResult;
use LaravelParallel\Support\TaskValidator;
use LaravelParallel\Tasks\ClosureTask;
use LaravelParallel\Workers\WorkerConfiguration;
use LaravelParallel\Workers\WorkerPoolFactory;
use Throwable;

/**
 * Executes parallel tasks using worker pools.
 *
 * This class coordinates the execution of parallel tasks by creating worker pools,
 * submitting tasks, and collecting results. It implements the core execution logic
 * extracted from ParallelManager.
 */
final class Executor implements ExecutorContract
{
    private ?WorkerPool $activePool = null;

    private ?WorkerConfiguration $cachedConfig = null;

    private bool $configDirty = true;

    private ?float $timeout = null;

    private ?int $workerCount = null;

    public function __construct(
        private readonly WorkerPoolFactory $poolFactory,
        private readonly ResultCollector $resultCollector,
        private readonly TaskValidator $validator,
    ) {
    }

    /**
     * Destructor ensures worker pool is properly shutdown.
     *
     * This provides a safety net for resource cleanup even if exceptions
     * occur or the object is destroyed unexpectedly.
     */
    public function __destruct()
    {
        if ($this->activePool !== null) {
            try {
                $this->activePool->shutdown();
                $this->log('debug', 'Worker pool shutdown in destructor');
            } catch (Throwable $e) {
                // Silently handle shutdown errors in destructor
                // Logging may not be available during shutdown
            }
            $this->activePool = null;
        }
    }

    /**
     * Execute an array of tasks in parallel.
     *
     * @param  array<string|int, callable>  $tasks  Array of tasks to execute
     * @return array<string|int, ParallelResult> Results indexed by task keys
     *
     * @throws \LaravelParallel\Exceptions\ParallelException If execution fails
     */
    public function execute(array $tasks): array
    {
        if (empty($tasks)) {
            return [];
        }

        $this->validator->validateAll($tasks);

        $config = $this->getOrBuildConfig();

        $startTime = microtime(true);
        $taskCount = count($tasks);

        $this->log('info', 'Parallel execution started', [
            'task_count' => $taskCount,
            'worker_count' => $config->workerCount,
            'timeout' => $config->timeout,
        ]);

        $pool = $this->poolFactory->create($config);
        $this->activePool = $pool;

        try {
            $executions = $this->submitTasks($pool, $tasks);

            $results = $this->resultCollector->collect($executions, $this->timeout);

            $duration = microtime(true) - $startTime;

            $successCount = count(array_filter($results, fn ($r) => $r->isSuccess()));
            $failureCount = $taskCount - $successCount;

            $this->log('info', 'Parallel execution completed', [
                'task_count' => $taskCount,
                'success_count' => $successCount,
                'failure_count' => $failureCount,
                'duration_ms' => round($duration * 1000, 2),
                'avg_time_per_task_ms' => round(($duration / $taskCount) * 1000, 2),
            ]);

            return $results;
        } catch (Throwable $e) {
            $duration = microtime(true) - $startTime;

            $this->log('error', 'Parallel execution failed', [
                'task_count' => $taskCount,
                'duration_ms' => round($duration * 1000, 2),
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
            ]);

            throw $e;
        } finally {
            $pool->shutdown();
            $this->activePool = null;
        }
    }

    /**
     * Get the configured timeout in seconds.
     */
    public function getTimeout(): ?float
    {
        return $this->timeout;
    }

    /**
     * Get the configured worker count.
     */
    public function getWorkerCount(): ?int
    {
        return $this->workerCount;
    }

    /**
     * Set the maximum execution timeout in seconds.
     *
     * @param  float  $seconds  Timeout in seconds (must be > 0)
     */
    public function setTimeout(float $seconds): self
    {
        $this->validator->validateTimeout($seconds);
        $this->timeout = $seconds;
        $this->configDirty = true;

        return $this;
    }

    /**
     * Set the number of worker processes.
     *
     * @param  int  $count  The number of workers
     */
    public function setWorkerCount(int $count): self
    {
        $this->workerCount = $count;
        $this->configDirty = true;

        return $this;
    }

    /**
     * Get or build the worker configuration.
     *
     * Caches the configuration to avoid recreating it on every execution.
     * Cache is invalidated when timeout or worker count changes.
     */
    private function getOrBuildConfig(): WorkerConfiguration
    {
        if ($this->cachedConfig === null || $this->configDirty) {
            $config = WorkerConfiguration::fromConfig($this->workerCount);

            if ($this->timeout !== null) {
                $config = $config->withTimeout($this->timeout);
            }

            $this->cachedConfig = $config;
            $this->configDirty = false;
        }

        return $this->cachedConfig;
    }

    /**
     * Log a message if logging is enabled.
     *
     * @param  string  $level  The log level (debug, info, warning, error, etc.)
     * @param  string  $message  The log message
     * @param  array<string, mixed>  $context  Additional context data
     */
    private function log(string $level, string $message, array $context = []): void
    {
        if (! config('parallel.logging.enabled', true)) {
            return;
        }

        $channel = config('parallel.logging.channel', 'stack');
        $minLevel = config('parallel.logging.level', 'info');

        $levels = ['debug' => 0, 'info' => 1, 'notice' => 2, 'warning' => 3, 'error' => 4];
        if (($levels[$level] ?? 0) < ($levels[$minLevel] ?? 0)) {
            return;
        }

        Log::channel($channel)->$level($message, $context);
    }

    /**
     * Submit tasks to the worker pool.
     *
     * @param  WorkerPool  $pool  The worker pool to use
     * @param  array<string|int, callable>  $tasks  Closures to execute
     * @return array<string|int, \Amp\Parallel\Worker\Execution<mixed, mixed, mixed>> Execution handles indexed by original keys
     */
    private function submitTasks($pool, array $tasks): array
    {
        $executions = [];

        foreach ($tasks as $key => $task) {
            if ($task instanceof Closure) {
                $closureTask = new ClosureTask($task);
                $executions[$key] = $pool->submit($closureTask);
            }
        }

        return $executions;
    }
}
