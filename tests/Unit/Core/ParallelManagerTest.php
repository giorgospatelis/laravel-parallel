<?php

declare(strict_types=1);

use LaravelParallel\Core\Executor;
use LaravelParallel\Core\ParallelManager;
use LaravelParallel\Core\ResultCollector;
use LaravelParallel\Support\CpuDetector;
use LaravelParallel\Support\TaskValidator;
use LaravelParallel\Workers\WorkerPoolFactory;

beforeEach(function () {
    $cpuDetector = new CpuDetector();
    $poolFactory = new WorkerPoolFactory($cpuDetector);
    $resultCollector = new ResultCollector();
    $validator = new TaskValidator();

    $executor = new Executor($poolFactory, $resultCollector, $validator);

    $this->manager = new ParallelManager($executor, $validator);
});

it('supports fluent timeout configuration', function () {
    $result = $this->manager->timeout(30.0);

    expect($result)->toBe($this->manager);
});

it('supports fluent worker configuration', function () {
    $result = $this->manager->workers(4);

    expect($result)->toBe($this->manager)
        ->and($this->manager->getWorkerCount())->toBe(4);
});

it('throws on invalid timeout', function () {
    expect(fn () => $this->manager->timeout(-1.0))
        ->toThrow(LaravelParallel\Exceptions\ParallelException::class);
});

it('throws on invalid worker count', function () {
    expect(fn () => $this->manager->workers(0))
        ->toThrow(LaravelParallel\Exceptions\ParallelException::class);

    expect(fn () => $this->manager->workers(999))
        ->toThrow(LaravelParallel\Exceptions\ParallelException::class);
});

it('does not leak state between instances from container', function () {
    // First instance with worker count set
    $manager1 = app('parallel');
    $manager1->workers(4);
    expect($manager1->getWorkerCount())->toBe(4);

    // Second instance should be fresh (no state leakage)
    $manager2 = app('parallel');
    expect($manager2->getWorkerCount())->toBeNull();

    // They should be different instances
    expect($manager1)->not->toBe($manager2);
});

it('is octane safe - no state persistence across resolutions', function () {
    // Simulate multiple requests with different configurations
    for ($i = 1; $i <= 5; $i++) {
        $manager = app('parallel');
        $manager->workers($i);
        expect($manager->getWorkerCount())->toBe($i);
    }

    // Fresh instance should have no state
    $freshManager = app('parallel');
    expect($freshManager->getWorkerCount())->toBeNull();
});
