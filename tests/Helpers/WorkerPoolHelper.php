<?php

declare(strict_types=1);

namespace LaravelParallel\Tests\Helpers;

use Amp\Parallel\Worker\WorkerPool;
use LaravelParallel\Tests\Mocks\SyncWorkerPool;

/**
 * Helper functions for creating worker pools in tests.
 *
 * This class provides utilities for creating mock worker pools that execute
 * synchronously, avoiding the need to spawn real worker processes during testing.
 * This is essential for compatibility with code coverage tools like PCOV.
 */
final class WorkerPoolHelper
{
    /**
     * Create a synchronous mock worker pool for testing.
     *
     * This pool executes tasks synchronously in the main process rather than
     * spawning worker processes, making it safe to use with code coverage tools.
     *
     * @param  int  $workerCount  The number of workers to simulate
     * @return WorkerPool A WorkerPool that executes tasks synchronously
     */
    public static function createMockWorkerPool(int $workerCount = 4): WorkerPool
    {
        return new SyncWorkerPool($workerCount);
    }

    /**
     * Create a worker pool, using mocks if coverage is enabled.
     *
     * This function automatically decides whether to create a real pool or a mock
     * based on the current environment. This is useful for tests that need to
     * verify factory behavior while still being coverage-safe.
     *
     * @param  int  $workerCount  The number of workers
     */
    public static function createWorkerPool(int $workerCount = 4): WorkerPool
    {
        if (self::shouldUseMockPools()) {
            return self::createMockWorkerPool($workerCount);
        }

        // If not using mocks, create a real pool (not recommended during coverage)
        return new \Amp\Parallel\Worker\ContextWorkerPool(
            $workerCount,
            new \Amp\Parallel\Worker\ContextWorkerFactory
        );
    }

    /**
     * Determine if mock pools should be used instead of real pools.
     *
     * This checks if code coverage is being collected or if we're in a test
     * environment where mock pools are preferred.
     *
     * @return bool True if mock pools should be used
     */
    public static function shouldUseMockPools(): bool
    {
        // Check if PCOV is loaded and enabled
        if (extension_loaded('pcov') && ini_get('pcov.enabled')) {
            return true;
        }

        // Check if Xdebug coverage is enabled
        if (extension_loaded('xdebug') && function_exists('xdebug_info')) {
            $xdebugInfo = xdebug_info();
            if (isset($xdebugInfo['mode']) && str_contains($xdebugInfo['mode'], 'coverage')) {
                return true;
            }
        }

        // Check for common coverage environment variables
        if (getenv('COVERAGE') !== false || getenv('XDEBUG_MODE') === 'coverage') {
            return true;
        }

        // Default to using mocks in test environment for safety and speed
        return true;
    }
}
