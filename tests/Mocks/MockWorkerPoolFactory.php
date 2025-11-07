<?php

declare(strict_types=1);

namespace LaravelParallel\Tests\Mocks;

use Amp\Parallel\Worker\WorkerPool;
use LaravelParallel\Exceptions\ParallelException;
use LaravelParallel\Support\CpuDetector;
use LaravelParallel\Workers\WorkerConfiguration;
use LaravelParallel\Workers\WorkerPoolFactory;
use Throwable;

/**
 * Mock Worker Pool Factory for testing.
 *
 * This factory extends the real WorkerPoolFactory but overrides the create()
 * method to return synchronous mock worker pools instead of real AMPHP worker
 * pools, making tests compatible with code coverage tools.
 */
final class MockWorkerPoolFactory extends WorkerPoolFactory
{
    /**
     * Create a synchronous mock worker pool from configuration.
     *
     * Overrides the parent method to return a SyncWorkerPool instead of
     * a real ContextWorkerPool.
     *
     * @throws ParallelException If pool creation fails
     */
    public function create(WorkerConfiguration $config): WorkerPool
    {
        try {
            // Validate worker count is not negative before resolution
            // 0 is allowed (means auto-detect), but negative values are invalid
            if ($config->workerCount < 0) {
                throw new ParallelException("Worker count must be 0 or positive, got: {$config->workerCount}");
            }

            $workerCount = $this->resolveWorkerCount($config);

            return new SyncWorkerPool($workerCount);
        } catch (ParallelException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw ParallelException::workerPoolCreationFailed($e);
        }
    }

}
