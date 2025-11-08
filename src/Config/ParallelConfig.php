<?php

declare(strict_types=1);

namespace LaravelParallel\Config;

use InvalidArgumentException;

/**
 * Type-safe configuration value object for parallel processing.
 *
 * This class provides type-safe access to package configuration values,
 * ensuring all configuration is properly typed and validated.
 */
final readonly class ParallelConfig
{
    public function __construct(
        public ?int $defaultWorkers = null,
        public ?float $defaultTimeout = 30.0,
        public int $maxWorkers = 128,
    ) {
    }

    /**
     * Create configuration from Laravel config array.
     *
     * @throws InvalidArgumentException If configuration values are invalid
     */
    public static function fromConfig(): self
    {
        $defaultWorkers = config('parallel.default_workers');
        if ($defaultWorkers !== null && ! is_int($defaultWorkers)) {
            throw new InvalidArgumentException(
                'Configuration "parallel.default_workers" must be an integer or null, got: '.gettype($defaultWorkers)
            );
        }

        $defaultTimeout = config('parallel.default_timeout');
        if ($defaultTimeout !== null && ! is_float($defaultTimeout) && ! is_int($defaultTimeout)) {
            throw new InvalidArgumentException(
                'Configuration "parallel.default_timeout" must be a number or null, got: '.gettype($defaultTimeout)
            );
        }

        $maxWorkers = config('parallel.max_workers', 128);
        if (! is_int($maxWorkers)) {
            throw new InvalidArgumentException(
                'Configuration "parallel.max_workers" must be an integer, got: '.gettype($maxWorkers)
            );
        }

        return new self(
            defaultWorkers: $defaultWorkers,
            defaultTimeout: $defaultTimeout !== null ? (float) $defaultTimeout : null,
            maxWorkers: $maxWorkers,
        );
    }

    /**
     * Convert configuration to array.
     *
     * @return array<string, int|float|null>
     */
    public function toArray(): array
    {
        return [
            'default_workers' => $this->defaultWorkers,
            'default_timeout' => $this->defaultTimeout,
            'max_workers' => $this->maxWorkers,
        ];
    }
}
