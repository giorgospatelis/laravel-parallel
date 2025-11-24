<?php

declare(strict_types=1);

use LaravelParallel\Benchmarking\BenchmarkResult;

it('creates benchmark result with all properties', function () {
    $result = new BenchmarkResult(
        scenarioName: 'test-scenario',
        category: 'cpu-bound',
        iterations: 100,
        workerCount: 4,
        totalTime: 5.5,
        averageTimePerTask: 0.055,
        throughput: 18.18,
        memoryPeakBytes: 1024 * 1024,
    );

    expect($result->scenarioName)->toBe('test-scenario');
    expect($result->category)->toBe('cpu-bound');
    expect($result->iterations)->toBe(100);
    expect($result->workerCount)->toBe(4);
    expect($result->totalTime)->toBe(5.5);
    expect($result->averageTimePerTask)->toBe(0.055);
    expect($result->throughput)->toBe(18.18);
    expect($result->memoryPeakBytes)->toBe(1048576);
});

it('formats total time correctly', function () {
    $result = new BenchmarkResult(
        scenarioName: 'test',
        category: 'cpu-bound',
        iterations: 100,
        workerCount: 4,
        totalTime: 5.5678,
        averageTimePerTask: 0.055,
        throughput: 18.18,
        memoryPeakBytes: 1024,
    );

    expect($result->getFormattedTotalTime())->toBe('5.5678 seconds');
});

it('formats average time correctly', function () {
    $result = new BenchmarkResult(
        scenarioName: 'test',
        category: 'cpu-bound',
        iterations: 100,
        workerCount: 4,
        totalTime: 5.5,
        averageTimePerTask: 0.055,
        throughput: 18.18,
        memoryPeakBytes: 1024,
    );

    expect($result->getFormattedAverageTime())->toBe('55.00 ms');
});

it('formats throughput correctly', function () {
    $result = new BenchmarkResult(
        scenarioName: 'test',
        category: 'cpu-bound',
        iterations: 100,
        workerCount: 4,
        totalTime: 5.5,
        averageTimePerTask: 0.055,
        throughput: 18.1818,
        memoryPeakBytes: 1024,
    );

    expect($result->getFormattedThroughput())->toBe('18.18 tasks/sec');
});

it('formats memory in bytes', function () {
    $result = new BenchmarkResult(
        scenarioName: 'test',
        category: 'cpu-bound',
        iterations: 100,
        workerCount: 4,
        totalTime: 5.5,
        averageTimePerTask: 0.055,
        throughput: 18.18,
        memoryPeakBytes: 512,
    );

    expect($result->getFormattedMemory())->toBe('512.00 B');
});

it('formats memory in kilobytes', function () {
    $result = new BenchmarkResult(
        scenarioName: 'test',
        category: 'cpu-bound',
        iterations: 100,
        workerCount: 4,
        totalTime: 5.5,
        averageTimePerTask: 0.055,
        throughput: 18.18,
        memoryPeakBytes: 1024 * 10,
    );

    expect($result->getFormattedMemory())->toBe('10.00 KB');
});

it('formats memory in megabytes', function () {
    $result = new BenchmarkResult(
        scenarioName: 'test',
        category: 'cpu-bound',
        iterations: 100,
        workerCount: 4,
        totalTime: 5.5,
        averageTimePerTask: 0.055,
        throughput: 18.18,
        memoryPeakBytes: 1024 * 1024 * 5,
    );

    expect($result->getFormattedMemory())->toBe('5.00 MB');
});

it('formats memory in gigabytes', function () {
    $result = new BenchmarkResult(
        scenarioName: 'test',
        category: 'cpu-bound',
        iterations: 100,
        workerCount: 4,
        totalTime: 5.5,
        averageTimePerTask: 0.055,
        throughput: 18.18,
        memoryPeakBytes: 1024 * 1024 * 1024 * 2,
    );

    expect($result->getFormattedMemory())->toBe('2.00 GB');
});

it('converts to array correctly', function () {
    $result = new BenchmarkResult(
        scenarioName: 'test-scenario',
        category: 'cpu-bound',
        iterations: 100,
        workerCount: 4,
        totalTime: 5.5,
        averageTimePerTask: 0.055,
        throughput: 18.18,
        memoryPeakBytes: 1048576,
    );

    $array = $result->toArray();

    expect($array)->toBe([
        'scenario' => 'test-scenario',
        'category' => 'cpu-bound',
        'iterations' => 100,
        'workers' => 4,
        'total_time' => 5.5,
        'average_time' => 0.055,
        'throughput' => 18.18,
        'memory_peak' => 1048576,
    ]);
});

it('is readonly and immutable', function () {
    $result = new BenchmarkResult(
        scenarioName: 'test',
        category: 'cpu-bound',
        iterations: 100,
        workerCount: 4,
        totalTime: 5.5,
        averageTimePerTask: 0.055,
        throughput: 18.18,
        memoryPeakBytes: 1024,
    );

    // Attempting to modify should throw error
    expect(fn () => $result->scenarioName = 'modified')->toThrow(Error::class);
});
