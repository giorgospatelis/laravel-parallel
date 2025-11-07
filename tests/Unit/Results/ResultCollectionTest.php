<?php

declare(strict_types=1);

use LaravelParallel\Results\ParallelResult;
use LaravelParallel\Results\ResultCollection;

beforeEach(function () {
    $this->successfulResult1 = ParallelResult::success('value1', 1.0);
    $this->successfulResult2 = ParallelResult::success('value2', 2.0);
    $this->failedResult1 = ParallelResult::failure(new RuntimeException('Error 1'), 1.5);
    $this->failedResult2 = ParallelResult::failure(new RuntimeException('Error 2'), 2.5);
});

it('filters successful results', function () {
    $collection = new ResultCollection([
        $this->successfulResult1,
        $this->failedResult1,
        $this->successfulResult2,
    ]);

    $successful = $collection->successful();

    expect($successful)->toHaveCount(2)
        ->and($successful->first())->toBe($this->successfulResult1);
});

it('filters failed results', function () {
    $collection = new ResultCollection([
        $this->successfulResult1,
        $this->failedResult1,
        $this->failedResult2,
    ]);

    $failed = $collection->failed();

    expect($failed)->toHaveCount(2)
        ->and($failed->first())->toBe($this->failedResult1);
});

it('extracts all values when all successful', function () {
    $collection = new ResultCollection([
        $this->successfulResult1,
        $this->successfulResult2,
    ]);

    $values = $collection->values();

    expect($values->all())->toBe(['value1', 'value2']);
});

it('throws when extracting values with failures', function () {
    $collection = new ResultCollection([
        $this->successfulResult1,
        $this->failedResult1,
    ]);

    expect(fn () => $collection->values())
        ->toThrow(RuntimeException::class, 'Error 1');
});

it('extracts values with defaults for failures', function () {
    $collection = new ResultCollection([
        $this->successfulResult1,
        $this->failedResult1,
        $this->successfulResult2,
    ]);

    $values = $collection->valuesOr('default');

    expect($values->all())->toBe(['value1', 'default', 'value2']);
});

it('extracts all exceptions', function () {
    $collection = new ResultCollection([
        $this->successfulResult1,
        $this->failedResult1,
        $this->failedResult2,
    ]);

    $exceptions = $collection->exceptions();

    expect($exceptions)->toHaveCount(2)
        ->and($exceptions->first())->toBeInstanceOf(RuntimeException::class)
        ->and($exceptions->first()->getMessage())->toBe('Error 1');
});

it('checks if all successful', function () {
    $allSuccess = new ResultCollection([$this->successfulResult1, $this->successfulResult2]);
    $mixed = new ResultCollection([$this->successfulResult1, $this->failedResult1]);

    expect($allSuccess->allSuccessful())->toBeTrue()
        ->and($mixed->allSuccessful())->toBeFalse();
});

it('checks if any failed', function () {
    $allSuccess = new ResultCollection([$this->successfulResult1, $this->successfulResult2]);
    $mixed = new ResultCollection([$this->successfulResult1, $this->failedResult1]);

    expect($allSuccess->anyFailed())->toBeFalse()
        ->and($mixed->anyFailed())->toBeTrue();
});

it('calculates total execution time', function () {
    $collection = new ResultCollection([
        $this->successfulResult1, // 1.0
        $this->successfulResult2, // 2.0
        $this->failedResult1,     // 1.5
    ]);

    expect($collection->totalExecutionTime())->toBe(4.5);
});

it('calculates average execution time', function () {
    $collection = new ResultCollection([
        $this->successfulResult1, // 1.0
        $this->successfulResult2, // 2.0
        $this->failedResult1,     // 1.5
    ]);

    expect($collection->averageExecutionTime())->toBe(1.5);
});

it('returns zero average for empty collection', function () {
    $collection = new ResultCollection([]);

    expect($collection->averageExecutionTime())->toBe(0.0);
});
