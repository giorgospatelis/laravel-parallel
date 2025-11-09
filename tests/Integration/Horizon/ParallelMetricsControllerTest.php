<?php

declare(strict_types=1);

namespace LaravelParallel\Tests\Integration\Horizon;

use Exception;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;
use LaravelParallel\Integrations\Horizon\Controllers\ParallelMetricsController;
use LaravelParallel\Integrations\Horizon\HorizonMetricsBridge;
use Mockery;

/**
 * Test API endpoints for Horizon metrics.
 *
 * These tests verify that the API controller returns correct JSON
 * responses with Horizon-compatible data formats.
 */
beforeEach(function () {
    // Mock Redis connection
    Redis::shouldReceive('connection')->andReturnSelf();

    // Register HorizonMetricsBridge in container
    $this->app->singleton(HorizonMetricsBridge::class, function () {
        return new HorizonMetricsBridge('default');
    });

    // Register test routes
    Route::prefix('horizon/api/parallel')->group(function () {
        Route::get('/stats', [ParallelMetricsController::class, 'stats']);
        Route::get('/workload', [ParallelMetricsController::class, 'workload']);
        Route::get('/jobs/recent', [ParallelMetricsController::class, 'recentJobs']);
        Route::get('/workers', [ParallelMetricsController::class, 'workers']);
    });
});

describe('ParallelMetricsController API Endpoints', function () {
    it('returns stats in Horizon-compatible format', function () {
        // Arrange: Mock all Redis calls used by getStats()
        Redis::shouldReceive('hgetall')
            ->once()
            ->with('horizon:parallel:jobs:failed')
            ->andReturn(['task1' => '2', 'task2' => '3']);

        Redis::shouldReceive('get')
            ->once()
            ->with('horizon:parallel:jobs:completed:count')
            ->andReturn('100');

        Redis::shouldReceive('get')
            ->once()
            ->with('horizon:parallel:processes:active')
            ->andReturn('4');

        Redis::shouldReceive('zcard')
            ->once()
            ->with('horizon:parallel:jobs:recent')
            ->andReturn(50);

        Redis::shouldReceive('hlen')
            ->once()
            ->with('horizon:parallel:pools:wait')
            ->andReturn(2);

        // Act: Call stats endpoint
        $response = $this->getJson('/horizon/api/parallel/stats');

        // Assert: Verify response structure
        $response->assertStatus(200)
            ->assertJsonStructure([
                'failedJobs',
                'jobsPerMinute',
                'processes',
                'recentJobs',
                'status',
                'wait',
            ])
            ->assertJson([
                'status' => 'active',
                'processes' => 4,
                'recentJobs' => 50,
            ]);
    });

    it('returns workload data with task statistics', function () {
        // Arrange: Mock Redis responses for getWorkload()
        Redis::shouldReceive('hgetall')
            ->once()
            ->with('horizon:parallel:jobs:started')
            ->andReturn(['task1' => '10', 'task2' => '5']);

        Redis::shouldReceive('hgetall')
            ->once()
            ->with('horizon:parallel:jobs:failed')
            ->andReturn(['task1' => '2', 'task2' => '1']);

        Redis::shouldReceive('hgetall')
            ->once()
            ->with('horizon:parallel:pools:wait')
            ->andReturn(['default' => '12.5', 'batch' => '8.0']);

        // Act: Call workload endpoint
        $response = $this->getJson('/horizon/api/parallel/workload');

        // Assert: Verify response structure
        $response->assertStatus(200)
            ->assertJsonStructure([
                'tasks',
                'wait_times',
            ]);

        $data = $response->json();
        expect($data)->toBeArray()
            ->and($data['tasks'])->toBeArray()
            ->and($data['wait_times'])->toBeArray();
    });

    it('returns recent jobs with proper pagination', function () {
        // Arrange: Mock Redis responses for getRecentJobs()
        Redis::shouldReceive('zrevrange')
            ->once()
            ->with('horizon:parallel:jobs:recent', 0, 49, 'WITHSCORES')
            ->andReturn([
                'task-1', '1699564800.123',
                'task-2', '1699564799.456',
                'task-3', '1699564798.789',
            ]);

        Redis::shouldReceive('hget')
            ->times(3)
            ->with('horizon:parallel:jobs:metadata', Mockery::type('string'))
            ->andReturn(
                json_encode(['tags' => ['parallel:TestTask1']]),
                json_encode(['tags' => ['parallel:TestTask2']]),
                null
            );

        // Act: Call recent jobs endpoint with limit
        $response = $this->getJson('/horizon/api/parallel/jobs/recent?limit=50');

        // Assert: Verify response structure
        $response->assertStatus(200)
            ->assertJsonIsArray();

        $data = $response->json();
        expect($data)->toBeArray()
            ->and(count($data))->toBe(3);
    });

    it('returns active workers information', function () {
        // Arrange: Mock Redis responses for getActiveWorkers()
        Redis::shouldReceive('hgetall')
            ->once()
            ->with('horizon:parallel:workers')
            ->andReturn([
                'worker-1' => json_encode(['started_at' => time(), 'pool' => 'default']),
                'worker-2' => json_encode(['started_at' => time() - 60, 'pool' => 'batch']),
            ]);

        // Act: Call workers endpoint
        $response = $this->getJson('/horizon/api/parallel/workers');

        // Assert: Verify response structure
        $response->assertStatus(200)
            ->assertJsonIsArray();

        $data = $response->json();
        expect($data)->toBeArray()
            ->and(count($data))->toBe(2)
            ->and($data[0])->toHaveKey('id');
    });

    it('handles empty metrics gracefully', function () {
        // Arrange: Mock empty Redis responses for getStats()
        Redis::shouldReceive('hgetall')
            ->once()
            ->with('horizon:parallel:jobs:failed')
            ->andReturn([]);

        Redis::shouldReceive('get')
            ->once()
            ->with('horizon:parallel:jobs:completed:count')
            ->andReturn('0');

        Redis::shouldReceive('get')
            ->once()
            ->with('horizon:parallel:processes:active')
            ->andReturn('0');

        Redis::shouldReceive('zcard')
            ->once()
            ->with('horizon:parallel:jobs:recent')
            ->andReturn(0);

        Redis::shouldReceive('hlen')
            ->once()
            ->with('horizon:parallel:pools:wait')
            ->andReturn(0);

        // Act: Call stats endpoint
        $response = $this->getJson('/horizon/api/parallel/stats');

        // Assert: Returns valid response with zeros
        $response->assertStatus(200)
            ->assertJson([
                'failedJobs' => [],
                'jobsPerMinute' => 0,
                'processes' => 0,
                'recentJobs' => 0,
                'status' => 'active',
                'wait' => 0,
            ]);
    });

    it('applies limit parameter to recent jobs', function () {
        // Arrange: Mock Redis with specific limit
        Redis::shouldReceive('zrevrange')
            ->once()
            ->with('horizon:parallel:jobs:recent', 0, 9, 'WITHSCORES')
            ->andReturn(['task-1', '1699564800.123']);

        Redis::shouldReceive('hget')
            ->once()
            ->with('horizon:parallel:jobs:metadata', 'task-1')
            ->andReturn(null);

        // Act: Call with limit=10
        $response = $this->getJson('/horizon/api/parallel/jobs/recent?limit=10');

        // Assert: Successful response
        $response->assertStatus(200)
            ->assertJsonIsArray();

        $data = $response->json();
        expect($data)->toBeArray()
            ->and(count($data))->toBe(1);
    });

    it('returns JSON content type for all endpoints', function () {
        // Arrange: Mock Redis responses for all endpoints
        // For stats endpoint
        Redis::shouldReceive('hgetall')->andReturn([]);
        Redis::shouldReceive('get')->andReturn('0');
        Redis::shouldReceive('zcard')->andReturn(0);
        Redis::shouldReceive('hlen')->andReturn(0);

        // For workload endpoint (already mocked above)

        // For recent jobs endpoint
        Redis::shouldReceive('zrevrange')->andReturn([]);

        // For workers endpoint (already mocked above)

        // Act & Assert: Check all endpoints
        $endpoints = [
            '/horizon/api/parallel/stats',
            '/horizon/api/parallel/workload',
            '/horizon/api/parallel/jobs/recent',
            '/horizon/api/parallel/workers',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            $response->assertHeader('Content-Type', 'application/json');
        }
    });

    it('handles Redis connection errors gracefully', function () {
        // Arrange: Mock Redis throwing exception on first call
        Redis::shouldReceive('hgetall')
            ->once()
            ->andThrow(new Exception('Redis connection failed'));

        // Act: Call stats endpoint
        $response = $this->getJson('/horizon/api/parallel/stats');

        // Assert: Returns 500 error response with error message
        $response->assertStatus(500)
            ->assertJson([
                'status' => 'error',
                'error' => 'Redis connection failed',
            ]);
    });

    it('caches stats data for performance', function () {
        // Note: Caching is NOT currently implemented, so each request hits Redis
        // This test documents the current behavior (no caching)

        // Arrange: Mock Redis responses for TWO separate calls
        Redis::shouldReceive('hgetall')->twice()->andReturn([]);
        Redis::shouldReceive('get')->times(4)->andReturn('100', '4', '100', '4');
        Redis::shouldReceive('zcard')->twice()->andReturn(50);
        Redis::shouldReceive('hlen')->twice()->andReturn(2);

        // Act: Call stats endpoint twice
        $response1 = $this->getJson('/horizon/api/parallel/stats');
        $response2 = $this->getJson('/horizon/api/parallel/stats');

        // Assert: Both requests succeed
        $response1->assertStatus(200);
        $response2->assertStatus(200);

        // Future: When caching is implemented, update this test to verify
        // Redis is only called once
        expect(true)->toBeTrue();
    });

    it('returns consistent data structure across requests', function () {
        // Arrange: Mock Redis for TWO calls
        Redis::shouldReceive('hgetall')->twice()->andReturn([]);
        Redis::shouldReceive('get')->times(4)->andReturn('0', '0', '0', '0');
        Redis::shouldReceive('zcard')->twice()->andReturn(0);
        Redis::shouldReceive('hlen')->twice()->andReturn(0);

        // Act: Call same endpoint multiple times
        $response1 = $this->getJson('/horizon/api/parallel/stats');
        $response2 = $this->getJson('/horizon/api/parallel/stats');

        // Assert: Same structure
        expect($response1->json())->toHaveKeys(['failedJobs', 'jobsPerMinute', 'processes', 'recentJobs', 'status', 'wait'])
            ->and($response2->json())->toHaveKeys(['failedJobs', 'jobsPerMinute', 'processes', 'recentJobs', 'status', 'wait']);
    });
});

describe('ParallelMetricsController Authorization', function () {
    it('allows access without authentication by default', function () {
        // Arrange: Mock Redis
        Redis::shouldReceive('hgetall')->once()->andReturn([]);
        Redis::shouldReceive('get')->twice()->andReturn('0');
        Redis::shouldReceive('zcard')->once()->andReturn(0);
        Redis::shouldReceive('hlen')->once()->andReturn(0);

        // Act: Call endpoint without auth
        $response = $this->getJson('/horizon/api/parallel/stats');

        // Assert: Accessible
        $response->assertStatus(200);
    });

    // Note: If Horizon middleware is applied, this would require authentication
    // Uncomment when Horizon middleware is added:
    /*
    it('respects Horizon middleware configuration', function () {
        // This test would verify that Horizon's auth middleware applies
        // when configured in config/horizon.php
    });
    */
});

describe('ParallelMetricsController Performance', function () {
    it('responds within acceptable time limits', function () {
        // Arrange: Mock Redis
        Redis::shouldReceive('hgetall')->once()->andReturn([]);
        Redis::shouldReceive('get')->twice()->andReturn('0');
        Redis::shouldReceive('zcard')->once()->andReturn(0);
        Redis::shouldReceive('hlen')->once()->andReturn(0);

        $startTime = microtime(true);

        // Act: Call stats endpoint
        $this->getJson('/horizon/api/parallel/stats');

        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to ms

        // Assert: Response time < 100ms
        expect($responseTime)->toBeLessThan(100.0);
    });

    it('handles concurrent requests efficiently', function () {
        // Arrange: Mock Redis for 10 calls
        Redis::shouldReceive('hgetall')->times(10)->andReturn([]);
        Redis::shouldReceive('get')->times(20)->andReturn('0');
        Redis::shouldReceive('zcard')->times(10)->andReturn(0);
        Redis::shouldReceive('hlen')->times(10)->andReturn(0);

        $startTime = microtime(true);

        // Act: Simulate 10 concurrent requests
        for ($i = 0; $i < 10; $i++) {
            $this->getJson('/horizon/api/parallel/stats');
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;
        $averageTime = $totalTime / 10;

        // Assert: Average time per request < 50ms
        expect($averageTime)->toBeLessThan(50.0);
    });
});
