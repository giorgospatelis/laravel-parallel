<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use LaravelParallel\Integrations\Horizon\Controllers\ParallelMetricsController;

/*
|--------------------------------------------------------------------------
| Laravel Parallel - Horizon API Routes
|--------------------------------------------------------------------------
|
| These routes provide HTTP API access to parallel job metrics recorded
| by the Horizon integration. They follow Horizon's API conventions and
| return data in Horizon-compatible JSON format.
|
| All routes are prefixed with: /horizon/api/parallel
|
*/

Route::prefix('horizon/api/parallel')
    ->middleware(config('horizon.middleware', ['web']))
    ->group(function () {
        // Overall statistics - jobs/minute, failures, processes, etc.
        Route::get('/stats', [ParallelMetricsController::class, 'stats'])
            ->name('parallel.horizon.stats');

        // Task workload breakdown with success rates and wait times
        Route::get('/workload', [ParallelMetricsController::class, 'workload'])
            ->name('parallel.horizon.workload');

        // Recent jobs list with pagination support
        Route::get('/jobs/recent', [ParallelMetricsController::class, 'recentJobs'])
            ->name('parallel.horizon.jobs.recent');

        // Active worker processes information
        Route::get('/workers', [ParallelMetricsController::class, 'workers'])
            ->name('parallel.horizon.workers');
    });
