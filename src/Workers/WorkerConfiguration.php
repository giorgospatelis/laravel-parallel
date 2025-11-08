<?php

declare(strict_types=1);

namespace LaravelParallel\Workers;

use InvalidArgumentException;

/**
 * Immutable configuration for worker pools.
 *
 * This value object encapsulates all configuration options for creating
 * and managing worker pools in parallel processing.
 */
final readonly class WorkerConfiguration
{
    /**
     * Create a new WorkerConfiguration instance.
     *
     * @param  int  $workerCount  Number of worker processes to create
     * @param  int  $maxWorkers  Maximum allowed workers (for validation)
     * @param  float|null  $timeout  Optional timeout for task execution in seconds
     */
    public function __construct(
        public int $workerCount,
        public int $maxWorkers = 128,
        public ?float $timeout = null,
    ) {
    }

    /**
     * Create a configuration from application config values.
     *
     * @param  int|null  $workerCount  Override worker count, or null to use config/auto-detect
     *
     * @throws InvalidArgumentException If configuration values are invalid
     */
    public static function fromConfig(?int $workerCount = null): self
    {
        $maxWorkers = config('parallel.max_workers', 128);
        if (! is_int($maxWorkers)) {
            throw new InvalidArgumentException(
                'Configuration "parallel.max_workers" must be an integer, got: '.gettype($maxWorkers)
            );
        }

        $timeout = config('parallel.timeout');
        if ($timeout !== null && ! is_float($timeout) && ! is_int($timeout)) {
            throw new InvalidArgumentException(
                'Configuration "parallel.timeout" must be a number or null, got: '.gettype($timeout)
            );
        }

        return new self(
            workerCount: $workerCount ?? self::getDefaultWorkerCount(),
            maxWorkers: $maxWorkers,
            timeout: $timeout !== null ? (float) $timeout : null,
        );
    }

    /**
     * Create a new configuration with a different timeout.
     */
    public function withTimeout(?float $timeout): self
    {
        return new self(
            workerCount: $this->workerCount,
            maxWorkers: $this->maxWorkers,
            timeout: $timeout,
        );
    }

    /**
     * Create a new configuration with a different worker count.
     */
    public function withWorkerCount(int $count): self
    {
        return new self(
            workerCount: $count,
            maxWorkers: $this->maxWorkers,
            timeout: $this->timeout,
        );
    }

    /**
     * Get the default worker count from configuration.
     *
     * @throws InvalidArgumentException If configured value is invalid
     */
    private static function getDefaultWorkerCount(): int
    {
        $configured = config('parallel.default_workers');

        if ($configured !== null) {
            if (! is_int($configured)) {
                throw new InvalidArgumentException(
                    'Configuration "parallel.default_workers" must be an integer, got: '.gettype($configured)
                );
            }

            return $configured;
        }

        // Will use CPU detection via factory
        return 0; // 0 indicates auto-detect
    }
}
