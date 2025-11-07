<?php

declare(strict_types=1);

namespace LaravelParallel;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use LaravelParallel\Contracts\ExecutorContract;
use LaravelParallel\Core\Executor;
use LaravelParallel\Core\ParallelManager;
use LaravelParallel\Core\ResultCollector;
use LaravelParallel\Support\CpuDetector;
use LaravelParallel\Support\TaskValidator;
use LaravelParallel\Workers\WorkerPoolFactory;

final class ParallelServiceProvider extends BaseServiceProvider
{
    /**
     * Bootstrap package services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/parallel.php' => config_path('parallel.php'),
            ], 'parallel-config');

            $this->commands([
                Console\ParallelTestCommand::class,
            ]);
        }
    }

    /**
     * Register package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/parallel.php', 'parallel');

        // Register support services
        $this->app->singleton(CpuDetector::class);
        $this->app->singleton(TaskValidator::class);
        $this->app->singleton(ResultCollector::class);

        // Register worker pool factory
        $this->app->singleton(WorkerPoolFactory::class, function ($app) {
            return new WorkerPoolFactory(
                $app->make(CpuDetector::class)
            );
        });

        // Register executor (non-singleton to prevent state leakage in Octane/Swoole)
        $this->app->bind(ExecutorContract::class, Executor::class);
        $this->app->bind(Executor::class, function ($app) {
            return new Executor(
                $app->make(WorkerPoolFactory::class),
                $app->make(ResultCollector::class),
                $app->make(TaskValidator::class)
            );
        });

        // Register parallel manager (non-singleton to prevent state leakage in Octane/Swoole)
        $this->app->bind('parallel', function ($app) {
            return new ParallelManager(
                $app->make(ExecutorContract::class),
                $app->make(TaskValidator::class)
            );
        });

        $this->app->alias('parallel', ParallelManager::class);
    }
}
