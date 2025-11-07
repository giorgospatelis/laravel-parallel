<?php

declare(strict_types=1);

use LaravelParallel\Core\Executor;
use LaravelParallel\Core\ParallelManager;
use LaravelParallel\Core\ResultCollector;
use LaravelParallel\Results\ResultCollection;
use LaravelParallel\Results\ExecutionMetrics;
use LaravelParallel\Support\CpuDetector;
use LaravelParallel\Support\TaskValidator;
use LaravelParallel\Tests\Mocks\MockWorkerPoolFactory;

it('executes tasks with different worker counts', function () {
    $cpuDetector = new CpuDetector();
    $poolFactory = new MockWorkerPoolFactory($cpuDetector);
    $resultCollector = new ResultCollector();
    $validator = new TaskValidator();

    $executor = new Executor($poolFactory, $resultCollector, $validator);

    foreach ([1, 2, 4, 8] as $workerCount) {
        $executor->setWorkerCount($workerCount);

        $tasks = array_fill(0, 10, fn () => 'result');

        $results = $executor->execute($tasks);

        expect($results)->toHaveCount(10);
    }
});

it('executes tasks with different timeouts', function () {
    $cpuDetector = new CpuDetector();
    $poolFactory = new MockWorkerPoolFactory($cpuDetector);
    $resultCollector = new ResultCollector();
    $validator = new TaskValidator();

    $executor = new Executor($poolFactory, $resultCollector, $validator);

    foreach ([1.0, 5.0, 10.0, 30.0] as $timeout) {
        $executor->setTimeout($timeout);

        $tasks = ['task1' => fn () => 'result1'];

        $results = $executor->execute($tasks);

        expect($results)->toHaveCount(1)
            ->and($results['task1']->isSuccess())->toBeTrue();
    }
});

it('collects metrics from execution results', function () {
    $cpuDetector = new CpuDetector();
    $poolFactory = new MockWorkerPoolFactory($cpuDetector);
    $resultCollector = new ResultCollector();
    $validator = new TaskValidator();

    $executor = new Executor($poolFactory, $resultCollector, $validator);

    $tasks = [
        'task1' => fn () => 'result1',
        'task2' => fn () => 'result2',
        'task3' => fn () => throw new RuntimeException('fail'),
    ];

    $results = $executor->execute($tasks);
    $collection = new ResultCollection($results);
    $metrics = ExecutionMetrics::fromResults($collection);

    expect($metrics->totalTasks)->toBe(3)
        ->and($metrics->successfulTasks)->toBe(2)
        ->and($metrics->failedTasks)->toBe(1)
        ->and($metrics->successRate())->toBeGreaterThan(0.0)
        ->and($metrics->averageExecutionTime)->toBeGreaterThanOrEqual(0.0);
});

it('handles varying task complexities', function () {
    $cpuDetector = new CpuDetector();
    $poolFactory = new MockWorkerPoolFactory($cpuDetector);
    $resultCollector = new ResultCollector();
    $validator = new TaskValidator();

    $executor = new Executor($poolFactory, $resultCollector, $validator);

    $tasks = [
        'simple' => fn () => 'simple',
        'math' => fn () => array_sum(range(1, 100)),
        'string' => fn () => str_repeat('a', 100),
        'array' => fn () => array_map(fn ($n) => $n * 2, range(1, 50)),
        'nested' => fn () => ['a' => ['b' => ['c' => 'value']]],
    ];

    $results = $executor->execute($tasks);

    expect($results)->toHaveCount(5)
        ->and($results['simple']->getValue())->toBe('simple')
        ->and($results['math']->getValue())->toBe(5050)
        ->and($results['string']->getValue())->toBe(str_repeat('a', 100))
        ->and($results['array']->getValue())->toBeArray()
        ->and($results['nested']->getValue())->toBe(['a' => ['b' => ['c' => 'value']]]);
});

it('supports fluent reconfiguration between executions', function () {
    $cpuDetector = new CpuDetector();
    $poolFactory = new MockWorkerPoolFactory($cpuDetector);
    $resultCollector = new ResultCollector();
    $validator = new TaskValidator();
    $executor = new Executor($poolFactory, $resultCollector, $validator);

    $manager = new ParallelManager($executor, $validator);

    // First execution with 2 workers
    $results1 = $manager->workers(2)->run(['task1' => fn () => 'result1']);
    expect($results1)->toHaveCount(1);

    // Second execution with 4 workers
    $results2 = $manager->workers(4)->run(['task2' => fn () => 'result2']);
    expect($results2)->toHaveCount(1);

    // Third execution with timeout
    $results3 = $manager->timeout(10.0)->run(['task3' => fn () => 'result3']);
    expect($results3)->toHaveCount(1);
});

it('handles task keys with special characters', function () {
    $cpuDetector = new CpuDetector();
    $poolFactory = new MockWorkerPoolFactory($cpuDetector);
    $resultCollector = new ResultCollector();
    $validator = new TaskValidator();

    $executor = new Executor($poolFactory, $resultCollector, $validator);

    $tasks = [
        'task-with-dash' => fn () => 'result1',
        'task_with_underscore' => fn () => 'result2',
        'task.with.dots' => fn () => 'result3',
        'task:with:colons' => fn () => 'result4',
    ];

    $results = $executor->execute($tasks);

    expect($results)->toHaveCount(4)
        ->and($results['task-with-dash']->getValue())->toBe('result1')
        ->and($results['task_with_underscore']->getValue())->toBe('result2')
        ->and($results['task.with.dots']->getValue())->toBe('result3')
        ->and($results['task:with:colons'])->getValue()->toBe('result4');
});

it('processes results with ResultCollection utility methods', function () {
    $cpuDetector = new CpuDetector();
    $poolFactory = new MockWorkerPoolFactory($cpuDetector);
    $resultCollector = new ResultCollector();
    $validator = new TaskValidator();

    $executor = new Executor($poolFactory, $resultCollector, $validator);

    $tasks = [
        's1' => fn () => 'result1',
        'f1' => fn () => throw new RuntimeException('error1'),
        's2' => fn () => 'result2',
        'f2' => fn () => throw new RuntimeException('error2'),
    ];

    $results = $executor->execute($tasks);
    $collection = new ResultCollection($results);

    expect($collection->successful())->toHaveCount(2)
        ->and($collection->failed())->toHaveCount(2)
        ->and($collection->allSuccessful())->toBeFalse()
        ->and($collection->anyFailed())->toBeTrue();
});
