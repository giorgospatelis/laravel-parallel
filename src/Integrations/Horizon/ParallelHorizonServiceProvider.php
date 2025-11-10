<?php

declare(strict_types=1);

namespace LaravelParallel\Integrations\Horizon;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use LaravelParallel\Integrations\Horizon\Listeners\ParallelHorizonEventSubscriber;

final class ParallelHorizonServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishConfiguration();

        if (! $this->shouldRegisterHorizonIntegration()) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../../../routes/horizon.php');
        $this->registerEventListeners();
        $this->registerHorizonTags();
    }

    /**
     * @param  array<string, mixed>  $job  Job data containing name, type, pool
     * @return array<int, string>
     */
    public function getParallelJobTags(array $job): array
    {
        $tags = [];

        if (isset($job['name'])) {
            $taskName = $this->sanitizeTagName((string) $job['name']);
            $tags[] = 'parallel:'.$taskName;
        }

        $tags[] = 'parallel';

        if (isset($job['pool'])) {
            $poolName = $this->sanitizeTagName((string) $job['pool']);
            $tags[] = 'pool:'.$poolName;
        }

        return $tags;
    }

    public function horizonIsInstalled(): bool
    {
        return $this->app->bound('Laravel\Horizon\Horizon');
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            HorizonMetricsBridge::class,
        ];
    }

    public function register(): void
    {
        if (! $this->horizonIsInstalled()) {
            return;
        }

        $this->registerHorizonMetricsBridge();
    }

    private function publishConfiguration(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../../config/parallel.php' => config_path('parallel.php'),
            ], 'parallel-horizon-config');
        }
    }

    private function registerEventListeners(): void
    {
        Event::subscribe(ParallelHorizonEventSubscriber::class);
    }

    private function registerHorizonMetricsBridge(): void
    {
        $this->app->singleton(HorizonMetricsBridge::class, function (Application $app): HorizonMetricsBridge {
            $redisConnection = config('parallel.horizon.redis_connection', 'default');

            return new HorizonMetricsBridge($redisConnection);
        });
    }

    private function registerHorizonTags(): void
    {
        if (! class_exists(\Laravel\Horizon\Horizon::class)) {
            return;
        }

        \Laravel\Horizon\Horizon::tag(function ($job) {
            // Check if this is a parallel job by looking for parallel-related data
            if (isset($job['tags']) && is_array($job['tags'])) {
                foreach ($job['tags'] as $tag) {
                    if (str_starts_with((string) $tag, 'parallel')) {
                        return $this->getParallelJobTags($job);
                    }
                }
            }

            return [];
        });
    }

    private function sanitizeTagName(string $name): string
    {
        $sanitized = preg_replace('/:+/', ':', $name);

        return preg_replace('/[^a-zA-Z0-9:_-]/', '', $sanitized ?? $name) ?? $name;
    }

    private function shouldRegisterHorizonIntegration(): bool
    {
        $enabled = config('parallel.horizon.enabled', true);

        if ($enabled && ! $this->horizonIsInstalled()) {
            Log::warning('Parallel Horizon integration enabled but Horizon is not installed');

            return false;
        }

        return $enabled && $this->horizonIsInstalled();
    }
}
