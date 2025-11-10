<?php

declare(strict_types=1);

/**
 * PHPBench Bootstrap for Laravel Parallel Package.
 *
 * This file bootstraps a minimal Laravel application for benchmarking.
 * It cannot use Orchestra TestCase because PHPBench serializes benchmark
 * classes in worker processes, and TestCase has non-serializable dependencies.
 *
 * Instead, we manually create and boot a Laravel application with our
 * package's service provider registered.
 */

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Events\EventServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use LaravelParallel\ParallelServiceProvider;

// Load Composer autoloader
require_once __DIR__.'/../vendor/autoload.php';

// Create a minimal Laravel application for benchmarking
$app = new Application(__DIR__.'/..');

// Bind the application instance to the container
$app->instance('app', $app);
$app->instance(Container::class, $app);

// Register base paths
$app->useAppPath($app->basePath('app'));
$app->useStoragePath($app->basePath('storage'));
$app->instance('path.config', $app->basePath('config'));

// Register configuration repository manually (before we can load config files)
$app->singleton('config', function () {
    return new ConfigRepository([]);
});

// Load package configuration with defaults
$app['config']->set('parallel', require __DIR__.'/../config/parallel.php');

// Set benchmark-specific configuration to minimize overhead
$app['config']->set('parallel.events.enabled', false);
$app['config']->set('parallel.logging.enabled', false);
$app['config']->set('parallel.horizon.enabled', false);

// Ensure real worker pools are used (not mocks)
putenv('PARALLEL_USE_MOCK_POOLS=false');

// Register core service providers
$app->register(EventServiceProvider::class);

// Register our package service provider
$app->register(ParallelServiceProvider::class);

// Set the application instance for Facades
Facade::setFacadeApplication($app);

// Boot the application
$app->boot();

// Store the application instance globally for benchmarks to access
$GLOBALS['__laravel_app'] = $app;

return $app;
