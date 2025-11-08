<?php

declare(strict_types=1);

use LaravelParallel\Core\Executor;
use LaravelParallel\Core\ResultCollector;
use LaravelParallel\Support\CpuDetector;
use LaravelParallel\Support\TaskValidator;
use LaravelParallel\Tests\Mocks\MockWorkerPoolFactory;

it('executes complete workflow from executor to result collection', function () {
    $cpuDetector = new CpuDetector;
    $poolFactory = new MockWorkerPoolFactory($cpuDetector);
    $resultCollector = new ResultCollector;
    $validator = new TaskValidator;

    $executor = new Executor($poolFactory, $resultCollector, $validator);

    $tasks = [
        'task1' => fn () => 'result1',
        'task2' => fn () => 'result2',
        'task3' => fn () => 'result3',
    ];

    $results = $executor->execute($tasks);

    expect($results)->toHaveCount(3)
        ->and($results['task1']->isSuccess())->toBeTrue()
        ->and($results['task2']->isSuccess())->toBeTrue()
        ->and($results['task3']->isSuccess())->toBeTrue();
});

it('handles mixed success and failure results', function () {
    $cpuDetector = new CpuDetector;
    $poolFactory = new MockWorkerPoolFactory($cpuDetector);
    $resultCollector = new ResultCollector;
    $validator = new TaskValidator;

    $executor = new Executor($poolFactory, $resultCollector, $validator);

    $tasks = [
        'success1' => fn () => 'result1',
        'failure1' => fn () => throw new RuntimeException('Error 1'),
        'success2' => fn () => 'result2',
        'failure2' => fn () => throw new RuntimeException('Error 2'),
    ];

    $results = $executor->execute($tasks);

    expect($results)->toHaveCount(4)
        ->and($results['success1']->isSuccess())->toBeTrue()
        ->and($results['failure1']->isSuccess())->toBeFalse()
        ->and($results['success2']->isSuccess())->toBeTrue()
        ->and($results['failure2']->isSuccess())->toBeFalse();
});

it('executes tasks with various data types', function () {
    $cpuDetector = new CpuDetector;
    $poolFactory = new MockWorkerPoolFactory($cpuDetector);
    $resultCollector = new ResultCollector;
    $validator = new TaskValidator;

    $executor = new Executor($poolFactory, $resultCollector, $validator);

    $tasks = [
        'string' => fn () => 'text',
        'int' => fn () => 42,
        'float' => fn () => 3.14,
        'array' => fn () => [1, 2, 3],
        'object' => fn () => (object) ['key' => 'value'],
        'bool' => fn () => true,
        'null' => fn () => null,
    ];

    $results = $executor->execute($tasks);

    expect($results)->toHaveCount(7)
        ->and($results['string']->getValue())->toBe('text')
        ->and($results['int']->getValue())->toBe(42)
        ->and($results['float']->getValue())->toBe(3.14)
        ->and($results['array']->getValue())->toBe([1, 2, 3])
        ->and($results['object']->getValue())->toBeInstanceOf(stdClass::class)
        ->and($results['bool']->getValue())->toBeTrue()
        ->and($results['null']->getValue())->toBeNull();
});

it('respects worker count configuration', function () {
    $cpuDetector = new CpuDetector;
    $poolFactory = new MockWorkerPoolFactory($cpuDetector);
    $resultCollector = new ResultCollector;
    $validator = new TaskValidator;

    $executor = new Executor($poolFactory, $resultCollector, $validator);
    $executor->setWorkerCount(8);

    expect($executor->getWorkerCount())->toBe(8);

    $tasks = ['task1' => fn () => 'result1'];
    $results = $executor->execute($tasks);

    expect($results)->toHaveCount(1)
        ->and($results['task1']->isSuccess())->toBeTrue();
});

it('respects timeout configuration', function () {
    $cpuDetector = new CpuDetector;
    $poolFactory = new MockWorkerPoolFactory($cpuDetector);
    $resultCollector = new ResultCollector;
    $validator = new TaskValidator;

    $executor = new Executor($poolFactory, $resultCollector, $validator);
    $executor->setTimeout(15.0);

    expect($executor->getTimeout())->toBe(15.0);

    $tasks = ['task1' => fn () => 'result1'];
    $results = $executor->execute($tasks);

    expect($results)->toHaveCount(1)
        ->and($results['task1']->isSuccess())->toBeTrue();
});

it('executes large batches efficiently', function () {
    $cpuDetector = new CpuDetector;
    $poolFactory = new MockWorkerPoolFactory($cpuDetector);
    $resultCollector = new ResultCollector;
    $validator = new TaskValidator;

    $executor = new Executor($poolFactory, $resultCollector, $validator);
    $executor->setWorkerCount(4);

    $tasks = [];
    for ($i = 1; $i <= 100; $i++) {
        $tasks["task_{$i}"] = function () use ($i) {
            return "result_{$i}";
        };
    }

    $results = $executor->execute($tasks);

    expect($results)->toHaveCount(100);

    foreach ($results as $key => $result) {
        expect($result->isSuccess())->toBeTrue();
    }
});

it('preserves execution order in results', function () {
    $cpuDetector = new CpuDetector;
    $poolFactory = new MockWorkerPoolFactory($cpuDetector);
    $resultCollector = new ResultCollector;
    $validator = new TaskValidator;

    $executor = new Executor($poolFactory, $resultCollector, $validator);

    $tasks = [
        'first' => fn () => 'first_result',
        'second' => fn () => 'second_result',
        'third' => fn () => 'third_result',
    ];

    $results = $executor->execute($tasks);

    $keys = array_keys($results);
    expect($keys)->toBe(['first', 'second', 'third']);
});

it('tracks execution time for all tasks', function () {
    $cpuDetector = new CpuDetector;
    $poolFactory = new MockWorkerPoolFactory($cpuDetector);
    $resultCollector = new ResultCollector;
    $validator = new TaskValidator;

    $executor = new Executor($poolFactory, $resultCollector, $validator);

    $tasks = [
        'task1' => fn () => 'result1',
        'task2' => fn () => 'result2',
        'task3' => fn () => 'result3',
    ];

    $results = $executor->execute($tasks);

    foreach ($results as $result) {
        expect($result->getExecutionTime())->toBeGreaterThanOrEqual(0.0);
    }
});
