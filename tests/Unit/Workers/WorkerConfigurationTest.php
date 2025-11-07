<?php

declare(strict_types=1);

use LaravelParallel\Workers\WorkerConfiguration;

it('creates a configuration with all parameters', function () {
    $config = new WorkerConfiguration(
        workerCount: 4,
        maxWorkers: 128,
        timeout: 30.0,
    );

    expect($config->workerCount)->toBe(4)
        ->and($config->maxWorkers)->toBe(128)
        ->and($config->timeout)->toBe(30.0);
});

it('creates a configuration with minimal parameters', function () {
    $config = new WorkerConfiguration(workerCount: 2);

    expect($config->workerCount)->toBe(2)
        ->and($config->maxWorkers)->toBe(128)
        ->and($config->timeout)->toBeNull();
});

it('can change worker count immutably', function () {
    $original = new WorkerConfiguration(workerCount: 2);
    $modified = $original->withWorkerCount(4);

    expect($original->workerCount)->toBe(2)
        ->and($modified->workerCount)->toBe(4)
        ->and($modified->maxWorkers)->toBe($original->maxWorkers);
});

it('can change timeout immutably', function () {
    $original = new WorkerConfiguration(workerCount: 2, timeout: 10.0);
    $modified = $original->withTimeout(20.0);

    expect($original->timeout)->toBe(10.0)
        ->and($modified->timeout)->toBe(20.0)
        ->and($modified->workerCount)->toBe($original->workerCount);
});

it('can clear timeout immutably', function () {
    $original = new WorkerConfiguration(workerCount: 2, timeout: 10.0);
    $modified = $original->withTimeout(null);

    expect($original->timeout)->toBe(10.0)
        ->and($modified->timeout)->toBeNull();
});

it('creates configuration from Laravel config with explicit worker count', function () {
    config(['parallel.max_workers' => 64]);
    config(['parallel.timeout' => 15.0]);

    $config = WorkerConfiguration::fromConfig(8);

    expect($config->workerCount)->toBe(8)
        ->and($config->maxWorkers)->toBe(64)
        ->and($config->timeout)->toBe(15.0);
});

it('creates configuration from Laravel config with null worker count uses default', function () {
    config(['parallel.default_workers' => 4]);
    config(['parallel.max_workers' => 128]);

    $config = WorkerConfiguration::fromConfig(null);

    expect($config->workerCount)->toBe(4)
        ->and($config->maxWorkers)->toBe(128);
});

it('creates configuration from Laravel config with auto-detect when default_workers is null', function () {
    config(['parallel.default_workers' => null]);
    config(['parallel.max_workers' => 128]);

    $config = WorkerConfiguration::fromConfig(null);

    expect($config->workerCount)->toBe(0) // 0 indicates auto-detect
        ->and($config->maxWorkers)->toBe(128);
});

it('throws InvalidArgumentException when max_workers config is not integer', function () {
    config(['parallel.max_workers' => 'invalid']);

    expect(fn () => WorkerConfiguration::fromConfig(4))
        ->toThrow(InvalidArgumentException::class, 'Configuration "parallel.max_workers" must be an integer');
});

it('throws InvalidArgumentException when timeout config is not number', function () {
    config(['parallel.max_workers' => 128]);
    config(['parallel.timeout' => 'invalid']);

    expect(fn () => WorkerConfiguration::fromConfig(4))
        ->toThrow(InvalidArgumentException::class, 'Configuration "parallel.timeout" must be a number or null');
});

it('throws InvalidArgumentException when default_workers config is not integer', function () {
    config(['parallel.default_workers' => 'invalid']);
    config(['parallel.max_workers' => 128]);

    expect(fn () => WorkerConfiguration::fromConfig(null))
        ->toThrow(InvalidArgumentException::class, 'Configuration "parallel.default_workers" must be an integer');
});

it('handles integer timeout from config', function () {
    config(['parallel.max_workers' => 128]);
    config(['parallel.timeout' => 30]); // Integer instead of float

    $config = WorkerConfiguration::fromConfig(4);

    expect($config->timeout)->toBe(30.0); // Converted to float
});

it('handles null timeout from config', function () {
    config(['parallel.max_workers' => 128]);
    config(['parallel.timeout' => null]);

    $config = WorkerConfiguration::fromConfig(4);

    expect($config->timeout)->toBeNull();
});

it('preserves all properties when creating with methods', function () {
    $config = new WorkerConfiguration(
        workerCount: 4,
        maxWorkers: 64,
        timeout: 15.0
    );

    $withNewWorkers = $config->withWorkerCount(8);
    $withNewTimeout = $config->withTimeout(30.0);

    expect($withNewWorkers->maxWorkers)->toBe(64)
        ->and($withNewWorkers->timeout)->toBe(15.0)
        ->and($withNewTimeout->workerCount)->toBe(4)
        ->and($withNewTimeout->maxWorkers)->toBe(64);
});
