<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Redis;
use LaravelParallel\Events\TaskCompleted;
use LaravelParallel\Events\TaskFailed;
use LaravelParallel\Events\TaskStarted;
use LaravelParallel\Integrations\Horizon\HorizonMetricsBridge;

beforeEach(function () {
    // Mock Redis connection - always return self for fluent interface
    Redis::shouldReceive('connection')
        ->andReturnSelf();

    $this->bridge = new HorizonMetricsBridge;
});

describe('HorizonMetricsBridge', function () {
    it('records job started metric to Redis', function () {
        $taskKey = 'task_1';
        $startedAt = microtime(true);

        Redis::shouldReceive('hincrby')
            ->once()
            ->with('horizon:parallel:jobs:started', $taskKey, 1)
            ->andReturn(1);

        Redis::shouldReceive('hset')
            ->once()
            ->andReturn(1);

        Redis::shouldReceive('zadd')
            ->once()
            ->andReturn(1);

        Redis::shouldReceive('zremrangebyrank')
            ->once()
            ->andReturn(1);

        $this->bridge->recordJobStarted($taskKey, $startedAt);
    });

    it('records job completed metric with execution time', function () {
        $taskKey = 'task_1';
        $executionTime = 1.234;

        Redis::shouldReceive('rpush')
            ->once()
            ->with('horizon:parallel:jobs:time:'.$taskKey, $executionTime)
            ->andReturn(1);

        Redis::shouldReceive('ltrim')
            ->once()
            ->with('horizon:parallel:jobs:time:'.$taskKey, -100, -1)
            ->andReturn(true);

        Redis::shouldReceive('zadd')
            ->once()
            ->andReturn(1);

        Redis::shouldReceive('incr')
            ->once()
            ->andReturn(1);

        $this->bridge->recordJobCompleted($taskKey, $executionTime);
    });

    it('records job failed metric with exception details', function () {
        $taskKey = 'task_1';
        $exception = new RuntimeException('Test error', 500);
        $executionTime = 0.5;

        Redis::shouldReceive('hincrby')
            ->once()
            ->with('horizon:parallel:jobs:failed', $taskKey, 1)
            ->andReturn(1);

        Redis::shouldReceive('hset')
            ->once()
            ->andReturn(1);

        Redis::shouldReceive('zadd')
            ->once()
            ->andReturn(1);

        $this->bridge->recordJobFailed($taskKey, $exception, $executionTime);
    });

    it('retrieves stats in Horizon-compatible format', function () {
        Redis::shouldReceive('hgetall')
            ->andReturn(['task_1' => 5, 'task_2' => 3]);

        Redis::shouldReceive('get')
            ->andReturn('120');

        Redis::shouldReceive('zcard')
            ->andReturn(42);

        Redis::shouldReceive('hlen')
            ->andReturn(10);

        $stats = $this->bridge->getStats();

        expect($stats)->toBeArray()
            ->and($stats)->toHaveKeys(['failedJobs', 'jobsPerMinute', 'processes', 'recentJobs', 'status', 'wait']);
    });

    it('calculates throughput as jobs per minute', function () {
        // Mock Redis to return job count data
        Redis::shouldReceive('get')
            ->with('horizon:parallel:jobs:completed:count')
            ->andReturn('60');

        Redis::shouldReceive('get')
            ->with('horizon:parallel:jobs:completed:timestamp')
            ->andReturn((string) (time() - 60));

        $throughput = $this->bridge->getJobsPerMinute();

        expect($throughput)->toBeFloat()
            ->and($throughput)->toBeGreaterThanOrEqual(0);
    });

    it('maintains recent jobs list limited to 100 items', function () {
        $taskKey = 'task_overflow';
        $startedAt = microtime(true);

        Redis::shouldReceive('hincrby')
            ->once()
            ->andReturn(1);

        Redis::shouldReceive('hset')
            ->once()
            ->andReturn(1);

        // Expect zadd to add the job
        Redis::shouldReceive('zadd')
            ->once()
            ->with('horizon:parallel:jobs:recent', $startedAt, $taskKey)
            ->andReturn(1);

        // Expect zremrangebyrank to keep only last 100
        Redis::shouldReceive('zremrangebyrank')
            ->once()
            ->with('horizon:parallel:jobs:recent', 0, -101)
            ->andReturn(1);

        $this->bridge->recordJobStarted($taskKey, $startedAt);
    });

    it('stores job metadata with parallel-specific tags', function () {
        $taskKey = 'image_processing_task';
        $metadata = [
            'worker_count' => 4,
            'timeout' => 30,
            'pool_name' => 'default',
        ];

        Redis::shouldReceive('hset')
            ->once()
            ->withArgs(function ($key, $field, $value) use ($taskKey, $metadata) {
                expect($key)->toBe('horizon:parallel:jobs:metadata')
                    ->and($field)->toBe($taskKey);

                $decoded = json_decode($value, true);
                expect($decoded)->toHaveKeys(['tags', 'metadata'])
                    ->and($decoded['tags'])->toContain('parallel')
                    ->and($decoded['metadata'])->toBe($metadata);

                return true;
            })
            ->andReturn(1);

        $this->bridge->storeJobMetadata($taskKey, $metadata);
    });

    it('handles custom Redis connection', function () {
        $customBridge = new HorizonMetricsBridge('custom');

        // Verify the bridge stored the connection name
        expect($customBridge->getRedisConnection())->toBe('custom');

        // The global connection mock from beforeEach will handle the actual connection
        Redis::shouldReceive('hincrby')
            ->once()
            ->andReturn(1);

        Redis::shouldReceive('hset')
            ->once()
            ->andReturn(1);

        Redis::shouldReceive('zadd')
            ->once()
            ->andReturn(1);

        Redis::shouldReceive('zremrangebyrank')
            ->once()
            ->andReturn(1);

        $customBridge->recordJobStarted('task_1', microtime(true));
    });

    it('uses correct Redis key prefix horizon:parallel:*', function () {
        $taskKey = 'task_1';

        Redis::shouldReceive('hincrby')
            ->once()
            ->withArgs(function ($key) {
                expect($key)->toStartWith('horizon:parallel:');

                return true;
            })
            ->andReturn(1);

        Redis::shouldReceive('hset')
            ->once()
            ->andReturn(1);

        Redis::shouldReceive('zadd')
            ->once()
            ->andReturn(1);

        Redis::shouldReceive('zremrangebyrank')
            ->once()
            ->andReturn(1);

        $this->bridge->recordJobStarted($taskKey, microtime(true));
    });

    it('calculates wait times per worker pool', function () {
        Redis::shouldReceive('hgetall')
            ->with('horizon:parallel:pools:wait')
            ->andReturn([
                'default' => '12.5',
                'high_priority' => '3.2',
                'low_priority' => '45.8',
            ]);

        $waitTimes = $this->bridge->getWaitTimes();

        expect($waitTimes)->toBeArray()
            ->and($waitTimes)->toHaveKeys(['default', 'high_priority', 'low_priority'])
            ->and($waitTimes['default'])->toBe(12.5)
            ->and($waitTimes['high_priority'])->toBe(3.2)
            ->and($waitTimes['low_priority'])->toBe(45.8);
    });

    it('tracks active process count', function () {
        Redis::shouldReceive('get')
            ->with('horizon:parallel:processes:active')
            ->andReturn('8');

        $activeProcesses = $this->bridge->getActiveProcessCount();

        expect($activeProcesses)->toBe(8);
    });

    it('performance impact is less than 1ms per operation', function () {
        // Setup minimal mocking for actual execution
        Redis::shouldReceive('hincrby')->andReturn(1);
        Redis::shouldReceive('hset')->andReturn(1);
        Redis::shouldReceive('zadd')->andReturn(1);
        Redis::shouldReceive('zremrangebyrank')->andReturn(1);

        $iterations = 100;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $this->bridge->recordJobStarted("task_{$i}", microtime(true));
        }

        $endTime = microtime(true);
        $averageTime = (($endTime - $startTime) / $iterations) * 1000; // Convert to ms

        expect($averageTime)->toBeLessThan(1.0);
    });
});

describe('HorizonMetricsBridge integration with Events', function () {
    it('records metrics when TaskStarted event is dispatched', function () {
        Redis::shouldReceive('hincrby')->once()->andReturn(1);
        Redis::shouldReceive('hset')->once()->andReturn(1);
        Redis::shouldReceive('zadd')->once()->andReturn(1);
        Redis::shouldReceive('zremrangebyrank')->once()->andReturn(1);

        $bridge = new HorizonMetricsBridge;
        $event = new TaskStarted('task_1', microtime(true));

        $bridge->handleTaskStarted($event);
    });

    it('records metrics when TaskCompleted event is dispatched', function () {
        Redis::shouldReceive('rpush')->once()->andReturn(1);
        Redis::shouldReceive('ltrim')->once()->andReturn(true);
        Redis::shouldReceive('zadd')->once()->andReturn(1);
        Redis::shouldReceive('incr')->once()->andReturn(1);

        $bridge = new HorizonMetricsBridge;
        $event = new TaskCompleted('task_1', 'result', 1.234);

        $bridge->handleTaskCompleted($event);
    });

    it('records metrics when TaskFailed event is dispatched', function () {
        Redis::shouldReceive('hincrby')->once()->andReturn(1);
        Redis::shouldReceive('hset')->once()->andReturn(1);
        Redis::shouldReceive('zadd')->once()->andReturn(1);

        $bridge = new HorizonMetricsBridge;
        $exception = new RuntimeException('Test error');
        $event = new TaskFailed('task_1', $exception, 0.5);

        $bridge->handleTaskFailed($event);
    });

    it('stores execution time histogram data', function () {
        $taskKey = 'task_1';
        $executionTimes = [0.5, 1.0, 1.5, 2.0, 2.5];

        foreach ($executionTimes as $time) {
            Redis::shouldReceive('rpush')
                ->with('horizon:parallel:jobs:time:'.$taskKey, $time)
                ->andReturn(1);

            Redis::shouldReceive('ltrim')
                ->with('horizon:parallel:jobs:time:'.$taskKey, -100, -1)
                ->andReturn(true);

            Redis::shouldReceive('zadd')->andReturn(1);
            Redis::shouldReceive('incr')->andReturn(1);

            $this->bridge->recordJobCompleted($taskKey, $time);
        }

        // Retrieve histogram data
        Redis::shouldReceive('lrange')
            ->with('horizon:parallel:jobs:time:'.$taskKey, 0, -1)
            ->andReturn(['0.5', '1.0', '1.5', '2.0', '2.5']);

        $histogram = $this->bridge->getExecutionTimeHistogram($taskKey);

        expect($histogram)->toBeArray()
            ->and($histogram)->toHaveCount(5);
    });

    it('properly serializes exception data for storage', function () {
        $exception = new RuntimeException('Test error message', 500);
        $taskKey = 'task_1';

        Redis::shouldReceive('hincrby')->once()->andReturn(1);

        Redis::shouldReceive('hset')
            ->once()
            ->withArgs(function ($key, $field, $value) {
                $decoded = json_decode($value, true);

                expect($decoded)->toHaveKeys(['class', 'message', 'code', 'file', 'line'])
                    ->and($decoded['class'])->toBe(RuntimeException::class)
                    ->and($decoded['message'])->toBe('Test error message')
                    ->and($decoded['code'])->toBe(500);

                return true;
            })
            ->andReturn(1);

        Redis::shouldReceive('zadd')->once()->andReturn(1);

        $this->bridge->recordJobFailed($taskKey, $exception, 0.5);
    });
});

describe('HorizonMetricsBridge Redis schema compatibility', function () {
    it('uses sorted sets for time-series data', function () {
        $taskKey = 'task_1';
        $timestamp = microtime(true);

        Redis::shouldReceive('hincrby')->andReturn(1);
        Redis::shouldReceive('hset')->andReturn(1);

        Redis::shouldReceive('zadd')
            ->once()
            ->withArgs(function ($key, $score, $member) use ($timestamp, $taskKey) {
                expect($key)->toBe('horizon:parallel:jobs:recent')
                    ->and($score)->toBe($timestamp)
                    ->and($member)->toBe($taskKey);

                return true;
            })
            ->andReturn(1);

        Redis::shouldReceive('zremrangebyrank')->andReturn(1);

        $this->bridge->recordJobStarted($taskKey, $timestamp);
    });

    it('uses hashes for counter data', function () {
        $taskKey = 'task_1';

        Redis::shouldReceive('hincrby')
            ->once()
            ->withArgs(function ($key, $field, $increment) use ($taskKey) {
                expect($key)->toBe('horizon:parallel:jobs:started')
                    ->and($field)->toBe($taskKey)
                    ->and($increment)->toBe(1);

                return true;
            })
            ->andReturn(1);

        Redis::shouldReceive('hset')->andReturn(1);
        Redis::shouldReceive('zadd')->andReturn(1);
        Redis::shouldReceive('zremrangebyrank')->andReturn(1);

        $this->bridge->recordJobStarted($taskKey, microtime(true));
    });

    it('uses lists for execution time tracking', function () {
        $taskKey = 'task_1';
        $executionTime = 1.234;

        Redis::shouldReceive('rpush')
            ->once()
            ->withArgs(function ($key, $value) use ($taskKey, $executionTime) {
                expect($key)->toBe('horizon:parallel:jobs:time:'.$taskKey)
                    ->and($value)->toBe($executionTime);

                return true;
            })
            ->andReturn(1);

        Redis::shouldReceive('ltrim')->once()->andReturn(true);
        Redis::shouldReceive('zadd')->once()->andReturn(1);
        Redis::shouldReceive('incr')->once()->andReturn(1);

        $this->bridge->recordJobCompleted($taskKey, $executionTime);
    });
});
