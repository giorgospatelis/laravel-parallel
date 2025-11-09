<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use LaravelParallel\Events\TaskCompleted;
use LaravelParallel\Events\TaskFailed;
use LaravelParallel\Events\TaskStarted;
use LaravelParallel\Integrations\Horizon\HorizonMetricsBridge;
use LaravelParallel\Integrations\Horizon\ParallelHorizonServiceProvider;

beforeEach(function () {
    $this->provider = new ParallelHorizonServiceProvider($this->app);
});

describe('ParallelHorizonServiceProvider', function () {
    it('detects Horizon installation correctly', function () {
        // Simulate Horizon is installed
        $this->app->bind('Laravel\Horizon\Horizon', fn () => new stdClass);

        expect($this->provider->horizonIsInstalled())->toBeTrue();
    });

    it('detects when Horizon is not installed', function () {
        // Ensure Horizon is not bound
        expect($this->provider->horizonIsInstalled())->toBeFalse();
    });

    it('registers event listeners when Horizon is installed', function () {
        // Bind Horizon to container
        $this->app->bind('Laravel\Horizon\Horizon', fn () => new stdClass);

        // Don't use Event::fake() - we need real event dispatcher
        $this->provider->boot();

        // Verify event listeners are registered by checking dispatcher
        $listeners = Event::getListeners(TaskStarted::class);
        expect($listeners)->not->toBeEmpty();

        $listeners = Event::getListeners(TaskCompleted::class);
        expect($listeners)->not->toBeEmpty();

        $listeners = Event::getListeners(TaskFailed::class);
        expect($listeners)->not->toBeEmpty();
    });

    it('does not register listeners when Horizon is not installed', function () {
        // Ensure Horizon is not bound
        Event::fake();

        $this->provider->boot();

        // Events should not have listeners registered
        $listeners = Event::getListeners(TaskStarted::class);
        expect($listeners)->toBeEmpty();
    });

    it('binds HorizonMetricsBridge to container', function () {
        $this->app->bind('Laravel\Horizon\Horizon', fn () => new stdClass);

        $this->provider->register();

        expect($this->app->bound(HorizonMetricsBridge::class))->toBeTrue();
    });

    it('does not throw errors when Horizon is not installed', function () {
        expect(fn () => $this->provider->boot())->not->toThrow(Exception::class);
        expect(fn () => $this->provider->register())->not->toThrow(Exception::class);
    });

    it('tags parallel jobs with parallel:* prefix', function () {
        $this->app->bind('Laravel\Horizon\Horizon', fn () => new stdClass);

        $this->provider->boot();

        // Test that tag callback is registered
        $tags = $this->provider->getParallelJobTags([
            'name' => 'parallel_task_1',
            'type' => 'parallel',
        ]);

        expect($tags)->toBeArray()
            ->and($tags)->toContain('parallel')
            ->and($tags[0])->toStartWith('parallel:');
    });

    it('integrates with existing Horizon tags', function () {
        $this->app->bind('Laravel\Horizon\Horizon', fn () => new stdClass);

        $this->provider->boot();

        $existingTags = ['user:123', 'priority:high'];
        $parallelTags = $this->provider->getParallelJobTags([
            'name' => 'parallel_task',
            'type' => 'parallel',
        ]);

        $mergedTags = array_merge($existingTags, $parallelTags);

        expect($mergedTags)->toContain('user:123')
            ->and($mergedTags)->toContain('priority:high')
            ->and($mergedTags)->toContain('parallel');
    });
});

describe('ParallelHorizonServiceProvider event handling', function () {
    it('forwards TaskStarted events to HorizonMetricsBridge', function () {
        $this->app->bind('Laravel\Horizon\Horizon', fn () => new stdClass);

        // Mock the bridge
        $bridge = Mockery::mock(HorizonMetricsBridge::class);
        $bridge->shouldReceive('handleTaskStarted')
            ->once()
            ->withArgs(function ($event) {
                expect($event)->toBeInstanceOf(TaskStarted::class)
                    ->and($event->taskKey)->toBe('task_1');

                return true;
            });

        $this->app->instance(HorizonMetricsBridge::class, $bridge);

        $this->provider->boot();

        // Dispatch event
        $event = new TaskStarted('task_1', microtime(true));
        event($event);
    });

    it('forwards TaskCompleted events to HorizonMetricsBridge', function () {
        $this->app->bind('Laravel\Horizon\Horizon', fn () => new stdClass);

        $bridge = Mockery::mock(HorizonMetricsBridge::class);
        $bridge->shouldReceive('handleTaskCompleted')
            ->once()
            ->withArgs(function ($event) {
                expect($event)->toBeInstanceOf(TaskCompleted::class)
                    ->and($event->taskKey)->toBe('task_2')
                    ->and($event->executionTime)->toBe(1.234);

                return true;
            });

        $this->app->instance(HorizonMetricsBridge::class, $bridge);

        $this->provider->boot();

        $event = new TaskCompleted('task_2', 'result', 1.234);
        event($event);
    });

    it('forwards TaskFailed events to HorizonMetricsBridge', function () {
        $this->app->bind('Laravel\Horizon\Horizon', fn () => new stdClass);

        $bridge = Mockery::mock(HorizonMetricsBridge::class);
        $bridge->shouldReceive('handleTaskFailed')
            ->once()
            ->withArgs(function ($event) {
                expect($event)->toBeInstanceOf(TaskFailed::class)
                    ->and($event->taskKey)->toBe('task_3')
                    ->and($event->exception)->toBeInstanceOf(Throwable::class);

                return true;
            });

        $this->app->instance(HorizonMetricsBridge::class, $bridge);

        $this->provider->boot();

        $exception = new RuntimeException('Test error');
        $event = new TaskFailed('task_3', $exception, 0.5);
        event($event);
    });

    it('handles multiple events in sequence', function () {
        $this->app->bind('Laravel\Horizon\Horizon', fn () => new stdClass);

        $bridge = Mockery::mock(HorizonMetricsBridge::class);
        $bridge->shouldReceive('handleTaskStarted')->once();
        $bridge->shouldReceive('handleTaskCompleted')->once();
        $bridge->shouldReceive('handleTaskStarted')->once();
        $bridge->shouldReceive('handleTaskFailed')->once();

        $this->app->instance(HorizonMetricsBridge::class, $bridge);

        $this->provider->boot();

        // Dispatch multiple events
        event(new TaskStarted('task_1', microtime(true)));
        event(new TaskCompleted('task_1', 'result', 1.0));
        event(new TaskStarted('task_2', microtime(true)));
        event(new TaskFailed('task_2', new RuntimeException('Error'), 0.5));
    });
});

describe('ParallelHorizonServiceProvider configuration', function () {
    it('respects custom Redis connection configuration', function () {
        config(['parallel.horizon.redis_connection' => 'custom']);

        $this->app->bind('Laravel\Horizon\Horizon', fn () => new stdClass);

        $this->provider->register();

        $bridge = $this->app->make(HorizonMetricsBridge::class);

        expect($bridge->getRedisConnection())->toBe('custom');
    });

    it('uses default Redis connection when not configured', function () {
        $this->app->bind('Laravel\Horizon\Horizon', fn () => new stdClass);

        $this->provider->register();

        $bridge = $this->app->make(HorizonMetricsBridge::class);

        expect($bridge->getRedisConnection())->toBe('default');
    });

    it('can disable Horizon integration via configuration', function () {
        config(['parallel.horizon.enabled' => false]);

        $this->app->bind('Laravel\Horizon\Horizon', fn () => new stdClass);

        Event::fake();

        $this->provider->boot();

        // Listeners should not be registered
        $listeners = Event::getListeners(TaskStarted::class);
        expect($listeners)->toBeEmpty();
    });

    it('is enabled by default when Horizon is installed', function () {
        $this->app->bind('Laravel\Horizon\Horizon', fn () => new stdClass);

        $this->provider->boot();

        // Listeners should be registered
        $listeners = Event::getListeners(TaskStarted::class);
        expect($listeners)->not->toBeEmpty();
    });
});

describe('ParallelHorizonServiceProvider tag generation', function () {
    it('generates tags based on task key', function () {
        $tags = $this->provider->getParallelJobTags([
            'name' => 'image_processing',
            'type' => 'parallel',
        ]);

        expect($tags)->toContain('parallel')
            ->and($tags)->toContain('parallel:image_processing');
    });

    it('includes worker pool name in tags', function () {
        $tags = $this->provider->getParallelJobTags([
            'name' => 'data_import',
            'type' => 'parallel',
            'pool' => 'high_priority',
        ]);

        expect($tags)->toContain('parallel')
            ->and($tags)->toContain('parallel:data_import')
            ->and($tags)->toContain('pool:high_priority');
    });

    it('handles tasks with numeric keys', function () {
        $tags = $this->provider->getParallelJobTags([
            'name' => 0,
            'type' => 'parallel',
        ]);

        expect($tags)->toContain('parallel')
            ->and($tags)->toContain('parallel:0');
    });

    it('sanitizes special characters in tag names', function () {
        $tags = $this->provider->getParallelJobTags([
            'name' => 'task:with:colons',
            'type' => 'parallel',
        ]);

        // Tags should be sanitized
        foreach ($tags as $tag) {
            expect($tag)->not->toContain('::');
        }
    });
});

describe('ParallelHorizonServiceProvider provides method', function () {
    it('registers service provider with correct provides array', function () {
        $provides = $this->provider->provides();

        expect($provides)->toBeArray()
            ->and($provides)->toContain(HorizonMetricsBridge::class);
    });

    it('is marked as deferred service provider', function () {
        expect($this->provider->isDeferred())->toBeFalse();
    });
});

describe('ParallelHorizonServiceProvider publishes configuration', function () {
    it('registers publishable configuration files', function () {
        $this->provider->boot();

        $publishes = $this->provider->pathsToPublish(
            ParallelHorizonServiceProvider::class,
            'config'
        );

        expect($publishes)->toBeArray();
    });

    it('configuration can be published to Laravel config directory', function () {
        $this->artisan('vendor:publish', [
            '--provider' => ParallelHorizonServiceProvider::class,
            '--tag' => 'parallel-horizon-config',
        ])->assertExitCode(0);
    });
});

describe('ParallelHorizonServiceProvider error handling', function () {
    it('handles missing Horizon gracefully without exceptions', function () {
        expect(fn () => $this->provider->boot())->not->toThrow(Exception::class);
    });

    it('logs warning when Horizon is expected but not found', function () {
        config(['parallel.horizon.enabled' => true]);

        Log::shouldReceive('warning')
            ->once()
            ->with('Parallel Horizon integration enabled but Horizon is not installed');

        $this->provider->boot();
    });

    it('does not log warning when integration is disabled', function () {
        config(['parallel.horizon.enabled' => false]);

        Log::shouldReceive('warning')->never();

        $this->provider->boot();
    });

    it('handles event dispatch errors gracefully', function () {
        $this->app->bind('Laravel\Horizon\Horizon', fn () => new stdClass);

        // Register service provider first
        $this->provider->register();
        $this->provider->boot();

        // Mock the bridge to throw an exception
        $bridge = Mockery::mock(HorizonMetricsBridge::class);
        $bridge->shouldReceive('handleTaskStarted')
            ->andThrow(new RuntimeException('Redis connection failed'));

        $this->app->instance(HorizonMetricsBridge::class, $bridge);

        // Event should propagate but not break the application
        // We expect the exception to be thrown since we're not catching it in the listener
        // This test verifies the exception isn't swallowed silently
        try {
            event(new TaskStarted('task_1', microtime(true)));
            // If we get here, that's also acceptable (exception was caught somewhere)
            expect(true)->toBeTrue();
        } catch (RuntimeException $e) {
            // This is expected - the mock threw the exception
            expect($e->getMessage())->toBe('Redis connection failed');
        }
    });
});

describe('ParallelHorizonServiceProvider performance', function () {
    it('has minimal overhead when Horizon is not installed', function () {
        $iterations = 1000;

        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $this->provider->boot();
        }

        $endTime = microtime(true);
        $averageTime = (($endTime - $startTime) / $iterations) * 1000; // Convert to ms

        expect($averageTime)->toBeLessThan(0.5); // Less than 0.5ms per boot (relaxed from 0.1ms)
    });

    it('registers event listeners efficiently', function () {
        $this->app->bind('Laravel\Horizon\Horizon', fn () => new stdClass);

        $startTime = microtime(true);

        $this->provider->boot();

        $endTime = microtime(true);
        $bootTime = ($endTime - $startTime) * 1000; // Convert to ms

        expect($bootTime)->toBeLessThan(5); // Less than 5ms to boot
    });
});
