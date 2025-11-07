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
