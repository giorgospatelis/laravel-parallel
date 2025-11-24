<?php

declare(strict_types=1);

use LaravelParallel\Benchmarking\BenchmarkResult;
use LaravelParallel\Benchmarking\BenchmarkRunner;
use LaravelParallel\Benchmarking\Scenarios\CpuBoundScenario;
use LaravelParallel\Benchmarking\Scenarios\IoBoundScenario;
use LaravelParallel\Support\CpuDetector;

beforeEach(function () {
    // Enable mock worker pools for testing
    config(['parallel.use_mock_worker_pools' => true]);
});

it('runs a single benchmark scenario', function () {
    $runner = new BenchmarkRunner(new CpuDetector);
    $scenario = new CpuBoundScenario;

    $result = $runner->run($scenario, workerCount: 2, iterations: 10);

    expect($result)->toBeInstanceOf(BenchmarkResult::class);
    expect($result->scenarioName)->toBe('cpu-bound');
    expect($result->category)->toBe('cpu-bound');
    expect($result->iterations)->toBe(10);
    expect($result->workerCount)->toBe(2);
    expect($result->totalTime)->toBeGreaterThan(0);
    expect($result->averageTimePerTask)->toBeGreaterThan(0);
    expect($result->throughput)->toBeGreaterThan(0);
});

it('runs benchmark with auto-detected worker count', function () {
    $runner = new BenchmarkRunner(new CpuDetector);
    $scenario = new CpuBoundScenario;

    $result = $runner->run($scenario, workerCount: null, iterations: 10);

    expect($result)->toBeInstanceOf(BenchmarkResult::class);
    expect($result->workerCount)->toBeGreaterThan(0); // Should auto-detect
});

it('calculates correct metrics', function () {
    $runner = new BenchmarkRunner(new CpuDetector);
    $scenario = new CpuBoundScenario;

    $result = $runner->run($scenario, workerCount: 2, iterations: 10);

    // Verify mathematical relationships
    expect($result->averageTimePerTask)->toBe($result->totalTime / $result->iterations);
    expect($result->throughput)->toBe($result->iterations / $result->totalTime);
});

it('runs multiple scenarios', function () {
    $runner = new BenchmarkRunner(new CpuDetector);
    $scenarios = [
        new CpuBoundScenario,
        new IoBoundScenario,
    ];

    $results = $runner->runMultiple($scenarios, workerCount: 2, iterations: 5);

    expect($results)->toBeArray();
    expect($results)->toHaveCount(2);
    expect($results[0])->toBeInstanceOf(BenchmarkResult::class);
    expect($results[1])->toBeInstanceOf(BenchmarkResult::class);
    expect($results[0]->scenarioName)->toBe('cpu-bound');
    expect($results[1]->scenarioName)->toBe('io-bound');
});

it('handles different iteration counts', function () {
    $runner = new BenchmarkRunner(new CpuDetector);
    $scenario = new CpuBoundScenario;

    $result5 = $runner->run($scenario, workerCount: 2, iterations: 5);
    $result10 = $runner->run($scenario, workerCount: 2, iterations: 10);

    expect($result5->iterations)->toBe(5);
    expect($result10->iterations)->toBe(10);
});

it('records memory usage', function () {
    $runner = new BenchmarkRunner(new CpuDetector);
    $scenario = new CpuBoundScenario;

    $result = $runner->run($scenario, workerCount: 2, iterations: 10);

    expect($result->memoryPeakBytes)->toBeGreaterThanOrEqual(0);
});

it('completes benchmarks within reasonable time', function () {
    $runner = new BenchmarkRunner(new CpuDetector);
    $scenario = new CpuBoundScenario;

    $start = microtime(true);
    $result = $runner->run($scenario, workerCount: 2, iterations: 5);
    $elapsed = microtime(true) - $start;

    // Should complete relatively quickly with mock pools
    expect($elapsed)->toBeLessThan(10.0);
    expect($result->totalTime)->toBeLessThan($elapsed);
});
