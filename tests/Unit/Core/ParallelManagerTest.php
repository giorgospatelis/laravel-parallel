<?php

declare(strict_types=1);

use LaravelParallel\Core\Executor;
use LaravelParallel\Core\ParallelManager;
use LaravelParallel\Core\ResultCollector;
use LaravelParallel\Support\CpuDetector;
use LaravelParallel\Support\TaskValidator;
use LaravelParallel\Tests\Mocks\MockWorkerPoolFactory;

beforeEach(function () {
    $cpuDetector = new CpuDetector();

    // Use mock factory to avoid spawning real processes during tests
    // This makes tests compatible with code coverage tools like PCOV
    $poolFactory = new MockWorkerPoolFactory($cpuDetector);

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

it('executes map operation over array items', function () {
    $items = [1, 2, 3, 4, 5];

    $results = $this->manager->map($items, fn ($n) => $n * 2);

    expect($results)->toHaveCount(5)
        ->and($results[0]->getValue())->toBe(2)
        ->and($results[1]->getValue())->toBe(4)
        ->and($results[2]->getValue())->toBe(6)
        ->and($results[3]->getValue())->toBe(8)
        ->and($results[4]->getValue())->toBe(10);
});

it('executes map operation over iterable with string keys', function () {
    $items = ['a' => 1, 'b' => 2, 'c' => 3];

    $results = $this->manager->map($items, fn ($n) => $n * 10);

    expect($results)->toHaveCount(3)
        ->and($results['a']->getValue())->toBe(10)
        ->and($results['b']->getValue())->toBe(20)
        ->and($results['c']->getValue())->toBe(30);
});

it('handles empty array in map operation', function () {
    $results = $this->manager->map([], fn ($n) => $n * 2);

    expect($results)->toBeArray()->toBeEmpty();
});

it('executes map with generator as iterable', function () {
    $generator = function () {
        yield 1;
        yield 2;
        yield 3;
    };

    $results = $this->manager->map($generator(), fn ($n) => $n * 3);

    expect($results)->toHaveCount(3);
});

it('can chain workers and timeout before map', function () {
    $items = [1, 2, 3];

    $results = $this->manager
        ->workers(2)
        ->timeout(5.0)
        ->map($items, fn ($n) => $n + 1);

    expect($results)->toHaveCount(3)
        ->and($results[0]->getValue())->toBe(2)
        ->and($results[1]->getValue())->toBe(3)
        ->and($results[2]->getValue())->toBe(4);
});

it('creates new instance via make factory method', function () {
    $cpuDetector = new CpuDetector();
    $poolFactory = new MockWorkerPoolFactory($cpuDetector);
    $resultCollector = new ResultCollector();
    $validator = new TaskValidator();
    $executor = new Executor($poolFactory, $resultCollector, $validator);

    $manager = ParallelManager::make($executor, $validator);

    expect($manager)->toBeInstanceOf(ParallelManager::class);
});

it('throws InvalidArgumentException when max_workers config is not integer', function () {
    config(['parallel.max_workers' => 'not-an-int']);

    expect(fn () => $this->manager->workers(4))
        ->toThrow(InvalidArgumentException::class, 'Configuration "parallel.max_workers" must be an integer');
});

it('executes run operation with closures', function () {
    $closures = [
        'task1' => fn () => 'result1',
        'task2' => fn () => 'result2',
    ];

    $results = $this->manager->run($closures);

    expect($results)->toHaveCount(2)
        ->and($results['task1']->getValue())->toBe('result1')
        ->and($results['task2']->getValue())->toBe('result2');
});
