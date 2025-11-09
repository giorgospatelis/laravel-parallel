<?php

declare(strict_types=1);

namespace LaravelParallel\Integrations\Horizon;

use Illuminate\Support\Facades\Redis;
use LaravelParallel\Events\TaskCompleted;
use LaravelParallel\Events\TaskFailed;
use LaravelParallel\Events\TaskStarted;
use Throwable;

/**
 * Bridges parallel task metrics to Laravel Horizon's Redis schema.
 *
 * This class records parallel job metrics to Redis using Horizon's data structure,
 * allowing parallel tasks to be monitored through the Horizon dashboard.
 *
 * Redis Schema:
 * - horizon:parallel:jobs:started (hash) - Counter of started jobs per task
 * - horizon:parallel:jobs:failed (hash) - Counter of failed jobs per task
 * - horizon:parallel:jobs:completed:count (string) - Total completed jobs
 * - horizon:parallel:jobs:completed:timestamp (string) - Last completion timestamp
 * - horizon:parallel:jobs:recent (sorted set) - Recent jobs with timestamps
 * - horizon:parallel:jobs:time:{key} (list) - Execution times per task (max 100)
 * - horizon:parallel:jobs:metadata (hash) - Job metadata with tags
 * - horizon:parallel:pools:wait (hash) - Wait times per worker pool
 * - horizon:parallel:processes:active (string) - Active process count
 */
class HorizonMetricsBridge
{
    private const EXECUTION_TIME_LIMIT = 100;

    private const KEY_PREFIX = 'horizon:parallel:';

    private const RECENT_JOBS_LIMIT = 100;

    /**
     * @param  string  $redisConnection  Redis connection name
     */
    public function __construct(
        private readonly string $redisConnection = 'default'
    ) {}

    public function getActiveProcessCount(): int
    {
        $redis = $this->getRedis();

        return (int) ($redis->get(self::KEY_PREFIX.'processes:active') ?? 0);
    }

    /**
     * Get active workers information.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getActiveWorkers(): array
    {
        $redis = $this->getRedis();

        $workersData = $redis->hgetall(self::KEY_PREFIX.'workers');

        if (! is_array($workersData)) {
            return [];
        }

        $workers = [];

        foreach ($workersData as $workerId => $data) {
            $decoded = json_decode($data, true);

            if (is_array($decoded)) {
                $workers[] = array_merge(['id' => $workerId], $decoded);
            }
        }

        return $workers;
    }

    /**
     * @param  string|int  $taskKey  Task identifier
     * @return array<int, float>
     */
    public function getExecutionTimeHistogram(string|int $taskKey): array
    {
        $redis = $this->getRedis();

        $times = $redis->lrange(self::KEY_PREFIX.'jobs:time:'.$taskKey, 0, -1);

        if (! is_array($times)) {
            return [];
        }

        return array_map('floatval', $times);
    }

    /**
     * Get count of failed jobs.
     */
    public function getFailedJobsCount(): int
    {
        $redis = $this->getRedis();

        $failedJobs = $redis->hgetall(self::KEY_PREFIX.'jobs:failed');

        if (! is_array($failedJobs)) {
            return 0;
        }

        return (int) array_sum(array_map('intval', $failedJobs));
    }

    public function getJobsPerMinute(): float
    {
        $redis = $this->getRedis();

        $count = (int) ($redis->get(self::KEY_PREFIX.'jobs:completed:count') ?? 0);
        $timestamp = (int) ($redis->get(self::KEY_PREFIX.'jobs:completed:timestamp') ?? time());

        $elapsed = time() - $timestamp;

        if ($elapsed === 0) {
            return 0.0;
        }

        return ($count / $elapsed) * 60;
    }

    /**
     * Get recent jobs list.
     *
     * @param  int  $limit  Maximum number of jobs to return
     * @return array<int, array<string, mixed>>
     */
    public function getRecentJobs(int $limit = 100): array
    {
        $redis = $this->getRedis();

        // Get recent job keys from sorted set (most recent first)
        $jobKeys = $redis->zrevrange(self::KEY_PREFIX.'jobs:recent', 0, $limit - 1, 'WITHSCORES');

        if (! is_array($jobKeys)) {
            return [];
        }

        $jobs = [];

        // jobKeys format: [key1, timestamp1, key2, timestamp2, ...]
        for ($i = 0; $i < count($jobKeys); $i += 2) {
            $taskKey = $jobKeys[$i];
            $timestamp = (float) ($jobKeys[$i + 1] ?? 0);

            // Get job metadata if available
            $metadataJson = $redis->hget(self::KEY_PREFIX.'jobs:metadata', $taskKey);
            $metadata = $metadataJson ? json_decode($metadataJson, true) : [];

            $jobs[] = [
                'task_key' => $taskKey,
                'timestamp' => $timestamp,
                'metadata' => is_array($metadata) ? $metadata : [],
            ];
        }

        return $jobs;
    }

    public function getRedisConnection(): string
    {
        return $this->redisConnection;
    }

    /**
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        $redis = $this->getRedis();

        return [
            'failedJobs' => $redis->hgetall(self::KEY_PREFIX.'jobs:failed'),
            'jobsPerMinute' => (float) ($redis->get(self::KEY_PREFIX.'jobs:completed:count') ?? 0),
            'processes' => (int) ($redis->get(self::KEY_PREFIX.'processes:active') ?? 0),
            'recentJobs' => (int) $redis->zcard(self::KEY_PREFIX.'jobs:recent'),
            'status' => 'active',
            'wait' => (int) $redis->hlen(self::KEY_PREFIX.'pools:wait'),
        ];
    }

    /**
     * @return array<string, float>
     */
    public function getWaitTimes(): array
    {
        $redis = $this->getRedis();

        $waitTimes = $redis->hgetall(self::KEY_PREFIX.'pools:wait');

        if (! is_array($waitTimes)) {
            return [];
        }

        return array_map('floatval', $waitTimes);
    }

    /**
     * Get workload information per worker pool.
     *
     * @return array<string, mixed>
     */
    public function getWorkload(): array
    {
        $redis = $this->getRedis();

        // Get started jobs per task
        $startedJobs = $redis->hgetall(self::KEY_PREFIX.'jobs:started');
        $failedJobs = $redis->hgetall(self::KEY_PREFIX.'jobs:failed');
        $waitTimes = $redis->hgetall(self::KEY_PREFIX.'pools:wait');

        $workload = [];

        if (is_array($startedJobs)) {
            foreach ($startedJobs as $taskKey => $count) {
                $failedCount = is_array($failedJobs) && isset($failedJobs[$taskKey])
                    ? (int) $failedJobs[$taskKey]
                    : 0;

                $workload[$taskKey] = [
                    'started' => (int) $count,
                    'failed' => $failedCount,
                    'success_rate' => $count > 0
                        ? round((($count - $failedCount) / $count) * 100, 2)
                        : 0,
                ];
            }
        }

        return [
            'tasks' => $workload,
            'wait_times' => is_array($waitTimes) ? array_map('floatval', $waitTimes) : [],
        ];
    }

    public function handleTaskCompleted(TaskCompleted $event): void
    {
        $this->recordJobCompleted($event->taskKey, $event->executionTime);
    }

    public function handleTaskFailed(TaskFailed $event): void
    {
        $this->recordJobFailed($event->taskKey, $event->exception, $event->executionTime);
    }

    public function handleTaskStarted(TaskStarted $event): void
    {
        $this->recordJobStarted($event->taskKey, $event->startedAt);
    }

    /**
     * @param  string|int  $taskKey  Task identifier
     * @param  float  $executionTime  Execution time in seconds
     */
    public function recordJobCompleted(string|int $taskKey, float $executionTime): void
    {
        $redis = $this->getRedis();

        // Store execution time
        $redis->rpush(self::KEY_PREFIX.'jobs:time:'.$taskKey, $executionTime);
        $redis->ltrim(self::KEY_PREFIX.'jobs:time:'.$taskKey, -self::EXECUTION_TIME_LIMIT, -1);

        // Update throughput tracking
        $redis->incr(self::KEY_PREFIX.'jobs:completed:count');
        $redis->zadd(self::KEY_PREFIX.'jobs:recent', microtime(true), (string) $taskKey);
    }

    /**
     * @param  string|int  $taskKey  Task identifier
     * @param  Throwable  $exception  The exception that caused the failure
     * @param  float  $executionTime  Execution time in seconds before failure
     */
    public function recordJobFailed(string|int $taskKey, Throwable $exception, float $executionTime): void
    {
        $redis = $this->getRedis();
        $redis->hincrby(self::KEY_PREFIX.'jobs:failed', (string) $taskKey, 1);
        $exceptionData = [
            'class' => $exception::class,
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];
        $redis->hset(
            self::KEY_PREFIX.'jobs:exceptions',
            (string) $taskKey,
            json_encode($exceptionData, JSON_THROW_ON_ERROR)
        );
        $redis->zadd(self::KEY_PREFIX.'jobs:recent', microtime(true), (string) $taskKey);
    }

    /**
     * @param  string|int  $taskKey  Task identifier
     * @param  float  $startedAt  Unix timestamp when task started
     */
    public function recordJobStarted(string|int $taskKey, float $startedAt): void
    {
        $redis = $this->getRedis();
        $redis->hincrby(self::KEY_PREFIX.'jobs:started', (string) $taskKey, 1);
        $redis->hset(
            self::KEY_PREFIX.'jobs:timestamps',
            (string) $taskKey,
            (string) $startedAt
        );
        $this->addToRecentJobs($taskKey, $startedAt);
    }

    /**
     * @param  string|int  $taskKey  Task identifier
     * @param  array<string, mixed>  $metadata  Job metadata
     */
    public function storeJobMetadata(string|int $taskKey, array $metadata): void
    {
        $redis = $this->getRedis();

        $data = [
            'tags' => ['parallel'],
            'metadata' => $metadata,
        ];

        $redis->hset(
            self::KEY_PREFIX.'jobs:metadata',
            (string) $taskKey,
            json_encode($data, JSON_THROW_ON_ERROR)
        );
    }

    /**
     * Add a job to the recent jobs list and maintain the limit.
     *
     * @param  string|int  $taskKey  Task identifier
     * @param  float  $timestamp  Unix timestamp
     */
    private function addToRecentJobs(string|int $taskKey, float $timestamp): void
    {
        $redis = $this->getRedis();
        $redis->zadd(self::KEY_PREFIX.'jobs:recent', $timestamp, (string) $taskKey);
        $redis->zremrangebyrank(self::KEY_PREFIX.'jobs:recent', 0, -self::RECENT_JOBS_LIMIT - 1);
    }

    private function getRedis(): mixed
    {
        return Redis::connection($this->redisConnection);
    }
}
