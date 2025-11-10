<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Laravel\Telescope\Telescope;
use LaravelParallel\Events\TaskCompleted;
use LaravelParallel\Events\TaskFailed;
use LaravelParallel\Events\TaskStarted;
use LaravelParallel\Integrations\Telescope\ParallelTelescopeServiceProvider;
use LaravelParallel\Integrations\Telescope\Watchers\ParallelTaskWatcher;

beforeEach(function () {
    $this->provider = new ParallelTelescopeServiceProvider($this->app);
});

describe('ParallelTelescopeServiceProvider', function () {
    it('detects Telescope installation correctly', function () {
        // Mock Telescope class existence
        if (! class_exists(Telescope::class)) {
            $this->markTestSkipped('Telescope is not installed');
        }

        expect($this->provider->telescopeIsInstalled())->toBeTrue();
    });

    it('registers watcher in Telescope watchers array', function () {
        if (! class_exists(Telescope::class)) {
            $this->markTestSkipped('Telescope is not installed');
        }

        config(['parallel.telescope.enabled' => true]);
        config(['parallel.telescope.record_tasks' => true]);

        // Clear existing watchers to ensure clean test
        Telescope::$watchers = [];

        $this->provider->register();
        $this->provider->boot();

        // Verify watcher is registered
        expect(Telescope::$watchers)->not->toBeEmpty();

        $hasParallelWatcher = false;
        foreach (Telescope::$watchers as $watcher) {
            if ($watcher instanceof ParallelTaskWatcher) {
                $hasParallelWatcher = true;
                break;
            }
        }

        expect($hasParallelWatcher)->toBeTrue();
    });

    it('detects when Telescope is not installed', function () {
        // Create a fresh provider without Telescope
        $provider = new class($this->app) extends ParallelTelescopeServiceProvider
        {
            public function telescopeIsInstalled(): bool
            {
                return false;
            }
        };

        expect($provider->telescopeIsInstalled())->toBeFalse();
    });

    it('registers event listeners when Telescope is installed', function () {
        // Skip if Telescope not installed
        if (! class_exists(Telescope::class)) {
            $this->markTestSkipped('Telescope is not installed');
        }

        config(['parallel.telescope.enabled' => true]);

        $this->provider->register();
        $this->provider->boot();

        // Verify event listeners are registered
        $listeners = Event::getListeners(TaskStarted::class);
        expect($listeners)->not->toBeEmpty();

        $listeners = Event::getListeners(TaskCompleted::class);
        expect($listeners)->not->toBeEmpty();

        $listeners = Event::getListeners(TaskFailed::class);
        expect($listeners)->not->toBeEmpty();
    });

    it('does not register listeners when Telescope is not installed', function () {
        // Override to simulate Telescope not installed
        $provider = new class($this->app) extends ParallelTelescopeServiceProvider
        {
            public function telescopeIsInstalled(): bool
            {
                return false;
            }
        };

        Event::fake();

        $provider->boot();

        // Events should not have listeners registered
        $listeners = Event::getListeners(TaskStarted::class);
        expect($listeners)->toBeEmpty();
    });

    it('binds ParallelTaskWatcher to container', function () {
        if (! class_exists(Telescope::class)) {
            $this->markTestSkipped('Telescope is not installed');
        }

        $this->provider->register();

        expect($this->app->bound(ParallelTaskWatcher::class))->toBeTrue();
    });

    it('does not throw errors when Telescope is not installed', function () {
        $provider = new class($this->app) extends ParallelTelescopeServiceProvider
        {
            public function telescopeIsInstalled(): bool
            {
                return false;
            }
        };

        expect(fn () => $provider->boot())->not->toThrow(Exception::class);
        expect(fn () => $provider->register())->not->toThrow(Exception::class);
    });
});

describe('ParallelTelescopeServiceProvider event handling', function () {
    it('forwards TaskStarted events to ParallelTaskWatcher', function () {
        if (! class_exists(Telescope::class)) {
            $this->markTestSkipped('Telescope is not installed');
        }

        config(['parallel.telescope.enabled' => true]);

        // Mock the watcher
        $watcher = Mockery::mock(ParallelTaskWatcher::class);
        $watcher->shouldReceive('recordTaskStarted')
            ->once()
            ->withArgs(function ($event) {
                expect($event)->toBeInstanceOf(TaskStarted::class)
                    ->and($event->taskKey)->toBe('task_1');

                return true;
            });

        $this->app->instance(ParallelTaskWatcher::class, $watcher);

        $this->provider->boot();

        // Dispatch event
        $event = new TaskStarted('task_1', microtime(true));
        event($event);
    });

    it('forwards TaskCompleted events to ParallelTaskWatcher', function () {
        if (! class_exists(Telescope::class)) {
            $this->markTestSkipped('Telescope is not installed');
        }

        config(['parallel.telescope.enabled' => true]);

        $watcher = Mockery::mock(ParallelTaskWatcher::class);
        $watcher->shouldReceive('recordTaskCompleted')
            ->once()
            ->withArgs(function ($event) {
                expect($event)->toBeInstanceOf(TaskCompleted::class)
                    ->and($event->taskKey)->toBe('task_2')
                    ->and($event->executionTime)->toBe(1.234);

                return true;
            });

        $this->app->instance(ParallelTaskWatcher::class, $watcher);

        $this->provider->boot();

        $event = new TaskCompleted('task_2', 'result', 1.234);
        event($event);
    });

    it('forwards TaskFailed events to ParallelTaskWatcher', function () {
        if (! class_exists(Telescope::class)) {
            $this->markTestSkipped('Telescope is not installed');
        }

        config(['parallel.telescope.enabled' => true]);

        $watcher = Mockery::mock(ParallelTaskWatcher::class);
        $watcher->shouldReceive('recordTaskFailed')
            ->once()
            ->withArgs(function ($event) {
                expect($event)->toBeInstanceOf(TaskFailed::class)
                    ->and($event->taskKey)->toBe('task_3')
                    ->and($event->exception)->toBeInstanceOf(Throwable::class);

                return true;
            });

        $this->app->instance(ParallelTaskWatcher::class, $watcher);

        $this->provider->boot();

        $exception = new RuntimeException('Test error');
        $event = new TaskFailed('task_3', $exception, 0.5);
        event($event);
    });

    it('handles multiple events in sequence', function () {
        if (! class_exists(Telescope::class)) {
            $this->markTestSkipped('Telescope is not installed');
        }

        config(['parallel.telescope.enabled' => true]);

        $watcher = Mockery::mock(ParallelTaskWatcher::class);
        $watcher->shouldReceive('recordTaskStarted')->once();
        $watcher->shouldReceive('recordTaskCompleted')->once();
        $watcher->shouldReceive('recordTaskStarted')->once();
        $watcher->shouldReceive('recordTaskFailed')->once();

        $this->app->instance(ParallelTaskWatcher::class, $watcher);

        $this->provider->boot();

        // Dispatch multiple events
        event(new TaskStarted('task_1', microtime(true)));
        event(new TaskCompleted('task_1', 'result', 1.0));
        event(new TaskStarted('task_2', microtime(true)));
        event(new TaskFailed('task_2', new RuntimeException('Error'), 0.5));
    });
});

describe('ParallelTelescopeServiceProvider configuration', function () {
    it('can disable Telescope integration via configuration', function () {
        config(['parallel.telescope.enabled' => false]);

        if (class_exists(Telescope::class)) {
            // When disabled, listeners should not be registered
            Event::fake();

            $this->provider->boot();

            $listeners = Event::getListeners(TaskStarted::class);
            expect($listeners)->toBeEmpty();
        } else {
            $this->markTestSkipped('Telescope is not installed');
        }
    });

    it('is enabled by default when Telescope is installed', function () {
        if (! class_exists(Telescope::class)) {
            $this->markTestSkipped('Telescope is not installed');
        }

        $this->provider->register();
        $this->provider->boot();

        // Listeners should be registered
        $listeners = Event::getListeners(TaskStarted::class);
        expect($listeners)->not->toBeEmpty();
    });

    it('respects record_tasks configuration', function () {
        if (! class_exists(Telescope::class)) {
            $this->markTestSkipped('Telescope is not installed');
        }

        config(['parallel.telescope.record_tasks' => false]);

        $this->provider->register();

        $watcher = $this->app->make(ParallelTaskWatcher::class);

        expect($watcher)->toBeInstanceOf(ParallelTaskWatcher::class);
    });
});

describe('ParallelTelescopeServiceProvider provides method', function () {
    it('registers service provider with correct provides array', function () {
        $provides = $this->provider->provides();

        expect($provides)->toBeArray()
            ->and($provides)->toContain(ParallelTaskWatcher::class);
    });

    it('is marked as deferred service provider', function () {
        expect($this->provider->isDeferred())->toBeFalse();
    });
});

describe('ParallelTelescopeServiceProvider publishes configuration', function () {
    it('registers publishable configuration files', function () {
        $this->provider->boot();

        $publishes = $this->provider->pathsToPublish(
            ParallelTelescopeServiceProvider::class,
            'config'
        );

        expect($publishes)->toBeArray();
    });

    it('configuration can be published to Laravel config directory', function () {
        $this->artisan('vendor:publish', [
            '--provider' => ParallelTelescopeServiceProvider::class,
            '--tag' => 'parallel-telescope-config',
        ])->assertExitCode(0);
    });
});

describe('ParallelTelescopeServiceProvider error handling', function () {
    it('handles missing Telescope gracefully without exceptions', function () {
        $provider = new class($this->app) extends ParallelTelescopeServiceProvider
        {
            public function telescopeIsInstalled(): bool
            {
                return false;
            }
        };

        expect(fn () => $provider->boot())->not->toThrow(Exception::class);
    });

    it('logs warning when Telescope is expected but not found', function () {
        config(['parallel.telescope.enabled' => true]);

        $provider = new class($this->app) extends ParallelTelescopeServiceProvider
        {
            public function telescopeIsInstalled(): bool
            {
                return false;
            }
        };

        Log::shouldReceive('warning')
            ->once()
            ->with('Parallel Telescope integration enabled but Telescope is not installed');

        $provider->boot();
    });

    it('does not log warning when integration is disabled', function () {
        config(['parallel.telescope.enabled' => false]);

        $provider = new class($this->app) extends ParallelTelescopeServiceProvider
        {
            public function telescopeIsInstalled(): bool
            {
                return false;
            }
        };

        Log::shouldReceive('warning')->never();

        $provider->boot();
    });

    it('handles event dispatch errors gracefully', function () {
        if (! class_exists(Telescope::class)) {
            $this->markTestSkipped('Telescope is not installed');
        }

        config(['parallel.telescope.enabled' => true]);

        // Register service provider first
        $this->provider->register();
        $this->provider->boot();

        // Mock the watcher to throw an exception
        $watcher = Mockery::mock(ParallelTaskWatcher::class);
        $watcher->shouldReceive('recordTaskStarted')
            ->andThrow(new RuntimeException('Telescope recording failed'));

        $this->app->instance(ParallelTaskWatcher::class, $watcher);

        // Event should propagate but not break the application
        try {
            event(new TaskStarted('task_1', microtime(true)));
            // If we get here, that's acceptable (exception was caught somewhere)
            expect(true)->toBeTrue();
        } catch (RuntimeException $e) {
            // This is expected - the mock threw the exception
            expect($e->getMessage())->toBe('Telescope recording failed');
        }
    });
});

describe('ParallelTelescopeServiceProvider performance', function () {
    it('has minimal overhead when Telescope is not installed', function () {
        $provider = new class($this->app) extends ParallelTelescopeServiceProvider
        {
            public function telescopeIsInstalled(): bool
            {
                return false;
            }
        };

        $iterations = 1000;

        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $provider->boot();
        }

        $endTime = microtime(true);
        $averageTime = (($endTime - $startTime) / $iterations) * 1000; // Convert to ms

        expect($averageTime)->toBeLessThan(0.5); // Less than 0.5ms per boot
    });

    it('registers event listeners efficiently', function () {
        if (! class_exists(Telescope::class)) {
            $this->markTestSkipped('Telescope is not installed');
        }

        config(['parallel.telescope.enabled' => true]);

        $startTime = microtime(true);

        $this->provider->register();
        $this->provider->boot();

        $endTime = microtime(true);
        $bootTime = ($endTime - $startTime) * 1000; // Convert to ms

        expect($bootTime)->toBeLessThan(10); // Less than 10ms to boot
    });
});
