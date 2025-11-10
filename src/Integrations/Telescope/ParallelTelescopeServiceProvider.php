<?php

declare(strict_types=1);

namespace LaravelParallel\Integrations\Telescope;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Laravel\Telescope\Telescope;
use LaravelParallel\Integrations\Telescope\Watchers\ParallelTaskWatcher;

class ParallelTelescopeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishConfiguration();

        if (! $this->shouldRegisterTelescopeIntegration()) {
            return;
        }

        $this->registerWatcher();
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            ParallelTaskWatcher::class,
        ];
    }

    public function register(): void
    {
        if (! $this->telescopeIsInstalled()) {
            return;
        }

        // Register the watcher in container for dependency injection
        $this->app->singleton(ParallelTaskWatcher::class, function (): ParallelTaskWatcher {
            $options = config('parallel.telescope', []);

            return new ParallelTaskWatcher($options);
        });
    }

    public function telescopeIsInstalled(): bool
    {
        return class_exists(Telescope::class);
    }

    private function publishConfiguration(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../../config/parallel.php' => config_path('parallel.php'),
            ], 'parallel-telescope-config');
        }
    }

    private function registerWatcher(): void
    {
        if (! class_exists(Telescope::class)) {
            return;
        }

        $watcher = $this->app->make(ParallelTaskWatcher::class);

        $watcher->register($this->app);

        Telescope::$watchers[] = $watcher;
    }

    private function shouldRegisterTelescopeIntegration(): bool
    {
        $enabled = config('parallel.telescope.enabled', true);
        $recordTasks = config('parallel.telescope.record_tasks', true);
        if (! $enabled) {
            return false;
        }

        if (! $this->telescopeIsInstalled()) {
            Log::warning('Parallel Telescope integration enabled but Telescope is not installed');

            return false;
        }

        if (! $recordTasks) {
            return false;
        }

        return true;
    }
}
