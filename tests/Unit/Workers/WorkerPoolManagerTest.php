<?php

declare(strict_types=1);

use LaravelParallel\Support\CpuDetector;
use LaravelParallel\Workers\WorkerConfiguration;
use LaravelParallel\Workers\WorkerPoolFactory;
use LaravelParallel\Workers\WorkerPoolManager;

beforeEach(function () {
    $cpuDetector = new CpuDetector();
    $factory = new WorkerPoolFactory($cpuDetector);
    $config = new WorkerConfiguration(workerCount: 4);
    $pool = $factory->create($config);

    $this->manager = new WorkerPoolManager($pool, 4);
});

it('reports the correct worker count', function () {
    expect($this->manager->getWorkerCount())->toBe(4);
});

it('is initially running', function () {
    expect($this->manager->isRunning())->toBeTrue();
});

it('can be shutdown', function () {
    expect($this->manager->isRunning())->toBeTrue();

    $this->manager->shutdown();

    expect($this->manager->isRunning())->toBeFalse();
});

it('can safely shutdown multiple times', function () {
    $this->manager->shutdown();
    $this->manager->shutdown(); // Should not error

    expect($this->manager->isRunning())->toBeFalse();
});

it('implements WorkerPoolContract', function () {
    expect($this->manager)->toBeInstanceOf(LaravelParallel\Contracts\WorkerPoolContract::class);
});
