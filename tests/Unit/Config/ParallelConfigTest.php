<?php

declare(strict_types=1);

use LaravelParallel\Config\ParallelConfig;

describe('ParallelConfig', function () {
    it('can be instantiated with default values', function () {
        $config = new ParallelConfig();

        expect($config->defaultWorkers)->toBeNull()
            ->and($config->defaultTimeout)->toBe(30.0)
            ->and($config->maxWorkers)->toBe(128);
    });

    it('can be instantiated with custom values', function () {
        $config = new ParallelConfig(
            defaultWorkers: 4,
            defaultTimeout: 60.0,
            maxWorkers: 256
        );

        expect($config->defaultWorkers)->toBe(4)
            ->and($config->defaultTimeout)->toBe(60.0)
            ->and($config->maxWorkers)->toBe(256);
    });

    it('is readonly', function () {
        $config = new ParallelConfig();

        $config->defaultWorkers = 10;
    })->throws(Error::class);

    it('converts to array correctly', function () {
        $config = new ParallelConfig(
            defaultWorkers: 8,
            defaultTimeout: 45.0,
            maxWorkers: 64
        );

        $array = $config->toArray();

        expect($array)->toBe([
            'default_workers' => 8,
            'default_timeout' => 45.0,
            'max_workers' => 64,
        ]);
    });

    it('converts to array with null values', function () {
        $config = new ParallelConfig(
            defaultWorkers: null,
            defaultTimeout: null,
            maxWorkers: 100
        );

        $array = $config->toArray();

        expect($array)->toBe([
            'default_workers' => null,
            'default_timeout' => null,
            'max_workers' => 100,
        ]);
    });
});

describe('ParallelConfig::fromConfig()', function () {
    it('creates instance from Laravel config with valid values', function () {
        config(['parallel.default_workers' => 6]);
        config(['parallel.default_timeout' => 50]);
        config(['parallel.max_workers' => 200]);

        $config = ParallelConfig::fromConfig();

        expect($config->defaultWorkers)->toBe(6)
            ->and($config->defaultTimeout)->toBe(50.0)
            ->and($config->maxWorkers)->toBe(200);
    });

    it('creates instance with null default workers', function () {
        config(['parallel.default_workers' => null]);
        config(['parallel.default_timeout' => 30]);
        config(['parallel.max_workers' => 128]);

        $config = ParallelConfig::fromConfig();

        expect($config->defaultWorkers)->toBeNull()
            ->and($config->defaultTimeout)->toBe(30.0)
            ->and($config->maxWorkers)->toBe(128);
    });

    it('creates instance with null default timeout', function () {
        config(['parallel.default_workers' => 4]);
        config(['parallel.default_timeout' => null]);
        config(['parallel.max_workers' => 128]);

        $config = ParallelConfig::fromConfig();

        expect($config->defaultWorkers)->toBe(4)
            ->and($config->defaultTimeout)->toBeNull()
            ->and($config->maxWorkers)->toBe(128);
    });

    it('converts integer timeout to float', function () {
        config(['parallel.default_workers' => 4]);
        config(['parallel.default_timeout' => 60]);
        config(['parallel.max_workers' => 128]);

        $config = ParallelConfig::fromConfig();

        expect($config->defaultTimeout)->toBe(60.0)
            ->and($config->defaultTimeout)->toBeFloat();
    });

    it('uses default value for max_workers when not set', function () {
        config(['parallel.default_workers' => 4]);
        config(['parallel.default_timeout' => 30]);

        $config = ParallelConfig::fromConfig();

        expect($config->maxWorkers)->toBe(128);
    });

    it('throws exception for invalid default_workers type', function () {
        config(['parallel.default_workers' => 'invalid']);
        config(['parallel.default_timeout' => 30]);
        config(['parallel.max_workers' => 128]);

        ParallelConfig::fromConfig();
    })->throws(
        InvalidArgumentException::class,
        'Configuration "parallel.default_workers" must be an integer or null, got: string'
    );

    it('throws exception for invalid default_timeout type', function () {
        config(['parallel.default_workers' => 4]);
        config(['parallel.default_timeout' => 'invalid']);
        config(['parallel.max_workers' => 128]);

        ParallelConfig::fromConfig();
    })->throws(
        InvalidArgumentException::class,
        'Configuration "parallel.default_timeout" must be a number or null, got: string'
    );

    it('throws exception for invalid max_workers type', function () {
        config(['parallel.default_workers' => 4]);
        config(['parallel.default_timeout' => 30]);
        config(['parallel.max_workers' => 'invalid']);

        ParallelConfig::fromConfig();
    })->throws(
        InvalidArgumentException::class,
        'Configuration "parallel.max_workers" must be an integer, got: string'
    );

    it('accepts boolean false for default_workers', function () {
        config(['parallel.default_workers' => false]);
        config(['parallel.default_timeout' => 30]);
        config(['parallel.max_workers' => 128]);

        expect(fn () => ParallelConfig::fromConfig())
            ->toThrow(InvalidArgumentException::class);
    });

    it('accepts array for default_timeout', function () {
        config(['parallel.default_workers' => 4]);
        config(['parallel.default_timeout' => []]);
        config(['parallel.max_workers' => 128]);

        expect(fn () => ParallelConfig::fromConfig())
            ->toThrow(InvalidArgumentException::class);
    });

    it('accepts object for max_workers', function () {
        config(['parallel.default_workers' => 4]);
        config(['parallel.default_timeout' => 30]);
        config(['parallel.max_workers' => new stdClass()]);

        expect(fn () => ParallelConfig::fromConfig())
            ->toThrow(InvalidArgumentException::class);
    });
});
