<?php

declare(strict_types=1);

namespace LaravelParallel\Workers;

use Amp\Parallel\Worker\ContextWorkerFactory;
use Amp\Parallel\Worker\ContextWorkerPool;
use Amp\Parallel\Worker\WorkerPool;
use LaravelParallel\Exceptions\ParallelException;
use LaravelParallel\Support\CpuDetector;
use Throwable;

/**
 * Factory for creating worker pools.
 *
 * This class handles the creation of worker pools with proper configuration,
 * including automatic CPU detection when needed.
 *
 * Note: This class is not final to allow test mocking via inheritance.
 */
class WorkerPoolFactory
{
    public function __construct(
        private readonly CpuDetector $cpuDetector,
    ) {}

    /**
     * Create a worker pool from configuration.
     *
     * @throws ParallelException If pool creation fails
     */
    public function create(WorkerConfiguration $config): WorkerPool
    {
        try {
            $workerCount = $this->resolveWorkerCount($config);

            // Create a dedicated worker pool with the configured worker count
            // This ensures the configured parallelism level is actually used
            return new ContextWorkerPool(
                $workerCount,
                new ContextWorkerFactory
            );
        } catch (ParallelException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw ParallelException::workerPoolCreationFailed($e);
        }
    }

    /**
     * Resolve the actual worker count to use.
     *
     * If the configuration specifies 0 workers, auto-detect using CPU count.
     *
     * @throws ParallelException If CPU detection fails when needed
     */
    protected function resolveWorkerCount(WorkerConfiguration $config): int
    {
        if ($config->workerCount > 0) {
            return $config->workerCount;
        }

        return $this->cpuDetector->detect();
    }
}
