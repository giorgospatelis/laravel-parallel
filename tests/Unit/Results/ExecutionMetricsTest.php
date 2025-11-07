<?php

declare(strict_types=1);

use LaravelParallel\Results\ExecutionMetrics;
use LaravelParallel\Results\ParallelResult;
use LaravelParallel\Results\ResultCollection;

it('creates metrics from result collection', function () {
    $results = new ResultCollection([
        ParallelResult::success('value1', 1.0),
        ParallelResult::success('value2', 3.0),
        ParallelResult::failure(new RuntimeException('Error'), 2.0),
    ]);

    $metrics = ExecutionMetrics::fromResults($results);

    expect($metrics->totalTasks)->toBe(3)
        ->and($metrics->successfulTasks)->toBe(2)
        ->and($metrics->failedTasks)->toBe(1)
        ->and($metrics->totalExecutionTime)->toBe(6.0)
        ->and($metrics->averageExecutionTime)->toBe(2.0)
        ->and($metrics->minExecutionTime)->toBe(1.0)
        ->and($metrics->maxExecutionTime)->toBe(3.0);
});

it('calculates success rate', function () {
    $results = new ResultCollection([
        ParallelResult::success('value1', 1.0),
        ParallelResult::success('value2', 1.0),
        ParallelResult::success('value3', 1.0),
        ParallelResult::failure(new RuntimeException('Error'), 1.0),
    ]);

    $metrics = ExecutionMetrics::fromResults($results);

    expect($metrics->successRate())->toBe(75.0);
});

it('calculates failure rate', function () {
    $results = new ResultCollection([
        ParallelResult::success('value1', 1.0),
        ParallelResult::failure(new RuntimeException('Error 1'), 1.0),
        ParallelResult::failure(new RuntimeException('Error 2'), 1.0),
        ParallelResult::failure(new RuntimeException('Error 3'), 1.0),
    ]);

    $metrics = ExecutionMetrics::fromResults($results);

    expect($metrics->failureRate())->toBe(75.0);
});

it('handles empty result collection', function () {
    $results = new ResultCollection([]);
    $metrics = ExecutionMetrics::fromResults($results);

    expect($metrics->totalTasks)->toBe(0)
        ->and($metrics->successRate())->toBe(0.0)
        ->and($metrics->failureRate())->toBe(0.0)
        ->and($metrics->totalExecutionTime)->toBe(0.0)
        ->and($metrics->minExecutionTime)->toBe(0.0)
        ->and($metrics->maxExecutionTime)->toBe(0.0);
});

it('converts to array', function () {
    $results = new ResultCollection([
        ParallelResult::success('value1', 1.0),
        ParallelResult::failure(new RuntimeException('Error'), 2.0),
    ]);

    $metrics = ExecutionMetrics::fromResults($results);
    $array = $metrics->toArray();

    expect($array)->toBeArray()
        ->toHaveKey('total_tasks', 2)
        ->toHaveKey('successful_tasks', 1)
        ->toHaveKey('failed_tasks', 1)
        ->toHaveKey('success_rate', 50.0)
        ->toHaveKey('failure_rate', 50.0)
        ->toHaveKey('total_execution_time', 3.0)
        ->toHaveKey('average_execution_time', 1.5)
        ->toHaveKey('min_execution_time', 1.0)
        ->toHaveKey('max_execution_time', 2.0);
});
