<?php

namespace LaravelParallel\Tests;

use Amp\Parallel\Worker\WorkerPool;
use LaravelParallel\Tests\Helpers\WorkerPoolHelper;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            \LaravelParallel\ParallelServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Parallel' => \LaravelParallel\Facades\Parallel::class,
        ];
    }

    /**
     * Create a test-friendly worker pool.
     *
     * This method creates a synchronous mock worker pool that executes tasks
     * in the main process, avoiding issues with code coverage collection.
     *
     * @param int $workerCount The number of workers to simulate
     * @return WorkerPool
     */
    protected function createTestWorkerPool(int $workerCount = 4): WorkerPool
    {
        return WorkerPoolHelper::createMockWorkerPool($workerCount);
    }

    /**
     * Determine if mock pools should be used in the current test environment.
     *
     * @return bool
     */
    protected function shouldUseMockPools(): bool
    {
        return WorkerPoolHelper::shouldUseMockPools();
    }
}
